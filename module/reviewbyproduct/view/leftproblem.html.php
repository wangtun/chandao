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
    <?php
    $allClass     = $status=='all'?'active':'';
    $unfixedClass = $status=='unfixed'?'active':'';
    if(common::hasPriv('reviewbyproduct', 'reviewbyproduct')) echo "<li>" . html::a(inlink('reviewbyproduct', "objectID=$objectID&from=$from"),  $lang->reviewbyproduct->reviewbyproduct) . "</li>";
    if(common::hasPriv('reviewbyproduct', 'leftProblem'))echo "<li class=$allClass>"   . html::a(inlink('leftProblem', "objectID=$objectID&from=$from&status=all"), $lang->reviewbyproduct->leftProblemAB) . "</li>";
    if(common::hasPriv('reviewbyproduct', 'leftProblem'))echo "<li class=$unfixedClass>"   . html::a(inlink('leftProblem', "objectID=$objectID&from=$from&status=unfixed"), $lang->reviewbyproduct->unfixed) . "</li>";
    ?>
  </ul>
  <?php if ($from == 'project'):?>
  <div class='actions'>
    <?php common::printIcon('reviewbyproduct', 'create', "project=$object->id", "", "button", "plus");?>
  </div>
  <?php endif;?>
  <!--<div id='querybox' class='show'></div>-->
</div>
<table class='table tablesorter table-condensed table-hover table-striped' id='storyReviewList' style="table-layout:fixed" >
  <thead>
  <?php
  $vars = "objectID=$objectID&from=$from&status=$status&type=$type&param=$param&orderBy=%s&recTotal={$pager->recTotal}&recPerPage={$pager->recPerPage}";
  ?>
    <tr class='colhead'>
      <th class='w-40px'><?php common::printOrderLink('id', $orderBy, $vars, $lang->idAB);?></th>
      <th class='w-200px'><?php common::printOrderLink('projectName', $orderBy, $vars, $lang->reviewbyproduct->project);?></th>
      <th><?php common::printOrderLink('problemTracking', $orderBy, $vars, $lang->reviewbyproduct->problemTracking);?></th>
      <th class='w-user'><?php common::printOrderLink('openedBy', $orderBy, $vars, $lang->reviewbyproduct->openedBy);?></th>
      <th class='w-date'><?php common::printOrderLink('reviewDate', $orderBy, $vars, $lang->reviewbyproduct->reviewDate);?></th>
      <th class='w-80px'><?php common::printOrderLink('resolution', $orderBy, $vars, $lang->reviewbyproduct->resolution);?></th>
      <th class='w-70px'><?php common::printOrderLink('solver', $orderBy, $vars, $lang->reviewbyproduct->solver);?></th>
      <th class='w-date'><?php common::printOrderLink('resolvedDate', $orderBy, $vars, $lang->reviewbyproduct->resolvedDate);?></th>
      <th class='w-80px'><?php echo $lang->actions;?></th>

    </tr>
  </thead>
  <tbody>
  <?php foreach($storyReviews as $reviewbyproduct):?>
  <tr class='text-center'>
    <td><?php echo html::a($this->createLink('reviewbyproduct', 'view', "reviewbyproduct=$reviewbyproduct->id$from=$from"), $reviewbyproduct->id);?></td>
    <td><?php echo $reviewbyproduct->projectName;?></td>
    <td style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;" class='text-left' title='<?php echo $reviewbyproduct->problemTracking; ?>'><?php echo !empty($reviewbyproduct->problemTracking) ? html::a($this->createLink('reviewbyproduct', 'view', "reviewbyproduct=$reviewbyproduct->id&from=$from"), strip_tags($reviewbyproduct->problemTracking)) : '';?></td>
    <td><?php echo $users[$reviewbyproduct->openedBy]; ?></td>
    <td><?php echo $reviewbyproduct->reviewDate; ?></td>
    <td><?php echo $lang->reviewbyproduct->resolutionList[$reviewbyproduct->resolution]; ?></td>
    <td><?php echo $users[$reviewbyproduct->solver]; ?></td>
    <td><?php echo $reviewbyproduct->resolvedDate; ?></td>
    <td>
      <?php
      common::printIcon('reviewbyproduct', 'resolve', "storyReviewID=$reviewbyproduct->id", $reviewbyproduct, 'list', '', '', 'iframe', true);
      ?>
    </td>
  </tr>
  <?php endforeach;?>
  </tbody>
  <tfoot><tr><td colspan='9'><?php $pager->show();?></td></tr></tfoot>
</table>
<script language="JavaScript">
  $(function()
  {
    setTimeout(function(){fixedTheadOfList('#storyReviewList')}, 100);
  })
</script>
<?php include '../../common/view/footer.html.php';?>
