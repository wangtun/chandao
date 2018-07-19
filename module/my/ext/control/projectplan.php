<?php
include '../../control.php';
class myMy extends my
{

    /**
     * 项目计划
     *
     * @access public
     * @return void
     */
    public function projectPlan()
    {
        /* Get doc. */

        $nowLoginDefaultPage = $this->dao->select('value')->from(TABLE_CONFIG)->where('`key`')->eq('loginDefaultPage')->fetch('value');

        $this->loadModel('doc');

        if (isset($nowLoginDefaultPage))
        {
            $data = explode('-', rtrim(trim($nowLoginDefaultPage, '/'), '.html'));

            if ($data['0']== 'doc' && $data['1']=='view')
            {
                $doc = $this->doc->getById($data['2']);
                /*$lib  = $this->doc->getLibByID($doc->lib);

                if (common::hasPriv('doc', 'view') && $this->doc->checkPriv($lib))
                {
                    die(js::locate($this->createLink('doc', 'view', array($data[2]))));
                }*/
            }
            elseif ($data['0']== 'my' && $data['1']=='todostory')
            {
                if (common::hasPriv('my', 'todostory'))
                {
                    die(js::locate($this->createLink('my', 'todostory')));
                }
            }
            else
            {
                $this->locate($this->createLink('my', 'index'));
            }
        }
        
        if(!$doc) die(js::error($this->lang->notFound) . js::locate('back'));

        if($doc->contentType == 'markdown')
        {
            $hyperdown    = $this->app->loadClass('hyperdown');
            $doc->content = $hyperdown->makeHtml($doc->content);
            $doc->digest  = $hyperdown->makeHtml($doc->digest);
        }

        /* Check priv when lib is product or project. */
        $lib  = $this->doc->getLibByID($doc->lib);
        $type = $lib->product ? 'product' : ($lib->project ? 'project' : 'custom');

        /* Set menu. */
        //$this->doc->setMenu($doc->lib, $doc->module);

        $this->view->title      = "DOC #$doc->id $doc->title - " . $lib->name;
        $this->view->position[] = html::a($this->createLink('doc', 'browse', "libID=$doc->lib"), $lib->name);
        $this->view->position[] = $this->lang->doc->view;

        $this->view->doc        = $doc;
        $this->view->lib        = $lib;
        $this->view->type       = $type;
        $this->view->version    = $doc->version;
        $this->view->actions    = $this->loadModel('action')->getList('doc', $data['2']);
        $this->view->users      = $this->user->getPairs('noclosed,noletter');
        $this->view->keTableCSS = $this->doc->extractKETableCSS($doc->content);

        $this->display();
    }
}