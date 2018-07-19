<?php
//需求收集池产品ID
$config->story->storyCollectionPoolProducts = '10,18,26';
//开发任务分类
$config->story->develTask = 'devel,web,ios,android,sdk,fos,script';
//禅道自身bug，待系统修复后可以删除
$config->story->edit->requiredFields = 'title';
$config->story->create->requiredFields = 'title';

//1522 增加产品需求所关联的需求和已细分需求的显示
$config->story->datatable->fieldList['childStories']['title']    = 'childStories';
$config->story->datatable->fieldList['childStories']['fixed']    = 'left';
$config->story->datatable->fieldList['childStories']['width']    = '100';
$config->story->datatable->fieldList['childStories']['required'] = 'no';

$config->story->datatable->fieldList['linkStories']['title']    = 'linkStories';
$config->story->datatable->fieldList['linkStories']['fixed']    = 'left';
$config->story->datatable->fieldList['linkStories']['width']    = '100';
$config->story->datatable->fieldList['linkStories']['required'] = 'no';

//1788 首次提需求时，屏蔽所属计划字段
$config->story->list->customCreateFields      = 'source,verify,pri,estimate,mailto,keywords';
$config->story->list->customBatchCreateFields = 'module,spec,source,verify,pri,estimate,keywords';
$config->story->custom->batchCreateFields = 'module,spec,pri,estimate';

//需求增加一个字段“期望实现时间”，该字段的值采用下拉菜单格式，并且下拉菜单最好能调用产品-计划中的未关闭计划
$config->story->datatable->fieldList['customPlan']['title']    = 'customPlan';
$config->story->datatable->fieldList['customPlan']['fixed']    = 'no';
$config->story->datatable->fieldList['customPlan']['width']    = '120';
$config->story->datatable->fieldList['customPlan']['required'] = 'no';

$config->story->datatable->fieldList['testDate']['title']    = 'testDate';
$config->story->datatable->fieldList['testDate']['fixed']    = 'no';
$config->story->datatable->fieldList['testDate']['width']    = '70';
$config->story->datatable->fieldList['testDate']['required'] = 'no';

$config->story->datatable->fieldList['specialPlan']['title']    = 'specialPlan';
$config->story->datatable->fieldList['specialPlan']['fixed']    = 'no';
$config->story->datatable->fieldList['specialPlan']['width']    = '90';
$config->story->datatable->fieldList['specialPlan']['required'] = 'no';

//3286 创建需求时就可以选择关联需求，并且支持相关需求处显示“无”
$config->story->datatable->fieldList['ifLinkStories']['title']    = 'ifLinkStories';
$config->story->datatable->fieldList['ifLinkStories']['fixed']    = 'no';
$config->story->datatable->fieldList['ifLinkStories']['width']    = '80';
$config->story->datatable->fieldList['ifLinkStories']['required'] = 'no';

$config->story->datatable->fieldList['testStatus']['title']    = 'testStatus';
$config->story->datatable->fieldList['testStatus']['fixed']    = 'no';
$config->story->datatable->fieldList['testStatus']['width']    = '70';
$config->story->datatable->fieldList['testStatus']['required'] = 'no';

$config->story->datatable->fieldList['verifyStatus']['title']    = 'verifyStatus';
$config->story->datatable->fieldList['verifyStatus']['fixed']    = 'no';
$config->story->datatable->fieldList['verifyStatus']['width']    = '70';
$config->story->datatable->fieldList['verifyStatus']['required'] = 'no';

$config->story->datatable->fieldList['devTeam']['title']    = 'devTeam';
$config->story->datatable->fieldList['devTeam']['fixed']    = 'no';
$config->story->datatable->fieldList['devTeam']['width']    = '100';
$config->story->datatable->fieldList['devTeam']['required'] = 'no';
