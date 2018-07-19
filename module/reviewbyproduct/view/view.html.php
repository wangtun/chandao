<?php
/**
 * The view file of reviewbyproduct module of ZenTaoPMS.
 *
 * @copyright   RabbitDog
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      RabbitDog
 * @package     reviewbyproduct
 * @version     20171214
 */
?>
<?php include '../../common/view/header.html.php';?>

<?php if(isonlybody()):?>
<style>
tbody tr td:last-child a{display:none;}
tbody tr td:first-child input{display:none;}
tfoot tr td .table-actions .btn{display:none;}
#titlebar .actions{display:none}
.row-table .col-side{display:none;}
</style>
<?php endif;?>
<div id='titlebar'>
  <div class='heading'>
    <span class='prefix'><?php echo html::icon($lang->icons['reviewbyproduct']);?> <strong><?php echo $reviewbyproduct->id;?></strong></span>
    <strong><?php echo $reviewbyproduct->title;?></strong>
    <?php if($reviewbyproduct->deleted):?>
    <span class='label label-danger'><?php echo $lang->reviewbyproduct->deleted;?></span>
    <?php endif; ?>
  </div>
  <div class='actions'>
  <?php
  $browseLink = $this->session->storyreviewList ? $this->session->storyreviewList : $this->createLink('reviewbyproduct', 'reviewbyproduct', "objectID=$objectID&from=$from");
  if(!$reviewbyproduct->deleted)
  {
    echo "<div class='btn-group'>";
    common::printIcon('reviewbyproduct', 'edit',   "storyReviewID=$reviewbyproduct->id&objectID=$objectID&from=$from", '','list', 'pencil', '', '', false, '', $lang->reviewbyproduct->edit);
    echo '</div>';
    echo "<div class='btn-group'>";
    common::printIcon('reviewbyproduct', 'resolve', "storyReviewID=$reviewbyproduct->id", $reviewbyproduct, 'list', '', '', 'iframe', true);
    echo '</div>';

    if ($from == 'project')
    {
      echo "<div class='btn-group'>";

      if(common::hasPriv('reviewbyproduct', 'delete'))
      {
        common::printIcon('reviewbyproduct', 'delete', "storyReviewID=$reviewbyproduct->id", '', 'list', 'remove', 'hiddenwin');
      }

      echo '</div>';
    }
  }
  echo common::printRPN($browseLink);
  ?>
  </div>
</div>
<div class='row-table'>
  <div class='col-main'>
    <div class='main'>
      <div class='tabs'>
        <fieldset>
          <legend><?php echo $lang->reviewbyproduct->problemRecord;?></legend>
          <div class='article-content'><?php echo $reviewbyproduct->problemRecord;?></div>
        </fieldset>
        <fieldset>
          <legend><?php echo $lang->reviewbyproduct->solveMethod;?></legend>
          <div class='article-content'><?php echo $reviewbyproduct->solveMethod;?></div>
        </fieldset>
        <fieldset>
          <legend><?php echo $lang->reviewbyproduct->comment;?></legend>
          <div class='article-content'><?php echo $reviewbyproduct->comment;?></div>
        </fieldset>
        <?php include '../../common/view/action.html.php';?>
      </div>
    </div>
  </div>
  <div class='col-side'>
    <div class='main-side main'>
      <fieldset>
        <legend><?php echo $lang->reviewbyproduct->basicInfo;?></legend>
        <table class='table table-data table-condensed table-borderless'>
          <tr>
            <th class='w-80px'><?php echo $lang->reviewbyproduct->product;?></th>
            <td><?php echo $reviewbyproduct->productName;?></td>
          </tr>
          <tr>
            <th class='w-80px'><?php echo $lang->reviewbyproduct->project;?></th>
            <td><?php echo $reviewbyproduct->projectName;?></td>
          </tr>
          <tr>
            <th><?php echo $lang->reviewbyproduct->storyReviewers;?></th>
            <td><?php $storyReviewers = explode(',', str_replace(' ', '', $reviewbyproduct->storyReviewers)); foreach($storyReviewers as $account) echo ' ' . zget($users, $account, $account);?></td>
          </tr>
          <tr>
            <th><?php echo $lang->reviewbyproduct->devReviewers;?></th>
            <td><?php $devReviewers = explode(',', str_replace(' ', '', $reviewbyproduct->devReviewers)); foreach($devReviewers as $account) echo ' ' . zget($users, $account, $account);?></td>
          </tr>
          <tr>
            <th><?php echo $lang->reviewbyproduct->testReviewers;?></th>
            <td><?php $testReviewers = explode(',', str_replace(' ', '', $reviewbyproduct->testReviewers)); foreach($testReviewers as $account) echo ' ' . zget($users, $account, $account);?></td>
          </tr>
          <tr>
            <th><?php echo $lang->reviewbyproduct->reviewDate;?></th>
            <td><?php echo $reviewbyproduct->reviewDate;?></td>
          </tr>
          <tr>
            <th><?php echo $lang->reviewbyproduct->mailto;?></th>
            <td><?php $mailto = explode(',', str_replace(' ', '', $reviewbyproduct->mailto)); foreach($mailto as $account) echo ' ' . zget($users, $account, $account);?></td>
          </tr>
        </table>
      </fieldset>
      <div class='main-side main'>
        <fieldset>
          <legend><?php echo $lang->reviewbyproduct->reviewStories;?></legend>
            <ul class='list-unstyled'>
              <?php
              if (!empty($reviewbyproduct->reviewStories))
              {
                foreach ($reviewbyproduct->reviewStories as $id =>$reviewStory)
                {
                  echo '<li>' . html::a($this->createLink('story', 'view', "storyID=$id"), "#$id " . $reviewStory) . '</li>';
                }
              }
              ?>
            </ul>
        </fieldset>
    </div>
  </div>
</div>
<?php include '../../common/view/footer.html.php';?>
