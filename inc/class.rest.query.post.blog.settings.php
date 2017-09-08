<?php
class RestQueryPostBlogSettings extends RestQuery
{
 private function arrayToSubArray($array){
   
   return array($array);
 }

  private function setSetting($nameSpace,$body){
    global $core;
    //verifier le JSON
    
    //permettre à l'user de fournir un ou plusieurs settings à la fois.
    $querryArr = json_decode($body,true);
    if(empty($querryArr)){
      $this->response_code = 400;
      $this->response_message = array(
        'error' => 'Can\'t parse input JSON',
        'code'  => 400
      );
      return;
    }
    if(isset($querryArr['id'])){
        //l'user n'a envoyé qu'un seul setting sans le mettre dans un objet
       $querryArr = $this->arrayToSubArray($querryArr);
    }
    
    //tester la présence des bonnes clefs
    foreach($querryArr as $setting){
        if($this->check_for_required_fields($setting,
            array('id','value'),
            array('type','label','value_change','global')
            ) === false)
        {
            return;
        }
    }
    
   foreach($querryArr as $setting){
        //set falcutative fields
      if(!isset($setting['type'])){
        $setting['type'] = null;
      }
      if(!isset($setting['value_change'])){
        $setting['value_change'] = true;
      }
      if(!isset($setting['global'])){
        $setting['global'] = false;
      }

      $core->blog->settings->$nameSpace->put($setting['id'],$setting['value'], 
                                                   $setting['type'],$setting['value_change'],
                                                   $setting['global']);
   }
  
    $this -> response_code = 201;
    $this -> response_message = array(
        'code'  => 201,
        'message' => 'settings Successfully created'
    );
    return;
  }
  public function __construct($args,$body){
    global $core;
    
    $explodedArgs = explode("/",$args);
    $nameSpace = $explodedArgs[2];
    $this->blog_id = $explodedArgs[0];
    $this->required_perms = 'admin'; 
    
    if($core->auth === false){
      $core->auth = new restAuth($core); //class dcBlog need it
      $unauth = true;
    }
    $core->blog = new dcBlog($core, $this->blog_id);
    if(!$core->blog->id){
      //Le blog n'existe pas
      $this->is404('Resource '.$blog_id.' not found');
      return;      
    }
    $blog_settings = new dcSettings($core,$this->blog_id);
    
    
    
    if($this->is_allowed() === false){
      return;
    }
    $core->blog->settings->addNamespace($nameSpace);      
    //error_log($body);
    if(empty($body)){
        $this -> response_code = 201;
        $this -> response_message = array(
            'code'  => 201,
            'message' => 'namespace '.$nameSpace.' Successfully created'
        );
    }else{
       $this-> setSetting($nameSpace,$body);
    }
  }
}
