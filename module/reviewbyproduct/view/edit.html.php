<?php
/**
 * The edit view of reviewbyproduct module of ZenTaoPMS.
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
      <strong><small class='text-muted'><?php echo html::icon($lang->icons['edit']);?></small> <?php echo $lang->reviewbyproduct->edit;?></strong>
    </div>
  </div>
  <form class='form-condensed' method='post' target='hiddenwin' id='dataform' enctype='multipart/form-data'>
    <table class='table table-form'>
      <?php if ($from == 'project'):?>
      <tr>
        <th class='w-80px'><?php echo $lang->reviewbyproduct->project;?></th>
        <td colspan="3"><?php echo html::select('project', $projects, $reviewbyproduct->project, 'class="form-control chosen"');?></td>
      </tr>
      <?php endif;?>
      <tr>
        <th><?php echo $lang->reviewbyproduct->title;?></th>
        <td colspan="3"><?php echo html::input('title', $reviewbyproduct->title, "class='form-control' autocomplete='off' placeholder='{$lang->reviewbyproduct->reviewTitle}'");?></td>
      </tr>
      <?php if ($from == 'project'):?>
      <tr>
        <th><?php echo $lang->reviewbyproduct->reviewStories;?></th>
        <td colspan="3">
          <?php
          echo html::select('reviewStories[]', $stories, str_replace(' ' , '', $reviewbyproduct->reviewStories), 'multiple class="form-control chosen"');
          ?>
        </td>
      </tr>
      <?php endif;?>
      <tr>
        <th><?php echo $lang->reviewbyproduct->storyReviewers;?></th>
        <td><?php echo html::select('storyReviewers[]', $users, str_replace(' ' , '', $reviewbyproduct->storyReviewers), 'multiple class="form-control chosen"');?></td>
        <td width="330">
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->reviewbyproduct->devReviewers;?></span>
            <?php echo html::select('devReviewers[]', $users, str_replace(' ' , '', $reviewbyproduct->devReviewers), "multiple class='form-control chosen'");?>
          </div>
        </td>
        <td width="330">
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->reviewbyproduct->testReviewers;?></span>
            <?php echo html::select('testReviewers[]', $users, str_replace(' ' , '', $reviewbyproduct->testReviewers), "multiple class='form-control chosen'");?>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->reviewDate;?></th>
        <td>
           <?php echo html::input('reviewDate', helper::today(), "class='form-control form-date';");?>
        </td>
        <td  colspan="2">
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->reviewbyproduct->mailto;?></span>
            <?php echo html::select('mailto[]', $users, str_replace(' ' , '', $reviewbyproduct->mailto), 'multiple class="form-control chosen"');?>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->problemRecord;?></th>
        <td colspan="3"><?php echo html::textarea('problemRecord',  $reviewbyproduct->problemRecord, "rows='4' class='form-control'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->resolvedDate;?></th>
        <td colspan="2">
          <?php echo html::input('resolvedDate', $reviewbyproduct->resolvedDate, "class='form-control form-date'");?>
        </td>
        <td  width="330">
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->reviewbyproduct->solved;?></span>
            <?php echo html::select('solved', $lang->reviewbyproduct->solvedList, $reviewbyproduct->solved, 'class="form-control"');?>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->solveMethod;?></th>
        <td colspan="3"><?php echo html::textarea('solveMethod',  $reviewbyproduct->solveMethod, "rows='5' class='form-control' placeholder='{$lang->reviewbyproduct->solveMethodAB}'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->reviewbyproduct->comment;?></th>
        <td colspan="3"><?php echo html::textarea('comment', $reviewbyproduct->comment, "rows='4' class='form-control' placeholder='{$lang->reviewbyproduct->commentAB}'");?></td>
      </tr>
      <tr><td></td>
        <td colspan="3">
          <?php
          if ($from == 'project')
          {
            $actionLink = $this->createLink('reviewbyproduct', 'edit', "storyReviewID=$reviewbyproduct->id&objectID=$objectID&from=$from&type=draft");
            echo html::submitButton($lang->reviewbyproduct->draft, "onclick=\"setFormAction('$actionLink')\"") . html::submitButton($lang->reviewbyproduct->finished) . html::backButton();
          }
          else
          {
            echo html::submitButton() . html::backButton();
          }
          ?>
           <sapn class='alert-danger'><?php echo $lang->reviewbyproduct->danger;?></sapn>
        </td>
      </tr>
      <tr><td colspan="4"><div class='alert alert-info'><?php echo $lang->reviewbyproduct->notice?></div></td></tr>
    </table>
  </form>
</div>
<?php include '../../common/view/footer.html.php';?>
