<?php
/**
 * Created by PhpStorm.
 * User: 月下亭中人
 * Date: 2017/12/5
 * Time: 16:07
 */
$lang->reviewbyproduct = new stdClass();

$lang->reviewbyproduct->common      = '产品需求评审';       //权限登记的当前模块名
$lang->reviewbyproduct->create      = '创建需求评审单';
$lang->reviewbyproduct->edit        = '编辑需求评审单';
$lang->reviewbyproduct->view        = '需求评审单详情';
$lang->reviewbyproduct->delete      = '删除需求评审单';
$lang->reviewbyproduct->reviewbyproduct = '产品需求评审';
$lang->reviewbyproduct->basicInfo   = '基本信息';
$lang->reviewbyproduct->product     = '所属产品';
$lang->reviewbyproduct->project     = '所属项目';
$lang->reviewbyproduct->id          = '评审单号';
$lang->reviewbyproduct->draft       = '草稿';
$lang->reviewbyproduct->finished    = '完成';
$lang->reviewbyproduct->resolve     = '解决遗留问题';
$lang->reviewbyproduct->unfixed     = '未解决';

$lang->reviewbyproduct->title           = '评审内容';
$lang->reviewbyproduct->reviewStories   = '评审需求';
$lang->reviewbyproduct->storyReviewers  = '需求参评者';
$lang->reviewbyproduct->devReviewers    = '开发参评者';
$lang->reviewbyproduct->testReviewers   = '测试参评者';
$lang->reviewbyproduct->reviewDate      = '评审日期';
$lang->reviewbyproduct->storySource     = '需求来源';
$lang->reviewbyproduct->solvedProblem   = '解决问题';
$lang->reviewbyproduct->users           = '使用用户';
$lang->reviewbyproduct->mailto          = '抄送给';
$lang->reviewbyproduct->problemRecord   = '问题记录';
$lang->reviewbyproduct->comment         = '备注';
$lang->reviewbyproduct->solveMethod     = '解决方案';
$lang->reviewbyproduct->solver          = '负责人';
$lang->reviewbyproduct->resolvedDate    = '解决时间';
$lang->reviewbyproduct->solved          = '是否解决';
$lang->reviewbyproduct->countermeasure  = '解决对策';
$lang->reviewbyproduct->openedBy        = '由谁提出';
$lang->reviewbyproduct->openedByAB      = '由谁创建';
$lang->reviewbyproduct->openedDate      = '创建时间';


$lang->reviewbyproduct->solvedList['0']     ='未解决';
$lang->reviewbyproduct->solvedList['1']     ='已解决';
$lang->reviewbyproduct->solvedList['2']     ='待解决';


$lang->reviewbyproduct->confirmDelete   = '您确认删除该条记录吗？';

$lang->reviewbyproduct->deliverablesAB  = '例如：1、脚本；2、接口；3、平台功能';
$lang->reviewbyproduct->influenceAB     = "主要是影响的功能和范围两方面，可以从耦合关系，数据流，任务流，操作控制等角度考虑。具体解释：" . PHP_EOL . "1、数据耦合：A、B共用一份数据，或数据有一部分重叠。" . PHP_EOL . "2、流程耦合：A、B为流程中前后串联的两个环节。" . PHP_EOL . "3、任务耦合：A、B单独无完成任务，但A+B能完成任务。（无前后顺序）" . PHP_EOL . "4、操作耦合：A向B发送控制指令，或A的状态影响到B。" . PHP_EOL . "例如1、日编要素变更要考虑关联维护是否受影响；2、采集端tips字段变更要考虑tips上传下载及日编显示是否受影响；3、web界面变更要考虑功能应用是否受影响。";
$lang->reviewbyproduct->problemTrackingAB = "分条描述问题，并保证需求、开发、测试人员的理解一致";
$lang->reviewbyproduct->solveMethodAB  = "分条描述，与‘问题记录’相互对应：" . PHP_EOL . "1、已解决的问题：问题的结论如果写入设计文档，此处不必重复录入，标记结论记录的位置即可，如：《xxxx文档》xx页；" . PHP_EOL . "2.待解决的问题：简要记录拟解决的方式，明确相关责任人（需求、开发或测试人员）；待问题解决后，按照第一类方式记录在此处；
"  . PHP_EOL . "3.无法解决的问题：需给出解决方案，明确结论，如：关闭需求、移至其他迭代等。" ;
$lang->reviewbyproduct->reviewTitle = '描述事件，如：需求评审_xxxx（需求名称）';
$lang->reviewbyproduct->commentAB = "需求评审过程中的备注事项，如：修订需求描述、修订指派人、修订提测时间等";
$lang->reviewbyproduct->notice = "风险样例：1、开发实现难度大；2、提交时间晚；3、影响范围广；4、需求疑问点多，不确定性强；5、有需求项可测试性差，难以验证；";
$lang->reviewbyproduct->danger = "草稿：只保存，不影响需求的评审状态；完成：点击完成后，关联需求变为已评审。";
$lang->reviewbyproduct->consumedAB = '小时';
$lang->reviewbyproduct->lblHour    = ' 工时';
$lang->reviewbyproduct->mailtoAB   = '参会者自动抄送邮件';

$lang->reviewbyproduct->mail = new stdclass();
$lang->reviewbyproduct->mail->create = new stdclass();
$lang->reviewbyproduct->mail->edit   = new stdclass();
$lang->reviewbyproduct->mail->close  = new stdclass();
$lang->reviewbyproduct->mail->create->title = "%s创建了需求评审单 #%s:%s";
$lang->reviewbyproduct->mail->edit->title   = "%s编辑了需求评审单 #%s:%s";
$lang->reviewbyproduct->mail->close->title  = "%s关闭了需求评审单 #%s:%s";

$lang->reviewbyproduct->qa = new stdclass();

$lang->reviewbyproduct->qa->bug       = array('link' => 'Bug|bug|browse|');
$lang->reviewbyproduct->qa->testcase  = array('link' => '用例|testcase|browse|');
$lang->reviewbyproduct->qa->testtask  = array('link' => '内测版本|testtask|browse|');
$lang->reviewbyproduct->qa->testtask  = array('link' => '测试单|testtask|browse|');
$lang->reviewbyproduct->qa->testsuite = array('link' => '套件|testsuite|browse|');
$lang->reviewbyproduct->qa->report    = array('link' => '报告|testreport|browse|');
$lang->reviewbyproduct->qa->caselib   = array('link' => '用例库|testsuite|library');
$lang->reviewbyproduct->qa->patchbuild  = array('link' => '补丁版本|patchbuild|patchbuild|objectID=0&from=qa', 'subModule' => 'patchbuild');
$lang->reviewbyproduct->qa->reviewbyproduct = array('link' => '需求评审|reviewbyproduct|reviewbyproduct|objectID=0&from=qa', 'subModule' => 'reviewbyproduct');
$lang->reviewbyproduct->qa->issue       = array('link' => '流出问题管理|issue|browse');

/* 操作记录。*/
$lang->reviewbyproduct->action = new stdclass();
$lang->reviewbyproduct->action->resolved = array('main' => '$date, 由 <strong>$actor</strong> 解决，方案为 <strong>$extra</strong>。', 'extra' => 'resolutionList');