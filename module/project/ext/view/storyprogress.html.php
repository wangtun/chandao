<?php
/**
 * The story view file of project module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     project
 * @version     $Id: story.html.php 5117 2013-07-12 07:03:14Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include '../../../common/view/header.html.php';?>
<?php include '../../../common/view/tablesorter.html.php';?>
<?php include '../../../common/view/sortable.html.php';?>
<?php include '../../../common/view/datepicker.html.php';?>
<?php js::set('moduleID', ($type == 'byModule' ? $param : 0));?>
<?php js::set('productID', ($type == 'byProduct' ? $param : 0));?>
<?php js::set('confirmUnlinkStory', $lang->project->confirmUnlinkStory)?>
<div id='featurebar'>
  <ul class='nav'>
    <li><?php if(common::hasPriv('project', 'story')) echo html::a($this->createLink('project', 'story', "project=$project->id"), $lang->project->story);?></li>
    <li class='active'><?php if(common::hasPriv('project', 'storyProgress')) echo html::a($this->createLink('project', 'storyProgress', "project=$project->id"), $lang->story->storyProgress);?></li>
    <li><?php if(common::hasPriv('project', 'storykanban')) echo html::a($this->createLink('project', 'storykanban', "project=$project->id"), $lang->project->kanban);?></li>
  </ul>
  <div class='actions'>
    <div class='btn-group'>
    <?php
    //需求1197 项目需求导出时，增加TBC字段的内容export方法增加projectID参数
    common::printIcon('story', 'export', "productID=$productID&orderBy=id_desc&project=$project->id", '', 'button', '', '', 'export');

    $this->lang->story->create = $this->lang->project->createStory;
    if($productID and !$this->loadModel('story')->checkForceReview()) common::printIcon('story', 'create', "productID=$productID&branch=&moduleID=0&story=0&project=$project->id");

    if(commonModel::isTutorialMode())
    {
        $wizardParams = helper::safe64Encode("project=$project->id");
        echo html::a($this->createLink('tutorial', 'wizard', "module=project&method=linkStory&params=$wizardParams"), "<i class='icon-link'></i> {$lang->project->linkStory}",'', "class='btn link-story-btn'");
    }
    else
    {
        common::printIcon('project', 'linkStory', "project=$project->id", '', 'button', 'link', '', 'link-story-btn');
    }
    ?>
    </div>
  </div>
  <div id='querybox' class='show'></div>
</div>

<div class='side' id='taskTree'>
  <a class='side-handle' data-id='projectTree'><i class='icon-caret-left'></i></a>
  <div class='side-body'>
    <div class='panel panel-sm'>
      <div class='panel-heading nobr'>
        <?php echo html::icon($lang->icons['project']);?> <strong><?php echo $project->name;?></strong>
      </div>
      <div class='panel-body'><?php echo $moduleTree;?></div>
    </div>
  </div>
</div>
<div class='main'>
  <script>setTreeBox();</script>
  <form method='post' id='projectStoryForm'>
    <table class='table tablesorter table-condensed table-fixed' id='storyList'>
      <thead>
        <tr class='colhead'>
        <?php $vars = "projectID={$project->id}&orderBy=%s&type=$type&param=$param&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}"; ?>
          <th class='w-id  {sorter:false}'>    <?php common::printOrderLink('id',         $orderBy, $vars, $lang->idAB);?></th>
          <th class='{sorter:false}'>          <?php common::printOrderLink('title',      $orderBy, $vars, $lang->story->title);?></th>
          <th class='w-60px {sorter:false}'>   <?php common::printOrderLink('openedBy',   $orderBy, $vars, $lang->openedByAB);?></th>
          <th class='w-60px {sorter:false}'> <?php common::printOrderLink('stage',      $orderBy, $vars, $lang->story->stageAB);?></th>
          <th class='w-70px {sorter:false}'>   <?php common::printOrderLink('reviewStatus',      $orderBy, $vars, $lang->story->reviewStatus);?></th>
          <th class='w-80px'>  <?php echo $lang->story->storyReviewID;?></th>
          <th class='w-70px {sorter:false}'>  <?php common::printOrderLink('testDate',      $orderBy, $vars, $lang->story->testDate);?></th>
          <th class='w-90px {sorter:false}'>  <?php common::printOrderLink('specialPlan',      $orderBy, $vars, $lang->story->specialPlan);?></th>
          <th class='w-80px'>  <?php echo $lang->project->build;?></th>
          <th class='w-80px'>  <?php echo $lang->project->patchBuild;?></th>
          <th class='w-80px'>  <?php echo $lang->project->release;?></th>
          <th class='w-70px {sorter:false}'>   <?php common::printOrderLink('testStatus',      $orderBy, $vars, $lang->story->testStatus);?></th>
          <th class='w-70px {sorter:false}'>   <?php common::printOrderLink('verifyStatus',      $orderBy, $vars, $lang->story->verifyStatus);?></th>
          <th class="w-80px {sorter:false}"><?php common::printOrderLink('linkStories',        $orderBy, $vars, $lang->story->linkStories);?></th>
          <th class="w-100px {sorter:false}"><?php common::printOrderLink('ifLinkStories',     $orderBy, $vars, $lang->story->ifLinkStories);?></th>
        </tr>
      </thead>
      <tbody id='storyTableList' class='sortable'>
        <?php
        $totalEstimate = 0;
        $canBatchEdit  = common::hasPriv('story', 'batchEdit');
        $canBatchClose = common::hasPriv('story', 'batchClose');
        //判断是否有需求批量转项目的权限
        $canBatchChangeProject = common::hasPriv('project', 'batchChangeProject');
        //9012 项目需求中增加需求所属计划的显示;可以批量关联计划
        $canBatchChangePlan = common::hasPriv('story', 'batchChangePlan');
        $batchStoryReview   = common::hasPriv('story', 'batchStoryReview');
        /*2911 优化需求提测计划、发版计划等内容*/
        $batchEditDate      = common::hasPriv('story', 'batchEditDate');
        $canBatchChangeTestStatus = common::hasPriv('story', 'batchChangeTestStatus');
        $canBatchChangeVerifyStatus = common::hasPriv('story', 'batchChangeVerifyStatus');
        ?>
        <?php foreach($stories as $key => $story):?>
        <?php
        $storyLink      = $this->createLink('story', 'view', "storyID=$story->id&version=$story->version&from=project&param=$project->id");
        $totalEstimate += $story->estimate;
        ?>
          <tr class='text-center' id="story<?php echo $story->id;?>" data-id='<?php echo $story->id;?>' data-order='<?php echo $story->order ?>'>
          <td class='cell-id'>
            <?php if($canBatchEdit or $canBatchClose or $canBatchChangeProject):?>
            <input type='checkbox' name='storyIDList[<?php echo $story->id;?>]' value='<?php echo $story->id;?>' /> 
            <?php endif;?>
            <?php echo html::a($storyLink, sprintf('%03d', $story->id));?>
          </td>
          <td class='text-left' title="<?php echo $story->title?>">
            <?php if(isset($branchGroups[$story->product][$story->branch])) echo "<span class='label label-info label-badge'>" . $branchGroups[$story->product][$story->branch] . '</span>';?>
            <?php echo html::a($storyLink,$story->title, null, "style='color: $story->color'");?>
          </td>
          <td><?php echo $users[$story->openedBy];?></td>
          <td><?php echo $lang->story->stageList[$story->stage];?></td>
          <td><?php echo $lang->story->reviewStatusList[$story->reviewStatus];?></td>
          <td>
            <?php
            if(!empty($story->storyReviewID))
            {
              $story->storyReviewID = explode(',', $story->storyReviewID);
              foreach ($story->storyReviewID as $value)
              {
                common::printLink('storyreview', 'view', "storyReviewID=$value&from=project", $value);
              }
            }
            ?>
          </td>
          <td><?php echo $story->testDate;?></td>
          <td><?php echo $story->specialPlan;?></td>
          <td>
            <?php
            foreach ($story->build as $value)
            {
              $buildLink = $this->createLink('build', 'view', "id=$value");
              echo html::a($buildLink, $value);
            };
            ?>
          </td>
          <td>
            <?php
            foreach ($story->patchBuild as $value)
            {
              $buildLink = $this->createLink('patchbuild', 'view', "id=$value");
              echo html::a($buildLink, $value);
            };
            ?>
          </td>
          <td>
            <?php
            foreach ($story->release as $value)
            {
              $releaseLink = $this->createLink('release', 'view', "id=$value");
              echo html::a($releaseLink, $value);
            };
            ?>
          </td>
          <td><?php echo $lang->story->testStatusList[$story->testStatus];?></td>
          <td><?php echo $lang->story->verifyStatusList[$story->verifyStatus];?></td>
          <td>
            <?php
            if (!empty($story->linkStories))
            {
              foreach (explode(',', $story->linkStories) as $value)
              {
                $storyLink = $this->createLink('story', 'view', "id=$value");
                echo html::a($storyLink, $value);
              };
            }
            ?>
          </td>
          <td><?php echo $lang->story->ifLinkStoriesList[$story->ifLinkStories];?></td>
        </tr>
        <?php endforeach;?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan='15'>
            <div class='table-actions clearfix'>
            <?php
            $storyInfo = sprintf($lang->project->productStories, inlink('linkStory', "project={$project->id}"));
            if(count($stories))
            {
              //9012 项目需求中增加需求所属计划的显示;可以批量关联计划
              if($canBatchChangePlan or $canBatchEdit or $canBatchClose or $canBatchChangeProject or $canBatchChangeTestStatus or $canBatchChangeVerifyStatus) echo html::selectButton();

              echo "<div class='btn-group dropup'>";
              if($canBatchEdit)
              {
                  $actionLink = $this->createLink('story', 'batchEdit', "productID=0&projectID=$project->id");
                  echo html::commonButton($lang->edit, "onclick=\"setFormAction('$actionLink')\"");
              }
              echo "<button id='moreAction' type='button' class='btn dropdown-toggle' data-toggle='dropdown'><span class='caret'></span></button>";
              echo "<ul class='dropdown-menu' id='moreActionMenu'>";
              //2911 优化需求提测计划、发版计划等内容
              if($batchEditDate)
              {
                echo "<li class='dropdown-submenu'>";
                echo html::a('javascript:;', $lang->story->testDate, '', "class ='change-date'");
                echo '</li>';

                echo "<li class='dropdown-submenu'>";
                echo html::a('javascript:;', $lang->story->specialPlan, '', "class ='change-date'");
                echo '</li>';
              }

              //9012 项目需求中增加需求所属计划的显示;可以批量关联计划
              if($canBatchChangePlan)
              {
                unset($plans['']);
                $plans      = array(0 => $lang->null) + $plans;
                $withSearch = count($plans) > 8;
                echo "<li class='dropdown-submenu'>";
                echo html::a('javascript:;', $lang->story->planAB, '', "id='planItem'");
                echo "<div class='dropdown-menu" . ($withSearch ? ' with-search':'') . "'>";
                echo "<ul class='dropdown-list'>";
                foreach($plans as $planID => $plan)
                {
                  $actionLink = $this->createLink('story', 'batchChangePlan', "planID=$planID");
                  echo "<li class='option' data-key='$planID'>" . html::a('#', $plan, '', "onclick=\"setFormAction('$actionLink','hiddenwin')\"") . "</li>";
                }
                echo '</ul>';
                if($withSearch) echo "<div class='menu-search'><div class='input-group input-group-sm'><input type='text' class='form-control' placeholder=''><span class='input-group-addon'><i class='icon-search'></i></span></div></div>";
                echo '</div></li>';
              }

              if($canBatchClose)
              {
                  $actionLink = $this->createLink('story', 'batchClose', "productID=0&projectID=$project->id");
                  $misc       = "onclick=\"setFormAction('$actionLink')\"";
                  echo '<li>' . html::a('#', $lang->close, '', $misc) . '</li>';
              }
              if(common::hasPriv('project', 'batchUnlinkStory'))
              {
                  $actionLink = $this->createLink('project', 'batchUnlinkStory', "projectID=$project->id");
                  $misc       = "onclick=\"setFormAction('$actionLink')\"";
                  echo '<li>' . html::a('#', $lang->project->unlinkStory, '', $misc) . '</li>';
              }

              if($canBatchChangeProject)
              {
                $withSearch = count($projects) > 10;
                echo "<li class='dropdown-submenu'>";
                echo html::a('javascript:;', $lang->project->projectAB, '', "id='projectItem'");
                echo "<div class='dropdown-menu" . ($withSearch ? ' with-search':'') . "'>";
                echo '<ul class="dropdown-list">';
                foreach($projects as $project)
                {
                  $actionLink = $this->createLink('project', 'batchChangeProject', "proID=$project->project&oldProID=$oldProjectID");
                  echo "<li class='option' data-key='$project->project'>" . html::a('#', $project->name, '', "onclick=\"setFormAction('$actionLink')\"") . "</li>";
                }
                echo '</ul>';
                if($withSearch) echo "<div class='menu-search'><div class='input-group input-group-sm'><input type='text' class='form-control' placeholder=''><span class='input-group-addon'><i class='icon-search'></i></span></div></div>";
                echo '</div></li>';
              }

              if($batchStoryReview)
              {
                echo "<li class='dropdown-submenu'>";
                echo html::a('javascript:;', $lang->story->reviewStatus, '', "id='reviewStatusItem'");
                echo "<ul class='dropdown-menu'>";
                unset($lang->story->reviewStatusList['']);
                foreach($lang->story->reviewStatusList as $key => $result)
                {
                  $actionLink = $this->createLink('story', 'batchStoryReview', "result=$key");
                  echo '<li>' . html::a('#', $result, '', "onclick=\"setFormAction('$actionLink','hiddenwin')\"") . '</li>';
                }
                echo '</ul></li>';
              }

              if($canBatchChangeTestStatus)
              {
                echo "<li class='dropdown-submenu'>";
                echo html::a('javascript:;', $lang->story->testStatus, '', "id='testStatusItem'");
                echo "<ul class='dropdown-menu'>";
                unset($lang->story->testStatusList['']);
                foreach($lang->story->testStatusList as $key => $result)
                {
                  $actionLink = $this->createLink('story', 'batchChangeTestStatus', "result=$key");
                  echo '<li>' . html::a('#', $result, '', "onclick=\"setFormAction('$actionLink','hiddenwin')\"") . '</li>';
                }
                echo '</ul></li>';
              }
              if($canBatchChangeVerifyStatus)
              {
                echo "<li class='dropdown-submenu'>";
                echo html::a('javascript:;', $lang->story->verifyStatus, '', "id='verifyStatusItem'");
                echo "<ul class='dropdown-menu'>";
                unset($lang->story->verifyStatusList['']);
                foreach($lang->story->verifyStatusList as $key => $result)
                {
                  $actionLink = $this->createLink('story', 'batchChangeVerifyStatus', "result=$key");
                  echo '<li>' . html::a('#', $result, '', "onclick=\"setFormAction('$actionLink','hiddenwin')\"") . '</li>';
                }
                echo '</ul></li>';
              }
              //3883 将有无相关需求功能调用在项目-需求-需求（需求列表、需求进展中均显示）显示，并可直接赋值
              if(common::hasPriv('story', 'batchChangeIfLinkStories'))
              {
                echo "<li class='dropdown-submenu'>";
                echo html::a('javascript:;', $lang->story->ifLinkStories, '', "id='ifLinkStoriesItem'");
                echo "<ul class='dropdown-menu'>";
                $lang->story->ifLinkStoriesList[''] = $lang->null;
                foreach($lang->story->ifLinkStoriesList as $key => $status)
                {
                  $actionLink = $this->createLink('story', 'batchChangeIfLinkStories', "status=$key");
                  echo "<li>" . html::a('#', $status, '', "onclick=\"setFormAction('$actionLink','hiddenwin')\"") . "</li>";
                }
                echo '</ul></li>';
              }

              echo '</ul></div>';
              $storyInfo = $summary;
            }
            echo "<div class='text'>{$storyInfo}</div>";
            ?>
            </div>
            <?php echo $pager->show();?>
          </td>
        </tr>
      </tfoot>
    </table>
  </form>
</div>
<?php js::set('projectID', $project->id);?>
<?php js::set('orderBy', $orderBy)?>
<?php include '../../../common/view/footer.html.php';?>
