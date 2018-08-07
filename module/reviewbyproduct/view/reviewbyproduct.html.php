<?php
/**
 * The reviewbyproduct view of reviewbyproduct module of ZenTaoPMS.
 *
 * @copyright   RabbitDog
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      RabbitDog
 * @package     reviewbyproduct
 * @version     20171214
 */
?>
<?php include '../../common/view/header.html.php';?>
<?php js::set('confirmDelete', $lang->reviewbyproduct->confirmDelete)?>
<div id='featurebar'>
    <ul class='nav'>
      <!-- <?php
      if(common::hasPriv('reviewbyproduct', 'reviewbyproduct')) echo "<li class='active'>" . html::a(inlink('reviewbyproduct', "objectID=$objectID&from=$from"),  $lang->reviewbyproduct->reviewbyproduct) . "</li>";
      if(common::hasPriv('reviewbyproduct', 'leftProblem'))echo "<li>"   . html::a(inlink('leftProblem', "objectID=$objectID&from=$from&status=all"), $lang->reviewbyproduct->leftProblemAB) . "</li>";
      if(common::hasPriv('reviewbyproduct', 'leftProblem'))echo "<li>"   . html::a(inlink('leftProblem', "objectID=$objectID&from=$from&status=unfixed"), $lang->reviewbyproduct->unfixed) . "</li>";
      ?> -->
    </ul>
  <?php if ($from == 'project'):?>
  <div class='actions'>
    <?php common::printIcon('reviewbyproduct', 'create', "project=$object->id", "", "button", "plus");?>
  </div>
  <?php endif;?>
  <!--<div id='querybox' class='show'></div>-->
</div>

<table class='table tablesorter table-fixed' id='storyReviewList'>
  <thead>
  <?php

  $vars = "objectID=$objectID&from=$from&type=$type&param=$param&orderBy=%s&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}";
  ?>
    <tr class='colhead'>
      <th class='w-30px'><?php common::printOrderLink('id', $orderBy, $vars, $lang->idAB);?></th>
      <th class='w-180px'><?php common::printOrderLink('projectName', $orderBy, $vars, $lang->reviewbyproduct->project);?></th>
      <th class='w-200px'><?php common::printOrderLink('title', $orderBy, $vars, $lang->reviewbyproduct->title);?></th>
      <th class='w-80px'><?php common::printOrderLink('reviewconclusion', $orderBy, $vars, $lang->reviewbyproduct->reviewConclusion);?></th>
      <th class='w-80px'><?php common::printOrderLink('feasibilityresult', $orderBy, $vars, $lang->reviewbyproduct->feasibilityresult);?></th>
      <th class='w-180px'><?php common::printOrderLink('storyReviewers', $orderBy, $vars, $lang->reviewbyproduct->storyReviewers);?></th>
      <th class='w-180px'><?php common::printOrderLink('devReviewers', $orderBy, $vars, $lang->reviewbyproduct->devReviewers);?></th>
      <th class='w-180px'><?php common::printOrderLink('testReviewers', $orderBy, $vars, $lang->reviewbyproduct->testReviewers);?></th>
      <th class='w-date'><?php common::printOrderLink('reviewDate', $orderBy, $vars, $lang->reviewbyproduct->reviewDate);?></th>
      <th class='w-80px'><?php echo $lang->actions;?></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($reviewbyproduct as $reviewbyproduct):?>
  <tr class='text-center'>
    <td><?php echo html::a($this->createLink('reviewbyproduct', 'view', "reviewbyproduct=$reviewbyproduct->id$from=$from"), $reviewbyproduct->id);?></td>
    <td><?php echo $reviewbyproduct->projectCode;?></td>
    <td class='text-left' title='<?php echo $reviewbyproduct->title?>'><?php echo html::a($this->createLink('reviewbyproduct', 'view', "reviewbyproduct=$reviewbyproduct->id&from=$from"), $reviewbyproduct->title);?></td>
    <td title='<?php echo $reviewbyproduct->reviewconclusion?>'>
      <?php
        $conclusionLabel = '不通过';
        if($reviewbyproduct->reviewconclusion == '1'){
          $conclusionLabel = '通过';
        }
        echo $conclusionLabel
      ?>
    </td>
    <td title='<?php echo $reviewbyproduct->feasibilityresult?>'>
      <?php
        $resultLabel = '不可行';
        if($reviewbyproduct->feasibilityresult=='1'){
          $resultLabel = '可行';
        }
        echo $resultLabel
      ?>
    </td>
    <td>
      <?php
      $storyReviewers = '';
      if (!empty($reviewbyproduct->storyReviewers))
      {
        foreach(explode(',', $reviewbyproduct->storyReviewers) as $account)
        {
          if(empty($account)) continue;
          $storyReviewers .=  $users[trim($account)] . '&nbsp;';
        }
      }
      echo $storyReviewers;
      ?>
    </td>
    <td>
      <?php
      $devReviewers = '';
      if (!empty($reviewbyproduct->devReviewers))
      {
        foreach(explode(',', $reviewbyproduct->devReviewers) as $account)
        {
          if(empty($account)) continue;
          $devReviewers .=  $users[trim($account)] . '&nbsp;';
        }
      }
      echo $devReviewers;
      ?>
    </td>
    <td>
      <?php
      $testReviewers = '';
      if (!empty($reviewbyproduct->testReviewers))
      {
        foreach(explode(',', $reviewbyproduct->testReviewers) as $account)
        {
          if(empty($account)) continue;
          $testReviewers .=  $users[trim($account)] . '&nbsp;';
        }
      }
      echo $testReviewers;
      ?>
    </td>
    <td><?php echo $reviewbyproduct->reviewDate; ?></td>
    <td>
      <?php

      common::printIcon('reviewbyproduct', 'edit',   "storyReviewID=$reviewbyproduct->id&objectID=$objectID&type=$from", '','list', 'pencil', '', '', false, '', $lang->reviewbyproduct->edit);
      if ($from == 'project')
      {
        if(common::hasPriv('reviewbyproduct', 'delete'))
        {
          $deleteURL = $this->createLink('reviewbyproduct', 'delete', "storyReviewID=$reviewbyproduct->id&confirm=yes");
          echo html::a("javascript:ajaxDelete(\"$deleteURL\",\"storyReviewList\",confirmDelete)", '<i class="icon-remove"></i>', '', "class='btn-icon' title='{$lang->reviewbyproduct->delete}'");
        }
      }
      ?>
    </td>
  </tr>
  <?php endforeach;?>
  </tbody>
  <tfoot><tr><td colspan='15'><?php $pager->show();?></td></tr></tfoot>
</table>
<script language="JavaScript">
  $(function()
  {
    setTimeout(function(){fixedTheadOfList('#storyReviewList')}, 100);
  })
</script>
<?php include '../../common/view/footer.html.php';?>
