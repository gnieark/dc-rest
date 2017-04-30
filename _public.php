<?php
if (!defined('DC_RC_PATH')) { return; }

$core->url->register('rest','rest','^rest(?:/(.*))?$',array('rest','getResponse'));
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
			header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE'); 
			header('Access-Control-Allow-Headers: Content-Type, authorization, x_dc_key');
		}
		header('Content-Type: application/json');
		
		$apiKey = rest::get_api_key_sended();
	
		if($apiKey){
			$user = new restAuth($core);
			;
			
			
			//test:
			if($user->checkUser('','',$apiKey) === false){
				error_log("wrong key");
				
			}else{
				error_log($user->userID());
			}
			
		
		
		}
	}
	private function get_api_key_sended(){
		//to do: test it on nginx
		$headers = apache_request_headers();
		if(isset($headers['x_dc_key'])){
			return $headers['x_dc_key'];
		}else{
			return false;
		}
	}
	
}	