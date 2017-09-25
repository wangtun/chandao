<?php
//增加legendComment字段；（导出bug的备注）
/*$config->bug->list->exportFields = 'id, product, branch, module, project, story, task,
    title, keywords, severity, pri, type, os, browser,
    steps, status, activatedCount, confirmed, mailto,
    openedBy, openedDate, openedBuild, 
    assignedTo, assignedDate,
    resolvedBy, resolution, resolvedBuild, resolvedDate,
    closedBy, closedDate, 
    duplicateBug, linkBug, 
    case,
    lastEditedBy,
    lastEditedDate, legendComment, files';*/
//增加deadline字段导出
$config->bug->list->exportFields = 'id, product, branch, module, project, story, task, 
    title, keywords, severity, pri, type, os, browser,
    steps, status, activatedCount, confirmed, mailto,
    openedBy, openedDate, openedBuild,deadline, 
    assignedTo, assignedDate,
    resolvedBy, resolution, resolvedBuild, resolvedDate,
    closedBy, closedDate, 
    duplicateBug, linkBug, 
    case,
    lastEditedBy,
    lastEditedDate, files';

//1747 提bug时，限定某些字段为必填项
$config->bug->create->requiredFields  = 'module,project,openedBuild,title,severity,pri';
$config->bug->list->customCreateFields      = 'project,story,task,pri,severity,os,browser,mailto,keywords';

//2061 提bug页面增加一个字段“发现阶段”，该字段需要支持后续搜索
$insert_array = array('discoveryPhase' => $lang->bug->discoveryPhase);
$first_array = array_splice ($config->bug->search['fields'], 0, 4);
$config->bug->search['fields'] = array_merge ($first_array, $insert_array, $config->bug->search['fields']);

$config->bug->search['params']['discoveryPhase']  = array('operator' => '=', 'control' => 'select',  'values' => $lang->bug->discoveryPhaseList);

$config->bug->datatable->fieldList['discoveryPhase']['title']    = 'discoveryPhase';
$config->bug->datatable->fieldList['discoveryPhase']['fixed']    = 'no';
$config->bug->datatable->fieldList['discoveryPhase']['width']    = '80';
$config->bug->datatable->fieldList['discoveryPhase']['required'] = 'no';