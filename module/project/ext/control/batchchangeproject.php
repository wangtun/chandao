<?php
include '../../control.php';
class myProject extends project
{
    public function batchChangeProject($projectID, $oldProjectId)
    {
        $data        = fixer::input('post')->get();
        $storyIDList = $data->storyIDList ? $data->storyIDList : array();
        //$this->dao->begin();
        foreach ($storyIDList as $storyID)
        {
            //需求转项目
            $this->dao->update(TABLE_PROJECTSTORY)
                ->set('project')->eq($projectID)
                ->where('story')->eq($storyID)
                ->andWhere('project')->eq($oldProjectId)
                ->limit(1)->exec();
           if (!dao::isError())
            {
                $this->loadModel('story')->setStage($storyID);
                $this->dao->update(TABLE_ACTION)
                    ->set('project')->eq($projectID)
                    ->set('extra')->eq($projectID)
                    ->where('objectID')->eq($storyID)
                    ->andWhere('project')->eq($oldProjectId)
                    ->andWhere('action')->eq('linked2project')
                    ->limit(1)->exec();
                //项目的版本转换
                $builds = $this->dao->select('*')->from(TABLE_BUILD)
                    ->where('project')->eq($oldProjectId)
                    ->andWhere('deleted')->eq('0')
                    ->fetchAll();
                foreach ($builds as $build)
                {
                    if (strstr($build->stories,$storyID))
                    {
                        //原项目的版本取消关联需求
                        $build->stories = trim(str_replace(",$storyID,", ',', ",$build->stories,"), ',');
                        $this->dao->update(TABLE_BUILD)
                            ->set('stories')->eq($build->stories)
                            ->where('id')->eq((int)$build->id)
                            ->andWhere('deleted')->eq('0')
                            ->exec();
                        //版本转到（复制）新项目，把需求关联到新项目的版本
                        $newBuild = $this->dao->select('*')->from(TABLE_BUILD)
                            ->where('project')->eq($projectID)
                            ->andWhere('deleted')->eq('0')
                            ->andWhere('name')->eq($build->name)
                            ->fetch();
                        if ($newBuild)
                        {
                            $newBuild->stories .= ',' . $storyID;
                            $this->dao->update(TABLE_BUILD)
                                ->set('stories')->eq($newBuild->stories)
                                ->where('id')->eq((int)$newBuild->id)
                                ->andWhere('deleted')->eq('0')
                                ->exec();
                        }
                        else
                        {
                            $build->stories = $storyID;
                            $build->project = $projectID;
                            unset($build->id);
                            $this->dao->insert(TABLE_BUILD)->data($build)->exec();
                        }
                    }
                }
                //任务转项目
                $this->dao->update(TABLE_TASK)
                    ->set('project')->eq($projectID)
                    ->set('`module`')->eq(0)
                    ->where('story')->eq($storyID)
                    ->andWhere('project')->eq($oldProjectId)
                    ->andWhere('deleted')->eq('0')
                    ->exec();
                //把任务对应的指派给转入新项目团队
                if (!dao::isError())
                {
                    $tasks = $this->dao->select('*')->from(TABLE_TASK)
                        ->where('story')->eq($storyID)
                        ->andWhere('project')->eq($projectID)
                        ->andWhere('deleted')->eq('0')
                        ->fetchAll();
                    foreach ($tasks as $task)
                    {
                        if ($task->assignedTo == '' || $task->assignedTo == 'closed') continue;
                        $isMember = $this->dao->select('*')->from(TABLE_TEAM)
                                    ->where('root')->eq($projectID)
                                    ->andWhere('account')->eq($task->assignedTo)
                                    ->fetch();
                        if (!$isMember)
                        {
                            $member = $this->dao->select('*')->from(TABLE_TEAM)
                                        ->where('root')->eq($oldProjectId)
                                        ->andWhere('account')->eq($task->assignedTo)
                                        ->fetch();
                            $member->root = $projectID;
                            unset($member->id);
                            $this->dao->insert(TABLE_TEAM)->data($member)->exec();
                        }
                    }
                }
                //任务转bug
                $this->dao->update(TABLE_BUG)
                    ->set('project')->eq($projectID)
                    ->where('story')->eq($storyID)
                    ->andWhere('project')->eq($oldProjectId)
                    ->andWhere('deleted')->eq('0')
                    ->exec();
                //任务转用例
                $cases = $this->dao->select('*')
                    ->from(TABLE_CASE)
                    ->where('story')->eq($storyID)
                    ->andWhere('deleted')->eq('0')
                    ->fetchAll();
                foreach ($cases as $case)
                {
                    $oldTestTasksAB  = $this->dao->select('t2.id, t2.name')
                        ->from(TABLE_TESTRUN)->alias('t1')
                        ->leftJoin(TABLE_TESTTASK)->alias('t2')
                        ->on('t1.task = t2.id')
                        ->where('t1.`case`')->eq($case->id)
                        ->andWhere('t2.`project`')->eq($oldProjectId)
                        ->andWhere('t2.`deleted`')->eq('0')
                        ->fetchAll();

                    foreach ($oldTestTasksAB as $oldTestTaskAB)
                    {
                        $oldTestTask = $this->dao->select('*')
                            ->from(TABLE_TESTTASK)
                            ->where('`id`')->eq($oldTestTaskAB->id)
                            ->andWhere('deleted')->eq('0')
                            ->fetch();
                        $TestTask = $this->dao->select('*')
                            ->from(TABLE_TESTTASK)
                            ->where('`name`')->eq($oldTestTaskAB->name)
                            ->andWhere('`project`')->eq($projectID)
                            ->andWhere('deleted')->eq('0')
                            ->fetch();
                        if (!$TestTask)
                        {
                            $oldTestTask->project = $projectID;
                            unset($oldTestTask->id);
                            $this->dao->insert(TABLE_TESTTASK)->data($oldTestTask)->exec();
                            if (!dao::isError())
                            {
                                $testTaskID = $this->dao->lastInsertID();
                                $this->dao->update(TABLE_TESTRUN)
                                    ->set('`task`')->eq($testTaskID)
                                    ->where('`case`')->eq($case->id)
                                    ->andWhere('`task`')->eq($oldTestTaskAB->id)
                                    ->exec();
                            }
                        }
                        else
                        {
                            $this->dao->update(TABLE_TESTRUN)
                                ->set('`task`')->eq($TestTask->id)
                                ->where('`case`')->eq($case->id)
                                ->andWhere('`task`')->eq($oldTestTaskAB->id)
                                ->exec();
                        }
                    }
                }
            }
       }
        die(js::locate($this->createLink('project', 'story', "projectID=$oldProjectId")));
    }
}
