<?php
//方法列表
$lang->story->parentStory   = '(父)';

//1084 产品需求下增加溯源号及搜索
$lang->story->sourceNO          = '溯源号';
$lang->story->sourceNote        = '溯源号';
$lang->story->batchStoryReview  = '需求评审';
$lang->story->batchEditDate     = '批量编辑提测/发版时间';

//需求增加一个字段“期望实现时间”，该字段的值采用下拉菜单格式，并且下拉菜单最好能调用产品-计划中的未关闭计划
$lang->story->customPlan     = '期望实现';
$lang->story->plan           = '研发计划';
$lang->story->planAB         = '研发计划';
$lang->story->build          = '外部用户提需求使用，研发部无需标记';
$lang->story->testDate       = '提测时间';
$lang->story->specialPlan    = '验收版时间';
$lang->story->testDatePlanAB = '研发专用';
$lang->story->devComment     = '研发专用,用户无需填写';

$lang->story->reviewStatus      = '评审状态';
$lang->story->storyReviewID     = '评审单号';
$lang->story->devTeam           = '开发关联方';

$lang->story->reviewStatusList['']  = '';
$lang->story->reviewStatusList['notReview']  = '未评审';
$lang->story->reviewStatusList['hasReview']  = '已评审';
$lang->story->reviewStatusList['freeReview'] = '免评审';

$lang->story->devTeamList[''] = '';

//3286 创建需求时就可以选择关联需求，并且支持相关需求处显示“无”
$lang->story->customProduct = '来源产品';
$lang->story->ifLinkStories = '有无相关需求';
$lang->story->ifLinkStoriesList['']      = '';
$lang->story->ifLinkStoriesList['none']  = '无';
$lang->story->ifLinkStoriesList['exist'] = '有';

$lang->story->specComment = "<p>[需求背景]</p><p>[功能描述]</p>";

$lang->story->magicAuth   = '用户需求关联';
$lang->story->batchChangeIfLinkStories = '批量修改是否有相关需求';
$lang->story->linkStoryOpenedBys = '相关需求创建者';

$lang->story->emptyBranch = '所属平台不能为空';
$lang->story->choseBranch = '请选择所属平台';

$lang->story->testStatus = '提测确认';
$lang->story->testStatusList['']         = '';
$lang->story->testStatusList['tested']   = '已提测';
$lang->story->testStatusList['notTest']  = '未提测';
$lang->story->testStatusList['partTest'] = '部分提测';
$lang->story->testStatusList['mentionTestBlock'] = '提测阻塞';
$lang->story->testStatusList['released'] = '已发布';
$lang->story->testStatusList['freeTest'] = '免提测';

$lang->story->verifyStatus = '测试验收';
$lang->story->verifyStatusList['']             = '';
$lang->story->verifyStatusList['pass']         = '已通过';
$lang->story->verifyStatusList['failed']       = '未通过';
$lang->story->verifyStatusList['noVerified']   = '暂未验收';
$lang->story->verifyStatusList['portVerified'] = '部分验收';
$lang->story->verifyStatusList['freeVerified'] = '免验收';

$lang->story->storyProgress = '需求进展';
$lang->story->batchChangeTestStatus   = '提测确认';
$lang->story->batchChangeVerifyStatus = '测试验收';
$lang->story->storyProgressSummary = "共 <strong>%s</strong> 个需求，未评审 <strong>%s</strong> 个，评审率<strong>%s</strong>；未提测 <strong>%s</strong> 个，提测率<strong>%s</strong>；未验收 <strong>%s</strong> 个，验收通过率<strong>%s</strong>。";

$lang->story->sourceNoteCommtent = '用户专用，如无溯源号，请填写无';
$lang->story->error = new stdClass();
$lang->story->error->branchErrorComment = '用户专用，如无溯源号，请填写无';

$lang->story->switchStoryLock = '项目需求锁';

$lang->story->action->linked2patchbuild        = array('main' => '$date, 由 <strong>$actor</strong> 关联到补丁版本 <strong>$extra</strong>。');
$lang->story->action->unlinkedfrompatchbuild   = array('main' => '$date, 由 <strong>$actor</strong> 从补丁版本 <strong>$extra</strong> 移除。');
