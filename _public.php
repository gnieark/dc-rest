<?php
if (!defined('DC_RC_PATH')) { return; }

$core->url->register('rest','rest','^rest(?:/(.*))?$',array('rest','getResponse'));
class rest extends dcUrlHandlers
{

  /**
  * Create the "good" RestQuery instance
  * Input: $httpMethod: String (POST/GET/PATCH etc...)
  * $args Url arguments
  * $user dcAuth object
  * $body Body of the input query. String
  * Output: object RestQuery
  */
  private function restFactoryQuery($httpMethod,$args,$body){
    //définir la methode API (pas HTML) appelée
    switch($httpMethod){
      case "GET":
        if($args == 'blogs'){
          return new RestQueryGetBlogs();
          break;
        }elseif($args == 'specs'){
          return new RestQueryGetSpecs();
          break;
        }elseif(preg_match('/^blogs\/(.*)$/', $args )){
           ///blogs/{blog-id}
          return new RestQueryGetBlog($args);
          break;      
        }elseif(preg_match('/^(.*)\/settings$/', $args )){
          return new RestQueryGetBlogSettings($args);
          break;
        }elseif(preg_match('/^(.*)\/settings\/(.*)$/', $args )){
          return new RestQueryGetBlogSettings($args);
          break;
        }
        
        break;
      case "POST":
        if($args == 'blogs'){
          return new RestQueryPostBlogs($body);
        }elseif(preg_match('/^(.*)\/settings\/(.*)$/', $args )){
          return new RestQueryPostBlogSettings($args,$body);
          break;
        }
      
        break;
      case "PUT":
        if(preg_match('/^blogs\/(.*)$/', $args )){
          return new ResQueryPutBlogs($args,$body);
          break;
        }      
        break;
        
      case "PATCH":
        if(preg_match('/^blogs\/(.*)$/', $args )){
          return new ResQueryPatchBlogs($args,$body);
          break;
        }

        break;
        
      case "DELETE":
        if(preg_match('/^blogs\/(.*)$/', $args )){
          return new ResQueryDeleteBlogs($args,$body);
          break;
        }elseif(preg_match('/^(.*)\/settings\/(.*)$/', $args )){
          return new RestQueryDeleteBlogSettings($args);
        }
        break;
      default:
        break;
    }
    return new RestQuery(); //will return a 404
  
  }

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
    
    
    //exception pour la documentation
    if($args == "documentation"){
                        include (dirname(__FILE__).'/documentation/swagger-ui-dist/index.php');
                        return;
    }
    
    header('Content-Type: application/json');
    
    //user authentification (facultative at this step)
    $apiKey = rest::get_api_key_sended();
    //$user = false;
    if($apiKey){
      $core->auth = new restAuth($core);
      if($core->auth->checkUser('','',$apiKey) === false){
        header(RestQuery::get_full_code_header(403));
        echo json_encode(array(
          "error" => "Wrong API Key",
          "code"  => 403
        ));
        return;
      }
    }else{
      $core->auth = false;
    }
    $r = rest::restFactoryQuery($_SERVER['REQUEST_METHOD'],$args,file_get_contents('php://input'));
    header($r->get_full_code_header());
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