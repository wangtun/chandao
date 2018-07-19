<?php
/**
 * Close a bug.
 *
 * @param  int    $bugID
 * @access public
 * @return void
 */
public function close($bugID)
{
    $now = helper::now();
    $bug = fixer::input('post')
        ->add('assignedTo',     'closed')
        ->add('assignedDate',   $now)
        ->add('status',         'closed')
        ->add('closedBy',       $this->app->user->account)
        ->add('closedDate',     $now)
        ->add('lastEditedBy',   $this->app->user->account)
        ->add('lastEditedDate', $now)
        ->add('confirmed',      1)
        ->add('withhold',       '')
        ->remove('comment')
        ->get();

    $this->dao->update(TABLE_BUG)->data($bug)->autoCheck()->where('id')->eq((int)$bugID)->exec();
}