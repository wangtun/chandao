<?php
include '../../control.php';
class myBug extends bug
{
    /**
     * 暂不关闭 bug。
     *
     * @param  int $bugID
     * @access public
     * @return void
     */
    public function withhold($bugID)
    {
        $bug = $this->bug->getById($bugID);
        
        if (!empty($_POST)) {
            $this->bug->withhold($bugID);
            if (dao::isError()) die(js::error(dao::getError()));

            //$changes = array();
            //$changes[] = array('field' => 'withhold', 'old' => $bug->withhold, 'new' => '1');
            //$action   = 'Edited';
            $actionID = $this->loadModel('action')->create('bug', $bugID, 'withhold', $this->post->comment);
            //$this->action->logHistory($actionID, $changes);
            
            if (isonlybody()) die(js::closeModal('parent.parent'));
            die(js::locate($this->createLink('bug', 'view', "bugID=$bugID"), 'parent'));
        }
        
        $productID = $bug->product;
        $this->bug->setMenu($this->products, $productID, $bug->branch);

        $this->view->title = $this->products[$productID] . $this->lang->colon . $this->lang->bug->withhold;
        $this->view->position[] = html::a($this->createLink('bug', 'browse', "productID=$productID"), $this->products[$productID]);
        $this->view->position[] = $this->lang->bug->withhold;

        $this->view->bug = $bug;
        $this->view->users = $this->user->getPairs('noletter');
        $this->view->actions = $this->action->getList('bug', $bugID);
        
        $this->display();
    }
}