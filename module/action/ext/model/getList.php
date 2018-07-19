<?php
/**
 * Get actions of an object.
 *
 * @param  int    $objectType
 * @param  int    $objectID
 * @access public
 * @return array
 */
public function getList($objectType, $objectID)
{
    $commiters = $this->loadModel('user')->getCommiters();
    $actions   = $this->dao->select('*')->from(TABLE_ACTION)
        ->beginIF($objectType == 'project')
        //1951 补丁版本增加详情页面，并增加历史记录信息
        ->where("objectType IN('project', 'testtask', 'build', 'patchbuild', 'storyreview', 'issue', 'riskmanage')")
        ->andWhere('project')->eq($objectID)
        ->fi()
        ->beginIF($objectType != 'project')
        ->where('objectType')->eq($objectType)
        ->andWhere('objectID')->eq($objectID)
        ->fi()
        ->orderBy('date, id')->fetchAll('id');
    $histories = $this->getHistory(array_keys($actions));
    $this->loadModel('file');

    if($objectType == 'project')
    {
        $this->app->loadLang('build');
        //1951 补丁版本增加详情页面，并增加历史记录信息
        $this->app->loadLang('patchbuild');
        $this->app->loadLang('storyreview');
        $this->app->loadLang('reviewbyproduct');
        $this->app->loadLang('issue');
        $this->app->loadLang('riskmanage');

        $this->app->loadLang('testtask');
        $actions = $this->processProjectActions($actions);
    }

    foreach($actions as $actionID => $action)
    {
        $actionName = strtolower($action->action);
        if($actionName == 'svncommited' and isset($commiters[$action->actor]))
        {
            $action->actor = $commiters[$action->actor];
        }
        elseif($actionName == 'gitcommited' and isset($commiters[$action->actor]))
        {
            $action->actor = $commiters[$action->actor];
        }
        elseif($actionName == 'linked2project')
        {
            $name = $this->dao->select('name')->from(TABLE_PROJECT)->where('id')->eq($action->extra)->fetch('name');
            if($name) $action->extra = common::hasPriv('project', 'story') ? html::a(helper::createLink('project', 'story', "projectID=$action->extra"), $name) : $name;
        }
        elseif($actionName == 'linked2plan')
        {
            $title = $this->dao->select('title')->from(TABLE_PRODUCTPLAN)->where('id')->eq($action->extra)->fetch('title');
            if($title) $action->extra = common::hasPriv('productplan', 'view') ? html::a(helper::createLink('productplan', 'view', "planID=$action->extra"), $title) : $title;
        }
        elseif($actionName == 'linked2build')
        {
            $name = $this->dao->select('name')->from(TABLE_BUILD)->where('id')->eq($action->extra)->fetch('name');
            if($name) $action->extra = common::hasPriv('build', 'view') ? html::a(helper::createLink('build', 'view', "builID=$action->extra&type={$action->objectType}"), $name) : $name;
        }
        elseif($actionName == 'linked2bug')
        {
            $name = $this->dao->select('name')->from(TABLE_BUILD)->where('id')->eq($action->extra)->fetch('name');
            if($name) $action->extra = common::hasPriv('build', 'view') ? html::a(helper::createLink('build', 'view', "builID=$action->extra&type={$action->objectType}"), $name) : $name;
        }
        elseif($actionName == 'linked2release')
        {
            $name = $this->dao->select('name')->from(TABLE_RELEASE)->where('id')->eq($action->extra)->fetch('name');
            if($name) $action->extra = common::hasPriv('release', 'view') ? html::a(helper::createLink('release', 'view', "releaseID=$action->extra&type={$action->objectType}"), $name) : $name;
        }
        elseif($actionName == 'moved')
        {
            $name = $this->dao->select('name')->from(TABLE_PROJECT)->where('id')->eq($action->extra)->fetch('name');
            if($name) $action->extra = common::hasPriv('project', 'task') ? html::a(helper::createLink('project', 'task', "projectID=$action->extra"), "#$action->extra " . $name) : "#$action->extra " . $name;
        }
        elseif($actionName == 'frombug' and common::hasPriv('bug', 'view'))
        {
            $action->extra = html::a(helper::createLink('bug', 'view', "bugID=$action->extra"), $action->extra);
        }
        elseif($actionName == 'unlinkedfromproject')
        {
            $name = $this->dao->select('name')->from(TABLE_PROJECT)->where('id')->eq($action->extra)->fetch('name');
            if($name) $action->extra = common::hasPriv('project', 'story') ? html::a(helper::createLink('project', 'story', "projectID=$action->extra"), "#$action->extra " . $name) : "#$action->extra " . $name;
        }
        elseif($actionName == 'unlinkedfrombuild')
        {
            $name = $this->dao->select('name')->from(TABLE_BUILD)->where('id')->eq($action->extra)->fetch('name');
            if($name) $action->extra = common::hasPriv('build', 'view') ? html::a(helper::createLink('build', 'view', "builID=$action->extra&type={$action->objectType}"), $name) : $name;
        }
        elseif($actionName == 'unlinkedfromrelease')
        {
            $name = $this->dao->select('name')->from(TABLE_RELEASE)->where('id')->eq($action->extra)->fetch('name');
            if($name) $action->extra = common::hasPriv('release', 'view') ? html::a(helper::createLink('release', 'view', "releaseID=$action->extra&type={$action->objectType}"), $name) : $name;
        }
        elseif($actionName == 'unlinkedfromplan')
        {
            $title = $this->dao->select('title')->from(TABLE_PRODUCTPLAN)->where('id')->eq($action->extra)->fetch('title');
            if($title) $action->extra = common::hasPriv('productplan', 'view') ? html::a(helper::createLink('productplan', 'view', "planID=$action->extra"), "#$action->extra " . $title) : "#$action->extra " . $title;
        }
        elseif($actionName == 'tostory')
        {
            $title = $this->dao->select('title')->from(TABLE_STORY)->where('id')->eq($action->extra)->fetch('title');
            if($title) $action->extra = common::hasPriv('story', 'view') ? html::a(helper::createLink('story', 'view', "storyID=$action->extra"), "#$action->extra " . $title) : "#$action->extra " . $title;
        }
        elseif($actionName == 'toissue')
        {
            $action->extra = common::hasPriv('issue', 'view') ? html::a(helper::createLink('issue', 'view', "issueID=$action->extra"), "#$action->extra") : "#$action->extra";
        }
        elseif($actionName == 'totask')
        {
            $name = $this->dao->select('name')->from(TABLE_TASK)->where('id')->eq($action->extra)->fetch('name');
            if($name) $action->extra = common::hasPriv('task', 'view') ? html::a(helper::createLink('task', 'view', "taskID=$action->extra"), "#$action->extra " . $name) : "#$action->extra " . $name;
        }
        elseif($actionName == 'buildopened')
        {
            $name = $this->dao->select('name')->from(TABLE_BUILD)->where('id')->eq($action->objectID)->fetch('name');
            if($name) $action->extra = common::hasPriv('build', 'view') ? html::a(helper::createLink('build', 'view', "buildID=$action->objectID"), "#$action->objectID " . $name) : "#$action->objectID " . $name;
        }
        elseif($actionName == 'testtaskopened' or $actionName == 'testtaskstarted' or $actionName == 'testtaskclosed')
        {
            $name = $this->dao->select('name')->from(TABLE_TESTTASK)->where('id')->eq($action->objectID)->fetch('name');
            if($name) $action->extra = common::hasPriv('testtask', 'view') ? html::a(helper::createLink('testtask', 'view', "testtaskID=$action->objectID"), "#$action->objectID " . $name) : "#$action->objectID " . $name;
        }
        elseif($actionName == 'fromlib' and $action->objectType == 'case')
        {
            $name = $this->dao->select('name')->from(TABLE_TESTSUITE)->where('id')->eq($action->extra)->fetch('name');
            if($name) $action->extra = common::hasPriv('testsuite', 'library') ? html::a(helper::createLink('testsuite', 'library', "libID=$action->extra"), $name) : $name;
        }
        elseif(($actionName == 'closed' and $action->objectType == 'story') or ($actionName == 'resolved' and $action->objectType == 'bug'))
        {
            $action->appendLink = '';
            if(strpos($action->extra, ':')!== false)
            {
                list($extra, $id) = explode(':', $action->extra);
                $action->extra    = $extra;
                if($id)
                {
                    $table = $action->objectType == 'story' ? TABLE_STORY : TABLE_BUG;
                    $name  = $this->dao->select('title')->from($table)->where('id')->eq($id)->fetch('title');
                    if($name) $action->appendLink = html::a(helper::createLink($action->objectType, 'view', "id=$id"), "#$id " . $name);
                }
            }
        }
        elseif($actionName == 'finished' and $objectType == 'todo')
        {
            $action->appendLink = '';
            if(strpos($action->extra, ':')!== false)
            {
                list($extra, $id) = explode(':', $action->extra);
                $action->extra    = strtolower($extra);
                if($id)
                {
                    $table = $this->config->objectTables[$action->extra];
                    $field = $this->config->action->objectNameFields[$action->extra];
                    $name  = $this->dao->select($field)->from($table)->where('id')->eq($id)->fetch($field);
                    if($name) $action->appendLink = html::a(helper::createLink($action->extra, 'view', "id=$id"), "#$id " . $name);
                }
            }
        }
        $action->history = isset($histories[$actionID]) ? $histories[$actionID] : array();

        $actionName = strtolower($action->action);
        if($actionName == 'svncommited')
        {
            foreach($action->history as $history)
            {
                if($history->field == 'subversion') $history->diff = str_replace('+', '%2B', $history->diff);
            }
        }
        elseif($actionName == 'gitcommited')
        {
            foreach($action->history as $history)
            {
                if($history->field == 'git') $history->diff = str_replace('+', '%2B', $history->diff);
            }
        }

        $action->comment = $this->file->setImgSize($action->comment, $this->config->action->commonImgSize);
        $actions[$actionID] = $action;
    }

    return $actions;
}