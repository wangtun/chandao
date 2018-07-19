<?php
include '../../control.php';
class myReport extends report
{
    /**
     * 用户bug统计.
     *
     * @return void
     */
    public function userBugSummary()
    {
        $this->app->loadLang('bug');
        $this->view->title      = $this->lang->report->userBugSummary;
        $this->view->position[] = $this->lang->report->userBugSummary;
        $this->view->submenu    = 'staff';
        $this->view->assigns    = $this->report->getUserBugSummary();
        //var_dump($this->view->assigns);die;
        $this->view->users      = $this->loadModel('user')->getPairs('noletter|noclosed|nodeleted');
        $this->display();
    }
}
