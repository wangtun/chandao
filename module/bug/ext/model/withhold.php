<?php
/**
 * Close a bug.
 *
 * @param  int    $bugID
 * @access public
 * @return void
 */
public function withhold($bugID)
{
    $now = helper::now();
    $bug = fixer::input('post')
        ->add('withhold',       '1')
        ->add('lastEditedBy',   $this->app->user->account)
        ->add('lastEditedDate', $now)
        ->add('confirmed',      1)
        ->remove('comment')
        ->get();

    $this->dao->update(TABLE_BUG)->data($bug)->autoCheck()->where('id')->eq((int)$bugID)->exec();
}
