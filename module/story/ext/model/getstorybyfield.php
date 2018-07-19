<?php
/**
 * Get stories of a user.
 *
 * @param  string $type         the query type
 * @param $browseType string
 * @param $queryID int
 * @param  string $orderBy
 * @param  object $pager
 * @access public
 * @return array
 */
public function getStoriesByField($type = 'toTestStory', $browseType, $queryID, $orderBy, $pager = null)
{
    //$limitDate = date("Y-m-d",strtotime("+10 day"));
    
    if ($browseType == 'bysearch')
    {
        $products = $this->loadModel('product')->getPairs();

        if($queryID)
        {
            $query = $this->loadModel('search')->getQuery($queryID);
            if($query)
            {
                $this->session->set('storyQuery', $query->sql);
                $this->session->set('storyForm', $query->form);
            }
            else
            {
                $this->session->set('storyQuery', ' 1 = 1');
            }
        }

        $allProduct     = "`product` = 'all'";
        $storyQuery = $this->session->storyQuery;
        if(strpos($storyQuery, $allProduct) !== false)
        {
            $storyQuery     = str_replace($allProduct, '1', $storyQuery);
            $queryProductID = 'all';
        }
        $storyQuery = $storyQuery . ' AND `product` ' . helper::dbIN(array_keys($products));
        $storyQuery = preg_replace('/`(\w+)`/', 't1.`$1`', $storyQuery);

        $stories = $this->dao->select('t1.*, t2.name as productTitle')->from(TABLE_STORY)->alias('t1')
            ->leftJoin(TABLE_PRODUCT)->alias('t2')->on('t1.product = t2.id')
            ->where('t1.deleted')->eq(0)
            ->andWhere('product')->notin($this->config->story->storyCollectionPoolProducts)
            ->andWhere($storyQuery)
            ->beginIF($type == 'toTestStory')->andWhere('stage')->in('projected,developing,developed')->andWhere('testDate')->ne('0000-00-00')->andWhere('verifyStatus')->ne('freeVerified')->fi()
            ->beginIF($type == 'toReleaseStory')->andWhere('stage')->notin('released,wait,planned')->andWhere('specialPlan')->ne('0000-00-00')->fi()
            ->andWhere("IF (t1.`status` = 'closed',t1.closedReason = 'done',2>1)")
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll();
    }
    else
    {
        $stories = $this->dao->select('t1.*, t2.name as productTitle')->from(TABLE_STORY)->alias('t1')
            ->leftJoin(TABLE_PRODUCT)->alias('t2')->on('t1.product = t2.id')
            ->where('t1.deleted')->eq(0)
            ->andWhere('product')->notin($this->config->story->storyCollectionPoolProducts)
            ->andWhere('product')->eq(2)
            ->beginIF($type == 'toTestStory')->andWhere('stage')->in('projected,developing,developed')->andWhere('testDate')->ne('0000-00-00')->andWhere('verifyStatus')->ne('freeVerified')->fi()
            ->beginIF($type == 'toReleaseStory')->andWhere('stage')->notin('released,wait,planned')->andWhere('specialPlan')->ne('0000-00-00')->fi()
            ->andWhere("IF (t1.`status` = 'closed',t1.closedReason = 'done',2>1)")
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll();
    }

    //$this->loadModel('common')->saveQueryCondition($this->dao->get(), 'story');
    $productIdList = array();
    //2828 我的地盘--指派给我的需求（点击需求可以直接定位到可以拆任务的界面）
    foreach($stories as $story)
    {
        $productIdList[$story->product] = $story->product;
        $story->projectID = $this->dao->select('project')->from(TABLE_PROJECTSTORY)->where('story')->eq($story->id)->fetch('project');
        $this->processStory($story);

        if (!empty($story->linkStories))
        {
            $relatedStories = $this->dao->select('id,openedBy')->from(TABLE_STORY) ->where('id')->in(trim($story->linkStories, ','))->fetchPairs();
            $users = $this->loadModel('user')->getPairs('noletter');
            foreach ($relatedStories as $relatedStory)
            {
                $story->linkStoryOpenedBys .= $users[$relatedStory] . ' ';
            };
            $story->linkStoryOpenedBys = implode(' ', array_unique(explode(' ',trim($story->linkStoryOpenedBys))));
        }
    }

    return $this->mergePlanTitle($productIdList, $stories);
}

/**
 * Process a task, judge it's status.
 *
 * @param  object    $story
 * @access private
 * @return object
 */
public function processStory($story)
{
    $today = helper::today();

    /* Delayed or not?. */
    if($story->stage != 'released' and $story->stage != 'verified' and $story->stage != 'tested' and $story->stage != 'testing')
    {
        if($story->testDate != '0000-00-00')
        {
            $delay = helper::diffDate($story->testDate, $today);

            if ($delay > 0 and $delay < 4)
            {
                $story->testWarning = 'green';
            }
            elseif($delay == 0)
            {
                $story->testWarning = 'blue';
            }
            elseif($delay < 0)
            {
                $story->testWarning = 'red';
            }
        }
    }
    if($story->stage != 'released')
    {
        if($story->specialPlan != '0000-00-00')
        {
            $delay = helper::diffDate($story->specialPlan, $today);

            if ($delay > 0 and $delay < 4)
            {
                $story->releaseWarning = 'green';
            }
            elseif($delay == 0)
            {
                $story->releaseWarning = 'blue';
            }
            elseif($delay < 0)
            {
                $story->releaseWarning = 'red';
            }
        }
    }

    $builds      = $this->dao->select('id')->from(TABLE_BUILD)->where('stories')->like('%,' . $story->id . '%')->fetchAll('id');
    $patchBuilds = $this->dao->select('id')->from(TABLE_PATCHBUILD)->where('linkStories')->like('%,' . $story->id . '%')->fetchAll('id');
    $releases = $this->dao->select('id')->from(TABLE_RELEASE)->where('stories')->like('%,' . $story->id . '%')->fetchAll('id');
    $story->build      = array_keys($builds);
    $story->patchBuild = array_keys($patchBuilds);
    $story->release = array_keys($releases);

    return $story;
}