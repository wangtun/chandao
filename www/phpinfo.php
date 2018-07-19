<?php
$arr = array(25,1,3,2,9,13,566,32,653,743,7,33,6,5,356);
function mao_pao($arr)
{
    $count = count($arr);
    if ($count == 1) return $arr;
    for ($i = 0; $i <$count; $i++)
    {
        for ($j = $count - 1; $j > $i; $j--)
        {
            if ($arr[$j] < $arr[$j - 1])
            {
                $tmp = $arr[$j];
                $arr[$j] = $arr[$j - 1];
                $arr[$j - 1] = $tmp;
            }
        }
    }
    return $arr;
}
//var_dump(mao_pao($arr));
function quick_sort($arr)
{
    $count = count($arr);
    if ($count <= 1) return $arr;
    $left_arr = array();
    $right_arr = array();
    $tmp = $arr[0];
    //var_dump($tmp);die;
    for ($i = 1; $i < $count; $i++)
    {
        if ($arr[$i] >= $tmp)
        {
            $right_arr[] = $arr[$i];
        }
        else
        {
            $left_arr[] = $arr[$i];
        }
    }
    $left_arr = quick_sort($left_arr);
    $right_arr = quick_sort($right_arr);
    return array_merge($left_arr, array($tmp), $right_arr);
}
//var_dump(quick_sort($arr));
function select_sort($arr)
{
    $count = count($arr);
    for ($i = 0; $i <$count; $i++)
    {
        for ($j = $i +1; $j <$count; $j++)
        {
            if ($arr[$i] > $arr[$j])
            {
                $tmp = $arr[$i];
                $arr[$i] = $arr[$j];
                $arr[$j] = $tmp;
            }
        }
    }
    return $arr;
}
//var_dump(select_sort($arr));
function insert_sort($arr)
{
    $count = count($arr);
    for ($i = 1; $i < $count; $i++)
    {
        $tmp = $arr[$i];
        $j = $i -1;
        while ($j >=0 && $arr[$j] > $tmp)
        {
            $arr[$j +1] = $arr[$j];
            $j--;
        }
        $arr[$j + 1] = $tmp;
    }
    return $arr;
}
//var_dump(insert_sort($arr));

function search($arr, $v, $low = 0, $high = 0)
{
    if (count($arr) != 0 && $high ==0)
    {
        $high = count($arr);
    }
    $mid = intval(($low + $high)/2);
    if ($arr[$mid] == $v)
    {
        return $mid;
    }
    elseif($arr[$mid] < $v)
    {
        search($arr, $v, $mid+1, $high);
    }
    else
    {
        search($arr, $v, $low, $mid - 1);
    }
}
$array = array(4,5,7,8,9,10);
$o = new stdClass();
$o->id = 1;
$o->stu = $array;

//en
//$obj = '{"id":1,"name":"jb51","email":"admin@jb51.net","interest":["wordpress","php"]}';
var_dump(json_encode($o));