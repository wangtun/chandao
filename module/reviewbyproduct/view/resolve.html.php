<?php
/**
 * The resolve file of bug module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     bug
 * @version     $Id: resolve.html.php 4129 2013-01-18 01:58:14Z wwccss $
 * @link        http://www.zentao.net
 */
?>
<?php include '../../common/view/header.html.php';?>
<?php include '../../common/view/kindeditor.html.php';?>
<?php 
js::set('page'      , 'resolve');
js::set('productID' , $reviewbyproduct->product);
?>
<div id='titlebar'>
  <div class='heading'>
    <span class='prefix'><?php echo html::icon($lang->icons['reviewbyproduct']);?> <strong><?php echo $reviewbyproduct->id;?></strong></span>
    <strong><?php echo html::a($this->createLink('reviewbyproduct', 'view', 'reviewbyproduct=' . $reviewbyproduct->id), $reviewbyproduct->title, '_blank');?></strong>
    <small class='text-success'> <?php echo $lang->reviewbyproduct->resolve;?> <?php echo html::icon($lang->icons['resolve']);?></small>
  </div>
</div>
<form class='form-condensed' method='post' enctype='multipart/form-data' target='hiddenwin'>
  <table class='table table-form'>
    <tr>
      <th class='w-80px'><?php echo $lang->reviewbyproduct->resolution;?></th>
      <td class='w-500px'><?php echo html::select('resolution', $lang->reviewbyproduct->resolutionList, '', 'class=form-control onchange=setDuplicate(this.value)');?></td>
    </tr>
    <tr>
      <th><?php echo $lang->reviewbyproduct->countermeasure;?></th>
      <td><?php echo html::textarea('countermeasure', '', "rows='4' class='form-control'");?></td>
    </tr>
    <tr>
      <th></th><td><?php echo html::submitButton() . html::linkButton($lang->goback, $this->session->storyReviewList);?></td>
    </tr>
  </table>
</form>
<div class='main'>
  <?php include '../../common/view/action.html.php';?>
</div>
<?php include '../../common/view/footer.html.php';?>
