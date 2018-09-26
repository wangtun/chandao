<?php
/**
 * Build task search form.
 *
 * @param  int    $projectID
 * @param  array  $projects
 * @param  int    $queryID
 * @param  string $actionURL
 * @access public
 * @return void
 */
public function buildTaskSearchForm($projectID, $projects, $queryID, $actionURL)
{
    $this->config->project->search['actionURL'] = $actionURL;
    $this->config->project->search['queryID']   = $queryID;
    //搜索框实现多项目下任务的搜索16-20
    //$this->config->project->search['params']['project']['values'] = array(''=>'', $projectID => $projects[$projectID], 'all' => $this->lang->project->allProject);
    $this->config->project->search['params']['project']['values'] = array(''=>'', 'all' => $this->lang->project->allProject);
    $this->config->project->search['params']['project']['values'] = $this->config->project->search['params']['project']['values'] +  $projects;
//优化搜索功能增加空选项；
    $this->config->project->search['params']['module']['values']  = array('' => '') + $this->loadModel('tree')->getTaskOptionMenu($projectID, $startModuleID = 0);

    $prodocutID = $this->dao->select('product')->from(TABLE_PROJECTPRODUCT)->where('project')->eq((int)$projectID);
    $this->config->project->search['params']['branch']['values']  =  $this->loadModel('branch')->getPairs((int)$prodocutID);

    $this->loadModel('search')->setSearchParams($this->config->project->search);
}