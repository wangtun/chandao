<?php
/**
 * The create view of build module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     build
 * @version     $Id: create.html.php 4728 2013-05-03 06:14:34Z chencongzhi520@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include '../../common/view/header.html.php';?>
<?php include '../../common/view/datepicker.html.php';?>
<?php include '../../common/view/kindeditor.html.php';?>
<div class='container'>
  <div id='titlebar'>
    <div class='heading'>
      <span class='prefix'><?php echo html::icon($lang->icons['build']);?> <strong><?php echo $build->id;?></strong></span>
      <strong><?php echo $build->version;?></strong>
      <small class='text-muted'> <?php echo $this->lang->patchbuild->editpatchbuild;?> <?php echo html::icon($lang->icons['edit']);?></small>
    </div>
  </div>
  <form class='form-condensed' method='post' target='hiddenwin' id='dataform' enctype='multipart/form-data'>
    <table class='table table-form'>
      <tr>
        <th width="70"><?php echo $lang->patchbuild->patchType;?></th>
        <td><?php echo html::select('patchType', $lang->patchbuild->patchTypeList, $build->patchType, 'class="form-control chosen"');?></td>
        <td width="350">
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->patchbuild->workSeason;?></span>
            <?php echo html::select('workSeason', $lang->patchbuild->workSeasonList, $build->workSeason, 'class="form-control chosen"');?>
          </div>
        </td>
        <td width="350">
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->patchbuild->platform;?></span>
            <?php echo html::select('platform', $lang->patchbuild->platformList, $build->platform, 'class="form-control chosen"');?>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->patchbuild->version;?></th>
        <td><?php echo html::input('version', $build->version, "class='form-control' autocomplete='off'");?></td>
        <td>
          <?php if ($from == 'project'):?>
            <div class="input-group">
              <span class='input-group-addon'><?php echo $lang->patchbuild->project;?></span>
              <?php echo html::select('project', $projects, $build->project, 'class="form-control chosen"');?>
            </div>
          <?php endif;?>
          <?php if ($from == 'qa'):?>
            <div class="input-group">
              <span class='input-group-addon'><?php echo $lang->patchbuild->assignedTo;?></span>
              <?php echo html::select('assignedTo', $users, $build->assignedTo, 'class="form-control chosen"');?>
            </div>
          <?php endif;?>
        </td>
        <td>
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->patchbuild->reason;?></span>
            <?php echo html::select('reason', $lang->patchbuild->reasonList, $build->reason, 'class="form-control chosen"');?>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->patchbuild->submitter;?></th>
        <td><?php echo html::select('submitter', $users, $build->submitter, 'class="form-control chosen"');?></td>
        <td>
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->patchbuild->group;?></span>
            <?php echo html::select('group', $lang->patchbuild->groupList, $build->group, 'class="form-control chosen"');?>
          </div>
        </td>
        <td>
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->patchbuild->releasedDate;?></span>
            <?php echo html::input('releasedDate', $build->releasedDate, "class='form-control form-date'");?>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->patchbuild->mailto;?></th>
        <td colspan='2'>
          <div id='mailtoGroup' class='input-group'>
            <?php
            echo html::select('mailto[]', $users, str_replace(' ' , '', $build->mailto), "multiple class='form-control'");
            echo $this->fetch('my', 'buildContactLists');
            ?>
          </div>
        </td>
        <td>
          <div class="input-group">
            <span class='input-group-addon'><?php echo $lang->patchbuild->versionDeploy;?></span>
            <?php echo html::select('versionDeploy', $lang->patchbuild->versionDeployList, $build->versionDeploy, 'class="form-control chosen"');?>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->patchbuild->svnPath;?></th>
        <td colspan='3'><?php echo html::input('svnPath', $build->svnPath, "class='form-control' autocomplete='off'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->patchbuild->patchProgram;?></th>
        <td colspan='3'><?php echo html::input('patchProgram', $build->patchProgram, "class='form-control' autocomplete='off'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->patchbuild->patchContent;?></th>
        <td colspan='3'><?php echo html::textarea('patchContent', $build->patchContent, "rows='10' class='form-control'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->patchbuild->influence;?></th>
        <td colspan='3'><?php echo html::textarea('influence', $build->influence, "rows='6' class='form-control'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->patchbuild->config;?></th>
        <td colspan='3'><?php echo html::textarea('config', $build->config, "rows='6' class='form-control'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->patchbuild->notice;?></th>
        <td colspan='3'><?php echo html::textarea('notice', $build->notice, "rows='6' class='form-control'");?></td>
      </tr>
      <?php if ($from == 'qa'):?>
      <tr>
        <th><?php echo $lang->patchbuild->testPass?></th>
        <td colspan='3'><?php echo html::select('testPass', $lang->patchbuild->testPassList, $build->testPass, 'class="form-control chosen"');?></td>
      </tr>
      <tr>
        <th><?php echo $lang->patchbuild->testComment?></th>
        <td colspan='3'><?php echo html::textarea('testComment', $build->testComment, "rows='6' class='form-control'");?></td>
      </tr>
        <tr>
          <th><?php echo $lang->patchbuild->testEnv?></th>
          <td colspan='3'>
            <?php echo html::select('testEnv', $lang->patchbuild->testEnvList, $build->testEnv, 'class="form-control chosen"');?>
          </td>
        </tr>
        <tr>
          <th><?php echo $lang->patchbuild->testEnvComment?></th>
          <td colspan='3'><?php echo html::textarea('testEnvComment', $build->testEnvComment, "rows='6' class='form-control'");?></td>
        </tr>
      <?php endif;?>
      <tr><td></td><td colspan='3'><?php echo html::submitButton() . html::backButton();?></td></tr>
    </table>
  </form>
</div>
<?php js::set('productGroups', $productGroups)?>
<?php include '../../common/view/footer.html.php';?>
