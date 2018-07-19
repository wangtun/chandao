<?php
/**
 * Get bug of users group by project.
 *
 * @access public
 * @return array
 */
public function getUserBugSummary()
{
    $bugs = $this->dao->select('t1.*, t2.name as projectName')->from(TABLE_BUG)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->where('t1.deleted')->eq(0)
        ->andWhere('t1.status')->ne('closed')
        ->andWhere('t1.assignedTo')->ne('')
        ->andWhere('t2.status')->notin('cancel, closed')
        ->andWhere('t2.deleted')->eq(0)
        ->fetchGroup('assignedTo', 'id');

    $assign = array();
    foreach($bugs as $user => $userBugs)
    {
        if($user)
        {
            foreach($userBugs as $bug)
            {
                $assign[$user]['bug'][$bug->projectName]['count'] =
                    isset($assign[$user]['bug'][$bug->projectName]['count']) ?
                    $assign[$user]['bug'][$bug->projectName]['count'] + 1 : 1;

                if($bug->status == 'active')
                {
                    $assign[$user]['bug'][$bug->projectName]['active'] =
                        isset($assign[$user]['bug'][$bug->projectName]['active']) ?
                        $assign[$user]['bug'][$bug->projectName]['active'] + 1 : 1;

                    switch ($bug->pri)
                    {
                        case '4':
                            $assign[$user]['bug'][$bug->projectName]['pri4'] =
                                isset($assign[$user]['bug'][$bug->projectName]['pri4']) ?
                                    $assign[$user]['bug'][$bug->projectName]['pri4'] + 1 : 1;
                            break;
                        case '1':
                            $assign[$user]['bug'][$bug->projectName]['pri1'] =
                                isset($assign[$user]['bug'][$bug->projectName]['pri1']) ?
                                    $assign[$user]['bug'][$bug->projectName]['pri1'] + 1 : 1;
                            break;
                        case '2':
                            $assign[$user]['bug'][$bug->projectName]['pri2'] =
                                isset($assign[$user]['bug'][$bug->projectName]['pri2']) ?
                                    $assign[$user]['bug'][$bug->projectName]['pri2'] + 1 : 1;
                            break;
                        case '3':
                            $assign[$user]['bug'][$bug->projectName]['pri3'] =
                                isset($assign[$user]['bug'][$bug->projectName]['pri3']) ?
                                    $assign[$user]['bug'][$bug->projectName]['pri3'] + 1 : 1;
                            break;
                    }
                }
                else
                {
                    $assign[$user]['bug'][$bug->projectName]['resolved'] =
                        isset($assign[$user]['bug'][$bug->projectName]['resolved']) ?
                        $assign[$user]['bug'][$bug->projectName]['resolved'] + 1 : 1;
                }
            }
        }
    }

    unset($assign['closed']);
    return $assign;
}
