<?php
/* 查询条中可以选择的对象列表。*/
$lang->searchObjects['patchbuild']    = '补丁版本';
$lang->searchObjects['storyreview']   = '需求测试';
$lang->searchObjects['issue']         = '流出问题管理';
$lang->searchObjects['riskmanage']    = '风险管理表';

$lang->icons['storyReview']  = 'list-alt';
$lang->icons['issue']        = 'bug';
$lang->icons['riskmanage']   = 'bug';

$lang->menugroup->patchbuild     = 'project';
$lang->menugroup->storyreview    = 'project';
$lang->menugroup->issue          = 'qa';
$lang->menugroup->riskmanage     = 'report';

$lang->patchbuild = new stdclass();

$lang->project->menu->patchbuild = array('link' => '补丁版本|patchbuild|patchbuild|objectID=%s&from=project', 'alias' => 'patchbuild,createpatchbuild,editpatchbuild,', 'subModule' => 'patchbuild');

$lang->qa->menu->patchbuild = array('link' => '补丁版本|patchbuild|patchbuild|objectID=0&from=qa', 'subModule' => 'patchbuild');
$lang->bug->menu->patchbuild = array('link' => '补丁版本|patchbuild|patchbuild|objectID=0&from=qa', 'subModule' => 'patchbuild');
$lang->testcase->menu->patchbuild = array('link' => '补丁版本|patchbuild|patchbuild|objectID=0&from=qa', 'subModule' => 'patchbuild');
$lang->caselib->menu->patchbuild  = array('link' => '补丁版本|patchbuild|patchbuild|objectID=0&from=qa', 'subModule' => 'patchbuild');
//菜单配置
$lang->report->menu->test    = array('link' => '测试|report|bugcreate', 'alias' => 'bugassign,bugsummary,dailybugstatistics,bugbranchstatistics,bugresponsetimestatistics');
$lang->report->menu->prj     = array('link' => $lang->projectCommon . '|report|projectdeviation', 'alias' => 'storysummary,storyreviewsummary,burnup,storycyclesummary,storytasksummary,tasksummary,taskplansummary,storyProgress');
$lang->report->menu->staff   = array('link' => '组织|report|workload', 'alias' => 'usertasksummary,userbugsummary');

$lang->report->menu->scripttask   = array('link' => '脚本任务|report|scripttask', 'alias' => 'viewscript,editscript');

$lang->searchObjects['testtask']    = '内测版本';
$lang->project->menu->testtask  = array('link' => '内测版本|project|testtask|projectID=%s');
$lang->qa->menu->testtask  = array('link' => '内测版本|testtask|browse|productID=%s');
$lang->bug->menu->testtask  = array('link' => '内测版本|testtask|browse|productID=%s');
$lang->testcase->menu->testtask  = array('link' => '内测版本|testtask|browse|productID=%s', 'alias' => 'view,create,edit,linkcase,cases,start,close,batchrun,groupcase,report');
$lang->caselib->menu->testtask  = array('link' => '内测版本|testtask|browse|');

$lang->storyreview = new stdclass();
$lang->storyreview->menu = new stdClass();

$lang->project->menu->storyreview = array('link' => '需求测试|storyreview|storyreview|objectID=%s&from=project', 'alias' => 'storyreview,createstoryreview,editstoryreview,', 'subModule' => 'storyreview');

$lang->qa->menu->storyreview = array('link' => '需求测试|storyreview|storyreview|objectID=0&from=qa', 'subModule' => 'storyreview');
$lang->bug->menu->storyreview = array('link' => '需求测试|storyreview|storyreview|objectID=0&from=qa', 'subModule' => 'storyreview');
$lang->testcase->menu->storyreview = array('link' => '需求测试|storyreview|storyreview|objectID=0&from=qa', 'subModule' => 'storyreview');
$lang->caselib->menu->storyreview    =array('link' => '需求测试|storyreview|storyreview|objectID=0&from=qa', 'subModule' => 'storyreview');

$lang->my->menu->todostory          = '进度预警|my|todostory';
$lang->todostory       = new stdclass();
$lang->todostory->menu = $lang->my->menu;
$lang->my->menu->projectplan = '项目迭代计划|my|projectplan';
$lang->projectplan           = new stdclass();
$lang->projectplan->menu     = $lang->my->menu;

$lang->issue = new stdClass();
$lang->issue->menu = new stdClass();

$lang->issue->menu->bug         = array('link' => 'Bug|bug|browse|');
$lang->issue->menu->testcase    = array('link' => '用例|testcase|browse|');
$lang->issue->menu->testtask    = array('link' => '测试单|testtask|browse|');
$lang->issue->menu->testsuite   = array('link' => '套件|testsuite|browse|');
$lang->issue->menu->report      = array('link' => '报告|testreport|browse|');
$lang->issue->menu->caselib     = array('link' => '用例库|testsuite|library');
$lang->issue->menu->patchbuild  = array('link' => '补丁版本|patchbuild|patchbuild|objectID=0&from=qa');
$lang->issue->menu->storyreview = array('link' => '需求测试|storyreview|storyreview|objectID=0&from=qa');
$lang->issue->menu->issue       = array('link' => '流出问题管理|issue|browse', 'alias' => 'browse,create,edit,', 'subModule' => 'issue');

$lang->qa->menu->issue = array('link' => '流出问题管理|issue|browse');
$lang->bug->menu->issue = array('link' => '流出问题管理|issue|browse');
$lang->testcase->menu->issue = array('link' => '流出问题管理|issue|browse');
$lang->caselib->menu->issue = array('link' => '流出问题管理|issue|browse');
$lang->storyreview->menu->issue = array('link' => '流出问题管理|issue|browse');

$lang->project->menu->story     = array('link' => '需求|project|story|projectID=%s', 'subModule' => 'story', 'alias' => 'linkstory,storykanban,storyprogress');

$lang->riskmanage = new stdclass();
$lang->riskmanage->menu = new stdClass();
$lang->riskmanage->menu = $lang->report->menu;
$lang->riskmanage->menu->riskmanage = array('link' => '风险管理表|riskmanage|browse', 'alias' => 'browse,create,edit,view', 'subModule' => 'riskmanage');
$lang->report->menu->riskmanage   = array('link' => '风险管理表|riskmanage|browse', 'subModule' => 'riskmanage');
$lang->riskmanage->menuOrder       = $lang->report->menuOrder;
