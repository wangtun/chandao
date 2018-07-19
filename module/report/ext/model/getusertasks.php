<?php
/**
 * get tasks of users
 *
 * @param $begin string
 * @param $end string
 * @access public
 * @return array
*/
public function getUserTaskSummary($begin, $end)
{
    $date = helper::today();
    $tasks = $this->dao->select('t1.id,t1.deadline,t1.status,t1.assignedTo, t2.name as projectName')->from(TABLE_TASK)->alias('t1')
        ->leftJoin(TABLE_PROJECT)->alias('t2')->on('t1.project = t2.id')
        ->where('t1.deleted')->eq(0)
        ->andWhere('t1.status')->notin('cancel, closed')
        ->andWhere('t1.deadline')->between($begin, $end)
        ->andWhere('t1.assignedTo')->ne('')
        ->andWhere('t2.deleted')->eq(0)
        ->andWhere('t2.status')->notin('cancel, closed')
        ->fetchGroup('assignedTo', 'id');

    if(empty($tasks)) return array();

    $data = array();
    foreach($tasks as $user => $userTasks)
    {
        if($user)
        {

            foreach($userTasks as $task)
            {
                $data[$user]['task'][$task->projectName]['count'] =
                    isset($data[$user]['task'][$task->projectName]['count'])
                    ? $data[$user]['task'][$task->projectName]['count'] + 1 : 1;

                if ($task->status !== 'done' and $task->deadline != '0000-00-00'
                    and helper::diffDate($date, $task->deadline) > 0)
                {
                    $data[$user]['task'][$task->projectName]['delay'] =
                        isset($data[$user]['task'][$task->projectName]['delay'])
                        ? $data[$user]['task'][$task->projectName]['delay'] + 1 : 1;
                }

                switch ($task->status)
                {
                    case 'wait':
                        $data[$user]['task'][$task->projectName]['wait']
                            = isset($data[$user]['task'][$task->projectName]['wait'])
                            ? $data[$user]['task'][$task->projectName]['wait'] + 1 : 1;
                        break;
                    case 'doing':
                        $data[$user]['task'][$task->projectName]['doing']
                            = isset($data[$user]['task'][$task->projectName]['doing'])
                            ? $data[$user]['task'][$task->projectName]['doing'] + 1 : 1;
                        break;
                    case 'pause':
                        $data[$user]['task'][$task->projectName]['pause']
                            = isset($data[$user]['task'][$task->projectName]['pause'])
                            ? $data[$user]['task'][$task->projectName]['pause'] + 1 : 1;
                        break;
                    case 'done':
                        $data[$user]['task'][$task->projectName]['done']
                            = isset($data[$user]['task'][$task->projectName]['done'])
                            ? $data[$user]['task'][$task->projectName]['done'] + 1 : 1;
                        break;
                }
            }
        }
    }
    unset($data['closed']);
    return $data;
}
