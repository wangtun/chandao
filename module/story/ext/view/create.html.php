<?php
/**
 * The create view of story module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     story
 * @version     $Id: create.html.php 4902 2013-06-26 05:25:58Z wyd621@gmail.com $
 * @link        http://www.zentao.net
 */
?>
<?php include '../../../common/view/header.html.php';?>
<?php include '../../../common/view/kindeditor.html.php';?>
<?php include '../../../common/view/form.html.php';?>
<?php include '../../../common/view/datepicker.html.php';?>
<?php js::set('holders', $lang->story->placeholder); ?>
<div class='container mw-1400px'>
  <div id='titlebar'>
    <div class='heading'>
      <span class='prefix'><?php echo html::icon($lang->icons['story']);?></span>
      <strong><small class='text-muted'><?php echo html::icon($lang->icons['create']);?></small> <?php echo $lang->story->create;?></strong>
    </div>
    <div class='actions'>
      <button type="button" class="btn btn-default" data-toggle="customModal"><i class='icon icon-cog'></i> </button>
    </div>
  </div>
  <form class='form-condensed' method='post' enctype='multipart/form-data' id='dataform' data-type='ajax'>
    <table class='table table-form'>
      <tr>
        <th class='w-90px'><?php echo $lang->story->product;?></th>
        <td class='w-p45-f'>
          <div class='input-group'>
            <?php echo html::select('product', $products, $productID, "onchange='loadProduct(this.value);' class='form-control chosen'");?>
            <!--3757 提需求时，所属平台需要变更为必填项-->
            <?php if($product->type != 'normal') echo html::select('branch', $branches,'', "data-placeholder='{$lang->story->choseBranch}' onchange='loadBranch();' class='form-control chosen' style='width:120px'");?>
            <span class="required required-wrapper"></span>
          </div>
        </td>
        <td>
          <div class='input-group' id='moduleIdBox'>
          <span class='input-group-addon w-80px'><?php echo $lang->story->module;?></span>
          <?php 
          echo html::select('module', $moduleOptionMenu, $moduleID, "class='form-control chosen'");
          if(count($moduleOptionMenu) == 1)
          {
              echo "<span class='input-group-addon'>";
              echo html::a($this->createLink('tree', 'browse', "rootID=$productID&view=story&currentModuleID=0&branch=$branch"), $lang->tree->manage, '_blank');
              echo '&nbsp; ';
              echo html::a("javascript:loadProductModules($productID)", $lang->refresh);
              echo '</span>';
          }
          ?>
          </div>
        </td><td></td>
      </tr>
      <!--需求增加一个字段“期望实现时间”，该字段的值采用下拉菜单格式，并且下拉菜单最好能调用产品-计划中的未关闭计划-->
      <tr>
        <th><?php echo $lang->story->customPlan;?></th>
        <td>
          <div class='input-group'>
        <?php echo html::select('customPlan', $plans, '', "class='form-control chosen' data-placeholder='{$this->lang->story->build}'");?>
          </div>
        </td>
        <!--2911 优化需求提测计划、发版计划等内容-->
        <td>
          <div class='input-group'>
            <span class='input-group-addon'><?php echo $lang->story->testDate?></span>
            <?php echo html::input('testDate', '', "class='form-control form-date' placeholder='{$lang->story->testDatePlanAB}'");?>
            <span class='input-group-addon fix-border'><?php echo $lang->story->specialPlan;?></span>
            <?php echo html::input('specialPlan', '', "class='form-control form-date'");?>
          </div>
        </td>
      </tr>

     <!--2013 外部门首次提需求时屏蔽所属计划这个字段 55-76行-->
      <tr>
        <th><?php echo $lang->story->reviewedBy;?></th>
        <td>
          <div class='input-group'>
            <?php echo html::select('assignedTo', $users, empty($needReview) ? $product->PO : '', "class='form-control chosen'");?>
            <?php if(!$this->story->checkForceReview()):?>
                <?php $disabled = $projectID === 0 ? disabled : ''?>
              <span class='input-group-addon'><?php echo html::checkbox('needNotReview', $lang->story->needNotReview, '', "id='needNotReview' {$needReview} $disabled");?></span>
            <?php endif;?>
          </div>
        </td>
        <?php if(strpos(",$showFields,", ',source,') !== false):?>
          <td>
            <div class='input-group'>
              <span class='input-group-addon w-60px'><?php echo $lang->story->source?></span>
              <?php echo html::select('source', $lang->story->sourceList, $source, "class='form-control chosen'");?>
              <!--1084 产品需求下增加溯源号及搜索-->
              <span class='input-group-addon fix-border'><?php echo $lang->story->sourceNO?></span>
              <?php echo html::input('sourceNote', $sourceNote, "class='form-control' autocomplete='off' style='width:200px;' placeholder='{$this->lang->story->sourceNoteCommtent}'");?>
            </div>
          </td>
        <?php endif;?>
      </tr>

      <tr>
        <th><?php echo $lang->story->title;?></th>
        <td colspan='2'>
          <div class='row-table'>
            <div class='col-table'>
              <div class="input-group w-p100">
                <input type='hidden' id='color' name='color' data-provide='colorpicker' data-wrapper='input-group-btn' data-pull-menu-right='false' data-btn-tip='<?php echo $lang->story->colorTag ?>' data-update-text='#title' value='<?php echo $color;?>'>
                <?php echo html::input('title', $storyTitle, "class='form-control' autocomplete='off'");?>
              </div>
            </div>
            <?php
            $hiddenPri = strpos(",$showFields,", ',pri,') === false;
            $hiddenEst = strpos(",$showFields,", ',estimate,') === false;
            ?>
            <?php if(!$hiddenPri or !$hiddenEst):?>
            <?php $widthClass = ($hiddenPri or $hiddenEst) ? 'w-100px' : 'w-230px';?>
            <div class='col-table <?php echo $widthClass?>'>
              <div class="input-group">
                <?php if(!$hiddenPri):?>
                <span class='input-group-addon fix-border br-0'><?php echo $lang->story->pri;?></span>
                <?php
                $hasCustomPri = false;
                foreach($lang->story->priList as $priKey => $priValue)
                {
                  if(!empty($priKey) and (string)$priKey != (string)$priValue)
                    {
                        $hasCustomPri = true;
                        break;
                    }
                }
                ?>
                <?php if($hasCustomPri):?>
                <?php echo html::select('pri', (array)$lang->story->priList, $pri, "class='form-control minw-80px chosen'");?>
                <?php else: ?>
                <div class='input-group-btn dropdown-pris' data-set='0,1,2,3,4'>
                  <button type='button' class='btn dropdown-toggle br-0' data-toggle='dropdown'>
                    <span class='pri-text'></span> &nbsp;<span class='caret'></span>
                  </button>
                  <ul class='dropdown-menu pull-right'></ul>
                  <?php echo html::select('pri', (array)$lang->story->priList, $pri, "class='hide'");?>
                </div>
                <?php endif; ?>
                <?php endif;?>
                <?php if(!$hiddenEst):?>
                <span class='input-group-addon fix-border br-0'><?php echo $lang->story->estimateAB;?></span>
                <?php echo html::input('estimate', $estimate, "class='form-control minw-60px' autocomplete='off'");?>
                <?php endif;?>
              </div>
            </div>
            <?php endif;?>
          </div>
        </td>
      </tr>  
      <tr>
        <th><?php echo $lang->story->spec;?></th>
        <!--3304 提需求时，需求描述中增加两个隐性提示-->
        <td colspan='2'><?php echo html::textarea('spec', $spec ? $spec : $lang->story->specComment, "rows='9' class='form-control disabled-ie-placeholder' placeholder='" . htmlspecialchars($lang->story->specTemplate) . "'");?></td>
      </tr>  
      <?php if(strpos(",$showFields,", ',verify,') !== false):?>
      <tr>
        <th><?php echo $lang->story->verify;?></th>
        <td colspan='2'><?php echo html::textarea('verify', $verify, "rows='6' class='form-control'");?></td>
      </tr>
      <?php endif;?>
      <?php
      $hiddenMailto   = strpos(",$showFields,", ',mailto,') === false;
      $hiddenKeywords = strpos(",$showFields,", ',keywords,') === false;
      ?>
      <?php if(!$hiddenMailto or !$hiddenKeywords):?>
      <?php $colspan = ($hiddenMailto or $hiddenKeywords) ? "colspan='2'" : '';?>
      <tr>
        <th><?php echo $hiddenMailto ? $lang->story->keywords : $lang->story->mailto;?></th>
        <?php if(!$hiddenMailto):?>
        <td>
          <div class='input-group' id='mailtoGroup'>
            <?php 
            echo html::select('mailto[]', $users, str_replace(' ' , '', $mailto), "multiple"); 
            echo $this->fetch('my', 'buildContactLists');
            ?>
          </div>
        </td>
        <?php endif;?>
        <!--15728 增加开发关联方字段快速识别需求涉及到的前后端开发-->
        <?php if(!$hiddenKeywords):?>
        <td <?php echo $colspan?>>
          <div class='input-group'>
            <?php if(!$hiddenMailto):?>
            <span class='input-group-addon'><?php echo $lang->story->keywords;?></span>
            <?php endif;?>
            <?php echo html::input('keywords', $keywords, 'class="form-control" autocomplete="off" style="width:200px;"');?>
            <span class="input-group-addon fix-border"><?php echo $lang->story->devTeam; ?></span>
            <?php echo html::select('devTeam[]', $lang->story->devTeamList, '', "multiple class='form-control chosen'");?>
          </div>
        </td>
        <?php endif;?>
      </tr>
      <?php endif;?>

      <tr>
        <th><?php echo $lang->story->legendAttatch;?></th>
        <td colspan='2'><?php echo $this->fetch('file', 'buildform');?></td>
      </tr>
      <!--3286 创建需求时就可以选择关联需求，并且支持相关需求处显示“无”-->
      <?php if (common::hasPriv('story', 'magicAuth') && !in_array($productID, $customStoryCollectPool)):?>
      <tr>
        <th><?php echo $lang->story->customProduct;?></th>
        <td colspan='2'>
          <div class='row-table'>
            <div class='col-table w-300px'>
              <div class="input-group w-p100">
                <?php echo html::select('customProduct', $customProducts, '', "onchange='loadStories(this.value);' class='form-control chosen' data-placeholder='{$this->lang->story->devComment}'");?>
              </div>
            </div>
            <div class='col-table'>
              <div class="input-group w-p100">
                <span class='input-group-addon'><?php echo $lang->story->linkStories;?></span>
                <?php echo html::select('story[]', '', '', "onchange='changeIfLinkStories(this.value);' multiple class='form-control chosen'  data-placeholder='{$this->lang->story->devComment}'");?>
                <span class='input-group-addon br-0'><?php echo $lang->story->ifLinkStories;?></span>
                <?php echo html::select('ifLinkStories', $lang->story->ifLinkStoriesList, '', "class='form-control' id='ifLinkStories' style='width:60px';");?>
              </div>
            </div>
          </div>
        </td>
      </tr>
      <?php endif;?>
      <tr><td></td><td colspan='2' class='text-center'><?php echo html::submitButton() . html::backButton();?></td></tr>
    </table>
    <span id='responser'></span>
  </form>
</div>
<?php $customLink = $this->createLink('custom', 'ajaxSaveCustomFields', 'module=story&section=custom&key=createFields')?>
<?php include '../../../common/view/customfield.html.php';?>
<?php js::set('storyModule', $lang->story->module);?>
<?php include '../../../common/view/footer.html.php';?>
<script language='Javascript'>
  //3286 创建需求时就可以选择关联需求，并且支持相关需求处显示“无”
  function loadStories(productID)
  {
    var link = createLink('story', 'ajaxGetCustomProductStories', 'productID=' + productID + '&branch=0&moduleID=0&storyID=0&onlyOption=false&status=noclosed');
    $.get(link, function(stories)
    {
      if(!stories) stories = '<select id="story[]" name="story" multiple="multiple" onchange="changeIfLinkStories(this.value);"></select>';
      $('#story').replaceWith(stories);
      $('#story_chosen').remove();
      $("#story").chosen(defaultChosenOptions);
    });
  }
  
  function changeIfLinkStories(linkStories)
  {
    if(linkStories === '') $('#ifLinkStories').val('');
    if(linkStories !== '') $('#ifLinkStories').val('exist');
  }
  
  function loadProduct(productID)
  {
    loadProductBranches(productID);
    loadProductModules(productID);
    loadProductPlans(productID);
  }

  function loadBranch()
  {
    var branchName = $('#branch').find("option:selected").text();
    branchName =  branchName.replace(/\s+/g,"");
    //脚本任务增加默认文本
    if(branchName == '脚本')
    {
      $(document.getElementsByTagName("iframe")[0].contentWindow.document.body).html("<p>[执行前提]</p><p>[脚本输入]</p><p>[测试验证]</p><p>[脚本输出]</p><p>[使用频率]</p><p>[性能需求]</p><p>[发版计划]</p><p>[遗留问题或风险]</p><p>[脚本具体业务原则]</p>");
    }

    var branch = $('#branch').val();
    if(typeof(branch) == 'undefined') branch = 0;
    loadProductModules($('#product').val(), branch);
    loadProductPlans($('#product').val(), branch);
  }

  function loadProductBranches(productID)
  {
    $('#branch').remove();
    $('#branch_chosen').remove();
    $.get(createLink('branch', 'ajaxGetBranches', "productID=" + productID), function(data)
    {
      if(data)
      {
        $('#product').closest('.input-group').append(data);
        $('#branch').css('width', config.currentMethod == 'create' ? '120px' : '65px');
        $('#branch').chosen(defaultChosenOptions);
      }
    })
  }

  function loadProductModules(productID, branch)
  {
    if(typeof(branch) == 'undefined') branch = 0;
    if(!branch) branch = 0;
    moduleLink = createLink('tree', 'ajaxGetOptionMenu', 'productID=' + productID + '&viewtype=story&branch=' + branch + '&rootModuleID=0&returnType=html&fieldID=&needManage=true');
    $('#moduleIdBox').load(moduleLink, function()
    {
      $('#moduleIdBox #module').chosen(defaultChosenOptions);
      if(typeof(storyModule) == 'string') $('#moduleIdBox').prepend("<span class='input-group-addon'>" + storyModule + "</span>")
    });
  }

  function loadProductPlans(productID, branch)
  {
    if(typeof(branch) == 'undefined') branch = 0;
    if(!branch) branch = 0;
    planLink = createLink('product', 'ajaxGetPlans', 'productID=' + productID + '&branch=' + branch + '&planID=' + $('#plan').val() + '&fieldID=&needCreate=true');
    $('#planIdBox').load(planLink, function(){$('#planIdBox #plan').chosen(defaultChosenOptions);});
  }

  $(function()
  {
    $("#reviewedBy").chosen(defaultChosenOptions);
    $("#mailto").chosen(defaultChosenOptions);
  })
</script>

