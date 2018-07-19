<?php

/**
 * The control file of reviewbyproduct module of ZenTaoPMS.
 *
 * @copyright   RabbitDog
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      RabbitDog
 * @package     reviewbyproduct
 * @version     20171214
 */
class reviewbyproduct extends control
{
    public function __construct($moduleName = '', $methodName = '')
    {
        parent::__construct($moduleName, $methodName);
        /* Load need modules. */
        $this->loadModel('reviewbyproduct');
    }

    /**
     * 需求评审单列表页
     * 
     * @param $objectID
     * @param $from
     * @param string $type
     * @param int $param
     * @param string $orderBy
     * @param int $recTotal
     * @param int $recPerPage
     * @param int $pageID
     */
    public function reviewbyproduct($objectID, $from, $type = 'byModule', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $this->loadModel('project');

        $this->session->set('storyReviewByProductList', $this->app->getURI(true));
        $queryID   = ($type == 'bySearch')  ? (int)$param : 0;

        $table  = $from == 'qa' ? TABLE_PRODUCT : TABLE_PROJECT;
        $object = $this->dao->select('id,name')->from($table)->where('id')->eq($objectID)->fetch();

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* Append id for secend sort. */
        $sort = $this->loadModel('common')->appendOrder($orderBy);

        if($from == 'qa')
        {
            $this->lang->reviewbyproduct->menu      = $this->lang->reviewbyproduct->qa;
            $this->lang->reviewbyproduct->menuOrder = $this->lang->qa->menuOrder;

            //$this->reviewbyproduct->setMenu($this->product->getPairs(), $objectID);
            $this->lang->set('menugroup.reviewbyproduct', 'qa');
            $this->view->product       = $object;
            //$this->view->position[] = html::a(helper::createLink('product', 'browse', "productID=$objectID"), $object->name);
            $this->view->position[]   = $this->lang->reviewbyproduct->reviewbyproduct;
            /* Header and position. */
            $this->view->title        = $this->lang->colon . $this->lang->reviewbyproduct->reviewbyproduct;
            $this->view->reviewbyproduct = $this->reviewbyproduct->getStoryReviews((int)$objectID, $from, 'false', $sort, $type, $queryID, $pager);
            $actionURL    = $this->createLink('reviewbyproduct', 'reviewbyproduct', "productID=0&from=qa&type=bySearch&param=myQueryID");
        }
        elseif($from == 'project')
        {
            $this->lang->reviewbyproduct->menu      = $this->lang->project->menu;
            $this->lang->reviewbyproduct->menuOrder = $this->lang->project->menuOrder;
            $this->project->setMenu($this->project->getPairs('nocode'), $objectID, 'project');
            $this->lang->set('menugroup.reviewbyproduct', 'project');
            $this->view->project      = $object;
            //$this->view->products      = $this->project->getProducts($object->id);
            /* Header and position. */
            $this->view->title        = $object->name . $this->lang->colon . $this->lang->reviewbyproduct->reviewbyproduct;
            $this->view->position[]   = html::a(helper::createLink('product', 'browse', "productID=$objectID"), $object->name);
            $this->view->position[]   = $this->lang->reviewbyproduct->reviewbyproduct;
            $this->view->reviewbyproduct = $this->reviewbyproduct->getStoryReviews((int)$object->id, $from, 'false', $sort, $type, $queryID, $pager);
            $actionURL    = $this->createLink('reviewbyproduct', 'reviewbyproduct', "objectID=$object->id&from=project&type=bySearch&param=myQueryID");
        }

        $this->reviewbyproduct->buildStoryReviewSearchForm($actionURL, $queryID);

        $this->view->users    = $this->loadModel('user')->getPairs('noletter');
        $this->view->from     = $from;
        $this->view->type     = $type;
        $this->view->object   = $object;
        $this->view->pager    = $pager;
        $this->view->orderBy  = $orderBy;
        $this->view->objectID = $objectID;
        $this->view->param    = $param;

        $this->display();
    }

    /**
     * 需求评审单遗留问题
     *
     * @param $objectID
     * @param $from
     * @param string $type
     * @param string $status
     * @param int $param
     * @param string $orderBy
     * @param int $recTotal
     * @param int $recPerPage
     * @param int $pageID
     */
    public function leftProblem($objectID, $from, $status= 'all', $type = 'byModule', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        $this->loadModel('project');

        $this->session->set('storyReviewList', $this->app->getURI(true));
        $queryID = ($type == 'bySearch')  ? (int)$param : 0;

        $table  = $from == 'qa' ? TABLE_PRODUCT : TABLE_PROJECT;
        $object = $this->dao->select('id,name')->from($table)->where('id')->eq($objectID)->fetch();

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* Append id for secend sort. */
        $sort = $this->loadModel('common')->appendOrder($orderBy);

        if($from == 'qa')
        {
            $this->lang->reviewbyproduct->menu      = $this->lang->reviewbyproduct->qa;
            $this->lang->reviewbyproduct->menuOrder = $this->lang->qa->menuOrder;

            //$this->reviewbyproduct->setMenu($this->product->getPairs(), $objectID);
            $this->lang->set('menugroup.reviewbyproduct', 'qa');
            $this->view->product      = $object;
            //$this->view->position[] = html::a(helper::createLink('product', 'browse', "productID=$objectID"), $object->name);
            $this->view->position[]   = $this->lang->reviewbyproduct->leftProblemAB;
            /* Header and position. */
            $this->view->title        = $this->lang->colon . $this->lang->reviewbyproduct->leftProblemAB;
            $this->view->storyReviews = $this->reviewbyproduct->getStoryReviews((int)$objectID, $from, 'true', $sort, $type, $queryID, $pager, $status);
            $actionURL = $this->createLink('reviewbyproduct', 'reviewbyproduct', "productID=0&from=qa&type=bySearch&param=myQueryID");
        }
        elseif($from == 'project')
        {
            $this->lang->reviewbyproduct->menu      = $this->lang->project->menu;
            $this->lang->reviewbyproduct->menuOrder = $this->lang->project->menuOrder;
            $this->project->setMenu($this->project->getPairs('nocode'), $objectID, 'project');
            $this->lang->set('menugroup.reviewbyproduct', 'project');
            $this->view->project      = $object;
            //$this->view->products      = $this->project->getProducts($object->id);
            /* Header and position. */
            $this->view->title        = $object->name . $this->lang->colon . $this->lang->reviewbyproduct->leftProblemAB;
            $this->view->position[]   = html::a(helper::createLink('product', 'browse', "productID=$objectID"), $object->name);
            $this->view->position[]   = $this->lang->reviewbyproduct->leftProblemAB;
            $this->view->storyReviews = $this->reviewbyproduct->getStoryReviews((int)$object->id, $from, 'true', $sort, $type, $queryID, $pager, $status);
            $actionURL = $this->createLink('reviewbyproduct', 'reviewbyproduct', "objectID=$object->id&from=project&type=bySearch&param=myQueryID");
        }

        $this->reviewbyproduct->buildStoryReviewSearchForm($actionURL, $queryID);

        $this->view->users    = $this->loadModel('user')->getPairs('noletter');
        $this->view->from     = $from;
        $this->view->type     = $type;
        $this->view->object   = $object;
        $this->view->pager    = $pager;
        $this->view->orderBy  = $orderBy;
        $this->view->objectID = $objectID;
        $this->view->param    = $param;
        $this->view->status   = $status;

        $this->display();
    }

    /**
     * 创建需求评审单
     *
     * @param $projectID
     * @param $type
     */
    public function create($projectID, $type = '')
    {
        if(!empty($_POST))
        {
            $storyReviewListID = $this->reviewbyproduct->create($projectID, $type);
            if(dao::isError()) die(js::error(dao::getError()));
            
            $actionID = $this->loadModel('action')->create('reviewbyproduct', $storyReviewListID, 'opened');

            $this->reviewbyproduct->sendmail($storyReviewListID, $actionID);
            die(js::locate($this->createLink('reviewbyproduct', 'reviewbyproduct', "objectID=$projectID&from=project"), 'parent'));
        }

        /* Load these models. */
        $this->loadModel('project');
        $this->loadModel('user');

        $this->lang->reviewbyproduct->menu      = $this->lang->project->menu;
        $this->lang->menugroup->reviewbyproduct = 'project';

        
        /* Set menu. */
        $this->project->setMenu($this->project->getPairs(), $projectID);

        /* Assign. */
        $project = $this->loadModel('project')->getById($projectID);

        //$productGroups = $this->project->getProducts($projectID);
        //$productID     = key($productGroups);
        //$products      = array();
        //foreach($productGroups as $product) $products[$product->id] = $product->name;

        $this->view->title         = $project->name . $this->lang->colon . $this->lang->reviewbyproduct->create;
        $this->view->position[]    = html::a($this->createLink('project', 'task', "projectID=$projectID"), $project->name);
        $this->view->position[]    = $this->lang->reviewbyproduct->create;
        //$this->view->product       = isset($productGroups[$productID]) ? $productGroups[$productID] : '';
        $this->view->projectID     = $projectID;
        $this->view->stories       = $this->reviewbyproduct->getStoryPairs($projectID);
        //$this->view->products      = $products;
        //$this->view->productGroups = $productGroups;
        $this->view->users         = $this->user->getPairs('nodeleted|noclosed');
        $this->display();
    }

    /**
     * Edit a reviewbyproduct.
     *
     * @param  int    $storyReviewID
     * @param int $objectID
     * @param string $from
     * @param $type
     * @access public
     * @return void
     */
    public function edit($storyReviewID, $objectID, $from,  $type = '')
    {
        $this->loadModel('project');
        $this->loadModel('story');

        if(!empty($_POST))
        {
            $changes = $this->reviewbyproduct->update($storyReviewID, $from, $type);
            if(dao::isError()) die(js::error(dao::getError()));
            $files = $this->loadModel('file')->saveUpload('build', $storyReviewID);

            if($changes)
            {
                //$fileAction = '';
                //if(!empty($files)) $fileAction = $this->lang->addFiles . join(',', $files) . "\n" ;
                $actionID = $this->loadModel('action')->create('reviewbyproduct', $storyReviewID, 'Edited');
                if(!empty($changes)) $this->action->logHistory($actionID, $changes);

                /* send mail.*/
                $this->reviewbyproduct->sendmail($storyReviewID, $actionID);
            }
            if ($from == 'project')
            {
                die(js::locate($this->createLink('reviewbyproduct', 'view', "buildID=$storyReviewID&from=project"), 'parent'));
            }
            else
            {
                die(js::locate($this->createLink('reviewbyproduct', 'view', "buildID=$storyReviewID&from=qa"), 'parent'));
            }
        }

        $reviewbyproduct = $this->reviewbyproduct->getStoryReviewById((int)$storyReviewID);

        $reviewbyproduct->solvedProblem   = str_replace("<br />","\n",trim($reviewbyproduct->solvedProblem));
        $reviewbyproduct->risk            = str_replace("<br />","\n",$reviewbyproduct->risk);
        $reviewbyproduct->result          = str_replace("<br />","\n",$reviewbyproduct->result);
        $reviewbyproduct->influence       = str_replace("<br />","\n",$reviewbyproduct->influence);
        $reviewbyproduct->problemTracking = str_replace("<br />","\n",$reviewbyproduct->problemTracking);

        $orderBy     = 'status_asc, stage_asc, id_desc';

        if ($from == 'project')
        {
            $project = $this->loadModel('project')->getById($reviewbyproduct->project);
            if(empty($project))
            {
                $project = new stdclass();
                $project->name = '';
            }
            
            $this->loadModel('project')->setMenu($this->project->getPairs('nocode'), $objectID, 'project');
            
            /* Set menu. */
            $this->lang->reviewbyproduct->menu      = $this->lang->project->menu;
            $this->lang->menugroup->reviewbyproduct = 'project';

            $this->view->position[] = html::a($this->createLink('reviewbyproduct', 'reviewbyproduct', "objectID=$reviewbyproduct->project&from=project"), $project->name);

            $this->view->stories    = $this->reviewbyproduct->getStoryPairs($objectID, $reviewbyproduct->reviewStories);

            $noclosedProjects = $this->project->getPairs('noclosed,nocode');
            unset($noclosedProjects[$reviewbyproduct->project]);
            $this->view->projects = array($reviewbyproduct->project => $project->name) + $noclosedProjects;
        }
        elseif ($from == 'qa')
        {
            /* Set menu. */
            $this->lang->reviewbyproduct->menu      = $this->lang->reviewbyproduct->qa;
            $this->lang->menugroup->reviewbyproduct = 'qa';
            //$this->reviewbyproduct->setMenu($this->loadModel('product')->getPairs(), $objectID);

            $this->view->position[] = html::a($this->createLink('reviewbyproduct', 'reviewbyproduct', "objectID=0&from=qa"), $this->lang->reviewbyproduct->reviewbyproduct);
        }

        $this->view->position[] = $this->lang->reviewbyproduct->edit;
        $this->view->title      = $this->lang->reviewbyproduct->edit . $this->lang->colon . " #$reviewbyproduct->id $reviewbyproduct->title";
        //$this->view->product    = isset($productGroups[$reviewbyproduct->product]) ? $productGroups[$reviewbyproduct->product] : '';
        //$this->view->branches   = (isset($productGroups[$reviewbyproduct->product]) and $productGroups[$reviewbyproduct->product]->type == 'normal') ? array() : $this->loadModel('branch')->getPairs($reviewbyproduct->product);
        $this->view->orderBy     = $orderBy;
        $this->view->objectID    = $objectID;

        //$this->view->projects      = $this->project->getPairs('noclosed,nocode');
        //$this->view->productGroups = $productGroups;
        //$this->view->products      = $products;
        $this->view->users       = $this->loadModel('user')->getPairs();
        $this->view->reviewbyproduct = $reviewbyproduct;
        $this->view->from        = $from;

        $this->display();
    }

    /**
     * View a reviewbyproduct case.
     *
     * @param  int    $storyReviewID
     * @param  string $from
     * @access public
     * @return void
     */
    public function view($storyReviewID, $from = 'project')
    {
        $this->loadModel('project');
        $this->loadModel('product');
        
        $reviewbyproduct = $this->reviewbyproduct->getStoryReviewById((int)$storyReviewID);
        if(!$reviewbyproduct) die(js::error($this->lang->notFound) . js::locate('back'));

        if ($from == 'project')
        {
            $projects = $this->project->getPairs('empty');
            $this->loadModel('project')->setMenu($this->project->getPairs('nocode'), $reviewbyproduct->project, 'project');

            $this->lang->reviewbyproduct->menu      = $this->lang->project->menu;
            $this->lang->menugroup->reviewbyproduct = 'project';
            $this->view->position[] = html::a($this->createLink('project', 'task', "projectID=$reviewbyproduct->project"), $projects[$reviewbyproduct->project]);
            $this->view->position[] = $this->lang->reviewbyproduct->reviewbyproduct;
            $this->view->position[] = $this->lang->reviewbyproduct->view;
            $this->view->from = 'project';
            $this->view->objectID   = $reviewbyproduct->project;
            $this->view->title      = "reviewbyproduct #$reviewbyproduct->id $reviewbyproduct->title - " . $projects[$reviewbyproduct->project];
        }
        elseif($from == 'qa')
        {
            $this->lang->reviewbyproduct->menu      = $this->lang->reviewbyproduct->qa;
            $this->lang->reviewbyproduct->menuOrder = $this->lang->qa->menuOrder;
            $this->lang->menugroup->reviewbyproduct = 'qa';
            $product = $this->loadModel('product')->getById($reviewbyproduct->product);
            //$this->reviewbyproduct->setMenu($this->loadModel('product')->getPairs(), $reviewbyproduct->product);

            $this->view->position[] = html::a($this->createLink('product', 'browse', "productID=$reviewbyproduct->product"), $product->name);
            $this->view->position[] = $this->lang->reviewbyproduct->view;
            $this->view->from = 'qa';
            $this->view->objectID   = $reviewbyproduct->product;
            $this->view->title      = "reviewbyproduct #$reviewbyproduct->id $reviewbyproduct->title - " . $product->name;
        }

        if(!empty($reviewbyproduct->reviewStories)) $reviewbyproduct->reviewStories = $this->dao->select('id,title')->from(TABLE_STORY)->where('id')->in($reviewbyproduct->reviewStories)->fetchPairs();

        /* Assign. */
        $this->view->users       = $this->loadModel('user')->getPairs('noletter');
        $this->view->reviewbyproduct = $reviewbyproduct;
        $this->view->actions     = $this->loadModel('action')->getList('reviewbyproduct', $storyReviewID);
        
        $this->display();
    }

    /**
     * Delete a reviewbyproduct.
     *
     * @param  int    $storyReviewID
     * @param  string $confirm  yes|noe
     * @access public
     * @return void
     */
    public function delete($storyReviewID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            die(js::confirm($this->lang->reviewbyproduct->confirmDelete, $this->createLink('reviewbyproduct', 'delete', "$storyReviewID=$storyReviewID&confirm=yes")));
        }
        else
        {
            $reviewbyproduct = $this->reviewbyproduct->getStoryReviewById($storyReviewID);
            $this->reviewbyproduct->delete(TABLE_REVIEWBYPRODUCT, $storyReviewID);

            /* if ajax request, send result. */
            if($this->server->ajax)
            {
                if(dao::isError())
                {
                    $response['result']  = 'fail';
                    $response['message'] = dao::getError();
                }
                else
                {
                    $response['result']  = 'success';
                    $response['message'] = '';
                }
                $this->send($response);
            }

            die(js::locate($this->createLink('reviewbyproduct', 'reviewbyproduct', "objectID=$reviewbyproduct->project&from=project"), 'parent'));
        }
    }

    /**
     * Resolve a leftProblem.
     *
     * @param  int    $storyReviewID
     * @access public
     * @return void
     */
    public function resolve($storyReviewID)
    {
        if(!empty($_POST))
        {
            $this->loadModel('action');
            $changes = $this->reviewbyproduct->resolve($storyReviewID);

            if(dao::isError()) die(js::error(dao::getError()));
            //$this->loadModel('file')->saveUpload('reviewbyproduct', $storyReviewID);
            
            //$fileAction = !empty($files) ? $this->lang->addFiles . join(',', $files) . "\n" : '';
            $actionID = $this->action->create('reviewbyproduct', $storyReviewID, 'Resolved', '', $this->post->resolution);
            $this->action->logHistory($actionID, $changes);
            $this->reviewbyproduct->sendmail($storyReviewID, $actionID);
            
            if(isonlybody()) die(js::closeModal('parent.parent'));
            die(js::locate($this->createLink('reviewbyproduct', 'view', "storyReviewID=$storyReviewID"), 'parent'));
        }

        $reviewbyproduct = $this->reviewbyproduct->getStoryReviewById($storyReviewID);
        $this->view->title       = $this->lang->colon . $this->lang->reviewbyproduct->resolve;
        $this->view->reviewbyproduct = $reviewbyproduct;
        $this->view->actions     = $this->loadModel('action')->getList('reviewbyproduct', $storyReviewID);
        
        $this->display();
    }
}