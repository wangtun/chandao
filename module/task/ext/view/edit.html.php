<?php
/**
 * The edit view of task module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     task
 * @version     $Id: edit.html.php 4897 2013-06-26 01:13:16Z wyd621@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include '../../../common/view/header.html.php';?>
<?php include '../../../common/view/datepicker.html.php';?>
<?php include '../../../common/view/kindeditor.html.php';?>
<?php js::set('oldStoryID', $task->story); ?>
<?php js::set('oldAssignedTo', $task->assignedTo); ?>
<?php js::set('oldProjectID', $task->project); ?>
<?php js::set('confirmChangeProject', $lang->task->confirmChangeProject); ?>
<?php js::set('changeProjectConfirmed', false); ?>
<form class='form-condensed' method='post' enctype='multipart/form-data' target='hiddenwin' id='dataform'>
<div id='titlebar'>
  <div class='heading'>
    <span class='prefix'><?php echo html::icon($lang->icons['task']);?> <strong><?php echo $task->id;?></strong></span>
    <strong><?php echo html::a($this->createLink('task', 'view', "taskID=$task->id"), $task->name, '', "class='task-title'");?></strong>
    <small><?php echo html::icon($lang->icons['edit']) . ' ' . $lang->task->edit;?></small>
  </div>
  <div class='actions'>
    <?php echo html::submitButton($lang->save)?>
  </div>
</div>
<div class='row-table'>
  <div class='col-main'>
    <div class='main'>
      <div class='form-group'>
        <div class='input-group'>
          <input type='hidden' id='color' name='color' data-provide='colorpicker' data-wrapper='input-group-btn fix-border-right' data-pull-menu-right='false' data-btn-tip='<?php echo $lang->task->colorTag ?>' value='<?php echo $task->color ?>' data-update-text='.task-title, #name'>
          <?php echo html::input('name', $task->name, 'class="form-control" autocomplete="off" placeholder="' . $lang->task->name . '"');?>
          <?php if(empty($task->children) and empty($task->parent) and $task->type != 'affair'):?>
            <span class='input-group-addon'>
            <label class='checkbox-inline'>
              <input type='checkBox' name='multiple' value='1' <?php echo empty($task->team) ? '' : 'checked';?> />
              <?php echo $lang->task->multipleAB;?>
            </label>
          </span>
          <?php endif;?>
        </div>
      </div>
      <?php if ($task->source == 'QA'):?>
        <fieldset class='fieldset-pure'>
          <legend><?php echo $lang->task->noItem;?></legend>
          <table class='table table-form table-fixed with-border'>
            <thead>
            <tr class='text-center'>
              <th class='w-20px'><?php echo $lang->task->auditID; ?><i class="required required-wrapper"></i></th>
              <th class='w-80px'><?php echo $lang->task->noDec; ?><i class="required required-wrapper"></i></th>
              <th class='w-40px'><?php echo $lang->task->noType; ?><i class="required required-wrapper"></i></th>
              <th class='w-50px'><?php echo $lang->task->serious; ?><i class="required required-wrapper"></i></th>
              <th class='w-80px'><?php echo $lang->task->cause; ?><i class="required required-wrapper"></i></th>
              <th class='w-80px'><?php echo $lang->task->measures; ?><i class="required required-wrapper"></i></th>
              <th class='w-20px'><?php echo $lang->task->add; ?></th>
              <th class='w-20px'><?php echo $lang->task->del; ?></th>
            </tr>
            </thead>
            <?php if($auditDetails == array()):?>
              <tr class='text-center'>
                <td>
                  <?php echo html::input('auditID[]', '', "class='form-control'"); ?>
                  <?php echo html::hidden('aid[]');?>
                </td>
                <td><?php echo html::textarea('noDec[]', '', "class=form-control rows= '1'"); ?></td>
                <td><?php echo html::select('noType[]', $lang->task->noTypeList, $noType, "class='form-control' id='noType[]'"); ?></td>
                <td><?php echo html::select('serious[]', $lang->task->seriousList, '', "class='form-control' id='serious[]'"); ?></td>
                <td><?php echo html::textarea('cause[]', '', "class=form-control rows= '1'"); ?></td>
                <td><?php echo html::textarea('measures[]', '', "class=form-control rows= '1'"); ?></td>
                <td><a href='javascript:;' class='add'><i class='icon-plus'></i></a></td>
                <td><a href='javascript:;' class='delAudit'><i class='icon icon-remove'></i></a></td>
              </tr>
            <?php else:?>
              <?php foreach($auditDetails as $auditDetail):?>
                <tr class='text-center'>
                  <td class='audit'>
                    <?php echo html::input('auditID[]', $auditDetail->auditID, "class='form-control'"); ?>
                    <?php echo html::hidden('aid[]',$auditDetail->id);?>
                  </td>
                  <td class='audit'><?php echo html::textarea('noDec[]', $auditDetail->noDec, "class=form-control rows= '1'"); ?></td>
                  <td class='audit'><?php echo html::select('noType[]', $lang->task->noTypeList, $auditDetail->noType, "class='form-control' id='noType[]'"); ?></td>
                  <td class='audit'><?php echo html::select('serious[]', $lang->task->seriousList, $auditDetail->serious, "class='form-control' id='serious[]'"); ?></td>
                  <td class='audit'><?php echo html::textarea('cause[]', $auditDetail->cause, "class=form-control rows= '1'"); ?></td>
                  <td class='audit'><?php echo html::textarea('measures[]', $auditDetail->measures, "class=form-control rows= '1'"); ?></td>
                  <td><a href='javascript:;' class='add'><i class='icon-plus'></i></a></td>
                  <td><a href='javascript:;' class='delAudit'><i class='icon icon-remove'></i></a></td>
                </tr>
              <?php endforeach;?>
            <?php endif;?>
          </table>
        </fieldset>
      <?php endif;?>

      <?php if ($task->type == 'script'): ?>
      <fieldset class='fieldset-pure'>
        <legend><?php echo $lang->task->script;?></legend>
        <table class='table table-form'>
          <tr>
            <th width="60"><?php echo $lang->task->scriptName;?></th>
            <td colspan="2">
              <?php echo html::input('scriptName', $script->scriptName, "class='form-control'");?>
              <?php echo html::hidden('scriptID', $script->id);?>
            </td>
            <td width="400">
              <div class="input-group">
                <span class='input-group-addon'><?php echo $lang->task->scriptType;?></span>
                <?php echo html::select('scriptType', $lang->task->scriptTypeList, $script->scriptType, 'class=form-control');?>
                <span class='input-group-addon fix-border br-0'><?php echo $lang->task->lob;?></span>
                <?php echo html::select('lob', $lang->task->lobList, $script->lob, 'class=form-control');?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->frequency;?></th>
            <td colspan="2"><?php echo html::input('frequency', $script->frequency, "class='form-control'");?></td>
            <td>
              <div class="input-group">
                <span class='input-group-addon'><?php echo $lang->task->configurationFile;?></span>
                <?php echo html::input('configurationFile', $script->configurationFile, "class='form-control'");?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->output;?></th>
            <td colspan="2"><?php echo html::input('output', $script->output, "class='form-control'");?></td>
            <td>
              <div class="input-group">
                <span class='input-group-addon'><?php echo $lang->task->precondition;?></span>
                <?php echo html::input('precondition', $script->precondition, "class='form-control'");?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->performMode;?></th>
            <td><?php echo html::select('performMode', $lang->task->performModeList, $script->performMode, 'class=form-control');?></td>
            <td width="200">
              <div class="input-group">
                <span class='input-group-addon'><?php echo $lang->task->performSystem;?></span>
                <?php echo html::select('performSystem', $lang->task->performSystemList, $script->performSystem, 'class=form-control');?>
              </div>
            </td>
            <td>
              <div class="input-group">
                <span class='input-group-addon'><?php echo $lang->task->performBody;?></span>
                <?php echo html::input('performBody', $script->performBody, "class='form-control'");?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->scriptPath;?></th>
            <td colspan="3"><?php echo html::input('scriptPath', $script->scriptPath, "class='form-control'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->notice;?></th>
            <td colspan="3"><?php echo html::textarea('notice', $script->notice, "rows='6' class='form-control'");?></td>
          </tr>
        </table>
      </fieldset>
      <?php endif;?>

      <!--bugxiugai-->
      <?php /*if($task->type == 'review' && $task->status == 'done'):*/?>
      <fieldset class='fieldset-pure create' style="display:none;">
        <legend><?php echo $lang->task->review;?></legend>
        <table class='table table-form'>
          <tr>
            <th><?php echo $lang->task->fileNO;?></th>
            <td>
              <?php echo html::input('fileNO', '', "class='form-control minw-330px'");?>
              <?php echo html::hidden('reviewID', '');?>
            </td>
            <td colspan="2">
              <div id='mailtoGroup' class='input-group'>
                <span class="input-group-addon"><?php echo $lang->task->recorder;?></span>
                <?php echo html::input('recorder', '', "class='form-control minw-330px'");?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->reviewName;?></th>
            <td><?php echo html::input('reviewName', '', "class='form-control minw-60px'");?></td>
            <td colspan="2">
              <div id='mailtoGroup' class='input-group'>
                <span class="input-group-addon"><?php echo $lang->task->reviewDate;?></span>
                <?php echo html::input('reviewDate', '', "class='form-control form-date'");?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->doc;?></th>
            <td><?php echo html::input('doc', '', "class='form-control minw-60px'");?></td>
            <td colspan="2">
              <div id='mailtoGroup' class='input-group'>
                <span class="input-group-addon"><?php echo $lang->task->reviewScope;?></span>
                <?php echo html::input('reviewScope', '', "class='form-control minw-60px'");?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->referenceDoc;?></th>
            <td><?php echo html::input('referenceDoc', '', "class='form-control minw-60px'");?></td>
            <td colspan="2">
              <div id='mailtoGroup' class='input-group'>
                <span class="input-group-addon"><?php echo $lang->task->reviewPlace;?></span>
                <?php echo html::input('reviewPlace', '', "class='form-control minw-60px'");?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->reference;?></th>
            <td><?php echo html::input('reference', '', "class='form-control minw-60px'");?></td>
            <td colspan="2">
              <div id='mailtoGroup' class='input-group'>
                <span class="input-group-addon"><?php echo $lang->task->effort;?></span>
                <?php echo html::input('effort', '', "class='form-control minw-60px' placeholder='{$lang->task->minute}'");?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->pages;?></th>
            <td><?php echo html::input('pages', '', "class='form-control minw-60px'");?></td>
            <td colspan="2">
              <div id='mailtoGroup' class='input-group'>
                <span class="input-group-addon"><?php echo $lang->task->conclusion;?></span>
                <?php echo html::select('conclusion', $lang->task->conclusionList, '', 'class=form-control');?>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->reviewers;?></th>
            <td colspan="2"><?php echo html::input('reviewers', '', "class='form-control minw-60px'");?></td>
          </tr>
        </table>
      </fieldset>
      <fieldset class='fieldset-pure create' style="display:none;">
        <legend><?php echo $lang->task->problem;?></legend>
        <table class='table table-form table-fixed with-border'>
          <thead>
          <tr class='text-center'>
            <th class='w-20px'><?php echo $lang->task->number;?></th>
            <th class='w-30px'><?php echo $lang->task->reviewer;?></th>
            <th class='w-30px'><?php echo $lang->task->item;?></th>
            <th class='w-20px'><?php echo $lang->task->line;?></th>
            <th class='w-20px'><?php echo $lang->task->severity;?></th>
            <th class='w-50px'><?php echo $lang->task->description;?></th>
            <th class='w-50px'><?php echo $lang->task->proposal;?></th>
            <th class='w-20px'><?php echo $lang->task->changed;?></th>
            <th class='w-50px'><?php echo $lang->task->action;?></th>
            <th class='w-20px'><?php echo $lang->task->chkd;?></th>
            <th class='w-20px'><?php echo $lang->task->add;?></th>
            <th class='w-20px'><?php echo $lang->task->del;?></th>
          </tr>
          </thead>
          <tr class='text-center'>
            <td>
              <?php echo html::hidden('id[]', '', "class='form-control'");?>
              <?php echo html::input('number[]', '', "class='form-control'");?>
            </td>
            <td><?php echo html::input('reviewer[]', '', "class='form-control'");?></td>
            <td><?php echo html::input('item[]', '', "class='form-control'");?></td>
            <td><?php echo html::input('line[]', '', "class='form-control'");?></td>
            <td><?php echo html::select('severity[]', $lang->task->severityList, '', 'class=form-control');?></td>
            <td><?php echo html::textarea('description[]', '', "class='form-control' rows= '1'");?></td>
            <td><?php echo html::textarea('proposal[]', '', "class='form-control' rows= '1'");?></td>
            <td><?php echo html::select('changed[]', $lang->task->changedList, '', 'class=form-control');?></td>
            <td><?php echo html::textarea('action[]', '', "class='form-control' rows= '1'");?></td>
            <td><?php echo html::select('chkd[]', $lang->task->chkdList, '', 'class=form-control');?></td>
            <td><a href='javascript:;' class='add'><i class='icon-plus'></i></a></td>
            <td><a href='javascript:;' class='del'><i class='icon icon-remove'></i></a></td>
          </tr>
        </table>
      </fieldset>
      <?php /*endif;*/?>

      <!--新改-->
      <?php if($task->type == 'review' && $task->status == 'done'):?>
        <fieldset class='fieldset-pure'>
          <legend><?php echo $lang->task->review;?></legend>
          <table class='table table-form'>
            <tr>
              <th><?php echo $lang->task->fileNO;?></th>
              <td>
                <?php echo html::input('fileNO', $review->fileNO, "class='form-control minw-330px'");?>
                <?php echo html::hidden('reviewID', $review->id);?>
              </td>
              <td colspan="2">
                <div id='mailtoGroup' class='input-group'>
                  <span class="input-group-addon"><?php echo $lang->task->recorder;?></span>
                  <?php echo html::input('recorder', $review->recorder, "class='form-control minw-330px'");?>
                </div>
              </td>
            </tr>
            <tr>
              <th><?php echo $lang->task->reviewName;?></th>
              <td><?php echo html::input('reviewName', $review->reviewName, "class='form-control minw-60px'");?></td>
              <td colspan="2">
                <div id='mailtoGroup' class='input-group'>
                  <span class="input-group-addon"><?php echo $lang->task->reviewDate;?></span>
                  <?php echo html::input('reviewDate', $review->reviewDate, "class='form-control form-date'");?>
                </div>
              </td>
            </tr>
            <tr>
              <th><?php echo $lang->task->doc;?></th>
              <td><?php echo html::input('doc', $review->doc, "class='form-control minw-60px'");?></td>
              <td colspan="2">
                <div id='mailtoGroup' class='input-group'>
                  <span class="input-group-addon"><?php echo $lang->task->reviewScope;?></span>
                  <?php echo html::input('reviewScope', $review->reviewScope, "class='form-control minw-60px'");?>
                </div>
              </td>
            </tr>
            <tr>
              <th><?php echo $lang->task->referenceDoc;?></th>
              <td><?php echo html::input('referenceDoc', $review->referenceDoc, "class='form-control minw-60px'");?></td>
              <td colspan="2">
                <div id='mailtoGroup' class='input-group'>
                  <span class="input-group-addon"><?php echo $lang->task->reviewPlace;?></span>
                  <?php echo html::input('reviewPlace', $review->reviewPlace, "class='form-control minw-60px'");?>
                </div>
              </td>
            </tr>
            <tr>
              <th><?php echo $lang->task->reference;?></th>
              <td><?php echo html::input('reference', $review->reference, "class='form-control minw-60px'");?></td>
              <td colspan="2">
                <div id='mailtoGroup' class='input-group'>
                  <span class="input-group-addon"><?php echo $lang->task->effort;?></span>
                  <?php echo html::input('effort', $review->effort, "class='form-control minw-60px' placeholder='{$lang->task->minute}'");?>
                </div>
              </td>
            </tr>
            <tr>
              <th><?php echo $lang->task->pages;?></th>
              <td><?php echo html::input('pages', $review->pages, "class='form-control minw-60px'");?></td>
              <td colspan="2">
                <div id='mailtoGroup' class='input-group'>
                  <span class="input-group-addon"><?php echo $lang->task->conclusion;?></span>
                  <?php echo html::select('conclusion', $lang->task->conclusionList, $review->conclusion, 'class=form-control');?>
                </div>
              </td>
            </tr>
            <tr>
              <th><?php echo $lang->task->reviewers;?></th>
              <td colspan="2"><?php echo html::input('reviewers', $review->reviewers, "class='form-control minw-60px'");?></td>
            </tr>
          </table>
        </fieldset>
        <fieldset class='fieldset-pure'>
          <legend><?php echo $lang->task->problem;?></legend>
          <table class='table table-form table-fixed with-border'>
            <thead>
              <tr class='text-center'>
                <th class='w-20px'><?php echo $lang->task->number;?></th>
                <th class='w-30px'><?php echo $lang->task->reviewer;?></th>
                <th class='w-30px'><?php echo $lang->task->item;?></th>
                <th class='w-20px'><?php echo $lang->task->line;?></th>
                <th class='w-20px'><?php echo $lang->task->severity;?></th>
                <th class='w-50px'><?php echo $lang->task->description;?></th>
                <th class='w-50px'><?php echo $lang->task->proposal;?></th>
                <th class='w-20px'><?php echo $lang->task->changed;?></th>
                <th class='w-50px'><?php echo $lang->task->action;?></th>
                <th class='w-20px'><?php echo $lang->task->chkd;?></th>
                <th class='w-20px'><?php echo $lang->task->add;?></th>
                <th class='w-20px'><?php echo $lang->task->del;?></th>
              </tr>
            </thead>
            <?php if($reviewDetails == array()):?>
              <tr class='text-center'>
                <td>
                  <?php echo html::hidden('id[]', '', "class='form-control'");?>
                  <?php echo html::input('number[]', '', "class='form-control'");?>
                </td>
                <td><?php echo html::input('reviewer[]', '', "class='form-control'");?></td>
                <td><?php echo html::input('item[]', '', "class='form-control'");?></td>
                <td><?php echo html::input('line[]', '', "class='form-control'");?></td>
                <td><?php echo html::select('severity[]', $lang->task->severityList, '', 'class=form-control');?></td>
                <td><?php echo html::textarea('description[]', '', "class='form-control' rows= '1'");?></td>
                <td><?php echo html::textarea('proposal[]', '', "class='form-control' rows= '1'");?></td>
                <td><?php echo html::select('changed[]', $lang->task->changedList, '', 'class=form-control');?></td>
                <td><?php echo html::textarea('action[]', '', "class='form-control' rows= '1'");?></td>
                <td><?php echo html::select('chkd[]', $lang->task->chkdList, '', 'class=form-control');?></td>
                <td><a href='javascript:;' class='add'><i class='icon-plus'></i></a></td>
                <td><a href='javascript:;' class='del'><i class='icon icon-remove'></i></a></td>
              </tr>
            <?php else:?>
              <?php foreach($reviewDetails as $reviewDetail):?>
                <tr class='text-center'>
                  <td>
                    <?php echo html::hidden('id[]', $reviewDetail->id, "class='form-control'");?>
                    <?php echo html::input('number[]', $reviewDetail->number, "class='form-control'");?>
                  </td>
                  <td><?php echo html::input('reviewer[]', $reviewDetail->reviewer, "class='form-control'");?></td>
                  <td><?php echo html::input('item[]', $reviewDetail->item, "class='form-control'");?></td>
                  <td><?php echo html::input('line[]', $reviewDetail->line, "class='form-control'");?></td>
                  <td><?php echo html::select('severity[]', $lang->task->severityList, $reviewDetail->severity, 'class=form-control');?></td>
                  <td><?php echo html::textarea('description[]', $reviewDetail->description, "class='form-control' rows= '1'");?></td>
                  <td><?php echo html::textarea('proposal[]', $reviewDetail->proposal, "class='form-control' rows= '1'");?></td>
                  <td><?php echo html::select('changed[]', $lang->task->changedList, $reviewDetail->changed, 'class=form-control');?></td>
                  <td><?php echo html::textarea('action[]', $reviewDetail->action, "class='form-control' rows= '1'");?></td>
                  <td><?php echo html::select('chkd[]', $lang->task->chkdList, $reviewDetail->chkd, 'class=form-control');?></td>
                  <td><a href='javascript:;' class='add'><i class='icon-plus'></i></a></td>
                  <td><a href='javascript:;' class='del'><i class='icon icon-remove'></i></a></td>
                </tr>
              <?php endforeach;?>
            <?php endif;?>
          </table>
        </fieldset>
      <?php endif;?>

      <fieldset class='fieldset-pure'>
        <legend><?php echo $lang->files;?></legend>
        <div class='form-group'><?php echo $this->fetch('file', 'buildform');?></div>
      </fieldset>
      <fieldset class='fieldset-pure'>
        <legend><?php echo $lang->task->desc;?></legend>
        <div class='form-group'>
          <?php echo html::textarea('desc', htmlspecialchars($task->desc), "rows='8' class='form-control'");?>
        </div>
      </fieldset>
      <fieldset class='fieldset-pure'>
        <legend><?php echo $lang->comment;?></legend>
        <div class='form-group'><?php echo html::textarea('comment', '',  "rows='5' class='form-control'");?></div>
      </fieldset>

      <div class='actions actions-form'>
        <?php echo html::hidden('lastEditedDate', $task->lastEditedDate);?>
        <?php echo html::submitButton($lang->save) .html::linkButton($lang->goback, $this->inlink('view', "taskID=$task->id")) .html::hidden('consumed', $task->consumed);?>
      </div>
      <?php include '../../../common/view/action.html.php';?>
    </div>
  </div>
  <div class='col-side'>
    <div class='main main-side'>
      <fieldset>
        <legend><?php echo $lang->task->legendBasic;?></legend>
        <table class='table table-form'>
          <?php /*if(empty($task->parent)):*/?>
          <tr>
            <th class='w-80px'><?php echo $lang->task->project;?></th>
            <td><?php echo html::select('project', $projects, $task->project, 'class="form-control chosen" onchange="loadAll(this.value)"');?></td>
          </tr>
          <?php /*endif;*/?>
          <tr>
            <th class='w-80px'><?php echo $lang->task->module;?></th>
            <td id="moduleIdBox"><?php echo html::select('module', $modules, $task->module, 'class="form-control chosen" onchange="loadModuleRelated()"');?></td>
          </tr>
          <?php if($config->global->flow != 'onlyTask' and $project->type != 'ops'):?>
          <tr>
            <th><?php echo $lang->task->story;?></th>
            <td><span id="storyIdBox"><?php echo html::select('story', $stories, $task->story, "class='form-control chosen'");?></span></td>
          </tr>
          <?php endif;?>
          <tr>
            <th><?php echo $lang->task->assignedTo;?></th>
            <td><span id="assignedToIdBox"><?php echo html::select('assignedTo', $members, $task->assignedTo, "class='form-control chosen'");?></span></td> 
          </tr>
          <tr class='<?php echo empty($task->team) ? 'hidden' : ''?>' id='teamTr'>
            <th><?php echo $lang->task->team;?></th>
            <td><?php echo html::a('javascript:;', $lang->task->team, '', "class='form-control btn' data-toggle='modalTeam'");?></td>
          </tr>
          <!--禅道任务增加关键字字段；需求：858；行号：384-388-->
          <tr>
            <th><?php echo $lang->task->keywords;?></th>
            <td><?php echo html::input('keywords', $task->keywords, 'class="form-control"');?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->type;?></th>
            <td><?php echo html::select('type', $lang->task->typeList, $task->type, "class='form-control chosen'");?></td>
          </tr>
          <?php if(empty($task->children)):?>
          <tr>
            <th><?php echo $lang->task->status;?></th>
            <td><?php echo html::select('status', (array)$lang->task->statusList, $task->status, "class='form-control chosen'");?></td>
          </tr>
          <?php endif;?>
          <tr>
            <th><?php echo $lang->task->pri;?></th>
            <td><?php echo html::select('pri', $lang->task->priList, $task->pri, "class='form-control chosen'");?> </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->mailto;?></th>
            <td>
              <?php echo html::select('mailto[]', $project->acl == 'private' ? $members : $users, str_replace(' ' , '', $task->mailto), 'class="form-control" multiple');?>
              <?php echo $this->fetch('my', 'buildContactLists');?>
            </td>
          </tr>
        </table>
      </fieldset>
      <fieldset>
        <legend><?php echo $lang->task->legendEffort;?></legend>
        <table class='table table-form'>
          <tr>
            <th class='w-70px'><?php echo $lang->task->estStarted;?></th>
            <td><?php echo html::input('estStarted', $task->estStarted, "class='form-control form-date'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->realStarted;?></th>
            <td><?php echo html::input('realStarted', $task->realStarted, "class='form-control form-date'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->deadline;?></th>
            <td><?php echo html::input('deadline', $task->deadline, "class='form-control form-date'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->estimate;?></th>
            <td>
              <?php $disabled = !empty($task->team) ? "disabled='disabled'" : '';?>
              <?php echo html::input('estimate', $task->estimate, "class='form-control' autocomplete='off' {$disabled}");?>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->task->consumed;?></th>
            <td><?php echo $task->consumed . ' '; common::printIcon('task', 'recordEstimate',   "taskID=$task->id", $task, 'list', '', '', 'iframe', true);?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->left;?></th>
            <td>
              <?php $disabled = !empty($task->team) ? "disabled='disabled'" : '';?>
              <?php echo html::input('left', $task->left, "class='form-control' autocomplete='off' {$disabled}");?>
            </td>
          </tr>
        </table>
      </fieldset>
      <fieldset>
        <legend><?php echo $lang->task->legendLife;?></legend>
        <table class='table table-form'>
          <tr>
            <th class='w-70px'><?php echo $lang->task->openedBy;?></th>
            <td><?php echo $users[$task->openedBy];?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->finishedBy;?></th>
            <td><?php echo html::select('finishedBy', $users, $task->finishedBy, "class='form-control chosen'");?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->finishedDate;?></th>
            <td><?php echo html::input('finishedDate', $task->finishedDate, 'class="form-control form-date"');?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->canceledBy;?></th>
            <td><?php echo html::select('canceledBy', $users, $task->canceledBy, 'class="form-control chosen"');?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->canceledDate;?></th>
            <td><?php echo html::input('canceledDate', $task->canceledDate, 'class="form-control form-date"');?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->closedBy;?></th>
            <td><?php echo html::select('closedBy', $users, $task->closedBy, 'class="form-control chosen"');?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->closedReason;?></th>
            <td><?php echo html::select('closedReason', $lang->task->reasonList, $task->closedReason, 'class="form-control chosen"');?></td>
          </tr>
          <tr>
            <th><?php echo $lang->task->closedDate;?></th>
            <td><?php echo html::input('closedDate', $task->closedDate, 'class="form-control form-date"');?></td>
          </tr>
        </table>
      </fieldset>
    </div>
  </div>
</div>
  <div class="modal fade modal-team" id="modalTeam">
    <div class="modal-dialog" style='width: 700px'>
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">
          <span aria-hidden="true">×</span><span class="sr-only"><?php echo $lang->task->close;?></span>
        </button>
        <h4 class="modal-title"><?php echo $lang->task->team?></h4>
      </div>
      <div class="modal-content">
        <table class='table table-form'>
          <?php foreach($task->team as $member):?>
            <tr>
              <td class='w-80px'><?php echo html::select("team[]", $members, $member->account, "class='form-control chosen'")?></td>
              <td>
                <div class='input-group'>
                  <span class='input-group-addon'><?php echo $lang->task->estimate?></span>
                  <?php echo html::input("teamEstimate[]", $member->estimate, "class='form-control text-center' placeholder='{$lang->task->hour}'")?>
                  <span class='input-group-addon fix-border'><?php echo $lang->task->consumed?></span>
                  <?php echo html::input("teamConsumed[]", $member->consumed, "class='form-control text-center' placeholder='{$lang->task->hour}'")?>
                  <span class='input-group-addon fix-border'><?php echo $lang->task->left?></span>
                  <?php echo html::input("teamLeft[]", $member->left, "class='form-control text-center' placeholder='{$lang->task->hour}'")?>
                </div>
              </td>
              <td class='w-90px'>
                <a href='javascript:;' class='btn btn-move-up btn-sm'><i class='icon-arrow-up'></i></a>
                <a href='javascript:;' class='btn btn-move-down btn-sm'><i class='icon-arrow-down'></i></a>
              </td>
            </tr>
          <?php endforeach;?>
          <?php for($i = 0; $i < 5; $i++):?>
            <tr>
              <td class='w-150px'><?php echo html::select("team[]", $members, '', "class='form-control chosen'")?></td>
              <td>
                <div class='input-group'>
                  <span class='input-group-addon'><?php echo $lang->task->estimate?></span>
                  <?php echo html::input("teamEstimate[]", '', "class='form-control text-center' placeholder='{$lang->task->hour}'")?>
                  <span class='input-group-addon fix-border'><?php echo $lang->task->consumed?></span>
                  <?php echo html::input("teamConsumed[]", '', "class='form-control text-center' placeholder='{$lang->task->hour}'")?>
                  <span class='input-group-addon fix-border'><?php echo $lang->task->left?></span>
                  <?php echo html::input("teamLeft[]", '', "class='form-control text-center' placeholder='{$lang->task->hour}'")?>
                </div>
              </td>
              <td class='w-90px'>
                <a href='javascript:;' class='btn btn-move-up btn-sm'><i class='icon-arrow-up'></i></a>
                <a href='javascript:;' class='btn btn-move-down btn-sm'><i class='icon-arrow-down'></i></a>
              </td>
            </tr>
          <?php endfor;?>
          <tr><td colspan='3' class='text-center'><?php echo html::a('javascript:void(0)', $lang->confirm, '', "class='btn btn-primary' data-dismiss='modal'");?></td></tr>
        </table>
      </div>
    </div>
  </div>
</form>
<?php include '../../../common/view/footer.html.php';?>
