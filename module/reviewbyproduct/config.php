<?php
$config->reviewbyproduct = new stdclass();

$config->reviewbyproduct->editor   = new stdclass();

$config->reviewbyproduct->editor->create   = array('id' => 'comment', 'tools' => 'simpleTools');
$config->reviewbyproduct->editor->edit     = array('id' => 'comment,countermeasure', 'tools' => 'simpleTools');
$config->reviewbyproduct->editor->resolve  = array('id' => 'countermeasure', 'tools' => 'simpleTools');

global $lang;
$config->reviewbyproduct->search['module']                   = 'reviewbyproduct';
$config->reviewbyproduct->search['fields']['id']             = $lang->reviewbyproduct->id;
$config->reviewbyproduct->search['fields']['title']          = $lang->reviewbyproduct->title;
//$config->storyreview->search['fields']['reviewStories']  = $lang->storyreview->reviewStories;
$config->reviewbyproduct->search['fields']['storyReviewers'] = $lang->reviewbyproduct->storyReviewers;
$config->reviewbyproduct->search['fields']['devReviewers']   = $lang->reviewbyproduct->devReviewers;
$config->reviewbyproduct->search['fields']['testReviewers']  = $lang->reviewbyproduct->testReviewers;
$config->reviewbyproduct->search['fields']['reviewDate']     = $lang->reviewbyproduct->reviewDate;
$config->reviewbyproduct->search['fields']['leftProblem']    = $lang->reviewbyproduct->leftProblem;
$config->reviewbyproduct->search['fields']['problemRecord']= $lang->reviewbyproduct->problemRecord;

$config->reviewbyproduct->search['params']['title']           = array('operator' => 'include', 'control' => 'input',  'values' => '');
$config->reviewbyproduct->search['params']['storyReviewers']  = array('operator' => 'include', 'control' => 'select', 'values' => 'users');
$config->reviewbyproduct->search['params']['devReviewers']    = array('operator' => 'include', 'control' => 'select', 'values' => 'users');
$config->reviewbyproduct->search['params']['testReviewers']   = array('operator' => 'include', 'control' => 'select', 'values' => 'users');
$config->reviewbyproduct->search['params']['reviewDate']      = array('operator' => '=', 'control' => 'input', 'values' => '',  'class' => 'date');
$config->reviewbyproduct->search['params']['storySource']     = array('operator' => 'include', 'control' => 'input',  'values' => '');
$config->reviewbyproduct->search['params']['users']           = array('operator' => 'include', 'control' => 'input',  'values' => '');
$config->reviewbyproduct->search['params']['leftProblem']     = array('operator' => '=', 'control' => 'select',  'values' => $lang->reviewbyproduct->leftProblemList);
$config->reviewbyproduct->search['params']['problemRecord'] = array('operator' => 'include', 'control' => 'input',  'values' => '');
