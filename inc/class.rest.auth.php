<?php

class restAuth extends dcAuth
{
  # L'utilisateur n'a pas le droit de changer son mot de passe
  protected $allow_pass_change = false;
 
  /**
  * Méthode de vérification de la clef d'api_key
  * Remplace la méthode chekUser (id: password)
  * Only use $user_key (all others parameters are for compatibility with the parent function)
  * input: $user_key STRING
  * output: boolean
  */
  
  public function checkUser($user_id, $pwd = NULL, $user_key = NULL, $check_blog = true) 
  {
  
    global $core;

    $hashedKey = $core->auth->crypt($user_key);
    //Check for the user api key
    $sqlStr = "  SELECT setting_id 
                FROM dc_setting 
                WHERE setting_ns='rest'
                AND setting_id LIKE 'rest_key_%'
                AND setting_value = '".$core->con->escape($hashedKey)."';";
                
    try {
      $rs = $core->con->select($sqlStr);
    } catch (Exception $e) {
      $err = $e->getMessage();
      return false;
    }

    if ($rs->isEmpty()) {
      sleep(rand(2,5));
      return false;
    }

    //get the user ID from the previous query
    $userId = explode("_", $rs->setting_id)[2];
    
    //get USER infos
    
    $strReq = 'SELECT user_id, user_super, user_pwd, user_change_pwd, '.
    'user_name, user_firstname, user_displayname, user_email, '.
    'user_url, user_default_blog, user_options, '.
    'user_lang, user_tz, user_post_status, user_creadt, user_upddt '.
    'FROM '.$core->con->escapeSystem($this->user_table).' '.
    "WHERE user_id = '".$core->con->escape($userId)."'";
    
    try {
      $rs = $core->con->select($strReq);
    } catch (Exception $e) {
      $err = $e->getMessage();
      return false;
    }

    if ($rs->isEmpty()) {
      sleep(rand(2,5));
      return false;
    }
    
    $this->user_id = $rs->user_id;
    $this->user_change_pwd = (boolean) $rs->user_change_pwd;
    $this->user_admin = (boolean) $rs->user_super;
    $this->user_info['user_pwd'] = $rs->user_pwd;
    $this->user_info['user_name'] = $rs->user_name;
    $this->user_info['user_firstname'] = $rs->user_firstname;
    $this->user_info['user_displayname'] = $rs->user_displayname;
    $this->user_info['user_email'] = $rs->user_email;
    $this->user_info['user_url'] = $rs->user_url;
    $this->user_info['user_default_blog'] = $rs->user_default_blog;
    $this->user_info['user_lang'] = $rs->user_lang;
    $this->user_info['user_tz'] = $rs->user_tz;
    $this->user_info['user_post_status'] = $rs->user_post_status;
    $this->user_info['user_creadt'] = $rs->user_creadt;
    $this->user_info['user_upddt'] = $rs->user_upddt;
    $this->user_info['user_cn'] = dcUtils::getUserCN($rs->user_id, $rs->user_name,
    $rs->user_firstname, $rs->user_displayname);
    
    //$this->user_options = array_merge($this->core->userDefaults(),$rs->options());
    $this->user_prefs = new dcPrefs($this->core,$this->user_id);    
    return true;
  }
  
  //return user permission for all blogs
  //[blog_id] => permission
  public function getAllPermissions()
  {
    global $core;
    
    //conserve the value
    static $blogs = false;
    
    if($blogs !== false){
      return $blogs;
    }
  
    
    if ($this->user_admin) { //user_admin == super admin
      $strReq = 'SELECT blog_id '.
        'from '.$this->blog_table;
      $rs = $core->con->select($strReq);  
      while ($rs->fetch())
      {
        $blogs[$rs->blog_id] = 'admin';
      }
      return $blogs;
    }
    $strReq = 'SELECT blog_id, permissions '.
        'FROM '.$this->perm_table.' '.
        "WHERE user_id = '".$this->con->escape($this->user_id)."' ".
        "AND (permissions LIKE '%|usage|%' OR permissions LIKE '%|admin|%' OR permissions LIKE '%|contentadmin|%') ";
        
    $rs = $this->con->select($strReq);
    while ($rs->fetch()){
      $blogs[$rs->blog_id] = $this->parsePermissions($rs->permissions);
    }
    return $blogs;
  }
}