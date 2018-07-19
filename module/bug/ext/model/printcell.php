<?php
/**
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
}