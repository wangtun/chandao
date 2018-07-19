<?php
global $app;
helper::cd($app->getBasePath());
helper::import('module\task\model.php');
helper::cd();
class exttaskModel extends taskModel 
{
/**
 * Activate a task.
 *
 * @param int $taskID
 *
 * @access public
 * @return array
 */
public function activate($taskID)
{
    if(strpos($this->config->task->activate->requiredFields, 'comment') !== false and !$this->post->comment)
    {
        dao::$errors[] = sprintf($this->lang->error->notempty, $this->lang->comment);
        return false;
    }

    $oldTask = $this->getById($taskID);
    $task = fixer::input('post')
        ->setDefault('left', 0)
        ->setDefault('status', 'doing')
        ->setDefault('finishedBy, canceledBy, closedBy, closedReason', '')
        ->setDefault('finishedDate, canceledDate, closedDate', '0000-00-00')
        ->setDefault('lastEditedBy',   $this->app->user->account)
        ->setDefault('lastEditedDate', helper::now())
        ->setDefault('assignedDate', helper::now())
        ->remove('comment')
        ->get();

    if(!empty($oldTask->team))
    {
        $this->dao->update(TABLE_TEAM)->set('left')->eq($this->post->left)
            ->where('root')->eq($taskID)
            ->andWhere('type')->eq('task')
            ->andWhere('account')->eq($this->post->assignedTo)
            ->exec();

        $task = $this->computeHours4Multiple($oldTask, $task);
    }

    $this->dao->update(TABLE_TASK)->data($task)
        ->autoCheck()
        ->batchCheck($this->config->task->activate->requiredFields, 'notempty')
        ->where('id')->eq((int)$taskID)
        ->exec();

    if ($oldTask->type == 'script')
    {
        $script = $this->dao->select('*')->from(TABLE_SCRIPT)->where('task')->eq($taskID)->andWhere('deleted')->eq('0')->fetch();
        $this->dao->update(TABLE_SCRIPT)->set('deleted')->eq(0)->where('id')->eq($script->id)->exec();
    }

    if($oldTask->parent) $this->updateParentStatus($taskID);
    if($oldTask->story)  $this->loadModel('story')->setStage($oldTask->story);
    if(!dao::isError()) return common::createChanges($oldTask, $task);
}/**
 * Create a batch task.
 *
 * @param  int    $projectID
 * @access public
 * @return void
 */
public function batchCreate($projectID)
{
    $this->loadModel('action');
    $now      = helper::now();
    $mails    = array();
    $tasks    = fixer::input('post')->get();
    $batchNum = count(reset($tasks));

    $storyIDs  = array();
    $taskNames = array();
    $preStory  = 0;

    /* Judge whether the current task is a parent. */
    $parentID = !empty($this->post->parent[0]) ? $this->post->parent[0] : 0;

    foreach($tasks->story as $key => $storyID)
    {
        if(empty($tasks->name[$key])) continue;
        if($tasks->type[$key] == 'affair') continue;
        if($tasks->type[$key] == 'ditto' && isset($tasks->type[$key - 1]) && $tasks->type[$key - 1] == 'affair') continue;

        if($storyID == 'ditto') $storyID = $preStory;
        $preStory = $storyID;

        $inNames = in_array($tasks->name[$key], $taskNames);
        if(!$inNames || ($inNames && !in_array($storyID, $storyIDs)))
        {
            $storyIDs[]  = $storyID;
            $taskNames[] = $tasks->name[$key];
        }
        else
        {
            dao::$errors['message'][] = sprintf($this->lang->duplicate, $this->lang->task->common);
            die(js::error(dao::getError()));
        }
    }

    $result = $this->loadModel('common')->removeDuplicate('task', $tasks, "project=$projectID and story " . helper::dbIN($storyIDs));
    $tasks  = $result['data'];

    $story      = 0;
    $module     = 0;
    $type       = '';
    $assignedTo = '';

    /* Get task data. */
    $data = array();
    for($i = 0; $i < $batchNum; $i++)
    {
        $story      = !isset($tasks->story[$i]) || $tasks->story[$i]           == 'ditto' ? $story     : $tasks->story[$i];
        $module     = !isset($tasks->module[$i]) || $tasks->module[$i]         == 'ditto' ? $module    : $tasks->module[$i];
        $type       = !isset($tasks->type[$i]) || $tasks->type[$i]             == 'ditto' ? $type      : $tasks->type[$i];
        $assignedTo = !isset($tasks->assignedTo[$i]) || $tasks->assignedTo[$i] == 'ditto' ? $assignedTo: $tasks->assignedTo[$i];

        if(empty($tasks->name[$i])) continue;

        $data[$i]             = new stdclass();
        $data[$i]->story      = (int)$story;
        $data[$i]->type       = $type;
        $data[$i]->module     = (int)$module;
        $data[$i]->assignedTo = $assignedTo;
        $data[$i]->color      = $tasks->color[$i];
        $data[$i]->name       = $tasks->name[$i];
        $data[$i]->desc       = nl2br($tasks->desc[$i]);
        $data[$i]->pri        = $tasks->pri[$i];
        $data[$i]->estimate   = $tasks->estimate[$i];
        $data[$i]->left       = $tasks->estimate[$i];
        $data[$i]->project    = $projectID;
        $data[$i]->estStarted = empty($tasks->estStarted[$i]) ? '0000-00-00' : $tasks->estStarted[$i];
        $data[$i]->deadline   = empty($tasks->deadline[$i]) ? '0000-00-00' : $tasks->deadline[$i];
        $data[$i]->status     = 'wait';
        $data[$i]->openedBy   = $this->app->user->account;
        $data[$i]->openedDate = $now;
        $data[$i]->parent     = $tasks->parent[$i];
        //禅道任务增加关键字字段；需求：858 批量添加任务，批量编辑任务增加关键字字段;行：69
        $data[$i]->keywords   = $tasks->keywords[$i];

        if($story) $data[$i]->storyVersion = $this->loadModel('story')->getVersion($data[$i]->story);
        if($assignedTo) $data[$i]->assignedDate = $now;
        if(strpos($this->config->task->create->requiredFields, 'estStarted') !== false and empty($tasks->estStarted[$i])) $data[$i]->estStarted = '';
        if(strpos($this->config->task->create->requiredFields, 'deadline') !== false and empty($tasks->deadline[$i]))     $data[$i]->deadline   = '';
    }

    /* check data. */
    foreach($data as $i => $task)
    {
        if($task->estimate and !preg_match("/^[0-9]+(.[0-9]{1,3})?$/", $task->estimate)) die(js::alert($this->lang->task->error->estimateNumber));
        foreach(explode(',', $this->config->task->create->requiredFields) as $field)
        {
            $field = trim($field);
            if($field and empty($task->$field)) die(js::alert(sprintf($this->lang->error->notempty, $this->lang->task->$field)));
        }
        if($task->estimate) $task->estimate = (float)$task->estimate;
    }

    foreach($data as $i => $task)
    {
        $this->dao->insert(TABLE_TASK)->data($task)
            ->autoCheck()
            ->batchCheck($this->config->task->create->requiredFields, 'notempty')
            ->checkIF($task->estimate != '', 'estimate', 'float')
            ->exec();

        if(dao::isError()) die(js::error(dao::getError()));

        $taskID = $this->dao->lastInsertID();
        if($story) $this->story->setStage($tasks->story[$i]);
        $actionID = $this->action->create('task', $taskID, 'Opened', '');
        if(!dao::isError()) $this->loadModel('score')->create('task', 'create', $taskID);

        $mails[$i] = new stdclass();
        $mails[$i]->taskID   = $taskID;
        $mails[$i]->actionID = $actionID;
    }

    if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchCreate');
    if($parentID && !empty($taskID)) $this->updateParentStatus($taskID);
    return $mails;
}/**
 * Batch update task.
 *
 * @access public
 * @return void
 */
public function batchUpdate()
{
    $tasks      = array();
    $allChanges = array();
    $now        = helper::now();
    $today      = date(DT_DATE1);
    $data       = fixer::input('post')->get();
    $taskIDList = $this->post->taskIDList;

    /* Process data if the value is 'ditto'. */
    foreach($taskIDList as $taskID)
    {
        if(isset($data->modules[$taskID]) and ($data->modules[$taskID] == 'ditto')) $data->modules[$taskID] = isset($prev['module']) ? $prev['module'] : 0;
        if($data->types[$taskID]       == 'ditto') $data->types[$taskID]       = isset($prev['type'])       ? $prev['type']       : '';
        if($data->statuses[$taskID]    == 'ditto') $data->statuses[$taskID]    = isset($prev['status'])     ? $prev['status']     : '';
        if($data->assignedTos[$taskID] == 'ditto') $data->assignedTos[$taskID] = isset($prev['assignedTo']) ? $prev['assignedTo'] : '';
        if($data->pris[$taskID]        == 'ditto') $data->pris[$taskID]        = isset($prev['pri'])        ? $prev['pri']        : 0;
        if($data->finishedBys[$taskID] == 'ditto') $data->finishedBys[$taskID] = isset($prev['finishedBy']) ? $prev['finishedBy'] : '';
        if($data->canceledBys[$taskID] == 'ditto') $data->canceledBys[$taskID] = isset($prev['canceledBy']) ? $prev['canceledBy'] : '';
        if($data->closedBys[$taskID]   == 'ditto') $data->closedBys[$taskID]   = isset($prev['closedBy'])   ? $prev['closedBy']   : '';

        $prev['module']     = $data->modules[$taskID];
        $prev['type']       = $data->types[$taskID];
        $prev['status']     = $data->statuses[$taskID];
        $prev['assignedTo'] = $data->assignedTos[$taskID];
        $prev['pri']        = $data->pris[$taskID];
        $prev['finishedBy'] = $data->finishedBys[$taskID];
        $prev['canceledBy'] = $data->canceledBys[$taskID];
        $prev['closedBy']   = $data->closedBys[$taskID];
    }

    /* Initialize tasks from the post data.*/
    $oldTasks = $taskIDList ? $this->getByList($taskIDList) : array();
    foreach($taskIDList as $taskID)
    {
        $oldTask = $oldTasks[$taskID];

        $task = new stdclass();
        $task->color          = $data->colors[$taskID];
        $task->name           = $data->names[$taskID];
        $task->module         = isset($data->modules[$taskID]) ? $data->modules[$taskID] : 0;
        $task->type           = $data->types[$taskID];
        $task->status         = $data->statuses[$taskID];
        $task->assignedTo     = $task->status == 'closed' ? 'closed' : $data->assignedTos[$taskID];
        $task->pri            = $data->pris[$taskID];
        $task->estimate       = isset($data->estimates[$taskID]) ? $data->estimates[$taskID] : $oldTask->estimate;
        $task->left           = isset($data->lefts[$taskID]) ? $data->lefts[$taskID] : $oldTask->left;
        $task->estStarted     = $data->estStarteds[$taskID];
        $task->deadline       = $data->deadlines[$taskID];
        $task->finishedBy     = $data->finishedBys[$taskID];
        $task->canceledBy     = $data->canceledBys[$taskID];
        $task->closedBy       = $data->closedBys[$taskID];
        $task->closedReason   = $data->closedReasons[$taskID];
        $task->assignedDate   = $oldTask->assignedTo ==$task->assignedTo  ? $oldTask->assignedDate : $now;
        $task->finishedDate   = $oldTask->finishedBy == $task->finishedBy ? $oldTask->finishedDate : $now;
        $task->canceledDate   = $oldTask->canceledBy == $task->canceledBy ? $oldTask->canceledDate : $now;
        $task->closedDate     = $oldTask->closedBy == $task->closedBy ? $oldTask->closedDate : $now;
        $task->lastEditedBy   = $this->app->user->account;
        $task->lastEditedDate = $now;
        $task->consumed       = $oldTask->consumed;
        //禅道任务增加关键字字段；需求：858 批量添加任务，批量编辑任务增加关键字字段;行：67-68
        $task->keywords       = $data->keywords[$taskID];
        //需求1340 任务点击完成时，开启时间和完成时间改为必填项。
        $task->realStarted    = $data->realStarted[$taskID];

        if($data->consumeds[$taskID])
        {
            if($data->consumeds[$taskID] < 0)
            {
                echo js::alert(sprintf($this->lang->task->error->consumed, $taskID));
            }
            else
            {
                $record = new stdclass();
                $record->account  = $this->app->user->account;
                $record->task     = $taskID;
                $record->date     = $today;
                $record->left     = $task->left;
                $record->consumed = $data->consumeds[$taskID];
                $this->addTaskEstimate($record);

                $task->consumed = $oldTask->consumed + $record->consumed;
            }
        }

        switch($task->status)
        {
            case 'done':
                //需求1340 任务点击完成时，开启时间和完成时间改为必填项。
                if ($task->type == 'review') die(js::error($this->lang->task->error->reviewError));
                if ($task->realStarted =='0000-00-00') die(js::error($this->lang->task->error->doneError));

                $task->left = 0;
                if(!$task->finishedBy)  $task->finishedBy = $this->app->user->account;
                if($task->closedReason) $task->closedDate = $now;
                $task->finishedDate = $oldTask->status == 'done' ?  $oldTask->finishedDate : $now;

                $task->canceledBy   = '';
                $task->canceledDate = '';
                break;
            case 'cancel':
                $task->assignedTo   = $oldTask->openedBy;
                $task->assignedDate = $now;

                if(!$task->canceledBy)   $task->canceledBy   = $this->app->user->account;
                if(!$task->canceledDate) $task->canceledDate = $now;

                $task->finishedBy   = '';
                $task->finishedDate = '';
                break;
            case 'closed':
                if(!$task->closedBy)   $task->closedBy   = $this->app->user->account;
                if(!$task->closedDate) $task->closedDate = $now;
                break;
            case 'wait':
                if($task->consumed > 0 and $task->left > 0) $task->status = 'doing';
                if($task->left == $oldTask->left and $task->consumed == 0) $task->left = $task->estimate;

                $task->canceledDate = '';
                $task->finishedDate = '';
                $task->closedDate   = '';
                break;
            case 'doing':
                $task->canceledDate = '';
                $task->finishedDate = '';
                $task->closedDate   = '';
                break;
            case 'pause':
                $task->finishedDate = '';
        }
        if($task->assignedTo) $task->assignedDate = $now;

        $this->dao->update(TABLE_TASK)->data($task)
            ->autoCheck()
            ->batchCheckIF($task->status != 'cancel', $this->config->task->edit->requiredFields, 'notempty')

            ->checkIF($task->estimate != false, 'estimate', 'float')
            ->checkIF($task->consumed != false, 'consumed', 'float')
            ->checkIF($task->left     != false, 'left',     'float')
            ->checkIF($task->left     == 0 and $task->status != 'cancel' and $task->status != 'closed' and $task->status != 'wait' and $task->consumed != 0, 'status', 'equal', 'done')

            ->batchCheckIF($task->status == 'wait' or $task->status == 'doing', 'finishedBy, finishedDate,canceledBy, canceledDate, closedBy, closedDate, closedReason', 'empty')

            ->checkIF($task->status == 'done', 'consumed', 'notempty')
            ->checkIF($task->status == 'done' and $task->closedReason, 'closedReason', 'equal', 'done')
            ->batchCheckIF($task->status == 'done', 'canceledBy, canceledDate', 'empty')

            ->checkIF($task->status == 'closed', 'closedReason', 'notempty')
            ->batchCheckIF($task->closedReason == 'cancel', 'finishedBy, finishedDate', 'empty')
            ->where('id')->eq((int)$taskID)
            ->exec();

        if($task->status == 'done' and $task->closedReason) $this->dao->update(TABLE_TASK)->set('status')->eq('closed')->where('id')->eq($taskID)->exec();

        if($oldTask->story != false) $this->loadModel('story')->setStage($oldTask->story);
        if(!dao::isError())
        {
            if($oldTask->parent) $this->updateParentStatus($oldTask->id);
            if($task->status == 'done')   $this->loadModel('score')->create('task', 'finish', $taskID);
            if($task->status == 'closed') $this->loadModel('score')->create('task', 'close', $taskID);
            $allChanges[$taskID] = common::createChanges($oldTask, $task);
        }
        else
        {
            die(js::error('task#' . $taskID . dao::getError(true)));
        }
    }
    if(!dao::isError()) $this->loadModel('score')->create('ajax', 'batchEdit');
    return $allChanges;
}/**
 * Created by PhpStorm.
 * User: 月下亭中人
 * Date: 2017/11/2
 * Time: 9:57
 */
/**
 * Cancel a task.
 *
 * @param int $taskID
 *
 * @access public
 * @return array
 */
public function cancel($taskID)
{
    $oldTask = $this->getById($taskID);
    $now     = helper::now();
    $task = fixer::input('post')
        ->setDefault('status', 'cancel')
        ->setDefault('assignedTo', $oldTask->openedBy)
        ->setDefault('assignedDate', $now)
        ->setDefault('finishedBy', '')
        ->setDefault('finishedDate', '0000-00-00')
        ->setDefault('canceledBy, lastEditedBy', $this->app->user->account)
        ->setDefault('canceledDate, lastEditedDate', $now)
        ->remove('comment')
        ->get();

    $this->dao->update(TABLE_TASK)->data($task)->autoCheck()->where('id')->eq((int)$taskID)->exec();

    if ($oldTask->type == 'script')
    {
        $script = $this->dao->select('*')->from(TABLE_SCRIPT)->where('task')->eq($taskID)->andWhere('deleted')->eq('0')->fetch();
        $this->dao->update(TABLE_SCRIPT)->set('deleted')->eq(1)->where('id')->eq($script->id)->exec();
    }
    
    if($oldTask->parent) $this->updateParentStatus($taskID);
    if($oldTask->story)  $this->loadModel('story')->setStage($oldTask->story);
    if(!dao::isError()) return common::createChanges($oldTask, $task);
}/**
 * Create a task.
 *
 * @param  int    $projectID
 * @access public
 * @return void
 */
public function create($projectID)
{
    $taskIdList  = array();
    $taskFiles = array();
    $taskDetail = new stdClass();

    $this->loadModel('file');
    $task = fixer::input('post')
        ->add('project', (int)$projectID)
        //->setDefault('estimate, left, story', 0)
        ->setDefault('story', 0)
        ->setDefault('status', 'wait')
        ->setIF($this->post->estimate != false, 'left', $this->post->estimate)
        ->setIF($this->post->story != false, 'storyVersion', $this->loadModel('story')->getVersion($this->post->story))
        ->setDefault('estStarted', '0000-00-00')
        ->setDefault('deadline', '0000-00-00')
        ->setIF(strpos($this->config->task->create->requiredFields, 'estStarted') !== false, 'estStarted', $this->post->estStarted)
        ->setIF(strpos($this->config->task->create->requiredFields, 'deadline') !== false, 'deadline', $this->post->deadline)
        ->setDefault('openedBy',   $this->app->user->account)
        ->setDefault('openedDate', helper::now())
        ->setDefault('releasedDate', '0000-00-00')
        ->stripTags($this->config->task->editor->create['id'], $this->config->allowedTags)
        ->join('mailto', ',')
        ->remove('after,files,labels,assignedTo,uid,storyEstimate,storyDesc,storyPri,team,teamEstimate,teamMember,multiple,teams')
        ->get();

    foreach($this->post->assignedTo as $assignedTo)
    {
        /* When type is affair and has assigned then ignore none. */
        if($task->type == 'affair' and count($this->post->assignedTo) > 1 and empty($assignedTo)) continue;

        $task->assignedTo = $assignedTo;
        if($assignedTo) $task->assignedDate = helper::now();

        /* Check duplicate task. */
        if($task->type != 'affair')
        {
            $result = $this->loadModel('common')->removeDuplicate('task', $task, "project=$projectID and story=$task->story");
            if($result['stop'])
            {
                $taskIdList[$assignedTo] = array('status' => 'exists', 'id' => $result['duplicate']);
                continue;
            }
        }

        $task = $this->file->processImgURL($task, $this->config->task->editor->create['id'], $this->post->uid);

        //任务数据新增
        $taskDetail->source = $task->source;
        $taskDetail->module = $task->module;
        $taskDetail->type = $task->type;
        $taskDetail->story = $task->story;
        $taskDetail->color = $task->color;
        $taskDetail->name = $task->name;
        $taskDetail->pri = $task->pri;
        $taskDetail->estimate = $task->estimate;
        $taskDetail->desc = $task->desc;
        $taskDetail->estStarted = $task->estStarted;
        $taskDetail->deadline = $task->deadline;
        $taskDetail->mailto = $task->mailto;
        $taskDetail->project = $task->project;
        $taskDetail->left = $task->left;
        $taskDetail->status = $task->status;
        if ($this->post->story != false)
        {
            $taskDetail->storyVersion = $task->storyVersion;
        }
        $taskDetail->openedBy = $task->openedBy;
        $taskDetail->openedDate = $task->openedDate;
        $taskDetail->assignedTo = $task->assignedTo;
        $taskDetail->assignedDate = $task->assignedDate;
        //禅道任务增加关键字字段；需求：858；行号：72-73
        $taskDetail->keywords    = $task->keywords;

        //源代码
        $this->dao->insert(TABLE_TASK)->data($taskDetail)
            ->autoCheck()
            ->batchCheck($this->config->task->create->newRequiredFields, 'notempty')
            ->checkIF($taskDetail->estimate != '', 'estimate', 'float')
            ->checkIF($taskDetail->deadline != '0000-00-00', 'deadline', 'ge', $task->estStarted)
            ->exec();

        if(!dao::isError())
        {
            $taskID = $this->dao->lastInsertID();
            if($this->post->story) $this->loadModel('story')->setStage($this->post->story);
            $this->file->updateObjectID($this->post->uid, $taskID, 'task');
            if(!empty($taskFiles))
            {
                foreach($taskFiles as $taskFile)
                {
                    $taskFile->objectID = $taskID;
                    $this->dao->insert(TABLE_FILE)->data($taskFile)->exec();
                }
            }
            else
            {
                $taskFileTitle = $this->file->saveUpload('task', $taskID);
                $taskFiles = $this->dao->select('*')->from(TABLE_FILE)->where('id')->in(array_keys($taskFileTitle))->fetchAll('id');
                foreach($taskFiles as $fileID => $taskFile) unset($taskFiles[$fileID]->id);
            }

            $teams = array();
            if($this->post->multiple)
            {
                foreach($this->post->team as $row => $account)
                {
                    if(empty($account) or isset($team[$account])) continue;
                    $member = new stdClass();
                    $member->root     = 0;
                    $member->account  = $account;
                    $member->role     = $assignedTo;
                    $member->join     = helper::today();
                    $member->estimate = $this->post->teamEstimate[$row] ? (float)$this->post->teamEstimate[$row] : 0;
                    $member->left     = $member->estimate;
                    $member->order    = $row;
                    $teams[$account]  = $member;
                }
            }

            if(!empty($teams))
            {
                foreach($teams as $team)
                {
                    $team->root = $taskID;
                    $team->type = 'task';
                    $this->dao->insert(TABLE_TEAM)->data($team)->autoCheck()->exec();
                }

                $task->id = $taskID;
                $this->computeHours4Multiple($task);
            }

            if(!dao::isError()) $this->loadModel('score')->create('task', 'create', $taskID);
            $taskIdList[$assignedTo] = array('status' => 'created', 'id' => $taskID);
        }
        else
        {
            return false;
        }

        if ($task->type == 'script')
        {
            $scriptDetail = new stdClass();
            $scriptDetail->task = $taskID;
            $scriptDetail->scriptName = $task->scriptName;
            $scriptDetail->scriptType = $task->scriptType;
            $scriptDetail->lob = $task->lob;
            $scriptDetail->frequency = $task->frequency;
            $scriptDetail->configurationFile = $task->configurationFile;
            $scriptDetail->output = $task->output;
            $scriptDetail->precondition = $task->precondition;
            $scriptDetail->performBody = $task->performBody;
            $scriptDetail->performMode = $task->performMode;
            $scriptDetail->performSystem = $task->performSystem;
            $scriptDetail->scriptPath = $task->scriptPath;
            $scriptDetail->notice = $task->notice;

            $this->dao->insert(TABLE_SCRIPT)->data($scriptDetail)
                ->autoCheck()
                ->exec();

            if (!dao::isError()) {
                $scriptDetail->id = $this->dao->lastInsertID();
            } else {
                return false;
            }
        }

        if ($task->source == 'QA') {
            $num = count($task->auditID);

            for ($i = 0; $i < $num; $i++)
            {
                $auditDetail["$i"] = new stdClass();
                $auditDetail["$i"]->task = $taskID;
                $auditDetail["$i"]->auditID = $task->auditID["$i"];
                $auditDetail["$i"]->noDec = $task->noDec["$i"];
                $auditDetail["$i"]->noType = $task->noType["$i"];
                $auditDetail["$i"]->serious = $task->serious["$i"];
                $auditDetail["$i"]->cause = $task->cause["$i"];
                $auditDetail["$i"]->measures = $task->measures["$i"];

                $this->dao->insert(TABLE_QAAUDIT)->data($auditDetail["$i"])
                    ->autoCheck()
                    ->batchCheck($this->config->task->create->newRequiredFields, 'notempty')
                    ->exec();

                if (!dao::isError()) {
                    $auditDetail["$i"]->id = $this->dao->lastInsertID();
                } else {
                    return false;
                }
            }
        }
    }
    return $taskIdList;
}/**
 * Update a task.
 *
 * @param  int    $taskID
 * @access public
 * @return void
 */
public function update($taskID)
{
    //新增
    $reviewDetail = array();
    $auditDetail = array();
    $taskDetail = new stdClass();
    $emptyReviewDetail = new stdclass();
    $reviewDetailChanges = array();
    $changes = array();

    $emptyReviewDetail->reviewID = '';
    $emptyReviewDetail->number = '';
    $emptyReviewDetail->reviewer = '';
    $emptyReviewDetail->item = '';
    $emptyReviewDetail->line = '';
    $emptyReviewDetail->severity = '';
    $emptyReviewDetail->description = '';
    $emptyReviewDetail->proposal = '';
    $emptyReviewDetail->changed = '';
    $emptyReviewDetail->action = '';
    $emptyReviewDetail->chkd = '';

    $oldTask = $this->dao->select('*')->from(TABLE_TASK)->where('id')->eq((int)$taskID)->fetch();
    if(!empty($_POST['lastEditedDate']) and $oldTask->lastEditedDate != $this->post->lastEditedDate)
    {
        dao::$errors[] = $this->lang->error->editedByOther;
        return false;
    }

    $now  = helper::now();
    $task = fixer::input('post')
        ->setDefault('story, estimate, left, consumed', 0)
        ->setDefault('estStarted', '0000-00-00')
        ->setDefault('deadline', '0000-00-00')
        ->setIF(strpos($this->config->task->edit->requiredFields, 'estStarted') !== false, 'estStarted', $this->post->estStarted)
        ->setIF(strpos($this->config->task->edit->requiredFields, 'deadline') !== false, 'deadline', $this->post->deadline)
        ->setIF($this->post->story != false and $this->post->story != $oldTask->story, 'storyVersion', $this->loadModel('story')->getVersion($this->post->story))

        ->setIF($this->post->status == 'done', 'left', 0)
        ->setIF($this->post->status == 'done'   and !$this->post->finishedBy,   'finishedBy',   $this->app->user->account)
        ->setIF($this->post->status == 'done'   and !$this->post->finishedDate, 'finishedDate', $now)

        ->setIF($this->post->status == 'cancel' and !$this->post->canceledBy,   'canceledBy',   $this->app->user->account)
        ->setIF($this->post->status == 'cancel' and !$this->post->canceledDate, 'canceledDate', $now)
        ->setIF($this->post->status == 'cancel', 'assignedTo',   $oldTask->openedBy)
        ->setIF($this->post->status == 'cancel', 'assignedDate', $now)

        ->setIF($this->post->status == 'closed' and !$this->post->closedBy,     'closedBy',     $this->app->user->account)
        ->setIF($this->post->status == 'closed' and !$this->post->closedDate,   'closedDate',   $now)
        ->setIF($this->post->consumed > 0 and $this->post->left > 0 and $this->post->status == 'wait', 'status', 'doing')

        ->setIF($this->post->assignedTo != $oldTask->assignedTo, 'assignedDate', $now)

        ->setIF($this->post->status == 'wait' and $this->post->left == $oldTask->left and $this->post->consumed == 0, 'left', $this->post->estimate)

        ->add('lastEditedBy',   $this->app->user->account)
        ->add('lastEditedDate', $now)
        ->stripTags($this->config->task->editor->edit['id'], $this->config->allowedTags)
        ->join('mailto', ',')
        ->remove('comment,files,labels,uid,multiple,team,teamEstimate,teamConsumed,teamLeft')
        ->get();

    if($task->consumed < $oldTask->consumed) die(js::error($this->lang->task->error->consumedSmall));

    /* Fix bug#1388, Check children task projectID and moduleID. */
    if($task->project != $oldTask->project)
    {
        $this->dao->update(TABLE_TASK)->set('project')->eq($task->project)->set('module')->eq($task->module)->where('parent')->eq($taskID)->exec();
    }

    $task = $this->loadModel('file')->processImgURL($task, $this->config->task->editor->edit['id'], $this->post->uid);

    $teams = array();
    if($this->post->multiple)
    {
        foreach($this->post->team as $row => $account)
        {
            if(empty($account) or isset($team[$account])) continue;

            $member = new stdClass();
            $member->account  = $account;
            $member->role     = $task->assignedTo;
            $member->join     = helper::today();
            $member->root     = $taskID;
            $member->type     = 'task';
            $member->estimate = $this->post->teamEstimate[$row] ? $this->post->teamEstimate[$row] : 0;
            $member->consumed = $this->post->teamConsumed[$row] ? $this->post->teamConsumed[$row] : 0;
            $member->left     = $this->post->teamLeft[$row] ? $this->post->teamLeft[$row] : ($member->estimate - $member->consumed);
            $member->order    = $row;
            $teams[$account]  = $member;
            if($task->status == 'done') $member->left = 0;
        }
    }

    /* Save team. */
    $this->dao->delete()->from(TABLE_TEAM)->where('root')->eq($taskID)->andWhere('type')->eq('task')->exec();
    if(!empty($teams))
    {
        foreach($teams as $member) $this->dao->insert(TABLE_TEAM)->data($member)->autoCheck()->exec();
        $task = $this->computeHours4Multiple($oldTask, $task);
    }

    //需求1340 任务点击完成时，开启时间和完成时间改为必填项。
    if($task->status == 'done' || $task->status == 'doing')
    {
        if ($task->realStarted =='0000-00-00')
        {
            die(js::error($this->lang->task->error->doneError));
        }
    }

    //任务数据新增
    $taskDetail->module = $task->module;
    $taskDetail->type = $task->type;
    $taskDetail->story = $task->story;
    $taskDetail->color = $task->color;
    $taskDetail->name = $task->name;
    $taskDetail->pri = $task->pri;
    $taskDetail->estimate = $task->estimate;
    $taskDetail->desc = $task->desc;
    $taskDetail->estStarted = $task->estStarted;
    $taskDetail->deadline = $task->deadline;
    $taskDetail->mailto = $task->mailto;
    $taskDetail->project = $task->project;
    $taskDetail->left = $task->left;
    if (isset($task->status))
    {
        $taskDetail->status = $task->status;
    }
    //禅道任务增加关键字字段；需求：858；行号：100-101
    $taskDetail->keywords    = $task->keywords;

    if ($this->post->story != false and $this->post->story != $oldTask->story)
    {
        $taskDetail->storyVersion = $task->storyVersion;
    }
    $taskDetail->assignedTo = $task->assignedTo;
    if (!empty($task->assignedDate))
    {
        $taskDetail->assignedDate = $task->assignedDate;
    }
    $taskDetail->consumed = $task->consumed;
    $taskDetail->realStarted = $task->realStarted;
    $taskDetail->finishedBy = $task->finishedBy;
    $taskDetail->finishedDate = $task->finishedDate;
    $taskDetail->canceledBy = $task->canceledBy;
    $taskDetail->canceledDate = $task->canceledDate;
    $taskDetail->closedBy = $task->closedBy;
    $taskDetail->closedReason = $task->closedReason;
    $taskDetail->closedDate = $task->closedDate;
    $taskDetail->lastEditedBy = $task->lastEditedBy;
    $taskDetail->lastEditedDate = $task->lastEditedDate;
    //1 task
    $this->dao->begin();
    $this->dao->update(TABLE_TASK)->data($taskDetail)
        ->autoCheck()
        ->batchCheckIF($taskDetail->status != 'cancel', $this->config->task->edit->requiredFields, 'notempty')
        ->checkIF($taskDetail->deadline != '0000-00-00', 'deadline', 'ge', $task->estStarted)

        ->checkIF($taskDetail->estimate != false, 'estimate', 'float')
        ->checkIF($taskDetail->left     != false, 'left',     'float')
        ->checkIF($taskDetail->consumed != false, 'consumed', 'float')
        ->checkIF($taskDetail->status != 'wait' and $taskDetail->left == 0 and $taskDetail->status != 'cancel' and $taskDetail->status != 'closed', 'status', 'equal', 'done')

        ->batchCheckIF($taskDetail->status == 'wait' or $taskDetail->status == 'doing', 'finishedBy, finishedDate,canceledBy, canceledDate, closedBy, closedDate, closedReason', 'empty')

        ->checkIF($taskDetail->status == 'done', 'consumed', 'notempty')
        ->checkIF($taskDetail->status == 'done' and $taskDetail->closedReason, 'closedReason', 'equal', 'done')
        ->batchCheckIF($taskDetail->status == 'done', 'canceledBy, canceledDate', 'empty')

        ->checkIF($taskDetail->status == 'closed', 'closedReason', 'notempty')
        ->batchCheckIF($taskDetail->closedReason == 'cancel', 'finishedBy, finishedDate', 'empty')
        ->where('id')->eq((int)$taskID)->exec();

    if(dao::isError())
    {
        $this->dao->rollback();
        return false;
    }else
    {
        $changes = common::createChanges($oldTask, $taskDetail);
    }
    //QA审计;
    if ($oldTask->source == 'QA')
    {
        $emptyAuditDetail = new stdclass();
        $emptyAuditDetail->auditID = '';
        $emptyAuditDetail->noDec = '';
        $emptyAuditDetail->noType = '';
        $emptyAuditDetail->serious = '';
        $emptyAuditDetail->cause = '';
        $emptyAuditDetail->measures = '';
        //不符合项
        $num = count($task->auditID);
        for ($i = 0; $i < $num; $i++)
        {
            $auditDetail["$i"] = new stdClass();
            //$auditDetail["$i"]->aid       = $task->aid["$i"];
            $auditDetail["$i"]->auditID  = $task->auditID["$i"];
            $auditDetail["$i"]->noDec    = $task->noDec["$i"];
            $auditDetail["$i"]->noType   = $task->noType["$i"];
            $auditDetail["$i"]->serious  = $task->serious["$i"];
            $auditDetail["$i"]->cause     = $task->cause["$i"];
            $auditDetail["$i"]->measures   = $task->measures["$i"];
            //过滤空记录
            if ($auditDetail["$i"]->noDec == '')
            {
                continue;
            }
            $oldAuditDetail = $this->dao->select('*')
                ->from(TABLE_QAAUDIT)
                ->where('id')->eq($task->aid["$i"])
                ->andWhere('deleted')->eq('0')
                ->fetch();
            //新增不合格项详情
            if ($task->aid["$i"] == '')
            {
                if(empty($auditDetail["$i"]->noType)) die(js::error($this->lang->task->error->emptyNoType));
                if(empty($auditDetail["$i"]->serious)) die(js::error($this->lang->task->error->emptySerious));
                //unset($auditDetail["$i"]->id);
                $auditDetail["$i"]->task   = $taskID;
                $this->dao->insert(TABLE_QAAUDIT)->data($auditDetail["$i"])
                    ->autoCheck()
                    ->batchCheck($this->config->task->edit->requiredFields, 'notempty')
                    ->exec();
                if (!dao::isError()) {
                    unset($auditDetail["$i"]->task);
                    $auditDetailChanges["$i"] = common::createChanges($emptyAuditDetail, $auditDetail["$i"]);
                } else {
                    $this->dao->rollback();
                    return false;
                }
            }
            //编辑不合格项详情
            else
            {
                $this->dao->update(TABLE_QAAUDIT)->data($auditDetail["$i"])
                    ->autoCheck()
                    ->batchCheck($this->config->task->edit->requiredFields, 'notempty')
                    ->where('id')->eq($task->aid["$i"])->exec();
                if (!dao::isError())
                {
                    $auditDetailChanges["$i"] = common::createChanges($oldAuditDetail, $auditDetail["$i"]);
                }
                else
                {
                    $this->dao->rollback();
                    return false;
                }
            }
        }
        //成功操作
        foreach($auditDetailChanges as $auditDetailChange)
        {
            $changes = array_merge($changes,$auditDetailChange);
        }
    }

    if ($task->type == 'review' && $task->status == 'done')
    {
        $review = new stdClass();
        $review->fileNO = $task->fileNO;
        $review->recorder = $task->recorder;
        $review->reviewName = $task->reviewName;
        $review->task = $taskID;
        $review->doc = $task->doc;
        $review->referenceDoc = $task->referenceDoc;
        $review->reference = $task->reference;
        $review->pages = $task->pages;
        $review->reviewers = $task->reviewers;
        $review->reviewDate = $task->reviewDate;
        $review->reviewScope = $task->reviewScope;
        $review->reviewPlace = $task->reviewPlace;
        $review->effort = $task->effort;
        $review->conclusion = $task->conclusion;

        //编辑评审
        $oldReview = $this->dao->select('*')
            ->from(TABLE_REVIEW)
            ->where('id')
            ->eq("$task->reviewID")
            ->fetch();
        //review
        if (!$oldReview)
        {
            //附件为空跳出
            if($_FILES['files']['size']['0'] == '0') die(js::error($this->lang->task->error->fileNotEmpty));

            $this->dao->insert(TABLE_REVIEW)->data($review)
                ->autoCheck()
                ->batchCheck($this->config->task->finish->requiredFields, 'notempty')
                ->exec();
            if (!dao::isError()) {
                $task->reviewID = $this->dao->lastInsertID();
            } else {
                $this->dao->rollback();
                return false;
            }
        }
        else
        {
            $review->id = $task->reviewID;
            $this->dao->update(TABLE_REVIEW)->data($review)
                ->autoCheck()
                ->batchCheckIF($task->status != 'cancel', $this->config->task->edit->requiredFields, 'notempty')
                ->where('id')->eq($review->id)->limit(1)->exec();
            if(dao::isError())
            {
                $this->dao->rollback();
                return false;
            }
        }

        //reviewDetail
        $num = count($task->number);
        for ($i = 0; $i < $num; $i++)
        {
            $reviewDetail["$i"] = new stdClass();
            $reviewDetail["$i"]->id = $task->id["$i"];
            $reviewDetail["$i"]->reviewID = $task->reviewID;
            $reviewDetail["$i"]->number = $task->number["$i"];
            $reviewDetail["$i"]->reviewer = $task->reviewer["$i"];
            $reviewDetail["$i"]->item = $task->item["$i"];
            $reviewDetail["$i"]->line = $task->line["$i"];
            $reviewDetail["$i"]->severity = $task->severity["$i"];
            $reviewDetail["$i"]->description = $task->description["$i"];
            $reviewDetail["$i"]->proposal = $task->proposal["$i"];
            $reviewDetail["$i"]->changed = $task->changed["$i"];
            $reviewDetail["$i"]->action = $task->action["$i"];
            $reviewDetail["$i"]->chkd = $task->chkd["$i"];
            //过滤空记录
            if ($reviewDetail["$i"]->description == '')
            {
                continue;
            }
            $oldreViewDetail = $this->dao->select('*')
                ->from(TABLE_REVIEWDETAIL)
                ->where('id')->eq($reviewDetail["$i"]->id)
                ->andWhere('deleted')->eq('0')
                ->fetch();
            //新增评审详情
            if (!$oldreViewDetail)
            {
                unset($reviewDetail["$i"]->id);

                $this->dao->insert(TABLE_REVIEWDETAIL)->data($reviewDetail["$i"])
                    ->autoCheck()
                    ->batchCheck($this->config->task->create->requiredFields, 'notempty')
                    ->exec();
                if (!dao::isError()) {
                    $emptyReviewDetail->reviewID = $task->reviewID;
                    $reviewDetailChanges["$i"] = common::createChanges($emptyReviewDetail, $reviewDetail["$i"]);
                } else {
                    $this->dao->rollback();
                    return false;
                }
            }
            //编辑任务详情
            else
            {
                $this->dao->update(TABLE_REVIEWDETAIL)->data($reviewDetail["$i"])
                    ->autoCheck()
                    ->batchCheckIF($task->status != 'cancel', $this->config->task->edit->requiredFields, 'notempty')
                    ->where('id')->eq($reviewDetail["$i"]->id)->exec();
                if (!dao::isError())
                {
                    $reviewDetailChanges["$i"] = common::createChanges($oldreViewDetail, $reviewDetail["$i"]);
                }
                else
                {
                    $this->dao->rollback();
                    return false;
                }
            }
        }
        //成功操作
        $reviewChange = common::createChanges($oldReview, $review);
        $changes = array_merge($changes,$reviewChange);

        foreach($reviewDetailChanges as $reviewDetailChange)
        {
            $changes = array_merge($changes,$reviewDetailChange);
        }
    }

    if ($task->type == 'script')
    {
        $oldScriptDetail = $this->dao->select('*')
            ->from(TABLE_SCRIPT)
            ->where('task')->eq("$taskID")
            ->andWhere('deleted')->eq('0')
            ->fetch();
        
        $scriptDetail = new stdClass();
        $scriptDetail->task = $taskID;
        $scriptDetail->scriptName = $task->scriptName;
        $scriptDetail->scriptType = $task->scriptType;
        $scriptDetail->lob = $task->lob;
        $scriptDetail->frequency = $task->frequency;
        $scriptDetail->configurationFile = $task->configurationFile;
        $scriptDetail->output = $task->output;
        $scriptDetail->precondition = $task->precondition;
        $scriptDetail->performBody = $task->performBody;
        $scriptDetail->performMode = $task->performMode;
        $scriptDetail->performSystem = $task->performSystem;
        $scriptDetail->scriptPath = $task->scriptPath;
        $scriptDetail->notice = $task->notice;

        if ($oldScriptDetail)
        {
            $this->dao->update(TABLE_SCRIPT)->data($scriptDetail)
                ->autoCheck()
                ->batchCheck($this->config->task->finshScript->requiredFields, 'notempty')
                ->where('id')->eq($task->scriptID)->limit(1)->exec();

            if(dao::isError())
            {
                $this->dao->rollback();
                return false;
            }
            else
            {
                $scriptChange = common::createChanges($oldScriptDetail, $scriptDetail);
                $changes = array_merge($changes, $scriptChange);
            }
        }
        else
        {
            $this->dao->insert(TABLE_SCRIPT)->data($scriptDetail)
                ->autoCheck()
                ->exec();

            if(dao::isError())
            {
                $this->dao->rollback();
                return false;
            }
        }
    }

    $this->dao->commit();
    if($oldTask->parent) $this->updateParentStatus($taskID);
    
    if($this->post->story != false) $this->loadModel('story')->setStage($this->post->story);
    
    if($task->status == 'done')   $this->loadModel('score')->create('task', 'finish', $taskID);
    if($task->status == 'closed') $this->loadModel('score')->create('task', 'close', $taskID);
    $this->file->updateObjectID($this->post->uid, $taskID, 'task');
    return $changes;
}public function setListValue($productID)
{
    return $this->loadExtension('excel')->setListValue($productID);
}

public function createFromImport($productID)
{
    return $this->loadExtension('excel')->createFromImport($productID);
}/**
 * Finish a task.
 *
 * @param  int      $taskID
 * @access public
 * @return void
 */
public function finish($taskID)
{
    $emptyReviewDetail  = new stdClass();
    $reviewDetail       = array();
    $emptyReview        = new stdClass();
    $review             = new stdClass();
    $taskDetail         = new stdClass();
    $estimate           = new stdClass();

    $emptyReviewDetail->reviewID = '';
    $emptyReviewDetail->number = '';
    $emptyReviewDetail->reviewer = '';
    $emptyReviewDetail->item = '';
    $emptyReviewDetail->line = '';
    $emptyReviewDetail->severity = '';
    $emptyReviewDetail->description = '';
    $emptyReviewDetail->proposal = '';
    $emptyReviewDetail->changed = '';
    $emptyReviewDetail->action = '';
    $emptyReviewDetail->chkd = '';

    $oldTask = $this->getById($taskID);
    $now  = helper::now();

    if(strpos($this->config->task->finish->requiredFields, 'comment') !== false and !$this->post->comment)
    {
        dao::$errors[] = sprintf($this->lang->error->notempty, $this->lang->comment);
        return false;
    }

    $task = fixer::input('post')
        ->setDefault('left', 0)
        ->setDefault('assignedTo',   $oldTask->openedBy)
        ->setDefault('assignedDate', $now)
        ->setDefault('status', 'done')
        ->setDefault('finishedBy, lastEditedBy', $this->app->user->account)
        ->setDefault('finishedDate, lastEditedDate', $now)
        ->removeIF(!empty($oldTask->team), 'finishedBy,finishedDate,status')
        ->remove('comment,files,labels')
        ->get();
    //1340 任务点击完成时，开启时间和完成时间改为必填项。
    if ($task->realStarted =='0000-00-00')
    {
        die(js::error($this->lang->task->error->doneError));
    }

    if(!is_numeric($task->consumed)) die(js::error($this->lang->task->error->consumedNumber));

    /* Record consumed and left. */
    if(empty($oldTask->team))
    {
        $consumed = $task->consumed - $oldTask->consumed;
        if($consumed < 0) die(js::error($this->lang->task->error->consumedSmall));
    }
    else
    {
        $consumed = $oldTask->team[$this->app->user->account]->consumed;
        if($task->consumed < $consumed) die(js::error($this->lang->task->error->consumedSmall));
    }

    $estimate->uid     = $this->post->uid;
    $estimate->account = $this->app->user->account;
    $estimate->task    = $taskID;
    $estimate->date    = date(DT_DATE1);
    $estimate->left    = 0;

    $estimate->consumed = $consumed;
    if($estimate->consumed) $this->addTaskEstimate($estimate);

    if(!empty($oldTask->team))
    {
        $this->dao->update(TABLE_TEAM)
            ->set('left')->eq(0)
            ->set('consumed')->eq($task->consumed)
            ->where('root')->eq((int)$taskID)
            ->andWhere('type')->eq('task')
            ->andWhere('account')->eq($this->app->user->account)->exec();

        $task = $this->computeHours4Multiple($oldTask, $task);
    }

    if($task->finishedDate == substr($now, 0, 10)) $task->finishedDate = $now;

    $taskInfo = $this->dao->select('*')->from(TABLE_TASK)->where('id')->eq("$taskID")->fetch();

    $taskDetail->consumed = $task->consumed;
    $taskDetail->assignedTo = $task->assignedTo;
    $taskDetail->finishedDate = $task->finishedDate;
    $taskDetail->left = $task->left;
    $taskDetail->assignedDate = $task->assignedDate;
    $taskDetail->status = $task->status;
    $taskDetail->finishedBy = $task->finishedBy;
    $taskDetail->lastEditedBy = $task->lastEditedBy;
    $taskDetail->lastEditedDate = $task->lastEditedDate;

    if (isset($task->tid))
    {
        foreach ($task->tid as $scriptTaskID)
        {
            $scriptTask = new stdClass();
            $scriptTask->tester = $task->tester[$scriptTaskID];
            $scriptTask->AT = $task->AT[$scriptTaskID];
            $this->dao->update(TABLE_SCRIPT)->data($scriptTask)->autoCheck()->where('task')->eq((int)$scriptTaskID)->exec();
        }
    }

    //1.task
    $this->dao->begin();
    $this->dao->update(TABLE_TASK)->data($taskDetail)
        ->autoCheck()
        ->batchCheck($this->config->task->finish->requiredFields, 'notempty')
        ->where('id')->eq((int)$taskID)
        ->exec();

    if(dao::isError())
    {
        $this->dao->rollback();
        return false;
    }

    $changes = common::createChanges($oldTask, $taskDetail);

    if ($taskInfo->type == 'script')
    {
        if($task->scriptName == '') die(js::error($this->lang->task->error->scriptNameEmpty));
        if($task->scriptType == '') die(js::error($this->lang->task->error->scriptTypeEmpty));
        if($task->lob == '') die(js::error($this->lang->task->error->lobEmpty));
        if($task->frequency == '') die(js::error($this->lang->task->error->frequencyEmpty));
        if($task->configurationFile == '') die(js::error($this->lang->task->error->configurationFileEmpty));
        if($task->output == '') die(js::error($this->lang->task->error->outputEmpty));
        if($task->precondition == '') die(js::error($this->lang->task->error->preconditionDocEmpty));
        if($task->performBody == '') die(js::error($this->lang->task->error->performBodyEmpty));
        if($task->performMode == '') die(js::error($this->lang->task->error->performModeEmpty));
        if($task->performSystem == '') die(js::error($this->lang->task->error->performSystemEmpty));
        if($task->scriptPath == '') die(js::error($this->lang->task->error->scriptPathEmpty));
        
        $oldScriptDetail = $this->dao->select('*')
            ->from(TABLE_SCRIPT)
            ->where('task')->eq("$taskID")
            ->andWhere('deleted')->eq('0')
            ->fetch();

        $scriptDetail = new stdClass();
        $scriptDetail->task = $taskID;
        $scriptDetail->scriptName = $task->scriptName;
        $scriptDetail->scriptType = $task->scriptType;
        $scriptDetail->lob = $task->lob;
        $scriptDetail->frequency = $task->frequency;
        $scriptDetail->configurationFile = $task->configurationFile;
        $scriptDetail->output = $task->output;
        $scriptDetail->precondition = $task->precondition;
        $scriptDetail->performBody = $task->performBody;
        $scriptDetail->performMode = $task->performMode;
        $scriptDetail->performSystem = $task->performSystem;
        $scriptDetail->scriptPath = $task->scriptPath;
        $scriptDetail->notice = $task->notice;

        if ($oldScriptDetail)
        {
            $this->dao->update(TABLE_SCRIPT)->data($scriptDetail)
                ->autoCheck()
                ->where('task')->eq($taskID)->limit(1)->exec();

            if(dao::isError())
            {
                $this->dao->rollback();
                return false;
            }
            else
            {
                $scriptChange = common::createChanges($oldScriptDetail, $scriptDetail);
                $changes = array_merge($changes, $scriptChange);
            }
        }
        else
        {
            $this->dao->insert(TABLE_SCRIPT)->data($scriptDetail)
                ->autoCheck()
                ->exec();

            if(dao::isError())
            {
                $this->dao->rollback();
                return false;
            }
        }
    }

    if ($taskInfo->type == 'review')
    {
        $review->fileNO = $task->fileNO;
        $review->recorder = $task->recorder;
        $review->reviewName = $task->reviewName;
        $review->task = $taskID;
        $review->doc = $task->doc;
        $review->referenceDoc = $task->referenceDoc;
        $review->reference = $task->reference;
        $review->pages = $task->pages;
        $review->reviewers = $task->reviewers;
        $review->reviewDate = $task->reviewDate;
        $review->reviewScope = $task->reviewScope;
        $review->reviewPlace = $task->reviewPlace;
        $review->effort = $task->effort;
        $review->conclusion = $task->conclusion;

        //附件为空跳出
        if($_FILES['files']['size']['0'] == '0') die(js::error($this->lang->task->error->fileNotEmpty));

        if($task->fileNO == '') die(js::error($this->lang->task->error->fileNOEmpty));
        if($task->recorder == '') die(js::error($this->lang->task->error->recorderEmpty));
        if($task->reviewName == '') die(js::error($this->lang->task->error->reviewNameEmpty));
        if($task->reviewDate == '') die(js::error($this->lang->task->error->reviewDateEmpty));
        if($task->doc == '') die(js::error($this->lang->task->error->docEmpty));
        if($task->reviewScope == '') die(js::error($this->lang->task->error->reviewScopeEmpty));
        if($task->referenceDoc == '') die(js::error($this->lang->task->error->referenceDocEmpty));
        if($task->reviewPlace == '') die(js::error($this->lang->task->error->reviewPlaceEmpty));
        if($task->reference == '') die(js::error($this->lang->task->error->referenceEmpty));
        if($task->effort == '') die(js::error($this->lang->task->error->effortEmpty));
        if($task->pages == '') die(js::error($this->lang->task->error->pagesEmpty));
        if($task->conclusion == '') die(js::error($this->lang->task->error->conclusionEmpty));
        if($task->reviewers == '') die(js::error($this->lang->task->error->reviewersEmpty));

        if(!is_numeric($task->effort)) die(js::error($this->lang->task->error->effortNumber));
        if(!is_numeric($task->pages)) die(js::error($this->lang->task->error->pagesNumber));

        //2.insert review
        $this->dao->insert(TABLE_REVIEW)->data($review)
            ->autoCheck()
            ->batchCheck($this->config->task->finish->requiredFields, 'notempty')
            ->exec();
        if (!dao::isError()) {
            $reviewID = $this->dao->lastInsertID();
        } else {
            $this->dao->rollback();
            return false;
        }

        $emptyReviewDetail->reviewID = $reviewID;

        $num = count($task->number);
        for ($i = 0; $i < $num; $i++) {
            $reviewDetail["$i"]->reviewID = $reviewID;
            $reviewDetail["$i"]->number = $task->number["$i"];
            $reviewDetail["$i"]->reviewer = $task->reviewer["$i"];
            $reviewDetail["$i"]->item = $task->item["$i"];
            $reviewDetail["$i"]->line = $task->line["$i"];
            $reviewDetail["$i"]->severity = $task->severity["$i"];
            $reviewDetail["$i"]->description = $task->description["$i"];
            $reviewDetail["$i"]->proposal = $task->proposal["$i"];
            $reviewDetail["$i"]->changed = $task->changed["$i"];
            $reviewDetail["$i"]->action = $task->action["$i"];
            $reviewDetail["$i"]->chkd = $task->chkd["$i"];
            //过滤空记录
            if ($reviewDetail["$i"]->description == '')
            {
                continue;
            }
            $this->dao->insert(TABLE_REVIEWDETAIL)->data($reviewDetail["$i"])
                ->autoCheck()
                ->batchCheck($this->config->task->create->requiredFields, 'notempty')
                ->exec();
            if (!dao::isError()) {
                $reviewDetailChanges["$i"] = common::createChanges($emptyReviewDetail, $reviewDetail["$i"]);
            } else {
                $this->dao->rollback();
                return false;
            }
        }
        //获得改变值
        //建一个空对象
        $emptyReview->fileNO = '';
        $emptyReview->recorder = '';
        $emptyReview->reviewName = '';
        $emptyReview->task = $taskID;
        $emptyReview->doc = '';
        $emptyReview->referenceDoc = '';
        $emptyReview->reference = '';
        $emptyReview->pages = '';
        $emptyReview->reviewers = '';
        $emptyReview->reviewDate = '';
        $emptyReview->reviewScope = '';
        $emptyReview->reviewPlace = '';
        $emptyReview->effort = '';
        $emptyReview->conclusion = '';
        $reviewChange = common::createChanges($emptyReview, $review);
        $changes = array_merge($changes,$reviewChange);

        foreach($reviewDetailChanges as $reviewDetailChange)
        {
            $changes = array_merge($changes,$reviewDetailChange);
        }
    }

    //成功操作
    $this->dao->commit();
    //设置需求状态
    if($oldTask->parent) $this->updateParentStatus($taskID);

    if($oldTask->story) $this->loadModel('story')->setStage($oldTask->story);
    if($task->status == 'done' && !dao::isError()) $this->loadModel('score')->create('task', 'finish', $taskID);
    return $changes;
}/**
 * Get tasks of a project.
 *
 * @param int    $projectID
 * @param int    $productID
 * @param string $type
 * @param string $modules
 * @param string $orderBy
 * @param null   $pager
 *
 * @access public
 * @return array|void
 */
public function getProjectTasks($projectID, $productID = 0, $type = 'all', $modules = 0, $orderBy = 'status_asc, id_desc', $pager = null)
{
    if(is_string($type)) $type = strtolower($type);
    //2911 优化需求提测计划、发版计划等内容
    $tasks = $this->dao->select('t1.*, t2.id AS storyID, t2.title AS storyTitle, t2.product, t2.branch, t2.version AS latestStoryVersion, t2.status AS storyStatus, t2.testDate, t3.realname AS assignedToRealName')
        ->from(TABLE_TASK)->alias('t1')
        ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
        ->leftJoin(TABLE_USER)->alias('t3')->on('t1.assignedTo = t3.account')
        ->leftJoin(TABLE_TEAM)->alias('t4')->on('t4.root = t1.id')
        ->leftJoin(TABLE_MODULE)->alias('t5')->on('t1.module = t5.id')
        ->where('t1.project')->eq((int)$projectID)
        ->beginIF($type =='all' || is_array($type))->andWhere('t1.parent')->eq(0)->fi()
        ->beginIF($type == 'myinvolved')
        ->andWhere("((t4.`account` = '{$this->app->user->account}' AND t4.`type` = 'task') OR t1.`assignedTo` = '{$this->app->user->account}' OR t1.`finishedby` = '{$this->app->user->account}')")
        ->fi()
        ->beginIF($productID)->andWhere("((t5.root=" . (int)$productID . " and t5.type='story') OR t2.product=" . (int)$productID . ")")->fi()
        ->beginIF($type == 'undone')->andWhere("(t1.status = 'wait' or t1.status ='doing')")->fi()
        ->beginIF($type == 'needconfirm')->andWhere('t2.version > t1.storyVersion')->andWhere("t2.status = 'active'")->fi()
        ->beginIF($type == 'assignedtome')->andWhere('t1.assignedTo')->eq($this->app->user->account)->fi()
        ->beginIF($type == 'finishedbyme')->andWhere('t1.finishedby')->eq($this->app->user->account)->fi()
        ->beginIF($type == 'delayed')->andWhere('t1.deadline')->gt('1970-1-1')->andWhere('t1.deadline')->lt(date(DT_DATE1))->andWhere('t1.status')->in('wait,doing')->fi()
        ->beginIF(is_array($type) or strpos(',all,undone,needconfirm,assignedtome,delayed,finishedbyme,myinvolved,', ",$type,") === false)->andWhere('t1.status')->in($type)->fi()
        ->beginIF($modules)->andWhere('t1.module')->in($modules)->fi()
        ->andWhere('t1.deleted')->eq(0)
        ->orderBy('t1.`parent`,' . $orderBy)
        ->page($pager, 't1.id')
        ->fetchAll('id');

    $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'task', ($productID or in_array($type, array('myinvolved', 'needconfirm'))) ? false : true);

    if(empty($tasks)) return array();

    $taskList = array_keys($tasks);
    $taskTeam = $this->dao->select('*')->from(TABLE_TEAM)->where('root')->in($taskList)->andWhere('type')->eq('task')->fetchGroup('root');
    if(!empty($taskTeam))
    {
        foreach($taskTeam as $taskID => $team) $tasks[$taskID]->team = $team;
    }

    /* Select children task. */
    //2911 优化需求提测计划、发版计划等内容
    $children = $this->dao->select('t1.*, t2.id AS storyID, t2.title AS storyTitle, t2.product, t2.branch, t2.version AS latestStoryVersion, t2.status AS storyStatus, t2.testDate, t3.realname AS assignedToRealName')
        ->from(TABLE_TASK)->alias('t1')
        ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
        ->leftJoin(TABLE_USER)->alias('t3')->on('t1.assignedTo = t3.account')
        ->leftJoin(TABLE_MODULE)->alias('t4')->on('t1.module = t4.id')
        ->where('t1.project')->eq((int)$projectID)
        ->andWhere('t1.parent')->in($taskList)
        ->andWhere('t1.deleted')->eq(0)
        ->beginIF($productID)->andWhere("((t4.root=" . (int)$productID . " and t4.type='story') OR t2.product=" . (int)$productID . ")")->fi()
        ->beginIF($type == 'undone')->andWhere("(t1.status = 'wait' or t1.status ='doing')")->fi()
        ->beginIF($type == 'needconfirm')->andWhere('t2.version > t1.storyVersion')->andWhere("t2.status = 'active'")->fi()
        ->beginIF($type == 'assignedtome')->andWhere('t1.assignedTo')->eq($this->app->user->account)->fi()
        ->beginIF($type == 'finishedbyme')->andWhere('t1.finishedby')->eq($this->app->user->account)->fi()
        ->beginIF($type == 'delayed')->andWhere('t1.deadline')->gt('1970-1-1')->andWhere('t1.deadline')->lt(date(DT_DATE1))->andWhere('t1.status')->in('wait,doing')->fi()
        ->beginIF(is_array($type) or strpos(',all,undone,needconfirm,assignedtome,delayed,finishedbyme,myinvolved,', ",$type,") === false)->andWhere('t1.status')->in($type)->fi()
        ->beginIF($modules)->andWhere('t1.module')->in($modules)->fi()
        ->orderBy('t1.`id`,' . $orderBy)
        ->fetchAll('id');

    if(!empty($children))
    {
        foreach($children as $child)
        {
            $tasks[$child->parent]->children[] = $child;
        }
    }

    return $this->processTasks($tasks);
}/**
 * Get tasks list of a project.
 *
 * @param  int           $projectID
 * @param  array|string  $moduleIdList
 * @param  string        $status
 * @param  string        $orderBy
 * @param  object        $pager
 * @access public
 * @return array
 */
public function getTasksByModule($projectID = 0, $moduleIdList = 0, $orderBy = 'id_desc', $pager = null)
{
    //2911 优化需求提测计划、发版计划等内容
    $tasks = $this->dao->select('t1.*, t2.id AS storyID, t2.title AS storyTitle, t2.product, t2.branch, t2.version AS latestStoryVersion, t2.status AS storyStatus, t2.testDate, t3.realname AS assignedToRealName')
        ->from(TABLE_TASK)->alias('t1')
        ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
        ->leftJoin(TABLE_USER)->alias('t3')->on('t1.assignedTo = t3.account')
        ->leftJoin(TABLE_PRODUCTPLAN)->alias('t4')->on('t2.plan = t4.id')
        ->where('t1.project')->eq((int)$projectID)
        ->beginIF($moduleIdList)->andWhere('t1.module')->in($moduleIdList)->fi()
        ->andWhere('t1.deleted')->eq(0)
        ->orderBy($orderBy)
        ->page($pager)
        ->fetchAll();

    $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'task');

    if($tasks) return $this->processTasks($tasks);
    return array();
}/**
 * Judge an action is clickable or not.
 *
 * @param  object    $task
 * @param  string    $action
 * @access public
 * @return bool
 */
public static function isClickable($task, $action)
{
    $action = strtolower($action);

    if($action == 'start'          and !empty($task->children)) return false;
    if($action == 'recordestimate' and !empty($task->children)) return false;
    if($action == 'finish'         and !empty($task->children)) return false;
    if($action == 'cancel'         and !empty($task->children)) return false;
    if($action == 'pause'          and !empty($task->children)) return false;
    if($action == 'activate'       and !empty($task->children)) return false;
    if($action == 'assignto'       and !empty($task->children)) return false;
    if($action == 'close'          and !empty($task->children)) return false;
    if($action == 'batchcreate'    and !empty($task->team))     return false;
    if($action == 'batchcreate'    and $task->parent)           return false;

    if($action == 'start')    return $task->status == 'wait';
    if($action == 'restart')  return $task->status == 'pause';
    if($action == 'pause')    return $task->status == 'doing';
    if($action == 'assignto') return $task->status != 'closed' and $task->status != 'cancel';
    if($action == 'close')    return $task->status == 'done'   or  $task->status == 'cancel';
    if($action == 'activate') return $task->status == 'done'   or  $task->status == 'closed'  or $task->status  == 'cancel';
    if($action == 'finish')   return $task->status != 'done'   and $task->status != 'closed'  and $task->status != 'cancel';
    if($action == 'cancel')   return $task->status != 'done'   and $task->status != 'closed'  and $task->status != 'cancel';
    //未开始任务的详情页增加暂停按钮
    if($action == 'pause')    return $task->status == 'doing' or  $task->status == 'wait';

    return true;
}/**
 * Print cell data.
 *
 * @param object $col
 * @param object $task
 * @param array  $users
 * @param string $browseType
 * @param array  $branchGroups
 * @param array  $modulePairs
 * @param string $mode
 * @param bool   $child
 *
 * @access public
 * @return void
 */
public function printCell($col, $task, $users, $browseType, $branchGroups, $modulePairs = array(), $mode = 'datatable', $child = false)
{
    $canView  = common::hasPriv('task', 'view');
    $taskLink = helper::createLink('task', 'view', "taskID=$task->id");
    $account  = $this->app->user->account;
    $id       = $col->id;
    if($col->show)
    {
        $class = '';
        if($id == 'status') $class .= ' task-' . $task->status;
        if($id == 'id')     $class .= ' cell-id';
        if($id == 'name')   $class .= ' text-left';
        if($id == 'deadline' and isset($task->delay)) $class .= ' delayed';
        if($id == 'assignedTo' && $task->assignedTo == $account) $class .= ' red';

        $title = '';
        if($id == 'name')
        {
            $title = " title='{$task->name}'";
            if(!empty($task->children)) $class .= ' has-child';
        }
        if($id == 'story') $title = " title='{$task->storyTitle}'";

        echo "<td class='" . $class . "'" . $title . ">";
        switch($id)
        {
            case 'id':
                if($mode == 'table') echo "<input type='checkbox' name='taskIDList[{$task->id}]' value='{$task->id}'/> ";
                echo $canView ? html::a($taskLink, sprintf('%03d', $task->id)) : sprintf('%03d', $task->id);
                break;
            case 'pri':
                echo "<span class='pri" . zget($this->lang->task->priList, $task->pri) . "'>";
                echo $task->pri == '0' ? '' : zget($this->lang->task->priList, $task->pri);
                echo "</span>";
                break;
            case 'name':
                if(!empty($task->product) && isset($branchGroups[$task->product][$task->branch])) echo "<span class='label label-info label-badge'>" . $branchGroups[$task->product][$task->branch] . '</span> ';
                if(empty($task->children) and $task->module and isset($modulePairs[$task->module])) echo "<span class='label label-info label-badge'>" . $modulePairs[$task->module] . '</span> ';
                if($child or !empty($task->parent)) echo '<span class="label">' . $this->lang->task->childrenAB . '</span> ';
                if(!empty($task->team)) echo '<span class="label">' . $this->lang->task->multipleAB . '</span> ';
                echo $canView ? html::a($taskLink, $task->name, null, "style='color: $task->color'") : "<span style='color: $task->color'>$task->name</span>";
                if($task->fromBug) echo html::a(helper::createLink('bug', 'view', "id=$task->fromBug"), "[BUG#$task->fromBug]", '_blank', "class='bug'");
                if(!empty($task->children)) echo '<span class="task-toggle" data-id="' . $task->id . '">&nbsp;&nbsp;<i class="icon icon-double-angle-up"></i>&nbsp;&nbsp;</span>';
                break;
            case 'type':
                echo $this->lang->task->typeList[$task->type];
                break;
            //在任务列表页面增加关键字字段
            case 'keywords':
                echo $task->keywords;
                break;
            //在任务列表页面增加任务关联需求的所属字段
            case 'testDate':
                echo $task->testDate;
                break;

            case 'status':
                $storyChanged = (!empty($task->storyStatus) and $task->storyStatus == 'active' and $task->latestStoryVersion > $task->storyVersion);
                $storyChanged ? print("<span class='warning'>{$this->lang->story->changed}</span> ") : print($this->lang->task->statusList[$task->status]);
                break;
            case 'estimate':
                echo round($task->estimate, 1);
                break;
            case 'consumed':
                echo round($task->consumed, 1);
                break;
            case 'left':
                echo round($task->left, 1);
                break;
            case 'progress':
                echo "{$task->progress}%";
                break;
            case 'deadline':
                if(substr($task->deadline, 0, 4) > 0) echo substr($task->deadline, 5, 6);
                break;
            case 'openedBy':
                echo zget($users, $task->openedBy);
                break;
            case 'openedDate':
                echo substr($task->openedDate, 5, 11);
                break;
            case 'estStarted':
                echo $task->estStarted;
                break;
            case 'realStarted':
                echo $task->realStarted;
                break;
            case 'assignedTo':
                echo zget($users, $task->assignedTo);
                break;
            case 'assignedDate':
                echo substr($task->assignedDate, 5, 11);
                break;
            case 'finishedBy':
                echo zget($users, $task->finishedBy);
                break;
            case 'finishedDate':
                echo substr($task->finishedDate, 5, 11);
                break;
            case 'canceledBy':
                echo zget($users, $task->canceledBy);
                break;
            case 'canceledDate':
                echo substr($task->canceledDate, 5, 11);
                break;
            case 'closedBy':
                echo zget($users, $task->closedBy);
                break;
            case 'closedDate':
                echo substr($task->closedDate, 5, 11);
                break;
            case 'closedReason':
                echo $this->lang->task->reasonList[$task->closedReason];
                break;
            case 'story':
                if(!empty($task->storyID))
                {
                    if(common::hasPriv('story', 'view'))
                    {
                        echo html::a(helper::createLink('story', 'view', "storyid=$task->storyID", 'html', true), "<i class='icon icon-{$this->lang->icons['story']}'></i>", '', "class='iframe' title='{$task->storyTitle}'");
                    }
                    else
                    {
                        echo "<i class='icon icon-{$this->lang->icons['story']}' title='{$task->storyTitle}'></i>";
                    }
                }
                break;
            case 'mailto':
                $mailto = explode(',', $task->mailto);
                foreach($mailto as $account)
                {
                    $account = trim($account);
                    if(empty($account)) continue;
                    echo zget($users, $account) . ' &nbsp;';
                }
                break;
            case 'lastEditedBy':
                echo zget($users, $task->lastEditedBy);
                break;
            case 'lastEditedDate':
                echo substr($task->lastEditedDate, 5, 11);
                break;
            case 'actions':
                common::printIcon('task', 'assignTo', "projectID=$task->project&taskID=$task->id", $task, 'list', '', '', 'iframe', true);
                common::printIcon('task', 'start',    "taskID=$task->id", $task, 'list', '', '', 'iframe', true);

                common::printIcon('task', 'recordEstimate', "taskID=$task->id", $task, 'list', 'time', '', 'iframe', true);
                if($browseType == 'needconfirm')
                {
                    $this->lang->task->confirmStoryChange = $this->lang->confirm;
                    common::printIcon('task', 'confirmStoryChange', "taskid=$task->id", '', 'list', '', 'hiddenwin');
                }
                common::printIcon('task', 'finish', "taskID=$task->id", $task, 'list', '', '', 'iframe', true);
                common::printIcon('task', 'close',  "taskID=$task->id", $task, 'list', '', '', 'iframe', true);
                common::printIcon('task', 'edit',   "taskID=$task->id", $task, 'list');
                if(empty($task->team) or empty($task->children))
                {
                    common::printIcon('task', 'batchCreate', "project=$task->project&storyID=$task->story&moduleID=$task->module&taskID=$task->id&ifame=0", $task, 'list', 'plus', '', '', '', '', $this->lang->task->children);
                }
                break;
        }
        echo '</td>';
    }
}/**
 * Process a task, judge it's status.
 *
 * @param  object    $task
 * @access private
 * @return object
 */
public function processTask($task)
{
    $today = helper::today();

    /* Delayed or not?. */
    if($task->status !== 'done' and $task->status !== 'cancel' and $task->status != 'closed')
    {
        if($task->deadline != '0000-00-00')
        {
            $delay = helper::diffDate($today, $task->deadline);
            if($delay > 0) $task->delay = $delay;
        }
    }

    /* Story changed or not. */
    $task->needConfirm = false;
    /*去掉需求只有在激活状态下才能显示变更按钮的限制*/
    if(!empty($task->storyStatus) and $task->latestStoryVersion > $task->storyVersion) $task->needConfirm = true;

    /* Set product type for task. */
    if(isset($task->product))
    {
        $product = $this->loadModel('product')->getById($task->product);
        $task->productType = $product->type;
    }

    /* Set closed realname. */
    if($task->assignedTo == 'closed') $task->assignedToRealName = 'Closed';

    /* Compute task progress. */
    if($task->consumed == 0 and $task->left == 0)
    {
        $task->progress = 0;
    }
    elseif($task->consumed != 0 and $task->left == 0)
    {
        $task->progress = 100;
    }
    else
    {
        $task->progress = round($task->consumed / ($task->consumed + $task->left), 2) * 100;
    }

    return $task;
}
//**//
}