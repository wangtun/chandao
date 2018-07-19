<?php
include '../../control.php';
class myReport extends report
{
    /**
     * 用户任务统计.
     *
     * @param $begin string
     * @param $end string
     * @return void
     */
    public function userTaskSummary($begin = '', $end = '')
    {
        if($_POST)
        {
            $data    = fixer::input('post')->get();
            $begin   = $data->begin;
            $end     = $data->end;
        }

        $begin  = $begin ? strtotime($begin) : time();
        $end    = $end   ? strtotime($end)   : time() + (7 * 24 * 3600);
        $end   += 24 * 3600;
        $begin  = date('Y-m-d', $begin);
        $end    = date('Y-m-d', $end);

        $this->view->userTasks = $this->report->getUserTaskSummary($begin, $end);
        $this->view->users     = $this->loadModel('user')->getPairs('noletter|noclosed|nodeleted');
        $this->view->begin     = $begin;
        $this->view->end       = date('Y-m-d', strtotime($end) - 24 * 3600);

        $this->view->title      = $this->lang->report->userTaskSummary;
        $this->view->position[] = $this->lang->report->userTaskSummary;
        $this->view->submenu    = 'staff';
        $this->display();
    }
}
