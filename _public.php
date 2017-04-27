<?php
if (!defined('DC_RC_PATH')) { return; }

$core->url->register('rest','rest','^rest(?:/(.+))?$',array('rest','getResponse'));
class rest extends dcUrlHandlers
{
	public static function getResponse($args)
	{
		global $core;
		$active = (boolean)$core->blog->settings->rest->rest_active;
		if (!$active){
			self::p404();
			return;
		}
		
		//To do make headers optionals
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST'); 
		header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
		
		
		
		echo "HELLO".$_SERVER['REQUEST_METHOD'].$args;
	}
}	