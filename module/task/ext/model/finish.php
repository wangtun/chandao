<?php
/**
 * Finish a task.
 *
 * @param  int      $taskID
 * @access public
 * @return void
 */
public function finish($taskID)
{
    $emptyReviewDetail  = new stdClass();
    $reviewDetail       = array();
    $emptyReview        = new stdClass();
    $review             = new stdClass();
    $taskDetail         = new stdClass();
    $estimate           = new stdClass();

    $emptyReviewDetail->reviewID = '';
    $emptyReviewDetail->number = '';
    $emptyReviewDetail->reviewer = '';
    $emptyReviewDetail->item = '';
    $emptyReviewDetail->line = '';
    $emptyReviewDetail->severity = '';
    $emptyReviewDetail->description = '';
    $emptyReviewDetail->proposal = '';
    $emptyReviewDetail->changed = '';
    $emptyReviewDetail->action = '';
    $emptyReviewDetail->chkd = '';

    $oldTask = $this->getById($taskID);
    $now  = helper::now();

    if(strpos($this->config->task->finish->requiredFields, 'comment') !== false and !$this->post->comment)
    {
        dao::$errors[] = sprintf($this->lang->error->notempty, $this->lang->comment);
        return false;
    }

    $task = fixer::input('post')
        ->setDefault('left', 0)
        ->setDefault('assignedTo',   $oldTask->openedBy)
        ->setDefault('assignedDate', $now)
        ->setDefault('status', 'done')
        ->setDefault('finishedBy, lastEditedBy', $this->app->user->account)
        ->setDefault('finishedDate, lastEditedDate', $now)
        ->removeIF(!empty($oldTask->team), 'finishedBy,finishedDate,status')
        ->remove('comment,files,labels')
        ->get();
    //1340 任务点击完成时，开启时间和完成时间改为必填项。
    if ($task->realStarted =='0000-00-00')
    {
        die(js::error($this->lang->task->error->doneError));
    }

    if(!is_numeric($task->consumed)) die(js::error($this->lang->task->error->consumedNumber));

    /* Record consumed and left. */
    if(empty($oldTask->team))
    {
        $consumed = $task->consumed - $oldTask->consumed;
        if($consumed < 0) die(js::error($this->lang->task->error->consumedSmall));
    }
    else
    {
        $consumed = $oldTask->team[$this->app->user->account]->consumed;
        if($task->consumed < $consumed) die(js::error($this->lang->task->error->consumedSmall));
    }

    $estimate->uid     = $this->post->uid;
    $estimate->account = $this->app->user->account;
    $estimate->task    = $taskID;
    $estimate->date    = date(DT_DATE1);
    $estimate->left    = 0;

    $estimate->consumed = $consumed;
    if($estimate->consumed) $this->addTaskEstimate($estimate);

    if(!empty($oldTask->team))
    {
        $this->dao->update(TABLE_TEAM)
            ->set('left')->eq(0)
            ->set('consumed')->eq($task->consumed)
            ->where('root')->eq((int)$taskID)
            ->andWhere('type')->eq('task')
            ->andWhere('account')->eq($this->app->user->account)->exec();

        $task = $this->computeHours4Multiple($oldTask, $task);
    }

    if($task->finishedDate == substr($now, 0, 10)) $task->finishedDate = $now;

    $taskInfo = $this->dao->select('*')->from(TABLE_TASK)->where('id')->eq("$taskID")->fetch();

    $taskDetail->consumed = $task->consumed;
    $taskDetail->assignedTo = $task->assignedTo;
    $taskDetail->finishedDate = $task->finishedDate;
    $taskDetail->left = $task->left;
    $taskDetail->assignedDate = $task->assignedDate;
    $taskDetail->status = $task->status;
    $taskDetail->finishedBy = $task->finishedBy;
    $taskDetail->lastEditedBy = $task->lastEditedBy;
    $taskDetail->lastEditedDate = $task->lastEditedDate;

    if (isset($task->tid))
    {
        foreach ($task->tid as $scriptTaskID)
        {
            $scriptTask = new stdClass();
            $scriptTask->tester = $task->tester[$scriptTaskID];
            $scriptTask->AT = $task->AT[$scriptTaskID];
            $this->dao->update(TABLE_SCRIPT)->data($scriptTask)->autoCheck()->where('task')->eq((int)$scriptTaskID)->exec();
        }
    }

    //1.task
    $this->dao->begin();
    $this->dao->update(TABLE_TASK)->data($taskDetail)
        ->autoCheck()
        ->batchCheck($this->config->task->finish->requiredFields, 'notempty')
        ->where('id')->eq((int)$taskID)
        ->exec();

    if(dao::isError())
    {
        $this->dao->rollback();
        return false;
    }

    $changes = common::createChanges($oldTask, $taskDetail);

    if ($taskInfo->type == 'script')
    {
        if($task->scriptName == '') die(js::error($this->lang->task->error->scriptNameEmpty));
        if($task->scriptType == '') die(js::error($this->lang->task->error->scriptTypeEmpty));
        if($task->lob == '') die(js::error($this->lang->task->error->lobEmpty));
        if($task->frequency == '') die(js::error($this->lang->task->error->frequencyEmpty));
        if($task->configurationFile == '') die(js::error($this->lang->task->error->configurationFileEmpty));
        if($task->output == '') die(js::error($this->lang->task->error->outputEmpty));
        if($task->precondition == '') die(js::error($this->lang->task->error->preconditionDocEmpty));
        if($task->performBody == '') die(js::error($this->lang->task->error->performBodyEmpty));
        if($task->performMode == '') die(js::error($this->lang->task->error->performModeEmpty));
        if($task->performSystem == '') die(js::error($this->lang->task->error->performSystemEmpty));
        if($task->scriptPath == '') die(js::error($this->lang->task->error->scriptPathEmpty));
        
        $oldScriptDetail = $this->dao->select('*')
            ->from(TABLE_SCRIPT)
            ->where('task')->eq("$taskID")
            ->andWhere('deleted')->eq('0')
            ->fetch();

        $scriptDetail = new stdClass();
        $scriptDetail->task = $taskID;
        $scriptDetail->scriptName = $task->scriptName;
        $scriptDetail->scriptType = $task->scriptType;
        $scriptDetail->lob = $task->lob;
        $scriptDetail->frequency = $task->frequency;
        $scriptDetail->configurationFile = $task->configurationFile;
        $scriptDetail->output = $task->output;
        $scriptDetail->precondition = $task->precondition;
        $scriptDetail->performBody = $task->performBody;
        $scriptDetail->performMode = $task->performMode;
        $scriptDetail->performSystem = $task->performSystem;
        $scriptDetail->scriptPath = $task->scriptPath;
        $scriptDetail->notice = $task->notice;

        if ($oldScriptDetail)
        {
            $this->dao->update(TABLE_SCRIPT)->data($scriptDetail)
                ->autoCheck()
                ->where('task')->eq($taskID)->limit(1)->exec();

            if(dao::isError())
            {
                $this->dao->rollback();
                return false;
            }
            else
            {
                $scriptChange = common::createChanges($oldScriptDetail, $scriptDetail);
                $changes = array_merge($changes, $scriptChange);
            }
        }
        else
        {
            $this->dao->insert(TABLE_SCRIPT)->data($scriptDetail)
                ->autoCheck()
                ->exec();

            if(dao::isError())
            {
                $this->dao->rollback();
                return false;
            }
        }
    }

    if ($taskInfo->type == 'review')
    {
        $review->fileNO = $task->fileNO;
        $review->recorder = $task->recorder;
        $review->reviewName = $task->reviewName;
        $review->task = $taskID;
        $review->doc = $task->doc;
        $review->referenceDoc = $task->referenceDoc;
        $review->reference = $task->reference;
        $review->pages = $task->pages;
        $review->reviewers = $task->reviewers;
        $review->reviewDate = $task->reviewDate;
        $review->reviewScope = $task->reviewScope;
        $review->reviewPlace = $task->reviewPlace;
        $review->effort = $task->effort;
        $review->conclusion = $task->conclusion;

        //附件为空跳出
        if($_FILES['files']['size']['0'] == '0') die(js::error($this->lang->task->error->fileNotEmpty));

        if($task->fileNO == '') die(js::error($this->lang->task->error->fileNOEmpty));
        if($task->recorder == '') die(js::error($this->lang->task->error->recorderEmpty));
        if($task->reviewName == '') die(js::error($this->lang->task->error->reviewNameEmpty));
        if($task->reviewDate == '') die(js::error($this->lang->task->error->reviewDateEmpty));
        if($task->doc == '') die(js::error($this->lang->task->error->docEmpty));
        if($task->reviewScope == '') die(js::error($this->lang->task->error->reviewScopeEmpty));
        if($task->referenceDoc == '') die(js::error($this->lang->task->error->referenceDocEmpty));
        if($task->reviewPlace == '') die(js::error($this->lang->task->error->reviewPlaceEmpty));
        if($task->reference == '') die(js::error($this->lang->task->error->referenceEmpty));
        if($task->effort == '') die(js::error($this->lang->task->error->effortEmpty));
        if($task->pages == '') die(js::error($this->lang->task->error->pagesEmpty));
        if($task->conclusion == '') die(js::error($this->lang->task->error->conclusionEmpty));
        if($task->reviewers == '') die(js::error($this->lang->task->error->reviewersEmpty));

        if(!is_numeric($task->effort)) die(js::error($this->lang->task->error->effortNumber));
        if(!is_numeric($task->pages)) die(js::error($this->lang->task->error->pagesNumber));

        //2.insert review
        $this->dao->insert(TABLE_REVIEW)->data($review)
            ->autoCheck()
            ->batchCheck($this->config->task->finish->requiredFields, 'notempty')
            ->exec();
        if (!dao::isError()) {
            $reviewID = $this->dao->lastInsertID();
        } else {
            $this->dao->rollback();
            return false;
        }

        $emptyReviewDetail->reviewID = $reviewID;

        $num = count($task->number);
        for ($i = 0; $i < $num; $i++) {
            $reviewDetail["$i"]->reviewID = $reviewID;
            $reviewDetail["$i"]->number = $task->number["$i"];
            $reviewDetail["$i"]->reviewer = $task->reviewer["$i"];
            $reviewDetail["$i"]->item = $task->item["$i"];
            $reviewDetail["$i"]->line = $task->line["$i"];
            $reviewDetail["$i"]->severity = $task->severity["$i"];
            $reviewDetail["$i"]->description = $task->description["$i"];
            $reviewDetail["$i"]->proposal = $task->proposal["$i"];
            $reviewDetail["$i"]->changed = $task->changed["$i"];
            $reviewDetail["$i"]->action = $task->action["$i"];
            $reviewDetail["$i"]->chkd = $task->chkd["$i"];
            //过滤空记录
            if ($reviewDetail["$i"]->description == '')
            {
                continue;
            }
            $this->dao->insert(TABLE_REVIEWDETAIL)->data($reviewDetail["$i"])
                ->autoCheck()
                ->batchCheck($this->config->task->create->requiredFields, 'notempty')
                ->exec();
            if (!dao::isError()) {
                $reviewDetailChanges["$i"] = common::createChanges($emptyReviewDetail, $reviewDetail["$i"]);
            } else {
                $this->dao->rollback();
                return false;
            }
        }
        //获得改变值
        //建一个空对象
        $emptyReview->fileNO = '';
        $emptyReview->recorder = '';
        $emptyReview->reviewName = '';
        $emptyReview->task = $taskID;
        $emptyReview->doc = '';
        $emptyReview->referenceDoc = '';
        $emptyReview->reference = '';
        $emptyReview->pages = '';
        $emptyReview->reviewers = '';
        $emptyReview->reviewDate = '';
        $emptyReview->reviewScope = '';
        $emptyReview->reviewPlace = '';
        $emptyReview->effort = '';
        $emptyReview->conclusion = '';
        $reviewChange = common::createChanges($emptyReview, $review);
        $changes = array_merge($changes,$reviewChange);

        foreach($reviewDetailChanges as $reviewDetailChange)
        {
            $changes = array_merge($changes,$reviewDetailChange);
        }
    }

    //成功操作
    $this->dao->commit();
    //设置需求状态
    if($oldTask->parent) $this->updateParentStatus($taskID);

    if($oldTask->story) $this->loadModel('story')->setStage($oldTask->story);
    if($task->status == 'done' && !dao::isError()) $this->loadModel('score')->create('task', 'finish', $taskID);
    return $changes;
}