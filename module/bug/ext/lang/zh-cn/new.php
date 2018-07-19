<?php
$lang->bug->toIssue   = '流出问题';
$lang->bug->toIssueAB = '流出问题分析';
/* 模板。*/
$lang->bug->tplReason   = "<p>[问题原因]</p>\n";
$lang->bug->tplProject = "<p>[解决方案]</p>\n";
$lang->bug->tplInfluence = "<p>[影响范围]</p>\n";
$lang->bug->tplExpectedSolutionVersion = "<p>[预计解决版本]</p>";

$lang->bug->tplVerificationResults = "<p>[验证结果]</p>";
$lang->bug->tplVerificationVersion = "<p>[验证版本]</p>";
$lang->bug->tplVerificationContent = "<p>[验证内容]</p>";

//优化搜索功能搜索条件增加空选项
$lang->bug->confirmedList[''] = '';
$lang->bug->severityList[''] = '';

//新增bug截止日期统计
$lang->bug->report->charts['bugsDeadline']              = 'Bug截止日期统计';
$lang->bug->report->bugsDeadline                        = new stdclass();
$lang->bug->report->bugsDeadline->graph                 = new stdclass();
$lang->bug->report->bugsDeadline->type                  = 'bar';
$lang->bug->report->bugsDeadline->graph->xAxisName     = '日期';

//1747 提bug时，限定某些字段为必填项
$lang->bug->severityList[''] = '';

//2061 提bug页面增加一个字段“发现阶段”，该字段需要支持后续搜索
$lang->bug->discoveryPhase = '发现阶段';

$lang->bug->discoveryPhaseList[''] = '';
$lang->bug->discoveryPhaseList['check-in'] = '提测阶段';
$lang->bug->discoveryPhaseList['alpha'] = '内测阶段';
$lang->bug->discoveryPhaseList['acceptance'] = '验收阶段';
$lang->bug->discoveryPhaseList['production'] = '生产阶段';

$lang->bug->action->toissue = array('main' => '$date, 由 <strong>$actor</strong> 转为<strong>流出问题</strong>，编号为 <strong>$extra</strong>。');
$lang->bug->action->linked2patchbuild        = array('main' => '$date, 由 <strong>$actor</strong> 关联到补丁版本 <strong>$extra</strong>。');
$lang->bug->action->unlinkedfrompatchbuild   = array('main' => '$date, 由 <strong>$actor</strong> 从补丁版本 <strong>$extra</strong> 移除。');
$lang->bug->action->withhold                 = array('main' => '$date, 由 <strong>$actor</strong> 编辑为<strong>暂不关闭</strong>。');

$lang->bug->withhold         = '暂不关闭';
$lang->bug->withholdList['1'] = '是';
$lang->bug->withholdList['0'] = '否';
$lang->bug->withholdList['']  = '';

$lang->bug->workSeason = '所属作业季';
$lang->bug->workSeasonList[''] = '';
