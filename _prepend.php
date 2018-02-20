<?php
if (!defined('DC_RC_PATH')) { return; }

$core->url->register('rest','rest','^rest(?:/(.*))?$',array('rest','makeResponse'));
class rest extends dcUrlHandlers
{

  public function makeResponse(){

    
  }
}