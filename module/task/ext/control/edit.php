<?php
include '../../control.php';
class myTask extends task
{
    /**
     * Edit a task.
     *
     * @param  int    $taskID
     * @access public
     * @return void
     */
    public function edit($taskID, $comment = false)
    {
        
        $this->commonAction($taskID);
        if(!empty($_POST))
        {
            $this->loadModel('action');
            $changes = array();
            $files   = array();
            if($comment == false)
            {
                $changes = $this->task->update($taskID);
                //var_dump($changes);die;
                if(dao::isError()) die(js::error(dao::getError()));
                $files = $this->loadModel('file')->saveUpload('task', $taskID);
            }

            $task = $this->task->getById($taskID);
                
            if($this->post->comment != '' or !empty($changes) or !empty($files))
            {
                $action = !empty($changes) ? 'Edited' : 'Commented';
                $fileAction = !empty($files) ? $this->lang->addFiles . join(',', $files) . "\n" : '';
                $actionID = $this->action->create('task', $taskID, $action, $fileAction . $this->post->comment);
                if(!empty($changes)) $this->action->logHistory($actionID, $changes);
<<<<<<< HEAD
                $this->task->sendmail($taskID, $actionID);
=======
                $this->sendmail($taskID, $actionID);
>>>>>>> 56ce38b3ee68171c3a03a5f43eb2e61413da34c5
            }

            if($task->fromBug != 0)
            {
                foreach($changes as $change)
                {
                    if($change['field'] == 'status')
                    {
                        $confirmURL = $this->createLink('bug', 'view', "id=$task->fromBug");
                        $cancelURL  = $this->server->HTTP_REFERER;
                        die(js::confirm(sprintf($this->lang->task->remindBug, $task->fromBug), $confirmURL, $cancelURL, 'parent', 'parent'));
                    }
                }
            }
            die(js::locate($this->createLink('task', 'view', "taskID=$taskID"), 'parent'));
        }

        //xinzeng
        if ($this->view->task->type == 'review')
        {
            $this->view->review = $this->dao->select('*')
                ->from(TABLE_REVIEW)
                ->where('task')
                ->eq($taskID)
                ->fetch();
            $this->view->reviewDetails = $this->dao->select('*')
                ->from(TABLE_REVIEWDETAIL)
                ->where('reviewID')->eq($this->view->review->id)
                ->andWhere('deleted')->eq('0')
                ->fetchAll();
        }

        $noclosedProjects = $this->project->getPairs('noclosed,nocode');
        unset($noclosedProjects[$this->view->project->id]);
        $this->view->projects = array($this->view->project->id => $this->view->project->name) + $noclosedProjects;

        if(!isset($members[$this->view->task->assignedTo])) $members[$this->view->task->assignedTo] = $this->view->task->assignedTo;
        $this->view->title      = $this->lang->task->edit . 'TASK' . $this->lang->colon . $this->view->task->name;
        $this->view->position[] = $this->lang->task->common;
        $this->view->position[] = $this->lang->task->edit;
        $this->view->stories    = $this->story->getProjectStoryPairs($this->view->project->id);
        $this->view->users      = $this->loadModel('user')->getPairs('nodeleted', "{$this->view->task->openedBy},{$this->view->task->canceledBy},{$this->view->task->closedBy}");
        $this->view->modules    = $this->tree->getTaskOptionMenu($this->view->task->project);
        $this->display();
    }
}
