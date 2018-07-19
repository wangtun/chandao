<?php
/**
 * Get info of a bug.
 *
 * @param  int    $bugID
 * @param  bool   $setImgSize
 * @access public
 * @return object
 */
public function getById($bugID, $setImgSize = false)
{
    $bug = $this->dao->select('t1.*, t2.name AS projectName, t3.title AS storyTitle, t3.status AS storyStatus, t3.version AS latestStoryVersion, t4.name AS taskName, t5.title AS planName')
        ->from(TABLE_BUG)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->leftJoin(TABLE_STORY)->alias('t3')->on('t1.story = t3.id')
        ->leftJoin(TABLE_TASK)->alias('t4')->on('t1.task = t4.id')
        ->leftJoin(TABLE_PRODUCTPLAN)->alias('t5')->on('t1.plan = t5.id')
        ->where('t1.id')->eq((int)$bugID)->fetch();
    if(!$bug) return false;

    $bug = $this->loadModel('file')->replaceImgURL($bug, 'steps');
    if($setImgSize) $bug->steps = $this->file->setImgSize($bug->steps);
    foreach($bug as $key => $value) if(strpos($key, 'Date') !== false and !(int)substr($value, 0, 4)) $bug->$key = '';

    if($bug->duplicateBug) $bug->duplicateBugTitle = $this->dao->findById($bug->duplicateBug)->from(TABLE_BUG)->fields('title')->fetch('title');
    //bug需要关联多个用例
    if($bug->case)         $bug->caseTitles        = $this->dao->select('id,title')->from(TABLE_CASE)->where('id')->in($bug->case)->fetchPairs();

    if($bug->linkBug)      $bug->linkBugTitles     = $this->dao->select('id,title')->from(TABLE_BUG)->where('id')->in($bug->linkBug)->fetchPairs();
    if($bug->toStory > 0)  $bug->toStoryTitle      = $this->dao->findById($bug->toStory)->from(TABLE_STORY)->fields('title')->fetch('title');
    if($bug->toTask > 0)   $bug->toTaskTitle       = $this->dao->findById($bug->toTask)->from(TABLE_TASK)->fields('name')->fetch('name');

    $bug->toCases = array();
    $toCases      = $this->dao->select('id, title')->from(TABLE_CASE)->where('`fromBug`')->eq($bugID)->fetchAll();
    foreach($toCases as $toCase) $bug->toCases[$toCase->id] = $toCase->title;

    $bug->files = $this->loadModel('file')->getByObject('bug', $bugID);

    return $bug;
}
