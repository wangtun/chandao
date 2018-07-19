<?php
/**
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
}
