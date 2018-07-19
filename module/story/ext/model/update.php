<?php
/**
 * Update a story.
 *
 * @param  int    $storyID
 * @access public
 * @return array the changes of the story.
 */
public function update($storyID)
{
    $now      = helper::now();
    $oldStory = $this->dao->select('*')->from(TABLE_STORY)->where('id')->eq($storyID)->fetch();
    if(!empty($_POST['lastEditedDate']) and $oldStory->lastEditedDate != $this->post->lastEditedDate)
    {
        dao::$errors[] = $this->lang->error->editedByOther;
        return false;
    }

    $story = fixer::input('post')
        ->cleanInt('product,module,pri')
        ->add('assignedDate', $oldStory->assignedDate)
        ->add('lastEditedBy', $this->app->user->account)
        ->add('lastEditedDate', $now)
        ->setDefault('status', $oldStory->status)
        ->setDefault('product', $oldStory->product)
        //->setDefault('plan', $oldStory->plan)
        ->setDefault('branch', $oldStory->branch)
        ->setDefault('specialPlan', $oldStory->specialPlan)
        ->setDefault('testDate', $oldStory->testDate)
        ->setIF($this->post->assignedTo   != $oldStory->assignedTo, 'assignedDate', $now)
        ->setIF($this->post->closedBy     != false and $oldStory->closedDate == '', 'closedDate', $now)
        ->setIF($this->post->closedReason != false and $oldStory->closedDate == '', 'closedDate', $now)
        ->setIF($this->post->closedBy     != false or  $this->post->closedReason != false, 'status', 'closed')
        ->setIF($this->post->closedReason != false and $this->post->closedBy     == false, 'closedBy', $this->app->user->account)
        ->join('reviewedBy', ',')
        ->join('mailto', ',')
        ->join('devTeam', ',')
        //需求可以指派给多人
        ->join('assignedTo', ',')
        ->remove('linkStories,childStories,files,labels,comment')
        ->get();
    if(is_array($story->plan)) $story->plan = trim(join(',', $story->plan), ',');
    if(empty($_POST['product'])) $story->branch = $oldStory->branch;

    $this->dao->update(TABLE_STORY)
        ->data($story)
        ->autoCheck()
        ->checkIF(isset($story->closedBy), 'closedReason', 'notempty')
        ->checkIF(isset($story->closedReason) and $story->closedReason == 'done', 'stage', 'notempty')
        ->checkIF(isset($story->closedReason) and $story->closedReason == 'duplicate',  'duplicateStory', 'notempty')
        ->where('id')->eq((int)$storyID)->exec();

    if(!dao::isError())
    {
        if($story->product != $oldStory->product)
        {
            $this->dao->update(TABLE_PROJECTSTORY)->set('product')->eq($story->product)->where('story')->eq($storyID)->exec();
            $storyProjects  = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('story')->eq($storyID)->orderBy('project')->fetchPairs('project', 'project');
            $linkedProjects = $this->dao->select('project')->from(TABLE_PROJECTPRODUCT)->where('project')->in($storyProjects)->andWhere('product')->eq($story->product)->orderBy('project')->fetchPairs('project','project');
            $unlinkedProjects = array_diff($storyProjects, $linkedProjects);
            foreach($unlinkedProjects as $projectID)
            {
                $data = new stdclass();
                $data->project = $projectID;
                $data->product = $story->product;
                $this->dao->replace(TABLE_PROJECTPRODUCT)->data($data)->exec();
            }
        }
        if(isset($story->closedReason) and $story->closedReason == 'done') $this->loadModel('score')->create('story', 'close');
        return common::createChanges($oldStory, $story);
    }
}
