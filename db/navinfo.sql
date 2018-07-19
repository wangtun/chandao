ALTER TABLE `zt_task` ADD `source` varchar(10) NOT NULL AFTER `fromBug`;
ALTER TABLE `zt_bug` ADD `discoveryPhase` varchar(30) NOT NULL AFTER `keywords`;
ALTER TABLE `zt_bug` ADD `withhold` enum('','1','0') NOT NULL DEFAULT '' COMMENT '暂不关闭' AFTER `status`;
ALTER TABLE `zt_task` ADD `keywords` varchar(255) NOT NULL AFTER `name`;
ALTER TABLE `zt_bug` ADD `toIssue` mediumint(8) NOT NULL AFTER `toStory`;
ALTER TABLE `zt_story` ADD `customPlan` text NOT NULL AFTER `plan`;
ALTER TABLE `zt_story` ADD `testDate` date NOT NULL AFTER `customPlan`;
ALTER TABLE `zt_story` ADD `specialPlan` date NOT NULL AFTER `testDate`;
ALTER TABLE `zt_story` ADD `reviewStatus` enum('','hasReview','notReview','freeReview') NOT NULL DEFAULT '' AFTER `lastEditedDate`;
ALTER TABLE `zt_story` ADD  `storyReviewID` varchar(50) NOT NULL AFTER `reviewStatus`;
ALTER TABLE `zt_story` ADD `ifLinkStories` varchar(10) NOT NULL COMMENT '是否有关联需求' AFTER `childStories`;
ALTER TABLE `zt_story` ADD `testStatus` varchar(30) NOT NULL AFTER `status`;
ALTER TABLE `zt_story` ADD `verifyStatus` varchar(30) NOT NULL AFTER `testStatus`;
ALTER TABLE `zt_project` ADD `lockStory` enum('0','1') NOT NULL DEFAULT '0' AFTER `statge`;
ALTER TABLE `zt_project` ADD `lockPatchBuild` enum('0','1') NOT NULL COMMENT '补丁锁定' AFTER `lockStory`;

ALTER TABLE zt_task ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `zt_bugreport` (
  `date` date NOT NULL,
  `product` mediumint(8) unsigned NOT NULL,
  `createdPerDay` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '每日提交bug量',
  `resolvedPerDay` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '每日解决bug量',
  `active` mediumint(9) unsigned NOT NULL DEFAULT '0' COMMENT '累计未解决',
  `resolved` mediumint(9) unsigned NOT NULL DEFAULT '0' COMMENT '累计待回归',
  `opened` mediumint(9) unsigned NOT NULL DEFAULT '0' COMMENT '累计bug量',
  `count` mediumint(9) unsigned NOT NULL DEFAULT '0' COMMENT 'bug总数',
  KEY `bugreport` (`date`,`product`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zt_burnup` (
  `project` mediumint(9) unsigned NOT NULL,
  `date` date NOT NULL,
  `storyCount` smallint(6) unsigned NOT NULL COMMENT '项目内需求的总量',
  `testedStoryCount` smallint(6) unsigned NOT NULL COMMENT '测试完成阶段的需求量',
  `developedStoryCount` smallint(6) unsigned NOT NULL COMMENT '开发完成阶段的需求总量（开发完成统计需求状态为研发完毕、测试完毕、已发布的需求量）'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zt_issue` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `issueDesc` varchar(255) NOT NULL COMMENT '问题描述',
  `fromBug` mediumint(8) unsigned NOT NULL COMMENT '来源bug',
  `platform` varchar(30) NOT NULL COMMENT '所属平台',
  `discoveryDate` date NOT NULL COMMENT '发现日期',
  `environmentVersion` varchar(50) NOT NULL COMMENT '环境及版本',
  `influence` varchar(255) NOT NULL COMMENT '流出影响',
  `reason` varchar(255) NOT NULL COMMENT '原因分析',
  `shortTermMeasures` varchar(255) NOT NULL COMMENT '短期对策',
  `longTermMeasures` varchar(255) NOT NULL COMMENT '长期对策',
  `mailto` text NOT NULL,
  `openedBy` varchar(30) NOT NULL COMMENT '创建者',
  `openedTime` datetime NOT NULL COMMENT '创建时间',
  `deleted` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `issue` (`platform`,`discoveryDate`,`openedBy`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zt_patchbuild` (
  `id` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
  `product` mediumint(9) unsigned NOT NULL,
  `project` mediumint(9) unsigned NOT NULL,
  `workSeason` mediumint(9) NOT NULL,
  `patchType` varchar(20) NOT NULL COMMENT '补丁类型',
  `platform` varchar(20) NOT NULL COMMENT '所属平台',
  `patchDate` date NOT NULL COMMENT '优先级',
  `version` char(100) NOT NULL COMMENT '对应版本',
  `patchContent` text NOT NULL COMMENT '修复问题,新增功能',
  `reason` varchar(20) NOT NULL COMMENT '问题流出原因',
  `submitter` char(30) NOT NULL COMMENT '提交人',
  `assignedTo` char(30) NOT NULL,
  `linkStories` varchar(150) NOT NULL COMMENT '相关需求',
  `linkBugs` varchar(150) NOT NULL COMMENT '相关bug',
  `mailto` text NOT NULL,
  `group` varchar(20) NOT NULL COMMENT '所属组',
  `svnPath` char(255) NOT NULL COMMENT 'SVN路径',
  `patchProgram` char(255) NOT NULL,
  `releasedDate` date NOT NULL COMMENT '补丁日期',
  `testComment` text NOT NULL COMMENT '验收测试',
  `testPass` varchar(20) NOT NULL,
  `testEnvComment` text NOT NULL COMMENT '运维发布',
  `versionDeploy` enum('yes','no') NOT NULL DEFAULT 'no' COMMENT '版本部署',
  `testEnv` varchar(20) NOT NULL,
  `influence` text NOT NULL,
  `config` text NOT NULL,
  `notice` text NOT NULL,
  `deleted` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `patchbuild` (`workSeason`,`patchType`,`platform`,`patchDate`,`reason`,`submitter`,`assignedTo`,`group`,`releasedDate`,`testPass`,`versionDeploy`,`testEnv`,`deleted`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zt_qaaudit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `auditID` varchar(20) NOT NULL COMMENT '编号',
  `noDec` varchar(255) NOT NULL COMMENT '不符合项描述',
  `task` int(10) unsigned NOT NULL COMMENT '任务id',
  `noType` varchar(10) NOT NULL COMMENT '不符合类型',
  `serious` varchar(20) NOT NULL COMMENT '严重度',
  `cause` varchar(255) NOT NULL COMMENT '原因分析',
  `measures` varchar(255) NOT NULL COMMENT '任务描述',
  `deleted` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `qaqaudit` (`task`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zt_review` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '评审id',
  `fileNO` varchar(60) NOT NULL COMMENT '文件编号',
  `recorder` varchar(20) NOT NULL COMMENT '记录人员',
  `reviewName` varchar(30) NOT NULL COMMENT '评审类型',
  `task` int(10) unsigned NOT NULL,
  `doc` varchar(45) NOT NULL,
  `referenceDoc` text NOT NULL COMMENT '参考文档',
  `reference` varchar(60) NOT NULL COMMENT '文件版本',
  `pages` tinyint(4) unsigned NOT NULL COMMENT '文件页数',
  `reviewers` varchar(200) NOT NULL COMMENT '参评人员',
  `reviewDate` date NOT NULL COMMENT '评审日期',
  `reviewScope` varchar(255) NOT NULL COMMENT '评审范围',
  `reviewPlace` varchar(60) NOT NULL COMMENT '评审地点',
  `effort` smallint(5) unsigned NOT NULL COMMENT '评审所用时间',
  `conclusion` varchar(15) NOT NULL COMMENT '评审结论',
  PRIMARY KEY (`id`),
  KEY `review` (`task`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zt_reviewdetail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reviewID` int(10) unsigned NOT NULL,
  `number` varchar(3) NOT NULL COMMENT '序号',
  `reviewer` varchar(45) NOT NULL COMMENT '评审人员',
  `item` varchar(50) NOT NULL COMMENT '页码或章节',
  `line` varchar(10) NOT NULL COMMENT '行号或单元格',
  `severity` varchar(3) NOT NULL COMMENT '严重性',
  `description` varchar(255) NOT NULL COMMENT '评审描述',
  `proposal` varchar(255) NOT NULL COMMENT '评审建议',
  `changed` varchar(3) NOT NULL COMMENT '评审结果（是否变更）',
  `action` varchar(255) NOT NULL COMMENT '不变更原因',
  `chkd` varchar(3) NOT NULL COMMENT '评审结果确认',
  `deleted` enum('1','0') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `reviewdetail` (`reviewID`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zt_riskmanage` (
  `id` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
  `riskType` varchar(30) NOT NULL COMMENT '风险类别',
  `title` varchar(255) NOT NULL COMMENT '活动、事项',
  `desc` text NOT NULL COMMENT '风险和机遇描述',
  `object` varchar(60) NOT NULL COMMENT '对应的产品/项目/过程/组织级',
  `posedDepart` varchar(60) NOT NULL COMMENT '提出部门',
  `riskLife` varchar(60) NOT NULL COMMENT '风险存在的时间段',
  `influence` tinyint(3) unsigned NOT NULL COMMENT '影响系数',
  `probability` tinyint(3) unsigned NOT NULL COMMENT '发送概率',
  `riskScore` tinyint(3) NOT NULL COMMENT '风险系数',
  `pri` varchar(3) NOT NULL COMMENT '优先级',
  `preventiveMeasure` text NOT NULL COMMENT '缓解/预防措施',
  `emergencyMeasure` text NOT NULL COMMENT '应急措施',
  `responsibleDepart` varchar(30) NOT NULL COMMENT '责任部门',
  `responsiblePerson` varchar(30) NOT NULL COMMENT '责任人',
  `remissionCycle` text NOT NULL COMMENT '缓解/预防措施实施周期',
  `monitoringTime` date NOT NULL COMMENT '监控时间',
  `monitoringResult` varchar(30) NOT NULL,
  `stateTracking` varchar(30) NOT NULL COMMENT '状态跟踪',
  `kpi` varchar(120) NOT NULL COMMENT '对应KPI指标(适用时)',
  `openedBy` varchar(30) NOT NULL COMMENT '有谁创建',
  `openedDate` datetime NOT NULL COMMENT '创建时间',
  `mailto` text NOT NULL,
  `closedBy` varchar(30) DEFAULT NULL,
  `closedDate` datetime NOT NULL,
  `deleted` enum('0','1') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zt_script` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `task` mediumint(9) NOT NULL,
  `scriptName` varchar(255) NOT NULL,
  `scriptType` varchar(30) NOT NULL,
  `lob` varchar(30) NOT NULL COMMENT '业务线',
  `frequency` varchar(100) NOT NULL COMMENT '使用频率',
  `configurationFile` varchar(255) NOT NULL COMMENT '配置文件',
  `output` varchar(255) NOT NULL COMMENT '输出',
  `precondition` varchar(255) NOT NULL COMMENT '执行前提',
  `performBody` varchar(255) NOT NULL COMMENT '执行环节',
  `performMode` varchar(30) NOT NULL COMMENT '执行方式',
  `performSystem` varchar(30) NOT NULL COMMENT '执行系统',
  `scriptPath` varchar(255) NOT NULL COMMENT '脚本路径',
  `releasedDate` date NOT NULL COMMENT '实际发版时间',
  `tester` varchar(30) NOT NULL COMMENT '测试者',
  `AT` varchar(30) NOT NULL COMMENT '测试验收',
  `released` varchar(30) NOT NULL COMMENT '运维发布',
  `notice` text NOT NULL COMMENT '注意事项',
  `deleted` enum('1','0') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zt_storyreview` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '需求名称',
  `product` mediumint(8) unsigned NOT NULL,
  `project` mediumint(8) unsigned NOT NULL,
  `storyReviewers` char(120) NOT NULL COMMENT '需求评审者',
  `testReviewers` char(120) NOT NULL COMMENT '测试评审人员',
  `devReviewers` char(120) NOT NULL COMMENT '开发评审人员',
  `otherReviewers` char(120) NOT NULL COMMENT '其他评审者',
  `reviewDate` date NOT NULL COMMENT '评审日期',
  `mailto` text NOT NULL,
  `reviewStories` text NOT NULL COMMENT '相关需求',
  `storySource` varchar(90) NOT NULL COMMENT '需求来源',
  `solvedProblem` text NOT NULL COMMENT '解决的问题',
  `consumed` float unsigned NOT NULL COMMENT '评审用时',
  `users` varchar(120) NOT NULL COMMENT '使用用户',
  `application` varchar(255) NOT NULL COMMENT '使用环节',
  `frequency` varchar(90) NOT NULL COMMENT '使用频率',
  `teamDev` varchar(120) NOT NULL COMMENT '涉及开发团队',
  `testDate` date NOT NULL COMMENT '提测时间',
  `acceptanceDate` date NOT NULL COMMENT '验收时间',
  `releasedDate` date NOT NULL COMMENT '上线时间',
  `deliverables` varchar(90) NOT NULL COMMENT '成果物',
  `needTest` enum('','0','1') NOT NULL DEFAULT '' COMMENT '是否需要测试',
  `risk` text NOT NULL COMMENT '风险',
  `influence` text NOT NULL COMMENT '影响范围',
  `leftProblem` enum('','0','1') NOT NULL DEFAULT '' COMMENT '是否有遗留问题',
  `problemTracking` text NOT NULL COMMENT '需求问题记录追踪',
  `resolution` varchar(15) NOT NULL COMMENT '解决方式',
  `solver` varchar(30) NOT NULL COMMENT '解决者',
  `resolvedDate` date NOT NULL COMMENT '解决日期',
  `countermeasure` varchar(255) NOT NULL COMMENT '解决对策',
  `result` varchar(255) NOT NULL COMMENT '评审结果',
  `comment` text NOT NULL COMMENT '备注（有无类似功能及相关业务联系功能）',
  `openedBy` varchar(30) NOT NULL COMMENT '创建者',
  `openedDate` datetime NOT NULL COMMENT '创建时间',
  `deleted` enum('0','1') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zt_undonetaskreport` (
  `project` mediumint(9) NOT NULL,
  `date` date NOT NULL,
  `undoneStoryTaskCount` smallint(6) NOT NULL,
  `undoneDevelTaskCount` smallint(6) NOT NULL,
  `undoneTestTaskCount` smallint(6) NOT NULL,
  PRIMARY KEY (`project`,`date`),
  KEY `undonetaskreport` (`project`,`date`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
alter table zt_bug MODIFY `case` VARCHAR(60);
UPDATE zt_bug SET `case` = '' WHERE `case` = 0;



UPDATE zt_block SET `order` = 99, `grid` = 6 WHERE `module` = 'my' AND `title` = '流程图' AND `order` = 1;
UPDATE zt_block SET `order` = 98, `grid` = 6 WHERE `module` = 'my' AND `title` = '进行中的项目' AND `order` = 3;
UPDATE zt_block SET `order` = 97, `grid` = 6 WHERE `module` = 'my' AND `title` = '未关闭的产品' AND `order` = 5;
UPDATE zt_block SET `order` = 96 WHERE `module` = 'my' AND `title` = '最新动态' AND `order` = 2;
UPDATE zt_block SET `order` = 95 WHERE `module` = 'my' AND `title` = '我的待办' AND `order` = 4;
UPDATE zt_block SET `order` = 94 WHERE `module` = 'my' AND `title` = '指派给我的用例' AND `order` = 9;
UPDATE zt_block SET `order` = 93 WHERE `module` = 'my' AND `title` = '指派给我的Bug' AND `order` = 7;
UPDATE zt_block SET `order` = 92 WHERE `module` = 'my' AND `title` = '指派给我的任务' AND `order` = 6;
UPDATE zt_block SET `order` = 91 WHERE `module` = 'my' AND `title` = '指派给我的需求' AND `order` = 8;
ALTER TABLE `zt_bug` ADD `workSeason` varchar(30) NOT NULL AFTER `title`;
ALTER TABLE `zt_story` ADD `devTeam` varchar(100) NOT NULL COMMENT '开发关联方' AFTER `plan`;
