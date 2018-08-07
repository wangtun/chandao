<?php
/**
 * The create view of reviewbyproduct module of ZenTaoPMS.
 *
 * @copyright   RabbitDog
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      RabbitDog
 * @package     reviewbyproduct
 * @version     20171214
 */
?>
<?php include '../../common/view/header.html.php';?>
<?php include '../../common/view/datepicker.html.php';?>
<?php include '../../common/view/kindeditor.html.php';?>
<div class='container'>
  <div id='titlebar'>
    <div class='heading'>
      <strong><small class='text-muted'><?php echo html::icon($lang->icons['create']);?></small> <?php echo $lang->reviewbyproduct->create;?></strong>
    </div>
  </div>
  <form class='form-condensed' method='post' target='hiddenwin' id='dataform' enctype='multipart/form-data'>
    <table class='table table-form'> 
      <tr>
        <th><?php echo $lang->reviewbyproduct->title;?></th>
        <td colspan="3"><?php echo html::input('title', '', "class='form-control' autocomplete='off' placeholder='{$lang->reviewbyproduct->reviewTitle}'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->reviewStories;?></th>
        <td colspan="3"><?php echo html::select('reviewStories[]', $stories, '', 'multiple class="form-control chosen"');?></td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->storyReviewers;?></th>
        <td><?php echo html::select('storyReviewers[]', $users, '', 'multiple class="form-control chosen"');?></td>
        <td width="330">
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->reviewbyproduct->devReviewers;?></span>
            <?php echo html::select('devReviewers[]', $users, '', "multiple class='form-control chosen'");?>
          </div>
        </td>
        <td width="330">
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->reviewbyproduct->testReviewers;?></span>
            <?php echo html::select('testReviewers[]', $users, '', "multiple class='form-control chosen'");?>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->reviewDate;?></th>
        <td>
           <?php echo html::input('reviewDate', helper::today(), "class='form-control form-date';");?>
        </td>
        <td>
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->reviewbyproduct->reviewConclusion;?></span>
            <?php echo html::select('reviewconclusion', $lang->reviewbyproduct->conclusionList, '', "multiple class='form-control chosen' data-placeholder='{$lang->reviewbyproduct->conclusionAB}'");?>
          </div>
        </td>
        <td>
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->reviewbyproduct->mailto;?></span>
            <?php echo html::select('mailto[]', $users, '', "multiple class='form-control chosen' data-placeholder='{$lang->reviewbyproduct->mailtoAB}'");?>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->feasibilityanalysis;?></th>
        <td colspan="3"><?php echo html::textarea('feasibilityanalysis', '', "rows='7' class='form-control' placeholder='{$lang->reviewbyproduct->feasibilityanalysisAB}'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->feasibilityresult;?></th>
        <td colspan="3">  <?php echo html::select('feasibilityresult', $lang->reviewbyproduct->feasibilityResultList, '', "class='form-control chosen' data-placeholder='{$lang->reviewbyproduct->resultAB}'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->problemRecord;?></th>
        <td colspan="3"><?php echo html::textarea('problemRecord', '', "rows='5' class='form-control' placeholder='{$lang->reviewbyproduct->problemTrackingAB}'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->solveMethod;?></th>
        <td colspan="3"><?php echo html::textarea('solveMethod', '', "rows='5' class='form-control' placeholder='{$lang->reviewbyproduct->solveMethodAB}'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->resolvedDate;?></th>
        <td colspan="2">
          <?php echo html::input('resolvedDate', helper::today(), "class='form-control form-date';");?>
        </td>
        <td width="330">
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->reviewbyproduct->solved;?></span>
            <?php echo html::select('solved', $lang->reviewbyproduct->solvedList, '', 'class="form-control"');?>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->comment;?></th>
        <td colspan="3"><?php echo html::textarea('comment', '', "rows='4' class='form-control' placeholder='{$lang->reviewbyproduct->commentAB}'");?></td>
      </tr>
      <tr><td></td>
        <td colspan="3">
          <?php
          $actionLink = $this->createLink('reviewbyproduct', 'create', "projectID=$projectID&type=draft");
          echo html::submitButton($lang->reviewbyproduct->draft, "onclick=\"setFormAction('$actionLink')\"") . html::submitButton($lang->reviewbyproduct->finished) . html::backButton();
          ?>
          <sapn class='alert-danger'><?php echo $lang->reviewbyproduct->danger;?></sapn>
        </td></tr>
    </table>
  </form>
</div>
<?php include '../../common/view/footer.html.php';?>
