<?php
/**
* get progress of projectStory
*
* @access public
* @return array
*/
public function storyProgress()
{
    $projects = $this->dao->select("id,name")->from(TABLE_PROJECT)->where('status')->ne('done')->andWhere('deleted')->eq('0')->orderBy('id desc')->fetchAll('id');

    foreach ($projects as $projectID => $project)
    {
        $stories = $this->dao->select('t1.project,t1.story,t2.verifyStatus,t2.testStatus,t2.testDate,t2.stage')
            ->from(TABLE_PROJECTSTORY)->alias('t1')
            ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
            ->where('t1.project')->eq($projectID)
            ->andWhere('t2.deleted')->eq('0')
            ->andWhere('t2.`status`')->ne('cancel')
            //->andWhere('t2.testDate')->ne('0000-00-00')
            ->fetchGroup('testDate');

        $project->data = new stdClass();
        $project->rowspan = count($stories);
        $data = new stdClass();
        ksort($stories);

        foreach ($stories as $date => $groupStories)
        {
            
            
            $data->$date = new stdClass();
            $data->$date->stage = new stdClass();
            $data->$date->testStatus = new stdClass();
            $data->$date->verifyStatus = new stdClass();
            $data->$date->stage->wait = 0;
            $data->$date->stage->developed = 0;
            $data->$date->stage->tested = 0;
            $data->$date->testStatus->tested = 0;
            $data->$date->testStatus->notTest = 0;
            $data->$date->testStatus->freeTest = 0;
            $data->$date->verifyStatus->pass = 0;
            $data->$date->verifyStatus->failed = 0;
            $data->$date->verifyStatus->freeVerified = 0;
            $data->$date->storySum = count($groupStories);

            foreach ($groupStories as $story)
            {
                switch ($story->stage)
                {
                    case 'projected':
                        $data->$date->stage->wait ++;
                        break;
                    case 'developing':
                        $data->$date->stage->wait ++;
                        break;
                    case 'developed':
                        $data->$date->stage->developed ++;
                        break;
                    case 'testing':
                        $data->$date->stage->developed ++;
                        break;
                    case 'tested':
                        $data->$date->stage->developed ++;
                        $data->$date->stage->tested ++;
                        break;
                    case 'verified':
                        $data->$date->stage->developed ++;
                        $data->$date->stage->tested ++;
                        break;
                    case 'released':
                        $data->$date->stage->developed ++;
                        $data->$date->stage->tested ++;
                        break;
                }
                switch ($story->testStatus)
                {
                    case 'tested':
                        $data->$date->testStatus->tested ++;
                        break;
                    case 'released':
                        $data->$date->testStatus->tested ++;
                        break;
                    case 'freeTest':
                        $data->$date->testStatus->freeTest ++;
                        break;
                    default :
                        $data->$date->testStatus->notTest ++;
                        break;
                }
                switch ($story->verifyStatus)
                {
                    case 'pass':
                        $data->$date->verifyStatus->pass ++;
                        break;
                    case 'freeTest':
                        $data->$date->verifyStatus->freeVerified ++;
                        break;
                    default :
                        $data->$date->verifyStatus->failed ++;
                        break;
                }
            }
        }
        $project->data = $data;
    }
    
    return $projects;
}
