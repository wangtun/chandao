<?php
/**
 * Created by PhpStorm.
 * User: 月下亭中人
 * Date: 2017/12/5
 * Time: 16:07
 */
$lang->storyreview = new stdClass();

$lang->storyreview->common      = '需求测试';       //权限登记的当前模块名
$lang->storyreview->create      = '创建需求测试单';
$lang->storyreview->edit        = '编辑需求测试单';
$lang->storyreview->view        = '需求测试单详情';
$lang->storyreview->delete      = '删除需求测试单';
$lang->storyreview->storyreview = '需求测试';
$lang->storyreview->basicInfo   = '基本信息';
$lang->storyreview->product     = '所属产品';
$lang->storyreview->project     = '所属项目';
$lang->storyreview->id          = '评审单号';
$lang->storyreview->draft       = '草稿';
$lang->storyreview->finished    = '完成';
$lang->storyreview->resolve     = '解决遗留问题';
$lang->storyreview->unfixed     = '未解决';

$lang->storyreview->title           = '评审内容';
$lang->storyreview->reviewStories   = '评审需求';
$lang->storyreview->consumed        = '评审用时';
$lang->storyreview->storyReviewers  = '需求参评者';
$lang->storyreview->devReviewers    = '开发参评者';
$lang->storyreview->testReviewers   = '测试参评者';
$lang->storyreview->otherReviewers  = '其他参评者';
$lang->storyreview->reviewDate      = '评审日期';
$lang->storyreview->storySource     = '需求来源';
$lang->storyreview->solvedProblem   = '解决问题';
$lang->storyreview->users           = '使用用户';
$lang->storyreview->application     = '使用环节';
$lang->storyreview->frequency       = '使用频率';
$lang->storyreview->teamDev         = '开发团队';
$lang->storyreview->testDate        = '提测时间';
$lang->storyreview->acceptanceDate  = '验收时间';
$lang->storyreview->releasedDate    = '上线时间';
$lang->storyreview->deliverables    = '成果物';
$lang->storyreview->needTest        = '是否测试';
$lang->storyreview->risk            = '风险';
$lang->storyreview->mailto          = '抄送给';
$lang->storyreview->influence       = '影响范围';
$lang->storyreview->problemTracking = '问题记录';
$lang->storyreview->result          = '评审结论';
$lang->storyreview->progressInfo    = '进度信息';
$lang->storyreview->comment         = '备注';
$lang->storyreview->leftProblem     = '遗留问题';
$lang->storyreview->leftProblemAB   = '遗留问题';
$lang->storyreview->resolution      = '解决方案';
$lang->storyreview->solver          = '解决者';
$lang->storyreview->resolvedDate    = '解决时间';
$lang->storyreview->countermeasure  = '解决对策';
$lang->storyreview->openedBy        = '由谁提出';
$lang->storyreview->openedByAB      = '由谁创建';
$lang->storyreview->openedDate      = '创建时间';

$lang->storyreview->needTestList['']    = '';
$lang->storyreview->needTestList['1']   = '是';
$lang->storyreview->needTestList['0']   = '否';

$lang->storyreview->leftProblemList['']  = '';
$lang->storyreview->leftProblemList['0'] = '无';
$lang->storyreview->leftProblemList['1'] = '有';

$lang->storyreview->resolutionList['']           = '';
$lang->storyreview->resolutionList['fixed']      = '已解决';
$lang->storyreview->resolutionList['unfixed']   = '未解决';
$lang->storyreview->resolutionList['refusefix'] = '不予解决';
$lang->storyreview->resolutionList['improve']    = '持续改善';

$lang->storyreview->confirmDelete   = '您确认删除该条记录吗？';

$lang->storyreview->deliverablesAB  = '例如：1、脚本；2、接口；3、平台功能';
$lang->storyreview->influenceAB     = "主要是影响的功能和范围两方面，可以从耦合关系，数据流，任务流，操作控制等角度考虑。具体解释：" . PHP_EOL . "1、数据耦合：A、B共用一份数据，或数据有一部分重叠。" . PHP_EOL . "2、流程耦合：A、B为流程中前后串联的两个环节。" . PHP_EOL . "3、任务耦合：A、B单独无完成任务，但A+B能完成任务。（无前后顺序）" . PHP_EOL . "4、操作耦合：A向B发送控制指令，或A的状态影响到B。" . PHP_EOL . "例如1、日编要素变更要考虑关联维护是否受影响；2、采集端tips字段变更要考虑tips上传下载及日编显示是否受影响；3、web界面变更要考虑功能应用是否受影响。";
$lang->storyreview->problemTrackingAB = "第一部分：有结论问题。为需求测试过程中提出有结论的问题，记录问题和结论；" . PHP_EOL . "1、对测试有帮助、有启发的问题。" . PHP_EOL . "2、重大注意事项。" . PHP_EOL . "第二部分：无结论问题。为无结论的问题，要记录问题，后续问题结论要做补充（不论是邮件还是QQ的回复方式，都要把结论补充进来）";
$lang->storyreview->commentAB = "第一类：与本需求类似的功能：主要是开发实现方式类似的，评审过程中可以进行确认，并记录。程序开发可作为参考，测试也可作为参考。例如：1、LU和LC。2、电子眼和限高限重。第二类：与本需求有业务联系的功能：可评审后补充。例如：1、点限速和线限速";
$lang->storyreview->notice = "风险样例：1、开发实现难度大；2、提交时间晚；3、影响范围广；4、需求疑问点多，不确定性强；5、有需求项可测试性差，难以验证；";
$lang->storyreview->danger = "草稿：只保存，不影响需求的评审状态；完成：点击完成后，关联需求变为已评审。";
$lang->storyreview->consumedAB = '小时';
$lang->storyreview->lblHour    = ' 工时';
$lang->storyreview->mailtoAB   = '参会者自动抄送邮件';

$lang->storyreview->mail = new stdclass();
$lang->storyreview->mail->create = new stdclass();
$lang->storyreview->mail->edit   = new stdclass();
$lang->storyreview->mail->close  = new stdclass();
$lang->storyreview->mail->create->title = "%s创建了需求测试单 #%s:%s";
$lang->storyreview->mail->edit->title   = "%s编辑了需求测试单 #%s:%s";
$lang->storyreview->mail->close->title  = "%s关闭了需求测试单 #%s:%s";

$lang->storyreview->qa = new stdclass();

$lang->storyreview->qa->bug       = array('link' => 'Bug|bug|browse|');
$lang->storyreview->qa->testcase  = array('link' => '用例|testcase|browse|');
$lang->storyreview->qa->testtask  = array('link' => '内测版本|testtask|browse|');
$lang->storyreview->qa->testtask  = array('link' => '测试单|testtask|browse|');
$lang->storyreview->qa->testsuite = array('link' => '套件|testsuite|browse|');
$lang->storyreview->qa->report    = array('link' => '报告|testreport|browse|');
$lang->storyreview->qa->caselib   = array('link' => '用例库|testsuite|library');
$lang->storyreview->qa->patchbuild  = array('link' => '补丁版本|patchbuild|patchbuild|objectID=0&from=qa', 'subModule' => 'patchbuild');
$lang->storyreview->qa->storyreview = array('link' => '需求测试|storyreview|storyreview|objectID=0&from=qa', 'subModule' => 'storyreview');
$lang->storyreview->qa->issue       = array('link' => '流出问题管理|issue|browse');

/* 操作记录。*/
$lang->storyreview->action = new stdclass();
$lang->storyreview->action->resolved = array('main' => '$date, 由 <strong>$actor</strong> 解决，方案为 <strong>$extra</strong>。', 'extra' => 'resolutionList');