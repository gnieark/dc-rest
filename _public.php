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
		error_log($args);
		
		//exception pour la documentation
		if($args == "documentation"){
                        include (dirname(__FILE__).'/documentation/swagger-ui-dist/index.php');
                        return;
		}
		
            
		//coors headers
		if($core->blog->settings->rest->rest_send_cors_headers){
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE'); 
			header('Access-Control-Allow-Headers: Content-Type, authorization, x_dc_key');
		}
		header('Content-Type: application/json');
		
		//user authentification (facultative at this step)
		$apiKey = rest::get_api_key_sended();
		$user = false;
		if($apiKey){
			$user = new restAuth($core);
			if($user->checkUser('','',$apiKey) === false){
				header(RestQuery::get_full_code_header(403));
				echo json_encode(array(
					"error" => "Wrong API Key",
					"code"	=> 403
				));
				return;
			}
		}
		
		$r = new RestQuery($_SERVER['REQUEST_METHOD'],$args,$user);
		header($r->response_code);
		echo json_encode($r->response_message);		
		
	}
	
	private function get_api_key_sended()
	{
		//to do: test it on nginx
		$headers = apache_request_headers();
		if(isset($headers['x_dc_key'])){
			return $headers['x_dc_key'];
		}else{
			return false;
		}
	}
	
}	