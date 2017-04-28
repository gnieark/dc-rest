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
		
		//coors headers
		if($core->blog->settings->rest->rest_send_cors_headers){
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Methods: GET, POST'); 
			header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
		}
		header('Content-Type: application/json');
		
	
	
	
	private function get_api_key_sended(){
		$headers = getallheaders();
		if(isset($headers['x_dc_key'])){
			return $headers['x_dc_key'];
		}else{
			return false;
		}
	}
	
}	