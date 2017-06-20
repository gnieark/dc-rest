<?php
class ApiKey
{
  
  public $key;
  
  public function __construct(){
    $this->key = '';
  }
  public function new_key($dcUserId)
  {
    $this->key = $this->rand_str();    
    $this -> put_dc_setting_user_key($dcUserId);
    return $this->key;
  }
  
  public function set_key($key)
  {
    $this->key = $key;
  }
  
  public function get_dc_admin_form($dcUserId)
  {
  
    global $core;
    
    //tester si une clef d'API a été générée
    if($this->dc_is_key_setting_set($dcUserId)){
      $infoFormApiKey = __('Your api key has already been created.');
      $buttonFormApiKey = __('Erase existing API key and generate a new one for').' '.$dcUserId;
    }else{
      $infoFormApiKey = __('No API key found.');
      $buttonFormApiKey = __('Generate a API key for').' '.$dcUserId;  
    }
    
    if($this->key == ''){
      $infoKey = $infoFormApiKey;
    }else{
      $infoKey = '<p class="info">'.__('The api key is').':<input type ="texte" value="'.$this->key.'"/><br/>'.
      __('Copy and paste it, You will cannot see it again.').'</p>';
    }
    
    
    return '<form method="post" action="'.http::getSelfURI().'">'.
            $infoKey.
            '<p><input type="submit" name="resetApiKey" value="'.$buttonFormApiKey.'"/></p>'.
            $core->formNonce().
            '</form>';
  }
  
  private function dc_is_key_setting_set($dcUserId)
  {
    global $core;
    
    $apiKeyName = $this->get_dc_setting_api_name($dcUserId);
    $currentHashedKey = $core->blog->settings->rest->{$apiKeyName};
    if(empty($currentHashedKey)){
      return false;
    }else{
      return true;
    }
  }
  
  private function put_dc_setting_user_key($dcUserId)
  {
    global $core;
    
    if ($this->key == ''){
      //don't save an empty key
      return false;
    }
    $hashedKey = $core->auth->crypt($this->key);
    $core->blog->settings->rest->put(
      $this->get_dc_setting_api_name($dcUserId),
      $hashedKey,
      'string'
    );
    return $hashedKey;
  }
  
  private function get_dc_setting_api_name($dcUserId)
  {
    return 'rest_key_'.$dcUserId;
  }
  
  private function rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
  {
    $chars_length = (strlen($chars) - 1);
    $string = $chars{rand(0, $chars_length)};
    for ($i = 1; $i < $length; $i = strlen($string)){
        $r = $chars{rand(0, $chars_length)};
        if ($r != $string{$i - 1}) $string .=  $r;
    }
    return $string;
  }
}