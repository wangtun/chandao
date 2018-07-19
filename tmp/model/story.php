<?php
global $app;
helper::cd($app->getBasePath());
helper::import('module\story\model.php');
helper::cd();
class extstoryModel extends storyModel 
{
/**
 * Created by PhpStorm.
 * User: 月下亭中人
 * Date: 2018/1/3
 * Time: 17:17
 */
/**
 * Batch change the status of ifLinkStories.
 *
 * @param  $storyIDList
 * @param  string    $status
 * @access public
 * @return array
 */
public function batchChangeIfLinkStories($storyIDList, $status)
{
    //var_dump($storyIDList);die;
    $now         = helper::now();
    $allChanges  = array();
    $oldStories  = $this->getByList($storyIDList);
    foreach($storyIDList as $storyID)
    {
        $oldStory = $oldStories[$storyID];

        $story = new stdclass();
        $story->lastEditedBy   = $this->app->user->account;
        $story->lastEditedDate = $now;
        $story->ifLinkStories  = $status;
        
        $this->dao->update(TABLE_STORY)->data($story)->autoCheck()->where('id')->eq((int)$storyID)->exec();
        if(!dao::isError()) $allChanges[$storyID] = common::createChanges($oldStory, $story);
    }
    return $allChanges;
}/**
 * Created by PhpStorm.
 * User: 月下亭中人
 * Date: 2017/11/7
 * Time: 11:24
 */
/**
 * Batch change the plan of story.
 *
 * @param  array  $storyIDList
 * @param  int    $planID
 * @access public
 * @return array
 */
public function batchChangePlan($storyIDList, $planID, $oldPlanID = 0)
{
    $now         = helper::now();
    $allChanges  = array();
    $oldStories  = $this->getByList($storyIDList);
    $plan        = $this->loadModel('productplan')->getById($planID);
    foreach($storyIDList as $storyID)
    {
        $oldStory = $oldStories[$storyID];

        $story = new stdclass();
        $story->lastEditedBy   = $this->app->user->account;
        $story->lastEditedDate = $now;
        if(strpos(",{$oldStory->plan},", ",$planID,") !== false) continue;
        if($this->session->currentProductType == 'normal' or empty($oldPlanID) or $oldStory->branch)
        {
            $story->plan = $planID;
        }
        elseif($oldPlanID)
        {
            $story->plan = trim(str_replace(",$oldPlanID,", ',', ",$oldStory->plan,"), ',');
            if(empty($story->branch)) $story->plan .= ",$planID";
        }

        //if($planID) $story->stage = 'planned';
        if($planID and $oldStory->stage =='wait') $story->stage = 'planned';

        $this->dao->update(TABLE_STORY)->data($story)->autoCheck()->where('id')->eq((int)$storyID)->exec();
        if(!$planID) $this->setStage($storyID);
        if(!dao::isError()) $allChanges[$storyID] = common::createChanges($oldStory, $story);
    }
    return $allChanges;
}/**
 * Batch close story.
 *
 * @access public
 * @return void
 */
public function batchClose()
{
    /* Init vars. */
    $stories     = array();
    $allChanges  = array();
    $now         = helper::now();
    $data        = fixer::input('post')->get();
    $storyIDList = $data->storyIDList ? $data->storyIDList : array();

    $oldStories = $this->getByList($storyIDList);
    foreach($storyIDList as $storyID)
    {
        $oldStory = $oldStories[$storyID];
        if($oldStory->status == 'closed') continue;

        $story = new stdclass();
        $story->lastEditedBy   = $this->app->user->account;
        $story->lastEditedDate = $now;
        $story->closedBy       = $this->app->user->account;
        $story->closedDate     = $now;
        $story->assignedTo     = 'closed';
        $story->assignedDate   = $now;
        $story->status         = 'closed';
        $story->stage          = 'closed';

        $story->closedReason   = $data->closedReasons[$storyID];
        $story->duplicateStory = $data->duplicateStoryIDList[$storyID] ? $data->duplicateStoryIDList[$storyID] : $oldStory->duplicateStory;
        $story->childStories   = $data->childStoriesIDList[$storyID] ? $data->childStoriesIDList[$storyID] : $oldStory->childStories;

        if($story->closedReason == 'done') $story->stage = 'released';
        //bug修改把plan的值有0改为'';待官方修复后该文件删除
        if($story->closedReason != 'done') $story->plan  = '';

        $stories[$storyID] = $story;
        unset($story);
    }

    foreach($stories as $storyID => $story)
    {
        if(!$story->closedReason) continue;

        $oldStory = $oldStories[$storyID];

        $this->dao->update(TABLE_STORY)->data($story)
            ->autoCheck()
            ->checkIF($story->closedReason == 'duplicate',  'duplicateStory', 'notempty')
            ->where('id')->eq($storyID)->exec();

        if(!dao::isError())
        {
            $allChanges[$storyID] = common::createChanges($oldStory, $story);
        }
        else
        {
            die(js::error('story#' . $storyID . dao::getError(true)));
        }
        if(!dao::isError() && $story->stage == 'released') $this->loadModel('score')->create('story', 'close', $storyID);
    }

    return $allChanges;
}/**
 * Create a batch stories.
 *
 * @access public
 * @return int|bool the id of the created story or false when error.
 */
public function batchCreate($productID = 0, $branch = 0)
{
    $this->loadModel('action');
    $branch   = (int)$branch;
    $now      = helper::now();
    $mails    = array();
    $stories  = fixer::input('post')->get();
    $batchNum = count(reset($stories));

    $result  = $this->loadModel('common')->removeDuplicate('story', $stories, "product={$productID}");
    $stories = $result['data'];

    $module = 0;
    //bug修改把plan的值有0改为'';待官方修复后该文件删除
    $plan   = '';
    $pri    = 0;
    $source = '';

    for($i = 0; $i < $batchNum; $i++)
    {
        $module = $stories->module[$i] == 'ditto' ? $module : $stories->module[$i];
        $plan   = $stories->plan[$i]   == 'ditto' ? $plan   : $stories->plan[$i];
        $pri    = $stories->pri[$i]    == 'ditto' ? $pri    : $stories->pri[$i];
        $source = $stories->source[$i] == 'ditto' ? $source : $stories->source[$i];
        $stories->module[$i] = (int)$module;
        $stories->plan[$i]   = $plan;
        $stories->pri[$i]    = (int)$pri;
        $stories->source[$i] = $source;

        if(!empty($stories->title[$i]) && empty($stories->module[$i])) die(js::alert(sprintf($this->lang->error->notempty, $this->lang->story->module)));
    }

    if(isset($stories->uploadImage)) $this->loadModel('file');

    $forceReview = $this->checkForceReview();
    $data        = array();
    for($i = 0; $i < $batchNum; $i++)
    {
        if(empty($stories->title[$i])) continue;
        $story = new stdclass();
        $story->branch     = $stories->branch[$i];
        $story->module     = $stories->module[$i];
        $story->plan       = $stories->plan[$i];
        $story->color      = $stories->color[$i];
        $story->title      = $stories->title[$i];
        $story->source     = $stories->source[$i];
        $story->pri        = $stories->pri[$i];
        $story->estimate   = $stories->estimate[$i];
        $story->status     = ($stories->needReview[$i] == 0 and !$forceReview) ? 'active' : 'draft';
        $story->keywords   = $stories->keywords[$i];
        $story->product    = $productID;
        $story->openedBy   = $this->app->user->account;
        $story->openedDate = $now;
        $story->version    = 1;

        foreach(explode(',', $this->config->story->create->requiredFields) as $field)
        {
            $field = trim($field);
            if($field and empty($story->$field)) die(js::alert(sprintf($this->lang->error->notempty, $this->lang->story->$field)));
        }

        $data[$i] = $story;
    }

    foreach($data as $i => $story)
    {
        $this->dao->insert(TABLE_STORY)->data($story)->autoCheck()
            ->batchCheck($this->config->story->create->requiredFields, 'notempty')
            ->exec();
        if(dao::isError())
        {
            echo js::error(dao::getError());
            die(js::reload('parent'));
        }

        $storyID = $this->dao->lastInsertID();
        $this->setStage($storyID);

        $specData = new stdclass();
        $specData->story   = $storyID;
        $specData->version = 1;
        $specData->title   = $stories->title[$i];
        $specData->spec    = '';
        $specData->verify  = '';
        if(!empty($stories->spec[$i]))  $specData->spec   = nl2br($stories->spec[$i]);
        if(!empty($stories->verify[$i]))$specData->verify = nl2br($stories->verify[$i]);

        if(!empty($stories->uploadImage[$i]))
        {
            $fileName = $stories->uploadImage[$i];
            $file     = $this->session->storyImagesFile[$fileName];

            $realPath = $file['realpath'];
            unset($file['realpath']);

            if(rename($realPath, $this->file->savePath . $this->file->getSaveName($file['pathname'])))
            {
                $file['addedBy']    = $this->app->user->account;
                $file['addedDate']  = $now;
                $file['objectType'] = 'story';
                $file['objectID']   = $storyID;
                if(in_array($file['extension'], $this->config->file->imageExtensions))
                {
                    $file['extra'] = 'editor';
                    $this->dao->insert(TABLE_FILE)->data($file)->exec();

                    $fileID = $this->dao->lastInsertID();
                    $specData->spec .= '<img src="{' . $fileID . '.' . $file['extension'] . '}" alt="" />';
                }
                else
                {
                    $this->dao->insert(TABLE_FILE)->data($file)->exec();
                }
            }
        }

        $this->dao->insert(TABLE_STORYSPEC)->data($specData)->exec();

        $actionID = $this->action->create('story', $storyID, 'Opened', '');
        if(!dao::isError()) $this->loadModel('score')->create('story', 'create',$storyID);
        $mails[$i] = new stdclass();
        $mails[$i]->storyID  = $storyID;
        $mails[$i]->actionID = $actionID;
    }

    /* Remove upload image file and session. */
    if(!empty($stories->uploadImage) and $this->session->storyImagesFile)
    {
        $classFile = $this->app->loadClass('zfile');
        $file = current($_SESSION['storyImagesFile']);
        $realPath = dirname($file['realpath']);
        if(is_dir($realPath)) $classFile->removeDir($realPath);
        unset($_SESSION['storyImagesFile']);
    }
    if(!dao::isError())  $this->loadModel('score')->create('ajax', 'batchCreate');
    return $mails;
}/**
 * Batch update stories.
 *
 * @access public
 * @return array.
 */
public function batchUpdate()
{
    /* Init vars. */
    $stories     = array();
    $allChanges  = array();
    $now         = helper::now();
    $data        = fixer::input('post')->get();
    $storyIDList = $this->post->storyIDList ? $this->post->storyIDList : array();

    /* Init $stories. */
    if(!empty($storyIDList))
    {
        $oldStories = $this->getByList($storyIDList);

        /* Process the data if the value is 'ditto'. */
        foreach($storyIDList as $storyID)
        {
            if($data->pris[$storyID]     == 'ditto') $data->pris[$storyID]     = isset($prev['pri'])    ? $prev['pri']    : 0;
            if($data->branches[$storyID] == 'ditto') $data->branches[$storyID] = isset($prev['branch']) ? $prev['branch'] : 0;
            if($data->modules[$storyID]  == 'ditto') $data->modules[$storyID]  = isset($prev['module']) ? $prev['module'] : 0;
            //bug修改把plan的值有0改为'';待官方修复后该文件删除
            if($data->plans[$storyID]    == 'ditto') $data->plans[$storyID]    = isset($prev['plan'])   ? $prev['plan']   : '';
            if($data->sources[$storyID]  == 'ditto') $data->sources[$storyID]  = isset($prev['source']) ? $prev['source'] : '';
            if(isset($data->stages[$storyID])        and ($data->stages[$storyID]        == 'ditto')) $data->stages[$storyID]        = isset($prev['stage'])        ? $prev['stage']        : '';
            if(isset($data->closedBys[$storyID])     and ($data->closedBys[$storyID]     == 'ditto')) $data->closedBys[$storyID]     = isset($prev['closedBy'])     ? $prev['closedBy']     : '';
            if(isset($data->closedReasons[$storyID]) and ($data->closedReasons[$storyID] == 'ditto')) $data->closedReasons[$storyID] = isset($prev['closedReason']) ? $prev['closedReason'] : '';

            $prev['pri']    = $data->pris[$storyID];
            $prev['branch'] = isset($data->branches[$storyID]) ? $data->branches[$storyID] : 0;
            $prev['module'] = $data->modules[$storyID];
            $prev['plan']   = $data->plans[$storyID];
            $prev['source'] = $data->sources[$storyID];
            if(isset($data->stages[$storyID]))        $prev['stage']        = $data->stages[$storyID];
            if(isset($data->closedBys[$storyID]))     $prev['closedBy']     = $data->closedBys[$storyID];
            if(isset($data->closedReasons[$storyID])) $prev['closedReason'] = $data->closedReasons[$storyID];
        }

        foreach($storyIDList as $storyID)
        {
            $oldStory = $oldStories[$storyID];

            $story                 = new stdclass();
            $story->lastEditedBy   = $this->app->user->account;
            $story->lastEditedDate = $now;
            $story->status         = $oldStory->status;
            $story->color          = $data->colors[$storyID];
            $story->title          = $data->titles[$storyID];
            $story->estimate       = $data->estimates[$storyID];
            $story->pri            = $data->pris[$storyID];
            //需求可以指派给多人
            $story->assignedTo     = trim(implode(',', $data->assignedTo[$storyID]), ',');
            
            $story->assignedDate   = $oldStory == $data->assignedTo[$storyID] ? $oldStory->assignedDate : $now;
            $story->branch         = isset($data->branches[$storyID]) ? $data->branches[$storyID] : 0;
            $story->module         = $data->modules[$storyID];
            $story->plan           = $data->plans[$storyID];
            $story->source         = $data->sources[$storyID];
            $story->keywords       = $data->keywords[$storyID];
            $story->stage          = isset($data->stages[$storyID])             ? $data->stages[$storyID]             : $oldStory->stage;
            $story->closedBy       = isset($data->closedBys[$storyID])          ? $data->closedBys[$storyID]          : $oldStory->closedBy;
            $story->closedReason   = isset($data->closedReasons[$storyID])      ? $data->closedReasons[$storyID]      : $oldStory->closedReason;
            $story->duplicateStory = isset($data->duplicateStories[$storyID])   ? $data->duplicateStories[$storyID]   : $oldStory->duplicateStory;
            $story->childStories   = isset($data->childStoriesIDList[$storyID]) ? $data->childStoriesIDList[$storyID] : $oldStory->childStories;
            $story->version        = $story->title == $oldStory->title ? $oldStory->version : $oldStory->version + 1;

            if($story->title        != $oldStory->title)                         $story->status     = 'changed';
            //bug修改把plan的值有0改为'';待官方修复后该文件删除
            if($story->plan         !== false and $story->plan == '')            $story->plan       = '';
            if($story->closedBy     != false  and $oldStory->closedDate == '')   $story->closedDate = $now;
            if($story->closedReason != false  and $oldStory->closedDate == '')   $story->closedDate = $now;
            if($story->closedBy     != false  or  $story->closedReason != false) $story->status     = 'closed';
            if($story->closedReason != false  and $story->closedBy     == false) $story->closedBy   = $this->app->user->account;

            $stories[$storyID] = $story;
        }

        foreach($stories as $storyID => $story)
        {
            $oldStory = $oldStories[$storyID];

            $this->dao->update(TABLE_STORY)->data($story)
                ->autoCheck()
                ->checkIF($story->closedBy, 'closedReason', 'notempty')
                ->checkIF($story->closedReason == 'done', 'stage', 'notempty')
                ->checkIF($story->closedReason == 'duplicate',  'duplicateStory', 'notempty')
                ->where('id')->eq((int)$storyID)
                ->exec();
            if($story->title != $oldStory->title)
            {
                $data          = new stdclass();
                $data->story   = $storyID;
                $data->version = $story->version;
                $data->title   = $story->title;
                $data->spec    = $oldStory->spec;
                $data->verify  = $oldStory->verify;
                $this->dao->insert(TABLE_STORYSPEC)->data($data)->exec();
            }

            if(!dao::isError())
            {
                $this->setStage($storyID);
                if($story->closedReason == 'done') $this->loadModel('score')->create('story', 'close');
                $allChanges[$storyID] = common::createChanges($oldStory, $story);
            }
            else
            {
                die(js::error('story#' . $storyID . dao::getError(true)));
            }
        }
    }

    if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchEdit');
    return $allChanges;
}/**
 * Close a story.
 *
 * @param  int    $storyID
 * @access public
 * @return bool
 */
public function close($storyID)
{
    if(strpos($this->config->story->close->requiredFields, 'comment') !== false and !$this->post->comment)
    {
        dao::$errors[] = sprintf($this->lang->error->notempty, $this->lang->comment);
        return false;
    }

    $oldStory = $this->dao->findById($storyID)->from(TABLE_STORY)->fetch();
    $now      = helper::now();
    $story = fixer::input('post')
        ->add('lastEditedBy', $this->app->user->account)
        ->add('lastEditedDate', $now)
        ->add('closedDate', $now)
        ->add('closedBy',   $this->app->user->account)
        ->add('assignedTo',   'closed')
        ->add('assignedDate', $now)
        ->add('status', 'closed')
        ->add('stage', 'closed')
        ->removeIF($this->post->closedReason != 'duplicate', 'duplicateStory')
        ->removeIF($this->post->closedReason != 'subdivided', 'childStories')
        ->setIF($this->post->closedReason == 'done', 'stage', 'released')
        //bug修改把plan的值有0改为'';待官方修复后该文件删除
        ->setIF($this->post->closedReason != 'done', 'plan', '')
        ->remove('comment')
        ->get();

    $this->dao->update(TABLE_STORY)->data($story)
        ->autoCheck()
        ->batchCheck($this->config->story->close->requiredFields, 'notempty')
        ->checkIF($story->closedReason == 'duplicate',  'duplicateStory', 'notempty')
        ->where('id')->eq($storyID)->exec();
    if(!dao::isError() && $this->post->closedReason == 'done') $this->loadModel('score')->create('story', 'close', $storyID);
    return common::createChanges($oldStory, $story);
}/**
 * Created by PhpStorm.
 * User: 月下亭中人
 * Date: 2017/9/28
 * Time: 10:31
 */
/**
 * Create a story.
 *
 * @param $projectID int
 * @param $bugID int
 * @access public
 * @return int|bool the id of the created story or false when error.
 */
public function create($projectID = 0, $bugID = 0, $from = '')
{
    $now   = helper::now();
    $story = fixer::input('post')
        ->cleanInt('product,module,pri,plan')
        ->cleanFloat('estimate')
        ->callFunc('title', 'trim')
        ->setDefault('plan,verify', '')
        ->setDefault('reviewStatus', 'notReview')        //创建需求时评审状态设置为未评审
        ->setDefault('specialPlan', '0000-00-00')   //2911 优化需求提测计划、发版计划等内容 2行
        ->setDefault('testDate', '0000-00-00')
        ->add('openedBy', $this->app->user->account)
        ->add('openedDate', $now)
        ->add('assignedDate', 0)
        ->add('version', 1)
        ->add('status', 'draft')
        ->setIF($this->post->assignedTo != '', 'assignedDate', $now)
        ->setIF($this->post->needNotReview or $projectID > 0, 'status', 'active')
        ->setIF($this->post->plan > 0, 'stage', 'planned')
        ->setIF($projectID > 0, 'stage', 'projected')
        ->setIF($bugID > 0, 'fromBug', $bugID)
        ->join('mailto', ',')
        ->join('devTeam', ',')
        ->join('story', ',')
        ->stripTags($this->config->story->editor->create['id'], $this->config->allowedTags)
        ->remove('customProduct,files,labels,needNotReview,newStory,uid')
        ->get();

//3286 创建需求时就可以选择关联需求，并且支持相关需求处显示“无”38,40
    if (isset($story->story))
    {
        $story->linkStories = $story->story;
        unset($story->story);
        if (!empty($story->linkStories))
        {
            $story->ifLinkStories = 'exist';
        }
    }

    /* Check repeat story. */
    $result = $this->loadModel('common')->removeDuplicate('story', $story, "product={$story->product}");
    if($result['stop']) return array('status' => 'exists', 'id' => $result['duplicate']);

    if($this->checkForceReview()) $story->status = 'draft';
    if($story->status == 'draft') $story->stage   = $this->post->plan > 0 ? 'planned' : 'wait';
    $story = $this->loadModel('file')->processImgURL($story, $this->config->story->editor->create['id'], $this->post->uid);
    $this->dao->insert(TABLE_STORY)->data($story, 'spec,verify')->autoCheck()->batchCheck($this->config->story->create->requiredFields, 'notempty')->exec();
    if(!dao::isError())
    {
        $storyID = $this->dao->lastInsertID();
        $this->file->updateObjectID($this->post->uid, $storyID, 'story');
        $this->file->saveUpload('story', $storyID, $extra = 1);

        $data          = new stdclass();
        $data->story   = $storyID;
        $data->version = 1;
        $data->title   = $story->title;
        $data->spec    = $story->spec;
        $data->verify  = $story->verify;
        $this->dao->insert(TABLE_STORYSPEC)->data($data)->exec();

        if (isset($story->linkStories))
        {
            $linkStories = explode(',', trim($story->linkStories, ','));

            foreach ($linkStories as $id)
            {
                $linkStories = $this->dao->select('linkStories')->FROM(TABLE_STORY)->where('id')->eq(trim($id, ','))->fetch();

                $linkStoriesAB = $linkStories->linkStories .','. $storyID;
                $this->dao->update(TABLE_STORY)->set('ifLinkStories')->eq('exist')->set('linkStories')->eq(trim($linkStoriesAB, ','))->where('id')->eq(trim($id, ','))->exec();
                if(dao::isError()) die(js::error(dao::getError()));
            }
        }

        if($projectID != 0 and $story->status != 'draft')
        {
            $lastOrder = (int)$this->dao->select('*')->from(TABLE_PROJECTSTORY)->where('project')->eq($projectID)->orderBy('order_desc')->limit(1)->fetch('order');
            $this->dao->insert(TABLE_PROJECTSTORY)
                ->set('project')->eq($projectID)
                ->set('product')->eq($this->post->product)
                ->set('story')->eq($storyID)
                ->set('version')->eq(1)
                ->set('order')->eq($lastOrder + 1)
                ->exec();
        }
        //2262 bug转需求之后，bug自动关闭，无法再跟进验证，希望转需求后，bug状态呈现已解决，而非关闭
        if($bugID > 0)
        {
            $bugAB = $this->loadModel('bug')->getByID($bugID);
            $bug = new stdclass();
            $bug->toStory      = $storyID;
            $bug->status       = 'resolved';
            $bug->resolution   = 'tostory';
            $bug->resolvedBy   = $this->app->user->account;
            $bug->resolvedDate = $now;
            $bug->assignedTo   = $bugAB->openedBy;
            $bug->assignedDate = $now;
            $this->dao->update(TABLE_BUG)->data($bug)->where('id')->eq($bugID)->exec();

            $this->loadModel('action')->create('bug', $bugID, 'tostory', '', $storyID);
            $this->action->create('bug', $bugID, 'resolved', '', 'tostory');

            /* add files to story from bug. */
            $files = $this->dao->select('*')->from(TABLE_FILE)
                ->where('objectType')->eq('bug')
                ->andWhere('objectID')->eq($bugID)
                ->fetchAll();
            if(!empty($files))
            {
                foreach($files as $file)
                {
                    $file->objectType = 'story';
                    $file->objectID = $storyID;
                    unset($file->id);
                    $this->dao->insert(TABLE_FILE)->data($file)->exec();
                }
            }
        }
        $this->setStage($storyID);
        if(!dao::isError()) $this->loadModel('score')->create('story', 'create',$storyID);

        /* Callback the callable method to process the related data for object that is transfered to story. */
        if($from && is_callable(array($this, $this->config->story->fromObjects[$from]['callback']))) call_user_func(array($this, $this->config->story->fromObjects[$from]['callback']), $storyID);

        return array('status' => 'created', 'id' => $storyID);
    }
    return false;
}public function setListValue($productID, $branch = 0)
{
    return $this->loadExtension('excel')->setListValue($productID, $branch);
}

public function createFromImport($productID, $branch = 0)
{
    return $this->loadExtension('excel')->createFromImport($productID, $branch);
}/**
 * Format stories
 *
 * @param  array    $stories
 * @param  string   $type
 * @param  int      $limit
 * @access public
 * @return void
 */
public function formatStories($stories, $type = 'full', $limit = 0)
{
    /* Get module names of stories. */
    /*$modules = array();
    foreach($stories as $story) $modules[] = $story->module;
    $moduleNames = $this->dao->select('id, name')->from(TABLE_MODULE)->where('id')->in($modules)->fetchPairs();*/

    /* Format these stories. */
    $storyPairs = array('' => '');
    $i = 0;
    $this->config->user->showDeleted = 1;        //修改bug；列表页可以显示已删除的用户 21-22
    $users = $this->loadModel('user')->getPairs();
    foreach($stories as $story)
    {
        if($type == 'short')
        {
            $property = '[p' . (!empty($this->lang->story->priList[$story->pri]) ? $this->lang->story->priList[$story->pri] : 0) . ', ' . $story->estimate . 'h]';
        }
        else
        {
            $property = '(' . $this->lang->story->pri . ':' . (!empty($this->lang->story->priList[$story->pri]) ? $this->lang->story->priList[$story->pri] : 0) . ',' . $this->lang->story->estimate . ':' . $story->estimate . ')';
        }
        $storyPairs[$story->id] = $story->id . ':' . $story->title . $property;

        if($limit > 0 && ++$i > $limit)
        {
            $storyPairs['showmore'] = $this->lang->more . $this->lang->ellipsis;
            break;
        }
    }
    return $storyPairs;
}/**
 * Get stories by assignedTo.
 *
 * @param  int    $productID
 * @param  string $account
 * @param  string $orderBy
 * @param  object $pager
 * @access public
 * @return array
 */
public function getByAssignedTo($productID, $branch, $modules, $account, $orderBy, $pager)
{
    //需求可以指派给多个人；查询自拍给用like
    return $this->getByField($productID, $branch, $modules, 'assignedTo', $account, $orderBy, $pager, 'include');
}/**
 * Get stories pairs of a product.
 *
 * @param  int           $productID
 * @param  int           $branch
 * @param  array|string  $moduleIdList
 * @param  string        $status
 * @param  string        $order
 * @param  int           $limit
 * @access public
 * @return array
 */
public function getProductStoryPairs($productID = 0, $branch = 0, $moduleIdList = 0, $status = 'all', $order = 'id_desc', $limit = 0)
{
    if($branch) $branch = "0,$branch";//Fix bug 1059.
    $stories = $this->dao->select('t1.id, t1.title, t1.module, t1.pri, t1.estimate, t1.openedBy, t2.name AS product')
        ->from(TABLE_STORY)->alias('t1')->leftJoin(TABLE_PRODUCT)->alias('t2')->on('t1.product = t2.id')
        ->where('1=1')
        ->beginIF($productID)->andWhere('t1.product')->in($productID)->fi()
        ->beginIF($moduleIdList)->andWhere('t1.module')->in($moduleIdList)->fi()
        ->beginIF($branch)->andWhere('t1.branch')->in($branch)->fi()
        ->beginIF($status and $status != 'all')->andWhere('t1.status')->in($status)->fi()
        ->andWhere('t1.deleted')->eq(0)
        ->orderBy($order)
        ->fetchAll();
    if(!$stories) return array();
    return $this->formatStories($stories, 'full', $limit);
}/**
 * Get stories list of a project.
 *
 * @param  int    $projectID
 * @param  string $orderBy
 * @access public
 * @return array
 */
public function getProjectStories($projectID = 0, $orderBy = 't1.`order`_desc', $type = 'byModule', $param = 0, $pager = null)
{
    if(defined('TUTORIAL')) return $this->loadModel('tutorial')->getProjectStories();

    if($type == 'bySearch')
    {
        $queryID  = (int)$param;
        $products = $this->loadModel('project')->getProducts($projectID);

        if($this->session->projectStoryQuery == false) $this->session->set('projectStoryQuery', ' 1 = 1');
        if($queryID)
        {
            $query = $this->loadModel('search')->getQuery($queryID);
            if($query)
            {
                $this->session->set('projectStoryQuery', $query->sql);
                $this->session->set('projectStoryForm', $query->form);
            }
        }

        $allProduct = "`product` = 'all'";
        $storyQuery = $this->session->projectStoryQuery;
        if(strpos($this->session->projectStoryQuery, $allProduct) !== false)
        {
            $storyQuery = str_replace($allProduct, '1', $this->session->projectStoryQuery);
        }
        $storyQuery = preg_replace('/`(\w+)`/', 't2.`$1`', $storyQuery);

        $stories = $this->dao->select('distinct t1.*, t2.*, t3.branch as productBranch, t4.type as productType, t2.version as version')->from(TABLE_PROJECTSTORY)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
            ->leftJoin(TABLE_PROJECTPRODUCT)->alias('t3')->on('t1.project = t3.project')
            ->leftJoin(TABLE_PRODUCT)->alias('t4')->on('t2.product = t4.id')
            ->where($storyQuery)
            ->andWhere('t1.project')->eq((int)$projectID)
            ->orderBy($orderBy)
            ->page($pager, 't2.id')
            ->fetchAll('id');
    }
    else
    {
        $modules = ($type == 'byModule' and $param) ? $this->dao->select('*')->from(TABLE_MODULE)->where('path')->like("%,$param,%")->andWhere('type')->eq('story')->andWhere('deleted')->eq(0)->fetchPairs('id', 'id') : array();
        $stories = $this->dao->select('distinct t1.*, t2.*,t3.branch as productBranch,t4.type as productType,t2.version as version')->from(TABLE_PROJECTSTORY)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
            ->leftJoin(TABLE_PROJECTPRODUCT)->alias('t3')->on('t1.project = t3.project')
            ->leftJoin(TABLE_PRODUCT)->alias('t4')->on('t2.product = t4.id')
            ->where('t1.project')->eq((int)$projectID)
            ->beginIF($type == 'byProduct')->andWhere('t1.product')->eq($param)->fi()
            ->beginIF($type == 'byBranch')->andWhere('t2.branch')->eq($param)->fi()
            ->beginIF($type == 'byModule' and $param)->andWhere('t2.module')->in($modules)->fi()
            ->andWhere('t2.deleted')->eq(0)
            ->orderBy($orderBy)
            ->page($pager, 't2.id')
            ->fetchAll('id');
    }

    $query    = $this->dao->get();
    $branches = array();
    foreach($stories as $story)
    {
        if(empty($story->branch) and $story->productType != 'normal') $branches[$story->productBranch][$story->id] = $story->id;
    }
    //现阶段不需要该规则
    /*foreach($branches as $branchID => $storyIDList)
    {
        $stages = $this->dao->select('*')->from(TABLE_STORYSTAGE)->where('story')->in($storyIDList)->andWhere('branch')->eq($branchID)->fetchPairs('story', 'stage');
        foreach($stages as $storyID => $stage) $stories[$storyID]->stage = $stage;
    }*/

    $this->dao->sqlobj->sql = $query;
    return $stories;
}/**
 * Get stories pairs of a project.
 *
 * @param  int           $projectID
 * @param  int           $productID
 * @param  array|string  $moduleIdList
 * @param  string        $type
 * @access public
 * @return array
 */
public function getProjectStoryPairs($projectID = 0, $productID = 0, $branch = 0, $moduleIdList = 0, $type = 'full')
{
    if(defined('TUTORIAL')) return $this->loadModel('tutorial')->getProjectStoryPairs();
    $stories = $this->dao->select('t2.id, t2.title, t2.module, t2.pri, t2.estimate, t2.openedBy,  t3.name AS product')
        ->from(TABLE_PROJECTSTORY)->alias('t1')
        ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
        ->leftJoin(TABLE_PRODUCT)->alias('t3')->on('t1.product = t3.id')
        ->where('t1.project')->eq((int)$projectID)
        ->andWhere('t2.deleted')->eq(0)
        ->beginIF($productID)->andWhere('t2.product')->eq((int)$productID)->fi()
        ->beginIF($branch)->andWhere('t2.branch')->in("0,$branch")->fi()
        ->beginIF($moduleIdList)->andWhere('t2.module')->in($moduleIdList)->fi()
        ->orderBy('t1.`order` desc')
        ->fetchAll();
    if(!$stories) return array();
    return $this->formatStories($stories, $type);
}/**
 * Get stories of a user.
 *
 * @param  string $type         the query type
 * @param $browseType string
 * @param $queryID int
 * @param  string $orderBy
 * @param  object $pager
 * @access public
 * @return array
 */
public function getStoriesByField($type = 'toTestStory', $browseType, $queryID, $orderBy, $pager = null)
{
    //$limitDate = date("Y-m-d",strtotime("+10 day"));
    
    if ($browseType == 'bysearch')
    {
        $products = $this->loadModel('product')->getPairs();

        if($queryID)
        {
            $query = $this->loadModel('search')->getQuery($queryID);
            if($query)
            {
                $this->session->set('storyQuery', $query->sql);
                $this->session->set('storyForm', $query->form);
            }
            else
            {
                $this->session->set('storyQuery', ' 1 = 1');
            }
        }

        $allProduct     = "`product` = 'all'";
        $storyQuery = $this->session->storyQuery;
        if(strpos($storyQuery, $allProduct) !== false)
        {
            $storyQuery     = str_replace($allProduct, '1', $storyQuery);
            $queryProductID = 'all';
        }
        $storyQuery = $storyQuery . ' AND `product` ' . helper::dbIN(array_keys($products));
        $storyQuery = preg_replace('/`(\w+)`/', 't1.`$1`', $storyQuery);

        $stories = $this->dao->select('t1.*, t2.name as productTitle')->from(TABLE_STORY)->alias('t1')
            ->leftJoin(TABLE_PRODUCT)->alias('t2')->on('t1.product = t2.id')
            ->where('t1.deleted')->eq(0)
            ->andWhere('product')->notin($this->config->story->storyCollectionPoolProducts)
            ->andWhere($storyQuery)
            ->beginIF($type == 'toTestStory')->andWhere('stage')->in('projected,developing,developed')->andWhere('testDate')->ne('0000-00-00')->andWhere('verifyStatus')->ne('freeVerified')->fi()
            ->beginIF($type == 'toReleaseStory')->andWhere('stage')->notin('released,wait,planned')->andWhere('specialPlan')->ne('0000-00-00')->fi()
            ->andWhere("IF (t1.`status` = 'closed',t1.closedReason = 'done',2>1)")
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll();
    }
    else
    {
        $stories = $this->dao->select('t1.*, t2.name as productTitle')->from(TABLE_STORY)->alias('t1')
            ->leftJoin(TABLE_PRODUCT)->alias('t2')->on('t1.product = t2.id')
            ->where('t1.deleted')->eq(0)
            ->andWhere('product')->notin($this->config->story->storyCollectionPoolProducts)
            ->andWhere('product')->eq(2)
            ->beginIF($type == 'toTestStory')->andWhere('stage')->in('projected,developing,developed')->andWhere('testDate')->ne('0000-00-00')->andWhere('verifyStatus')->ne('freeVerified')->fi()
            ->beginIF($type == 'toReleaseStory')->andWhere('stage')->notin('released,wait,planned')->andWhere('specialPlan')->ne('0000-00-00')->fi()
            ->andWhere("IF (t1.`status` = 'closed',t1.closedReason = 'done',2>1)")
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll();
    }

    //$this->loadModel('common')->saveQueryCondition($this->dao->get(), 'story');
    $productIdList = array();
    //2828 我的地盘--指派给我的需求（点击需求可以直接定位到可以拆任务的界面）
    foreach($stories as $story)
    {
        $productIdList[$story->product] = $story->product;
        $story->projectID = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('story')->eq($story->id)->fetch('project');
        $this->processStory($story);

        if (!empty($story->linkStories))
        {
            $relatedStories = $this->dao->select('id,openedBy')->from(TABLE_STORY) ->where('id')->in(trim($story->linkStories, ','))->fetchPairs();
            $users = $this->loadModel('user')->getPairs('noletter');
            foreach ($relatedStories as $relatedStory)
            {
                $story->linkStoryOpenedBys .= $users[$relatedStory] . ' ';
            };
            $story->linkStoryOpenedBys = implode(' ', array_unique(explode(' ',trim($story->linkStoryOpenedBys))));
        }
    }

    return $this->mergePlanTitle($productIdList, $stories);
}

/**
 * Process a task, judge it's status.
 *
 * @param  object    $story
 * @access private
 * @return object
 */
public function processStory($story)
{
    $today = helper::today();

    /* Delayed or not?. */
    if($story->stage != 'released' and $story->stage != 'verified' and $story->stage != 'tested' and $story->stage != 'testing')
    {
        if($story->testDate != '0000-00-00')
        {
            $delay = helper::diffDate($story->testDate, $today);

            if ($delay > 0 and $delay < 4)
            {
                $story->testWarning = 'green';
            }
            elseif($delay == 0)
            {
                $story->testWarning = 'blue';
            }
            elseif($delay < 0)
            {
                $story->testWarning = 'red';
            }
        }
    }
    if($story->stage != 'released')
    {
        if($story->specialPlan != '0000-00-00')
        {
            $delay = helper::diffDate($story->specialPlan, $today);

            if ($delay > 0 and $delay < 4)
            {
                $story->releaseWarning = 'green';
            }
            elseif($delay == 0)
            {
                $story->releaseWarning = 'blue';
            }
            elseif($delay < 0)
            {
                $story->releaseWarning = 'red';
            }
        }
    }

    $builds      = $this->dao->select('id')->from(TABLE_BUILD)->where('stories')->like('%,' . $story->id . '%')->fetchAll('id');
    $patchBuilds = $this->dao->select('id')->from(TABLE_PATCHBUILD)->where('linkStories')->like('%,' . $story->id . '%')->fetchAll('id');
    $releases = $this->dao->select('id')->from(TABLE_RELEASE)->where('stories')->like('%,' . $story->id . '%')->fetchAll('id');
    $story->build      = array_keys($builds);
    $story->patchBuild = array_keys($patchBuilds);
    $story->release = array_keys($releases);

    return $story;
}/**
 * Get stories of a user.
 *
 * @param  string $account
 * @param  string $type         the query type
 * @param  string $orderBy
 * @param  object $pager
 * @access public
 * @return array
 */
public function getUserStories($account, $type = 'assignedTo', $orderBy = 'id_desc', $pager = null)
{
    $stories = $this->dao->select('t1.*, t2.name as productTitle')->from(TABLE_STORY)->alias('t1')
        ->leftJoin(TABLE_PRODUCT)->alias('t2')->on('t1.product = t2.id')
        ->where('t1.deleted')->eq(0)
        ->beginIF($type != 'all')
        //需求可以指派给多个人；查询指派给用like
        ->beginIF($type == 'assignedTo')->andWhere('assignedTo')->like('%' . $account . '%')->fi()
        ->beginIF($type == 'openedBy')->andWhere('openedBy')->eq($account)->fi()
        ->beginIF($type == 'reviewedBy')->andWhere('reviewedBy')->like('%' . $account . '%')->fi()
        ->beginIF($type == 'closedBy')->andWhere('closedBy')->eq($account)->fi()
        ->fi()
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll();

    $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'story');
    $productIdList = array();
    //2828 我的地盘--指派给我的需求（点击需求可以直接定位到可以拆任务的界面）
    foreach($stories as $story)
    {
        $productIdList[$story->product] = $story->product;
        $story->projectID = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('story')->eq($story->id)->fetch('project');
    }

    return $this->mergePlanTitle($productIdList, $stories);
}/**
 * Link stories.
 *
 * @param  int    $storyID
 * @param  string $type
 * @access public
 * @return void
 */
public function linkStories($storyID, $type = 'linkStories')
{
    if($this->post->stories == false) return;

    $story        = $this->getById($storyID);
    $stories2Link = $this->post->stories;
    $stories = implode(',', $stories2Link) . ',' . trim($story->$type, ',');
//3286 创建需求时就可以选择关联需求，并且支持相关需求处显示“无”
    $this->dao->update(TABLE_STORY)->set($type)->eq(trim($stories, ','))->set('ifLinkStories')->eq('exist')->where('id')->eq($storyID)->exec();
    if(dao::isError()) die(js::error(dao::getError()));

    $action = ($type == 'linkStories') ? 'linkRelatedStory' : 'subdivideStory';
    $this->loadModel('action')->create('story', $storyID, $action, '', implode(',', $stories2Link));
//需求双向关联
    if ($type == 'linkStories')
    {
        foreach ($stories2Link as $val)
        {
            $linkStories = $this->dao->select('linkStories')->FROM(TABLE_STORY)->where('id')->eq(trim($val, ','))->fetch();
            $linkStoriesAB = $linkStories->linkStories .','. $storyID;

            $this->dao->update(TABLE_STORY)->set($type)->eq(trim($linkStoriesAB, ','))->set('ifLinkStories')->eq('exist')->where('id')->eq(trim($val, ','))->exec();
            if(dao::isError()) die(js::error(dao::getError()));

            $action = ($type == 'linkStories') ? 'linkRelatedStory' : 'subdivideStory';
            $this->loadModel('action')->create('story', $val, $action, '', $storyID);
        }
    }
}/**
 * Merge plan title.
 *
 * @param  int|array    $productID
 * @param  array    $stories
 * @access public
 * @return array
 */
public function mergePlanTitle($productID, $stories, $branch = 0)
{
    $query = $this->dao->get();

    if(is_array($branch))
    {
        unset($branch[0]);
        $branch = join(',', $branch);
        if($branch) $branch = "0,$branch";
    }
    $plans = $this->dao->select('id,title')->from(TABLE_PRODUCTPLAN)
        ->where('product')->in($productID)
        ->andWhere('deleted')->eq(0)
        ->fetchPairs('id', 'title');

    foreach($stories as $story)
    {
        //2273 需求增加一个字段“期望实现时间”，该字段的值采用下拉菜单格式，并且下拉菜单最好能调用产品-计划中的未关闭计划
        $story->customPlan = zget($plans, $story->customPlan, '') . ' ';

        $story->planTitle = '';
        $storyPlans = explode(',', trim($story->plan, ','));
        foreach($storyPlans as $planID) $story->planTitle .= zget($plans, $planID, '') . ' ';
    }

    /* For save session query. */
    $this->dao->sqlobj->sql = $query;

    return $stories;
}/**
 * Print cell data
 *
 * @param  object $col
 * @param  object $story
 * @param  array  $users
 * @param  array  $branches
 * @param  array  $storyStages
 * @param  array  $modulePairs
 * @param  array  $storyTasks
 * @param  array  $storyBugs
 * @param  array  $storyCases
 * @access public
 * @return void
 */
public function printCell($col, $story, $users, $branches, $storyStages, $modulePairs = array(), $storyTasks = array(), $storyBugs = array(), $storyCases = array(), $mode = 'datatable')
{
    $canView   = common::hasPriv('story', 'view');
    $storyLink = helper::createLink('story', 'view', "storyID=$story->id");
    $account   = $this->app->user->account;
    $id        = $col->id;
    if($col->show)
    {
        $class = '';
        if($id == 'status') $class .= ' story-' . $story->status;
        if($id == 'title') $class .= ' text-left';
        if($id == 'id')     $class .= ' cell-id';
        if($id == 'assignedTo' && $story->assignedTo == $account) $class .= ' red';

        $title = '';
        if($id == 'title') $title = $story->title;
        if($id == 'plan')  $title = $story->planTitle;
        //2273 需求增加一个字段“期望实现时间”，该字段的值采用下拉菜单格式，并且下拉菜单最好能调用产品-计划中的未关闭计划
        if($id == 'customPlan') $title = $story->customPlan;
        //if($id == 'specialPlan') $title = $story->specialPlan;
        //需求指派多人
        if($id == 'assignedTo')
        {
            $assignedToAB = '';
            $assignedTo = explode(',', $story->assignedTo); foreach($assignedTo as $account) {if(empty($account)) continue; $assignedToAB .=  $users[trim($account)] . '&nbsp;'; };
            $title = $assignedToAB;
        }
        //15728 增加开发关联方字段快速识别需求涉及到的前后端开发
        if($id == 'devTeam')
        {
            $devTeamAB = '';
            $devTeams = explode(',', $story->devTeam); foreach($devTeams as $devTeam) {if(empty($devTeam)) continue; $devTeamAB .=  $this->lang->story->devTeamList[trim($devTeam)] . '&nbsp;'; };
            $title = $devTeamAB;
        }

        echo "<td class='" . $class . "' title='$title'>";
        switch($id)
        {
            case 'id':
                if($mode == 'table') echo "<input type='checkbox' name='storyIDList[{$story->id}]' value='{$story->id}' />";
                echo $canView ? html::a($storyLink, sprintf('%03d', $story->id)) : sprintf('%03d', $story->id);
                break;
            case 'pri':
                echo "<span class='pri" . zget($this->lang->story->priList, $story->pri, $story->pri) . "'>";
                echo zget($this->lang->story->priList, $story->pri, $story->pri);
                echo "</span>";
                break;
            case 'title':
                if($story->branch and isset($branches[$story->branch])) echo "<span class='label label-info label-badge'>{$branches[$story->branch]}</span> ";
                if($story->module and isset($modulePairs[$story->module])) echo "<span class='label label-info label-badge'>{$modulePairs[$story->module]}</span> ";
                echo $canView ? html::a($storyLink, $story->title, '', "style='color: $story->color'") : "<span style='color: $story->color'>{$story->title}</span>";

                break;
            case 'plan':
                echo $story->planTitle;
                break;
            case 'branch':
                echo zget($branches, $story->branch, '');
                break;
            case 'keywords':
                echo $story->keywords;
                break;
            //1522 增加产品需求所关联的需求和已细分需求的显示
            case 'childStories':
                echo $story->childStories;
                break;
            case 'linkStories':
                echo $story->linkStories;
                break;
            //需求增加一个字段“期望实现时间”，该字段的值采用下拉菜单格式，并且下拉菜单最好能调用产品-计划中的未关闭计划
            case 'customPlan':
                echo $story->customPlan;
                break;
            //2911 优化需求提测计划、发版计划等内容
            case 'testDate':
                echo $story->testDate;
                break;
            case 'specialPlan':
                echo $story->specialPlan;
                break;
            //3286 创建需求时就可以选择关联需求，并且支持相关需求处显示“无”
            case 'ifLinkStories':
                echo zget($this->lang->story->ifLinkStoriesList, $story->ifLinkStories, $story->ifLinkStories);
                break;
            //3759 项目需求下增加具体情况汇总展示页
            case 'testStatus':
                echo zget($this->lang->story->testStatusList, $story->testStatus, $story->testStatus);
                break;
            case 'verifyStatus':
                echo zget($this->lang->story->verifyStatusList, $story->verifyStatus, $story->verifyStatus);
                break;
            //15728 增加开发关联方字段快速识别需求涉及到的前后端开发
            case 'devTeam':
                $devTeams = explode(',', $story->devTeam);
                foreach($devTeams as $devTeam)
                {
                    $devTeam = trim($devTeam);
                    if(empty($devTeam)) continue;
                    echo zget($this->lang->story->devTeamList, $devTeam) . ' &nbsp;';
                }
                break;

            case 'source':
                echo zget($this->lang->story->sourceList, $story->source, $story->source);
                break;
            case 'sourceNote':
                echo $story->sourceNote;
                break;
            case 'status':
                echo $this->lang->story->statusList[$story->status];
                break;
            case 'estimate':
                echo $story->estimate;
                break;
            case 'stage':
                echo "<div" . (isset($storyStages[$story->id]) ? " class='popoverStage' data-toggle='popover' data-placement='bottom' data-target='\$next'" : '') . "'>";
                echo $this->lang->story->stageList[$story->stage];
                if(isset($storyStages[$story->id])) echo "<span><i class='icon icon-caret-down'></i></span>";
                echo '</div>';
                if(isset($storyStages[$story->id]))
                {
                    echo "<div class='popover'>";
                    foreach($storyStages[$story->id] as $storyBranch => $storyStage) echo $branches[$storyBranch] . ": " . $this->lang->story->stageList[$storyStage->stage] . '<br />';
                    echo "</div>";
                }
                break;
            case 'taskCount':
                $tasksLink = helper::createLink('story', 'tasks', "storyID=$story->id");
                $storyTasks[$story->id] > 0 ? print(html::a($tasksLink, $storyTasks[$story->id], '', 'class="iframe"')) : print(0);
                break;
            case 'bugCount':
                $bugsLink = helper::createLink('story', 'bugs', "storyID=$story->id");
                $storyBugs[$story->id] > 0 ? print(html::a($bugsLink, $storyBugs[$story->id], '', 'class="iframe"')) : print(0);
                break;
            case 'caseCount':
                $casesLink = helper::createLink('story', 'cases', "storyID=$story->id");
                $storyCases[$story->id] > 0 ? print(html::a($casesLink, $storyCases[$story->id], '', 'class="iframe"')) : print(0);
                break;
            case 'openedBy':
                echo zget($users, $story->openedBy, $story->openedBy);
                break;
            case 'openedDate':
                echo substr($story->openedDate, 5, 11);
                break;
            case 'assignedTo':
                //需求指派多人
                $assignedTo = explode(',', $story->assignedTo); foreach($assignedTo as $account) {if(empty($account)) continue; echo "<span>" . $users[trim($account)] . '</span> &nbsp;'; };
                break;
            case 'assignedDate':
                echo substr($story->assignedDate, 5, 11);
                break;
            case 'reviewedBy':
                echo zget($users, $story->reviewedBy, $story->reviewedBy);
                break;
            case 'reviewedDate':
                echo substr($story->reviewedDate, 5, 11);
                break;
            case 'closedBy':
                echo zget($users, $story->closedBy, $story->closedBy);
                break;
            case 'closedDate':
                echo substr($story->closedDate, 5, 11);
                break;
            case 'closedReason':
                echo zget($this->lang->story->reasonList, $story->closedReason, $story->closedReason);
                break;
            case 'lastEditedBy':
                echo zget($users, $story->lastEditedBy, $story->lastEditedBy);
                break;
            case 'lastEditedDate':
                echo substr($story->lastEditedDate, 5, 11);
                break;
            case 'mailto':
                $mailto = explode(',', $story->mailto);
                foreach($mailto as $account)
                {
                    $account = trim($account);
                    if(empty($account)) continue;
                    echo zget($users, $account) . ' &nbsp;';
                }
                break;
            case 'version':
                echo $story->version;
                break;
            case 'actions':
                $vars = "story={$story->id}";
                common::printIcon('story', 'change',     $vars, $story, 'list', 'random');
                common::printIcon('story', 'review',     $vars, $story, 'list', 'review');
                common::printIcon('story', 'close',      $vars, $story, 'list', 'off', '', 'iframe', true);
                common::printIcon('story', 'edit',       $vars, $story, 'list', 'pencil');
                if($this->config->global->flow != 'onlyStory') common::printIcon('story', 'createCase', "productID=$story->product&branch=$story->branch&module=0&from=&param=0&$vars", $story, 'list', 'sitemap');
                break;
        }
        echo '</td>';
    }
}/**
 * Get stories through search.
 *
 * @access public
 * @param  int    $productID
 * @param  int    $queryID
 * @param  string $orderBy
 * @param  object $pager
 * @param  string $projectID
 * @access public
 * @return array
 */
public function getBySearch($productID, $queryID, $orderBy, $pager = null, $projectID = '', $branch = 0)
{
    if($projectID != '')
    {
        $products = $this->loadModel('project')->getProducts($projectID);
    }
    else
    {
        $products = $this->loadModel('product')->getPairs();
    }
    $query = $queryID ? $this->loadModel('search')->getQuery($queryID) : '';

    /* Get the sql and form status from the query. */
    if($query)
    {
        $this->session->set('storyQuery', $query->sql);
        $this->session->set('storyForm', $query->form);
    }
    if($this->session->storyQuery == false) $this->session->set('storyQuery', ' 1 = 1');

    $allProduct     = "`product` = 'all'";
    $storyQuery     = $this->session->storyQuery;
    $queryProductID = $productID;
    if(strpos($storyQuery, $allProduct) !== false)
    {
        $storyQuery     = str_replace($allProduct, '1', $storyQuery);
        $queryProductID = 'all';
    }
    $storyQuery = $storyQuery . ' AND `product` ' . helper::dbIN(array_keys($products));
    if($projectID != '')
    {
        foreach($products as $product) $branches[$product->branch] = $product->branch;
        unset($branches[0]);
        $branches = join(',', $branches);
        if($branches) $storyQuery .= " AND `branch`" . helper::dbIN("0,$branches");
        if($this->app->moduleName == 'release' or $this->app->moduleName == 'build')
        {
            $storyQuery .= " AND `status` NOT IN ('draft')";// Fix bug #990.
        }
        else
        {
            $storyQuery .= " AND `status` NOT IN ('draft', 'closed')";
        }
    }
    elseif($branch)
    {
        $allBranch = "`branch` = 'all'";
        //跨产品关联需求时无法搜到预期结果
        //if($branch and strpos($storyQuery, '`branch` =') === false) $storyQuery .= " AND `branch` in('0','$branch')";

        if(strpos($storyQuery, $allBranch) !== false) $storyQuery = str_replace($allBranch, '1', $storyQuery);
    }
    $storyQuery = preg_replace("/`plan` +LIKE +'%([0-9]+)%'/i", "CONCAT(',', `plan`, ',') LIKE '%,$1,%'", $storyQuery);

    return $this->getBySQL($queryProductID, $storyQuery, $orderBy, $pager);
}

/**
 * Get stories by a sql.
 *
 * @param  int    $productID
 * @param  string $sql
 * @param  string $orderBy
 * @param  object $pager
 * @access public
 * @return array
 */
public function getBySQL($productID, $sql, $orderBy, $pager = null)
{
    /* Get plans. */
    $plans = $this->dao->select('id,title')->from(TABLE_PRODUCTPLAN)
        ->where('deleted')->eq('0')
        ->beginIF($productID != 'all' and $productID != '')->andWhere('product')->eq((int)$productID)->fi()
        ->fetchPairs();

    $sql = str_replace(array('`product`', '`version`'), array('t1.`product`', 't1.`version`'), $sql);
    $tmpStories = $this->dao->select('DISTINCT t1.*')->from(TABLE_STORY)->alias('t1')
        ->leftJoin(TABLE_PROJECTSTORY)->alias('t2')->on('t1.id=t2.story')
        ->where($sql)
        //跨产品需求搜索无法搜到结果；
        ->beginIF($productID != 'all' and strpos($sql, "`product` =") === false)->andWhere('t1.`product`')->eq((int)$productID)->fi()
        //->beginIF($productID != 'all' and $productID != '')->andWhere('t1.`product`')->eq((int)$productID)->fi()

        ->andWhere('deleted')->eq(0)
        ->orderBy($orderBy)
        ->page($pager, 't1.id')
        ->fetchAll('id');

    if(!$tmpStories) return array();

    /* Process plans. */
    $stories = array();
    foreach($tmpStories as $story)
    {
        //2273 需求增加一个字段“期望实现时间”，该字段的值采用下拉菜单格式，并且下拉菜单最好能调用产品-计划中的未关闭计划
        $story->customPlan = zget($plans, $story->customPlan, '') . ' ';
        $story->planTitle = '';
        $storyPlans = explode(',', trim($story->plan, ','));
        foreach($storyPlans as $planID) $story->planTitle .= zget($plans, $planID, '') . ' ';
        $stories[] = $story;
    }
    return $stories;
}/**
 * Set stage of a story.
 *
 * @param  int    $storyID
 * @access public
 * @return bool
 */
public function setStage($storyID)
{
    $storyID = (int)$storyID;

    /* Get projects which status is doing. */
    $this->dao->delete()->from(TABLE_STORYSTAGE)->where('story')->eq($storyID)->exec();
    $story    = $this->dao->findById($storyID)->from(TABLE_STORY)->fetch();
    $product  = $this->dao->findById($story->product)->from(TABLE_PRODUCT)->fetch();
    $projects = $this->dao->select('t1.project,t3.branch')->from(TABLE_PROJECTSTORY)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->leftJoin(TABLE_PROJECTPRODUCT)->alias('t3')->on('t1.project = t3.project')
        ->where('t1.story')->eq($storyID)
        ->andWhere('t2.deleted')->eq(0)
        ->fetchPairs('project', 'branch');

    $hasBranch = ($product->type != 'normal' and empty($story->branch));
    $stages    = array();
    if($hasBranch and $story->plan)
    {
        $plans = $this->dao->select('*')->from(TABLE_PRODUCTPLAN)->where('id')->in($story->plan)->fetchPairs('branch', 'branch');
        foreach($plans as $branch) $stages[$branch] = 'planned';
    }

    /* If no projects, in plan, stage is planned. No plan, wait. */
    if(!$projects)
    {
        $this->dao->update(TABLE_STORY)->set('stage')->eq('wait')->where('id')->eq($storyID)->andWhere('plan')->eq('')->exec();

        foreach($stages as $branch => $stage) $this->dao->insert(TABLE_STORYSTAGE)->set('story')->eq($storyID)->set('branch')->eq($branch)->set('stage')->eq($stage)->exec();
        $this->dao->update(TABLE_STORY)->set('stage')->eq('planned')->where('id')->eq($storyID)->andWhere("(plan != '' AND plan != '0')")->exec();
    }

    if($hasBranch)
    {
        foreach($projects as $projectID => $branch) $stages[$branch] = 'projected';
    }
    //获取任务类型配置信息
    $taskType = $this->config->story->develTask . ',test';

    /* Search related tasks. 取消统计status为pause的任务 */
    $tasks = $this->dao->select('type,project,status')->from(TABLE_TASK)
        ->where('project')->in(array_keys($projects))
        ->andWhere('story')->eq($storyID)
        ->andWhere('type')->in($taskType)
        ->andWhere('status')->ne('cancel')
        ->andWhere('status')->ne('pause')
        ->andWhere('closedReason')->ne('cancel')
        ->andWhere('deleted')->eq(0)
        ->fetchGroup('type');

    /* No tasks, then the stage is projected. */
    if(!$tasks and $projects)
    {
        foreach($stages as $branch => $stage) $this->dao->insert(TABLE_STORYSTAGE)->set('story')->eq($storyID)->set('branch')->eq($branch)->set('stage')->eq('projected')->exec();
        $this->dao->update(TABLE_STORY)->set('stage')->eq('projected')->where('id')->eq($storyID)->exec();
    }

    /* Get current stage and set as default value. */
    $currentStage = $story->stage;
    $stage = $currentStage;

    /* Cycle all tasks, get counts of every type and every status. */
    $branchStatusList = array();
    $branchDevelTasks = array();
    $branchTestTasks  = array();
    $statusList['devel'] = array('wait' => 0, 'doing' => 0, 'done'=> 0);
    $statusList['test']  = array('wait' => 0, 'doing' => 0, 'done'=> 0);

    foreach($tasks as $type => $typeTasks)
    {
        foreach($typeTasks as $task)
        {
            $status = $task->status ? $task->status : 'wait';
            $status = $status == 'closed' ? 'done' : $status;

            $branch = $projects[$task->project];

            if(!isset($branchStatusList[$branch])) $branchStatusList[$branch] = $statusList;
            //计算开发和测试各状态的任务数量
            if ($task->type != 'test')
            {
                $branchStatusList[$branch]['devel'][$status] ++;
            }
            else
            {
                $branchStatusList[$branch]['test'][$status] ++;
            }

            $develTask = explode(',', $this->config->story->develTask);

            if(in_array($type, $develTask))
            {
                if(!isset($branchDevelTasks[$branch])) $branchDevelTasks[$branch] = 0;
                $branchDevelTasks[$branch] ++;
            }
            elseif($type == 'test')
            {
                if(!isset($branchTestTasks[$branch])) $branchTestTasks[$branch] = 0;
                $branchTestTasks[$branch] ++;
            }
        }
    }

    /**
     * Judge stage according to the devel and test tasks' status.
     *
     * 1. one doing devel task, all test tasks waiting, set stage as developing.
     * 2. all devel tasks done, all test tasks waiting, set stage as developed.
     * 3. one test task doing, set stage as testing.
     * 4. all test tasks done, still some devel tasks not done(wait, doing), set stage as testing.
     * 5. all test tasks done, all devel tasks done, set stage as tested.
     */
    foreach($branchStatusList as $branch => $statusList)
    {
        $testTasks  = isset($branchTestTasks[$branch]) ? $branchTestTasks[$branch] : 0;
        $develTasks = isset($branchDevelTasks[$branch]) ? $branchDevelTasks[$branch] : 0;
        if($statusList['devel']['doing'] > 0 and $statusList['test']['wait'] == $testTasks) $stage = 'developing';
        if($statusList['devel']['done'] == $develTasks and $develTasks > 0 and $statusList['test']['wait'] == $testTasks) $stage = 'developed';
        if($statusList['test']['doing'] > 0) $stage = 'testing';
        if(($statusList['devel']['wait'] > 0 or $statusList['devel']['doing'] > 0) and $statusList['test']['done'] == $testTasks and $testTasks > 0) $stage = 'testing';
        if($statusList['devel']['done'] == $develTasks and $develTasks > 0 and $statusList['test']['done'] == $testTasks and $testTasks > 0) $stage = 'tested';

        $stages[$branch] = $stage;
    }

    $releases = $this->dao->select('*')->from(TABLE_RELEASE)->where("CONCAT(',', stories, ',')")->like("%,$storyID,%")->andWhere('deleted')->eq(0)->fetchPairs('branch', 'branch');
    foreach($releases as $branch) $stages[$branch] = 'released';

    if(empty($stages)) return;
    if($hasBranch)
    {
        $stageList   = join(',', array_keys($this->lang->story->stageList));
        $minStagePos = strlen($stageList);
        $minStage    = '';
        foreach($stages as $branch => $stage)
        {
            $this->dao->insert(TABLE_STORYSTAGE)->set('story')->eq($storyID)->set('branch')->eq($branch)->set('stage')->eq($stage)->exec();
            if(strpos($stageList, $stage) !== false and strpos($stageList, $stage) < $minStagePos)
            {
                $minStage    = $stage;
                $minStagePos = strpos($stageList, $stage);
            }
        }
        $this->dao->update(TABLE_STORY)->set('stage')->eq($minStage)->where('id')->eq($storyID)->exec();
    }
    else
    {
        $this->dao->update(TABLE_STORY)->set('stage')->eq(current($stages))->where('id')->eq($storyID)->exec();
    }

    return;
}/**
 * 需求进展统计
 *
 * @param $stories array
 * @return string
 */
public function storyProgressSummary($stories)
{
    $storyCount = count($stories);
    $notReviewStoryCount = 0;
    $notTestStoryCount   = 0;
    $notVerifyStoryCount = 0;
    
    foreach ($stories as $story)
    {
        if (empty($story->reviewStatus) or $story->reviewStatus == 'notReview') $notReviewStoryCount++;
        if (empty($story->testStatus) or $story->testStatus == 'notTest') $notTestStoryCount++;
        if (empty($story->verifyStatus) or strpos('failed,noVerified,portVerified', $story->verifyStatus) !== false) $notVerifyStoryCount++;
    }
    
    $reviewedStoryRate = $storyCount == 0 ? 0 : 1 - round($notReviewStoryCount/$storyCount, 2);
    $TestedStoryRate   = $storyCount == 0 ? 0 : 1 - round($notTestStoryCount/$storyCount, 2);
    $verifiedStoryRate = $storyCount == 0 ? 0 : 1 - round($notVerifyStoryCount/$storyCount, 2);

    return sprintf($this->lang->story->storyProgressSummary, $storyCount, $notReviewStoryCount, $reviewedStoryRate * 100 . "%", $notTestStoryCount, $TestedStoryRate * 100 . "%", $notVerifyStoryCount, $verifiedStoryRate * 100 . "%");
}/**
 * Update a story.
 *
 * @param  int    $storyID
 * @access public
 * @return array the changes of the story.
 */
public function update($storyID)
{
    $now      = helper::now();
    $oldStory = $this->dao->select('*')->from(TABLE_STORY)->where('id')->eq($storyID)->fetch();
    if(!empty($_POST['lastEditedDate']) and $oldStory->lastEditedDate != $this->post->lastEditedDate)
    {
        dao::$errors[] = $this->lang->error->editedByOther;
        return false;
    }

    $story = fixer::input('post')
        ->cleanInt('product,module,pri')
        ->add('assignedDate', $oldStory->assignedDate)
        ->add('lastEditedBy', $this->app->user->account)
        ->add('lastEditedDate', $now)
        ->setDefault('status', $oldStory->status)
        ->setDefault('product', $oldStory->product)
        //->setDefault('plan', $oldStory->plan)
        ->setDefault('branch', $oldStory->branch)
        ->setDefault('specialPlan', $oldStory->specialPlan)
        ->setDefault('testDate', $oldStory->testDate)
        ->setIF($this->post->assignedTo   != $oldStory->assignedTo, 'assignedDate', $now)
        ->setIF($this->post->closedBy     != false and $oldStory->closedDate == '', 'closedDate', $now)
        ->setIF($this->post->closedReason != false and $oldStory->closedDate == '', 'closedDate', $now)
        ->setIF($this->post->closedBy     != false or  $this->post->closedReason != false, 'status', 'closed')
        ->setIF($this->post->closedReason != false and $this->post->closedBy     == false, 'closedBy', $this->app->user->account)
        ->join('reviewedBy', ',')
        ->join('mailto', ',')
        ->join('devTeam', ',')
        //需求可以指派给多人
        ->join('assignedTo', ',')
        ->remove('linkStories,childStories,files,labels,comment')
        ->get();
    if(is_array($story->plan)) $story->plan = trim(join(',', $story->plan), ',');
    if(empty($_POST['product'])) $story->branch = $oldStory->branch;

    $this->dao->update(TABLE_STORY)
        ->data($story)
        ->autoCheck()
        ->checkIF(isset($story->closedBy), 'closedReason', 'notempty')
        ->checkIF(isset($story->closedReason) and $story->closedReason == 'done', 'stage', 'notempty')
        ->checkIF(isset($story->closedReason) and $story->closedReason == 'duplicate',  'duplicateStory', 'notempty')
        ->where('id')->eq((int)$storyID)->exec();

    if(!dao::isError())
    {
        if($story->product != $oldStory->product)
        {
            $this->dao->update(TABLE_PROJECTSTORY)->set('product')->eq($story->product)->where('story')->eq($storyID)->exec();
            $storyProjects  = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('story')->eq($storyID)->orderBy('project')->fetchPairs('project', 'project');
            $linkedProjects = $this->dao->select('project')->from(TABLE_PROJECTPRODUCT)->where('project')->in($storyProjects)->andWhere('product')->eq($story->product)->orderBy('project')->fetchPairs('project','project');
            $unlinkedProjects = array_diff($storyProjects, $linkedProjects);
            foreach($unlinkedProjects as $projectID)
            {
                $data = new stdclass();
                $data->project = $projectID;
                $data->product = $story->product;
                $this->dao->replace(TABLE_PROJECTPRODUCT)->data($data)->exec();
            }
        }
        if(isset($story->closedReason) and $story->closedReason == 'done') $this->loadModel('score')->create('story', 'close');
        return common::createChanges($oldStory, $story);
    }
}
//**//
}