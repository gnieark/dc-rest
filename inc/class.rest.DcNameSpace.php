<?php
class restDcNameSpace extends dcSettings
{
  //this function is private on the parent class
  public function settingExists($id,$global=false)
  {
    $array = $global ? 'global' : 'local';
    return isset($this->{$array.'_settings'}[$id]);
  }


}