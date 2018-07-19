<?php
global $app;
helper::cd($app->getBasePath());
helper::import('module\bug\model.php');
helper::cd();
class extbugModel extends bugModel 
{
/**
 * Activate a bug.
 *
 * @param  int    $bugID
 * @access public
 * @return void
 */
public function activate($bugID)
{
    $oldBug = $this->getById($bugID);
    $now = helper::now();
    $bug = fixer::input('post')
        ->setDefault('assignedTo', $oldBug->resolvedBy)
        ->add('assignedDate', $now)
        ->add('resolution', '')
        ->add('status', 'active')
        ->add('resolvedDate', '0000-00-00')
        ->add('resolvedBy', '')
        ->add('resolvedBuild', '')
        ->add('closedBy', '')
        ->add('closedDate', '0000-00-00')
        ->add('duplicateBug', 0)
        ->add('lastEditedBy',   $this->app->user->account)
        ->add('lastEditedDate', $now)
        ->join('openedBuild', ',')
        ->remove('comment,files,labels')
        ->add('withhold', '')
        ->get();

    $this->dao->update(TABLE_BUG)->data($bug)->autoCheck()->where('id')->eq((int)$bugID)->exec();
    $this->dao->update(TABLE_BUG)->set('activatedCount = activatedCount + 1')->where('id')->eq((int)$bugID)->exec();
    if (dao::isError())
    {
        $this->loadModel('github');
        $this->github->resolve($oldBug, $bug, 'bug');
    }
}/**
 * Build search form.
 *
 * @param  int    $productID
 * @param  array  $products
 * @param  int    $queryID
 * @param  string $actionURL
 * @access public
 * @return void
 */
public function buildSearchForm($productID, $products, $queryID, $actionURL)
{
    $this->config->bug->search['actionURL'] = $actionURL;
    $this->config->bug->search['queryID']   = $queryID;
    //优化搜索功能搜索条件增加空选项
    $this->config->bug->search['params']['product']['values']       = array('' => '', $productID => $products[$productID], 'all' => $this->lang->bug->allProduct);
    
    $this->config->bug->search['params']['plan']['values']          = $this->loadModel('productplan')->getPairs($productID);
    //优化搜索功能；增加空选项
    $this->config->bug->search['params']['module']['values']        = array(0 => '') + $this->loadModel('tree')->getOptionMenu($productID, $viewType = 'bug', $startModuleID = 0);

    $this->config->bug->search['params']['project']['values']       = $this->product->getProjectPairs($productID);
    $this->config->bug->search['params']['severity']['values']      = array(0 => '') + $this->lang->bug->severityList; //Fix bug #939.
    $this->config->bug->search['params']['openedBuild']['values']   = $this->loadModel('build')->getProductBuildPairs($productID, 0, $params = '');
    $this->config->bug->search['params']['resolvedBuild']['values'] = $this->config->bug->search['params']['openedBuild']['values'];
    if($this->session->currentProductType == 'normal')
    {
        unset($this->config->bug->search['fields']['branch']);
        unset($this->config->bug->search['params']['branch']);
    }
    else
    {
        $this->config->bug->search['fields']['branch'] = $this->lang->product->branch;
        $this->config->bug->search['params']['branch']['values']  = array('' => '') + $this->loadModel('branch')->getPairs($productID, 'noempty') + array('all' => $this->lang->branch->all);
    }

    $this->loadModel('search')->setSearchParams($this->config->bug->search);
}/**
 * Close a bug.
 *
 * @param  int    $bugID
 * @access public
 * @return void
 */
public function close($bugID)
{
    $now = helper::now();
    $bug = fixer::input('post')
        ->add('assignedTo',     'closed')
        ->add('assignedDate',   $now)
        ->add('status',         'closed')
        ->add('closedBy',       $this->app->user->account)
        ->add('closedDate',     $now)
        ->add('lastEditedBy',   $this->app->user->account)
        ->add('lastEditedDate', $now)
        ->add('confirmed',      1)
        ->add('withhold',       '')
        ->remove('comment')
        ->get();

    $this->dao->update(TABLE_BUG)->data($bug)->autoCheck()->where('id')->eq((int)$bugID)->exec();
}public function setListValue($productID, $branch = 0)
{
    return $this->loadExtension('excel')->setListValue($productID, $branch);
}

public function createFromImport($productID, $branch = 0)
{
    return $this->loadExtension('excel')->createFromImport($productID, $branch);
}/**
 * Get report data of deadline bugs per day.
 *
 * @access public
 * @return array
 */
public function getDataOfbugsDeadline()
{
    return $this->dao->select('deadline AS name, COUNT(*) AS value')->from(TABLE_BUG)
        ->where($this->reportCondition())->groupBy('name')
        ->having('name != 0000-00-00')
        ->orderBy('deadline')->fetchAll();
}/**
 * Get info of a bug.
 *
 * @param  int    $bugID
 * @param  bool   $setImgSize
 * @access public
 * @return object
 */
public function getById($bugID, $setImgSize = false)
{
    $bug = $this->dao->select('t1.*, t2.name AS projectName, t3.title AS storyTitle, t3.status AS storyStatus, t3.version AS latestStoryVersion, t4.name AS taskName, t5.title AS planName')
        ->from(TABLE_BUG)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->leftJoin(TABLE_STORY)->alias('t3')->on('t1.story = t3.id')
        ->leftJoin(TABLE_TASK)->alias('t4')->on('t1.task = t4.id')
        ->leftJoin(TABLE_PRODUCTPLAN)->alias('t5')->on('t1.plan = t5.id')
        ->where('t1.id')->eq((int)$bugID)->fetch();
    if(!$bug) return false;

    $bug = $this->loadModel('file')->replaceImgURL($bug, 'steps');
    if($setImgSize) $bug->steps = $this->file->setImgSize($bug->steps);
    foreach($bug as $key => $value) if(strpos($key, 'Date') !== false and !(int)substr($value, 0, 4)) $bug->$key = '';

    if($bug->duplicateBug) $bug->duplicateBugTitle = $this->dao->findById($bug->duplicateBug)->from(TABLE_BUG)->fields('title')->fetch('title');
    //bug需要关联多个用例
    if($bug->case)         $bug->caseTitles        = $this->dao->select('id,title')->from(TABLE_CASE)->where('id')->in($bug->case)->fetchPairs();

    if($bug->linkBug)      $bug->linkBugTitles     = $this->dao->select('id,title')->from(TABLE_BUG)->where('id')->in($bug->linkBug)->fetchPairs();
    if($bug->toStory > 0)  $bug->toStoryTitle      = $this->dao->findById($bug->toStory)->from(TABLE_STORY)->fields('title')->fetch('title');
    if($bug->toTask > 0)   $bug->toTaskTitle       = $this->dao->findById($bug->toTask)->from(TABLE_TASK)->fields('name')->fetch('name');

    $bug->toCases = array();
    $toCases      = $this->dao->select('id, title')->from(TABLE_CASE)->where('`fromBug`')->eq($bugID)->fetchAll();
    foreach($toCases as $toCase) $bug->toCases[$toCase->id] = $toCase->title;

    $bug->files = $this->loadModel('file')->getByObject('bug', $bugID);

    return $bug;
}/**
 * Get bugs by search.
 *
 * @param  int    $productID
 * @param  int    $queryID
 * @param  string $orderBy
 * @param  object $pager
 * @param  int    $branch
 * @param  string $status  // 增加参数$status
 * @access public
 * @return array
 */
public function getBySearch($productID, $queryID, $orderBy, $pager = null, $branch = 0, $status = '')
{
    if($queryID)
    {
        $query = $this->loadModel('search')->getQuery($queryID);
        if($query)
        {
            $this->session->set('bugQuery', $query->sql);
            $this->session->set('bugForm', $query->form);
        }
        else
        {
            $this->session->set('bugQuery', ' 1 = 1');
        }
    }
    else
    {
        if($this->session->bugQuery == false) $this->session->set('bugQuery', ' 1 = 1');
    }

    $allProduct = "`product` = 'all'";
    $bugQuery   = $this->session->bugQuery;

    if (empty($status))
    {
        if(strpos($bugQuery, '`product` =') === false) $bugQuery .= ' AND `product` = ' . $productID;
    }

    if(strpos($bugQuery, $allProduct) !== false)
    {
        $products = array_keys($this->loadModel('product')->getPrivProducts());
        $bugQuery = str_replace($allProduct, '1', $bugQuery);
        $bugQuery = $bugQuery . ' AND `product` ' . helper::dbIN($products);
    }
    $allBranch = "`branch` = 'all'";
    if($branch and strpos($bugQuery, '`branch` =') === false) $bugQuery .= " AND `branch` in('0','$branch')";
    if(strpos($bugQuery, $allBranch) !== false) $bugQuery = str_replace($allBranch, '1', $bugQuery);

    $bugs = $this->dao->select('*')->from(TABLE_BUG)->where($bugQuery)
        ->andWhere('deleted')->eq(0)
        //->beginIF($status != '')->andWhere('status')->in($status)->fi() //取消bug状态限制
        ->orderBy($orderBy)->page($pager)->fetchAll();
    return $bugs;
}/**
 * @param $productID string
 * @param $status string
 * @param $toIssue bool
 * @return array
 */
public function getProductBugsPairs($productID, $status='', $toIssue=false)
{
    $bugs = array('' => '');
    $data = $this->dao->select('id, title')->from(TABLE_BUG)
        ->where('deleted')->eq(0)
        ->beginIF($productID != 0)->andWhere('product')->in($productID)->fi()
        ->beginIF(!empty($status))->andWhere('status')->in($status)->fi()
        ->beginIF($toIssue==true)->andWhere('toIssue')->eq(0)->fi()
        ->orderBy('id desc')
        ->fetchAll();
    foreach($data as $bug)
    {
        $bugs[$bug->id] = $bug->id . ':' . $bug->title;
    }
    return $bugs;
}/**
 * Adjust the action is clickable.
 *
 * @param  string $object
 * @param  string $action
 * @access public
 * @return void
 */
public static function isClickable($object, $action)
{
    $action = strtolower($action);
    if($action == 'confirmbug') return $object->status == 'active' and $object->confirmed == 0;
    if($action == 'resolve')    return $object->status == 'active';
    if($action == 'close')      return $object->status == 'resolved';
    if($action == 'activate')   return $object->status != 'active';
    if($action == 'tostory')    return $object->status == 'active';
    if($action == 'toissue')    return $object->toIssue == 0;
    if($action == 'withhold')   return $object->status == 'resolved' and $object->withhold != 1;

    return true;
}/**
 * Link related bugs.
 *
 * @param  int    $bugID
 * @access public
 * @return void
 */
public function linkBugs($bugID)
{
    if($this->post->bugs == false) return;

    $bug       = $this->getById($bugID);
    $bugs2Link = $this->post->bugs;

    $bugs = implode(',', $bugs2Link) . ',' . trim($bug->linkBug, ',');
    $this->dao->update(TABLE_BUG)->set('linkBug')->eq(trim($bugs, ','))->where('id')->eq($bugID)->exec();
    if(dao::isError()) die(js::error(dao::getError()));
    $this->loadModel('action')->create('bug', $bugID, 'linkRelatedBug', '', implode(',', $bugs2Link));

    //Bug双向关联
    foreach ($bugs2Link as $val)
    {
        $linkbugs = $this->dao->select('linkBug')->FROM(TABLE_BUG)->where('id')->eq(trim($val, ','))->fetch();
        $linkBugsAB = $linkbugs->linkBug .','. $bugID;

        $this->dao->update(TABLE_BUG)->set('linkBug')->eq(trim($linkBugsAB, ','))->where('id')->eq(trim($val, ','))->exec();
        if(dao::isError()) die(js::error(dao::getError()));

        $this->loadModel('action')->create('bug', $val, 'linkRelatedBug', '', $bugID);
    }
}/**
 * Print cell data.
 *
 * @param  object $col
 * @param  object $bug
 * @param  array  $users
 * @param  array  $builds
 * @param  array  $branches
 * @param  array  $modulePairs
 * @param  array  $projects
 * @param  array  $plans
 * @param  array  $stories
 * @param  array  $tasks
 * @param  string $mode
 * @access public
 * @return void
 */
public function printCell($col, $bug, $users, $builds, $branches, $modulePairs, $projects = array(), $plans = array(), $stories = array(), $tasks = array(), $mode = 'datatable')
{
    $canView = common::hasPriv('bug', 'view');
    $bugLink = inlink('view', "bugID=$bug->id");
    $account = $this->app->user->account;
    $id = $col->id;
    if($col->show)
    {
        $class = '';
        if($id == 'status') $class .= ' bug-' . $bug->status;
        if($id == 'title')  $class .= ' text-left';
        if($id == 'id')     $class .= ' cell-id';
        if($id == 'assignedTo' && $bug->assignedTo == $account) $class .= ' red';
        if($id == 'deadline' && isset($bug->delay)) $class .= ' delayed';

        echo "<td class='" . $class . "'" . ($id=='title' ? " title='{$bug->title}'" : '') . ">";
        switch($id)
        {
            case 'id':
                if($mode == 'table') echo "<input type='checkbox' name='bugIDList[{$bug->id}]'  value='{$bug->id}'/> ";
                echo $canView ? html::a($bugLink, sprintf('%03d', $bug->id)) : sprintf('%03d', $bug->id);
                break;
            case 'severity':
                echo "<span class='severity" . zget($this->lang->bug->severityList, $bug->severity, $bug->severity) . "'>";
                echo zget($this->lang->bug->severityList, $bug->severity, $bug->severity);
                echo "</span>";
                break;
            case 'pri':
                echo "<span class='pri" . zget($this->lang->bug->priList, $bug->pri, $bug->pri) . "'>";
                echo zget($this->lang->bug->priList, $bug->pri, $bug->pri);
                echo "</span>";
                break;
            case 'title':
                $class = 'confirm' . $bug->confirmed;
                echo "<span class='$class'>[{$this->lang->bug->confirmedList[$bug->confirmed]}]</span> ";
                if($bug->branch and isset($branches[$bug->branch]))    echo "<span class='label label-info label-badge'>{$branches[$bug->branch]}</span> ";
                if($bug->module and isset($modulePairs[$bug->module])) echo "<span class='label label-info label-badge'>{$modulePairs[$bug->module]}</span> ";
                echo $canView ? html::a($bugLink, $bug->title, null, "style='color: $bug->color'") : "<span style='color: $bug->color'>{$bug->title}</span>";
                break;
            //2061 提bug页面增加一个字段“发现阶段”，该字段需要支持后续搜索
            case 'discoveryPhase':
                echo $this->lang->bug->discoveryPhaseList[$bug->discoveryPhase];
                break;
            case 'withhold':
                echo $this->lang->bug->withholdList[$bug->withhold];
                break;
            case 'workSeason':
                echo $this->lang->bug->workSeasonList[$bug->workSeason];
                break;
            case 'branch':
                echo zget($branches, $bug->branch, '');
                break;
            case 'project':
                echo zget($projects, $bug->project, '');
                break;
            case 'plan':
                echo zget($plans, $bug->plan, '');
                break;
            case 'story':
                if(isset($stories[$bug->story]))
                {
                    $story = $stories[$bug->story];
                    echo common::hasPriv('story', 'view') ? html::a(helper::createLink('story', 'view', "storyID=$story->id", 'html', true), $story->title, '', "class='iframe'") : $story->title;
                }
                break;
            case 'task':
                if(isset($tasks[$bug->task]))
                {
                    $task = $tasks[$bug->task];
                    echo common::hasPriv('task', 'view') ? html::a(helper::createLink('task', 'view', "taskID=$task->id", 'html', true), $task->name, '', "class='iframe'") : $task->name;
                }
                break;
            case 'type':
                echo zget($this->lang->bug->typeList, $bug->type);
                break;
            case 'status':
                echo zget($this->lang->bug->statusList, $bug->status);
                break;
            case 'activatedCount':
                echo $bug->activatedCount;
                break;
            case 'activatedDate':
                echo substr($bug->activatedDate, 5, 11);
                break;
            case 'keywords':
                echo $bug->keywords;
                break;
            case 'os':
                echo zget($this->lang->bug->osList, $bug->os);
                break;
            case 'browser':
                echo zget($this->lang->bug->browserList, $bug->browser);
                break;
            case 'mailto':
                $mailto = explode(',', $bug->mailto);
                foreach($mailto as $account)
                {
                    $account = trim($account);
                    if(empty($account)) continue;
                    echo zget($users, $account) . " &nbsp;";
                }
                break;
            case 'found':
                echo zget($users, $bug->found);
                break;
            case 'openedBy':
                echo zget($users, $bug->openedBy);
                break;
            case 'openedDate':
                echo substr($bug->openedDate, 5, 11);
                break;
            case 'openedBuild':
                $builds = array_flip($builds);
                foreach(explode(',', $bug->openedBuild) as $build)
                {
                    $buildID = zget($builds, $build, '');
                    if($buildID == 'trunk')
                    {
                        echo $build;
                    }
                    elseif($buildID and common::hasPriv('build', 'view'))
                    {
                        echo html::a(helper::createLink('build', 'view', "buildID=$buildID"), $build, '', "title='$bug->openedBuild'");
                    }
                    else
                    {
                        echo $build;
                    }
                }
                break;
            case 'assignedTo':
                echo zget($users, $bug->assignedTo, $bug->assignedTo);
                break;
            case 'assignedDate':
                echo substr($bug->assignedDate, 5, 11);
                break;
            case 'deadline':
                echo $bug->deadline;
                break;
            case 'resolvedBy':
                echo zget($users, $bug->resolvedBy, $bug->resolvedBy);
                break;
            case 'resolution':
                echo zget($this->lang->bug->resolutionList, $bug->resolution);
                break;
            case 'resolvedDate':
                echo substr($bug->resolvedDate, 5, 11);
                break;
            case 'resolvedBuild':
                echo $bug->resolvedBuild;
                break;
            case 'closedBy':
                echo zget($users, $bug->closedBy);
                break;
            case 'closedDate':
                echo substr($bug->closedDate, 5, 11);
                break;
            case 'lastEditedBy':
                echo zget($users, $bug->lastEditedBy);
                break;
            case 'lastEditedDate':
                echo substr($bug->lastEditedDate, 5, 11);
                break;
            case 'actions':
                $params = "bugID=$bug->id";
                common::printIcon('bug', 'confirmBug', $params, $bug, 'list', 'search', '', 'iframe', true);
                common::printIcon('bug', 'assignTo',   $params, $bug, 'list', '', '', 'iframe', true);
                common::printIcon('bug', 'resolve',    $params, $bug, 'list', '', '', 'iframe', true);
                common::printIcon('bug', 'close',      $params, $bug, 'list', '', '', 'iframe', true);
                common::printIcon('bug', 'edit',       $params, $bug, 'list');
                common::printIcon('bug', 'create',     "product=$bug->product&branch=$bug->branch&extra=bugID=$bug->id", $bug, 'list', 'copy');
                common::printIcon('bug', 'toIssue',     "fromBug=$bug->id", $bug, 'list', 'question-sign');
                break;
        }
        echo '</td>';
    }
}/**
 * Close a bug.
 *
 * @param  int    $bugID
 * @access public
 * @return void
 */
public function withhold($bugID)
{
    $now = helper::now();
    $bug = fixer::input('post')
        ->add('withhold',       '1')
        ->add('lastEditedBy',   $this->app->user->account)
        ->add('lastEditedDate', $now)
        ->add('confirmed',      1)
        ->remove('comment')
        ->get();

    $this->dao->update(TABLE_BUG)->data($bug)->autoCheck()->where('id')->eq((int)$bugID)->exec();
}
//**//
}