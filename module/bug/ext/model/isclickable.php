<?php
/**
 * Adjust the action is clickable.
 *
 * @param  string $object
 * @param  string $action
 * @access public
 * @return void
 */
public static function isClickable($object, $action)
{
    $action = strtolower($action);
    if($action == 'confirmbug') return $object->status == 'active' and $object->confirmed == 0;
    if($action == 'resolve')    return $object->status == 'active';
    if($action == 'close')      return $object->status == 'resolved';
    if($action == 'activate')   return $object->status != 'active';
    if($action == 'tostory')    return $object->status == 'active';
    if($action == 'toissue')    return $object->toIssue == 0;
    if($action == 'withhold')   return $object->status == 'resolved' and $object->withhold != 1;

    return true;
}