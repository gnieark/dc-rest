<?php

class RestQuery{
	public $response_code;
	public $response_message; //array
	private $queryObj;
	
	public function __construct($httpMethod,$args,$user){
		error_log($httpMethod." ".$args);
		//définir la methode API (pas HTML) appelée
		switch($httpMethod){
			case "GET":
				if($args == 'blogs'){
					$queryObj = new RestQueryGetBlogs($user);
					break;
				}elseif($args == 'specs'){
                                        $queryObj = new RestQueryGetSpecs($user);
					break;
				}
				break;
			case "POST":
			
				break;
			case "PUT":
			
				break;
				
			case "PATCH":
			
				break;
				
			case "DELETE":
			
				break;
			default:
				$this->response_code = RestQuery::get_full_code_header(400);
				$this->response_message = array(
					"error"	=> "Unrecoknized method",
					"code"	=> 400
				);
				return;
				break;
		}
		
		$this->response_code = $queryObj->response_code;
		$this->response_message = $queryObj->response_message;
	
	}

	public function get_full_code_header($code){
		static $codes = array(
			100 =>"Continue",
			101 =>"Switching Protocols",
			102 =>"Processing",
			200 =>"OK",
			201 =>"Created",
			202 =>"Accepted",
			203 =>"Non-Authoritative Information",
			204 =>"No Content",
			205 =>"Reset Content",
			206 =>"Partial Content",
			207 =>"Multi-Status",
			210 =>"Content Different",
			226 =>"IM Used",
			300 =>"Multiple Choices",
			301 =>"Moved Permanently",
			302 =>"Moved Temporarily",
			303 =>"See Other",
			304 =>"Not Modified",
			305 =>"Use Proxy",
			306 =>"(aucun)",
			307 =>"Temporary Redirect",
			308 =>"Permanent Redirect",
			310 =>"Too many Redirects",
			400 =>"Bad Request",
			401 =>"Unauthorized",
			402 =>"Payment Required",
			403 =>"Forbidden",
			404 =>"Not Found",
			405 =>"Method Not Allowed",
			406 =>"Not Acceptable",
			407 =>"Proxy Authentication Required",
			408 =>"Request Time-out",
			409 =>"Conflict",
			410 =>"Gone",
			411 =>"Length Required",
			412 =>"Precondition Failed",
			413 =>"Request Entity Too Large",
			414 =>"Request-URI Too Long",
			415 =>"Unsupported Media Type",
			416 =>"Requested range unsatisfiable",
			417 =>"Expectation failed",
			418 =>"I’m a teapot",
			421 =>"Bad mapping / Misdirected Request",
			422 =>"Unprocessable entity",
			423 =>"Locked",
			424 =>"Method failure",
			425 =>"Unordered Collection",
			426 =>"Upgrade Required",
			428 =>"Precondition Required",
			429 =>"Too Many Requests",
			431 =>"Request Header Fields Too Large",
			449 =>"Retry With",
			450 =>"Blocked by Windows Parental Controls",
			451 =>"Unavailable For Legal Reasons",
			456 =>"Unrecoverable Error"
		);
		
		if(isset($codes[$code])){
			return "HTTP/1.0 ".$code." ".$codes[$code];
		}else{
			return "HTTP/1.0 ".$code." Something wrong happened";
		}
		
	
	
	}


}

//etc...