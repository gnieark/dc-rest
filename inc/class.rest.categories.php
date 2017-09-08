<?php

class RestCategories
{
  /*
  * Don't expose Nested tree algorithm via the API
  * First goal of this class is to convert structure id|left|right
  * to - a less optimized but simpliest - way of use id|parent_id|position
  */

  /*
  * base structure is
  mysql> describe dc_category;
  +--------------+--------------+------+-----+---------+-------+
  | Field        | Type         | Null | Key | Default | Extra |
  +--------------+--------------+------+-----+---------+-------+
  | cat_id       | bigint(20)   | NO   | PRI | NULL    |       |
  | blog_id      | varchar(32)  | NO   | MUL | NULL    |       |
  | cat_title    | varchar(255) | NO   |     | NULL    |       |
  | cat_url      | varchar(255) | NO   | MUL | NULL    |       |
  | cat_desc     | longtext     | YES  |     | NULL    |       |
  | cat_position | int(11)      | YES  |     | 0   Unused (by dotclear) field
  | cat_lft      | int(11)      | YES  |     | NULL    |       |
  | cat_rgt      | int(11)      | YES  |     | NULL    |       |
  +--------------+--------------+------+-----+---------+-------+
  8 rows in set (0.00 sec)
  */
  
  public $cats; //Array structured like:
  
  /*                [
    {
      "cat_id": 4,
      "cat_title": "trololo",
      "cat_url": "trololo",
      "cat_desc": "",
      "cat_level": 0,
      "cat_parent_id": null,
      "cat_position": 0
    },
    {
      "cat_id": 6,
      "cat_title": "child",
      "cat_url": "trololo/child",
      "cat_desc": "",
      "cat_level": 1,
      "cat_parent_id": 4,
      "cat_position": 0
    },
  */
  protected $catsLftRgt;
  protected $blog_id;
  protected $core;
  protected $table;
  protected $con;
  

  protected function getNextInsertCatId(){
    $sql = "SELECT max(cat_id) as nextId FROM ".$this->table." ;";
    $rs = $this->con->select($sql);
    $rs->fetch();
    return (int)($rs->nextId) +1;
  }
 
 
 /*
 * build part of url like
 * parent/sub/category
 */
  protected function generateCatUrl($title,$cat_lft){
  
    //Select all parents
    $sql = "SELECT cat_title 
            FROM " .$this->table . "
            WHERE blog_id='". $this->con->escape($this->blog_id) . "'
            AND cat_lft < '" . $cat_lft ."'
            AND cat_rgt > '" . $cat_lft ."'
            ORDER BY cat_lft ASC";
    
    $rs = $this->con->select($sql);
    $urlParts =array();
    while($rs->fetch()){
      $urlParts[] = text::tidyURL($rs->cat_title,false);
    }
    $urlParts[] = text::tidyURL($title);
    return implode("/",$urlParts);
    
  }
  
  /*
  * $params keys can be: 
  * 'cat_title','cat_url','cat_desc',
  * 'cat_parent_id','cat_position','temporary'
  */
  public function updateCategory($cat_id,$params){
    $valuesToChange = array();
    if(isset($params['cat_title'])){
      $valuesToChange[] = "cat_title = '". $this->con->escape($params['cat_title']) . "'";
    }
    if(isset($params['cat_url'])){
      $valuesToChange[] = "cat_url = '". $this->con->escape($params['cat_url']). "'";
    }
    if(isset($params['cat_desc'])){
      $valuesToChange[] = "cat_desc = '". $this->con->escape($params['cat_desc']). "'";
    }

    if(count($valuesToChange) > 0){
      //do the update
      $sql = "UPDATE ".$this->table."
              SET ".implode(",",$valuesToChange)." 
              WHERE blog_id='". $this->con->escape($this->blog_id) . "'
              AND cat_id= '". $this->con->escape($cat_id)."';";
      $this->con->execute($sql);
    }
    
    if(
      (isset($params['cat_position']))
      OR (isset($params['cat_parent_id']))
    ){
        //changement de position
        $cat_position = (isset($params['cat_position']))? $params['cat_position'] : null;
        $cat_parent_id = (isset($params['cat_parent_id']))? $params['cat_parent_id'] : null;
        $permute=(isset($params['permute']))? $params['permute']:false;
        $this->changePosition($cat_id,$cat_parent_id,$cat_position,$permute);        
      }
  }
  
  /*
  * return the actual cat_left for a parent and a position given
  * If cat is a top level one. set $parent to null
  * in position doesn't exists the last position will be returned
  * IN $parent_id: integer, $position: integer
  * OUT: integer
  */
  protected function getCatLeft($parent_id,$position){
  
    if((is_null($parent_id)) && (is_null($position))){
        //on met la categorie après la dernière position
        $sql = "SELECT max(cat_rgt) AS max FROM ".$this->table."
                WHERE blog_id='". $this->con->escape($this->blog_id) . "';";
        $rs = $this->con->select($sql);
        if($rs->max == null){
          return 1; //it's the first category, there's no other
        }else{
          return $rs->max + 1;
        }
    }
    
    
    if(!is_null($position)){
      foreach($this->catsLftRgt as $cat){
        if(($cat['cat_parent_id'] == $parent_id)
          &&($cat['cat_position'] == $position)){
            return $cat['cat_lft'];
          }
      }
    }
    //position not found return the parent cat_rgt
    $sql = "SELECT cat_rgt FROM ".$this->table."
            WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
            AND cat_id='". $this->con->escape($parent_id)."';";
            
    $rs = $this->con->select($sql);
     if(!$rs->cat_rgt){
        //ce cas ne devrait jamais se produire
        error_log("something wrong on RestCategories::getCatLeft ".$parent_id."|".$position);
        return false;
      }else{
        return $rs->cat_rgt;
      }
  }
  
  /*
  * permute two categories positions
  */
  public function permutePosition($cat1_id,$cat2_id){
    $twoCats = array();
    foreach($this->calsLftRgt as $cat){
      if(($cat['cat_id'] == $cat1_id)
        ||($cat['cat_id'] == $cat2_id))
      {
        $twoCats[] = $cat;
      }
    }
    if(count($twoCats) <> 2){
      //At least one of the two cats was not found
      return false;
    }
  
    if($twoCats[0]['cat_lft'] > $twoCats[1]['cat_lft']){
      //inverser l'ordre
      $twoCats[2] = $twoCats[0];
      $twoCats[0] = $twoCats[1];
      $twoCats[1] = $twoCats[2];
      unset($twoCats[2]);
    }
    try{
      $this->con->begin();
  
        //grow or reduce cat1 parents, if not the sames
      if($twoCats[0]['cat_parent_id'] <> $twoCats[1]['cat_parent_id']){
      
        $dif = $twoCats[1]['cat_rgt'] - $twoCats[1]['cat_lft'] 
                - $twoCats[0]['cat_rgt'] + $twoCats[0]['cat_lft'];
        if($dif > 0){
          $sql = "UPDATE ".$this->table."
                  SET cat_rgt = cat_rgt + ".$dif."
                  WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
                  AND cat_lft  < ".$twoCats[0]['cat_lft']."
                  AND cat_rgt > ".$twoCats[0]['cat_lft'].";";
          $this->con->execute($sql);
        }elseif($dif < 0){
          $sql = "UPDATE ".$this->table."
                  SET cat_rgt = cat_rgt - ".abs($dif)."
                  WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
                  AND cat_lft  < ".$twoCats[0]['cat_lft']."
                  AND cat_rgt > ".$twoCats[0]['cat_lft'].";";
          $this->con->execute($sql);
          
        }else{
          //dif == 0 do nothing
        }
      }
      
      //shift cat
      if($dif > 0){
      
        $sql = "UPDATE ".$this->table."
                SET cat_lft = cat_lft + ".$dif.",
                cat_rgt = cat_rgt + ".$dif."
                WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
                AND cat_lft  > ".$twoCats[0]['cat_lft'].";";
        $this->con->execute($sql);
      
      }elseif($dif < 0){
        $sql = "UPDATE ".$this->table."
                SET cat_lft = cat_lft + ".$dif.",
                cat_rgt = cat_rgt + ".$dif."
                WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
                AND cat_lft  > ".$twoCats[0]['cat_lft'].";";
        $this->con->execute($sql);
      }else{
        //dif == 0 do nothing
      }
      
      //mémoriser le nouveau cat_lft de la cat 2
      $sql = "SELECT cat_lft,cat_rgt FROM ".$this->table."
            WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
            AND cat_id='".$this->con->escape($cat2_id)."'";
      $rs = $this->con->select($sql);
      $rs->fetch();
      $cat2_cat_lft = $rs->cat_lft;
      $cat2_cat_rgt = $rs->cat_rgt;
      
      //déplacer la cat 2
      $sql = "UPDATE  ".$this->table."
            SET cat_lft=".$twoCats[0]['cat_lft'].",
            cat_rgt=". (int)($twoCats[0]['cat_lft'] + $twoCats[1]['cat_rgt'] - $twoCats[1]['cat_lft'])."
            WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
            AND cat_id='".$this->con->escape($cat2_id)."'";
      $this->con->execute($sql);
      
      //faire la place autour
      //grow parents
          //grow or reduce cat2 parents, if not the sames
      if($twoCats[0]['cat_parent_id'] <> $twoCats[1]['cat_parent_id']){
      
        $dif = $twoCats[0]['cat_rgt'] - $twoCats[0]['cat_lft'] 
                - $twoCats[1]['cat_rgt'] + $twoCats[1]['cat_lft'];
        if($dif > 0){
          $sql = "UPDATE ".$this->table."
                  SET cat_rgt = cat_rgt + ".$dif."
                  WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
                  AND cat_lft  < ".$cat2_cat_lft."
                  AND cat_rgt > ".$cat2_cat_lft.";";
          $this->con->execute($sql);
        }elseif($dif < 0){
          $sql = "UPDATE ".$this->table."
                  SET cat_rgt = cat_rgt - ".abs($dif)."
                  WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
                  AND cat_lft  < ".$cat2_cat_lft."
                  AND cat_rgt > ".$cat2_cat_lft.";";
          $this->con->execute($sql);
          
        }else{
          //dif == 0 do nothing
        }
      }
      //décaler
      if($dif > 0){
    
      $sql = "UPDATE ".$this->table."
              SET cat_lft = cat_lft + ".$dif.",
              cat_rgt = cat_rgt + ".$dif."
              WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
              AND cat_lft  > ".$cat2_cat_lft.";";
      $this->con->execute($sql);
    
    }elseif($dif < 0){
      $sql = "UPDATE ".$this->table."
              SET cat_lft = cat_lft + ".$dif.",
              cat_rgt = cat_rgt + ".$dif."
              WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
              AND cat_lft  > ".$cat2_cat_lft.";";
      $this->con->execute($sql);
    }else{
      //dif == 0 do nothing
    }
    //move cat 2
    $sql = "UPDATE  ".$this->table."
            SET cat_lft=".$cat2_cat_lft.",
            cat_rgt=". (int)($cat2_cat_lft + $twoCats[0]['cat_rgt'] - $twoCats[0]['cat_lft'])."
            WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
            AND cat_id='".$this->con->escape($cat2_id)."'";
    $this->con->execute($sql);
    $this->con->commit();
    }catch (Exception $e) {
      $this->con->rollback();
      error_log($e);
      return false;
    }
    
    //refresh the array after changes
    $this->__construct($this->core);
    
    return true;
  }
  
  /*
  * Change the categorie position 
  * Facultative: $permute -> will permute position with an existing category
  * else shift if needed brother categories
  */
  public function changePosition($cat_id, $new_parent_id, $new_cat_position, $permute = false){
    if($permute){
      //find the cat_id
      
    
      return $this->permute($cat_id, $new_parent_id, $new_cat_position);
    }
    
    try
    {
      $this->con->begin();
      $currentCatKey = array_search($cat_id, array_column($this->catsLftRgt, 'cat_id'));
      if(!$currentCatKey){
        return false;
      }
      $currentCat = $this->catsLftRgt[$currentCatKey];
      $currentCatWidth = $currentCat['cat_rgt'] - $currentCat['cat_lft'] + 1;
      
      if($new_parent_id == null){
        $new_parent_id = $currentCat["cat_parent_id"];
      }
      
      if($new_parent_id !== null){
        $parentCatKey = array_search($new_parent_id, array_column($this->catsLftRgt, 'cat_id'));
        $parentCat = $this->catsLftRgt[$parentCatKey];
      }
      
      //find the future cat_lft
      $new_cat_left = $this->getCatLeft($new_parent_id,$new_cat_position);
      
      //reduce actuals parents
      if(!is_null($currentCat['cat_parent_id'])){
          $sql = "UPDATE ".$this->table."
                  SET cat_rgt = cat_rgt - ".$currentCatWidth."
                  WHERE blog_id='". $this->con->escape($this->blog_id) . "'
                  AND cat_lft < ".$currentCat['cat_lft']."
                  AND cat_rgt > ".$currentCat['cat_lft'].";";
          $this->con->execute($sql);
      }
      
      //shift all
      $sql = "UPDATE ".$this->table."
              SET cat_lft = cat_lft - ".$currentCatWidth.",
                  cat_rgt = cat_rgt - ".$currentCatWidth."
              WHERE blog_id='". $this->con->escape($this->blog_id) . "'
              AND cat_lft > " . $currentCat['cat_lft'].";";
        $this->con->execute($sql); 
        
      //augmenter les nouveaux parents
      if(!is_null($new_parent_id)){
          $sql = "UPDATE ".$this->table."
                  SET cat_rgt = cat_rgt + ".$currentCatWidth."
                  WHERE blog_id='". $this->con->escape($this->blog_id) . "'
                  AND cat_lft < ".$new_cat_left."
                  AND cat_rgt > ".$new_cat_left.";";
          $this->con->execute($sql);
      }
      
      //tout décaler again
      
      $sql = "UPDATE ".$this->table."
              SET cat_lft = cat_lft + ".$currentCatWidth.",
                  cat_rgt = cat_rgt + ".$currentCatWidth."
              WHERE blog_id='". $this->con->escape($this->blog_id) . "'
              AND cat_lft >= ".$new_cat_left.";";
      $this->con->execute($sql);
      
      //enregistrer les nouveaux cat_lft et cat_rgt
      
      $sql = "UPDATE ".$this->table."
              SET cat_lft ='".$new_cat_left."',
                  cat_rgt ='". ($new_cat_left + $currentCatWidth -1) . "'
              WHERE blog_id='". $this->con->escape($this->blog_id) . "'
              AND cat_id='".$currentCat['cat_id']."';";
      $this->con->execute($sql);
      
      $this->con->commit();
      //refresh the array after changes
      $this->__construct($this->core);
      return true;
    }catch (Exception $e) {
      $this->con->rollback();
      error_log($e);
      return false;
    }
  }
  
  public function addCategory($params){
    // /!\ cat_id is cat_title
    if(!isset($params['cat_title'])){ //the only mandatory field
      return false;
    }
    
    $this->con->writeLock($this->table);
    try
    {
      
      if(isset($params['cat_position'])){
        $cat_position = $params['cat_position'];
      }else{
        $cat_position = null;
      }
      if(isset($params['cat_parent_id'])){
        $cat_parent_id = $params['cat_parent_id'];
        //tester s'il existe
        if($this->getCatProperties($cat_parent_id) === false){
          return false;
        }
      }else{
        $cat_parent_id = null;
      }
      $cat_lft = $this->getCatLeft($cat_parent_id,$cat_position);
      
      //add +2 on parents cat-rgt to grown them
      $sql = "UPDATE ".$this->table."
              SET cat_rgt = cat_rgt + 2
              WHERE blog_id='". $this->con->escape($this->blog_id) . "'
              AND cat_lft < '" . $cat_lft . "'
              AND cat_rgt >= '" . $cat_lft . "';";
            
      $this->con->execute($sql);
      
      //Add +2 on cat_lft and cat_rgt when cat_lft is > in order to shift cats 
      $sql = "UPDATE ".$this->table."
              SET cat_rgt = cat_rgt + 2,
                  cat_lft = cat_lft + 2
              WHERE blog_id='". $this->con->escape($this->blog_id) . "'
              AND cat_lft >= '" . $cat_lft . "';";
      $this->con->execute($sql);
      
      //Let's insert the new catégorie:
      if(empty($params['cat_url'])){
        $new_cat_url = $this->generateCatUrl($params['cat_title'], $cat_lft);
      }else{
        $new_cat_url = $params['cat_url'];
      }
      
      $new_cat_id = $this->getNextInsertCatId();
      $cur = $this->con->openCursor($this->core->prefix.'category');
      $cur->cat_id = $this->getNextInsertCatId();
      $cur->blog_id = $this->con->escape($this->blog_id);
      $cur->cat_title = $params['cat_title'];
      $cur->cat_url =  $new_cat_url;
      if(isset($params['cat_desc'])){
        $cur->cat_desc =  $params['cat_desc'];
      }else{
        $cur->cat_desc = "";
      }
      $cur->cat_lft = $cat_lft;
      $cur->cat_rgt = $cat_lft + 1;
      
      $this->core->callBehavior('coreBeforeCategoryCreate',$this->core,$cur);
      
      if($cur->insert()){
        $this->con->unlock();
        
        $this->core->callBehavior('coreAfterCategoryCreate',$this->core,$cur);
        
        return $new_cat_id;
      }else{
        $this->con->unlock();
        //refresh the array after changes
        $this->__construct($this->core);
        return false;
      }
    }catch (Exception $e) {
      $this->con->unlock();
      error_log($e);
      return false;
    }
  }
   /*
  * Return filtered categories
  */
  public function getCats($filters){
    $cats = array();
    foreach($this->cats as $cat){
      $ok = true;
      foreach($filters as $key => $value){
        if($value !== $cat[$key]){
          $ok = false;
          break;
        }
      }
      if($ok){
        $cats[] = $cat;
      }
    }
    return $cats;
  }
  public function getCatProperties($cat_id){
    foreach($this->cats as $cat){
      if($cat['cat_id'] == $cat_id){
        return $cat;
      }
    }
    return false;
  }
  
  
  public function moveChilds($cat_from_id,$cat_dest_id,$deleteOriginCat = false)
  {
  
    //$cat_from ne doit pas être null
    if(is_null($cat_from_id)){
      return false;
    }
    
    //Si cat_dest_id est null, l'user veut transformer les enfants en categories de premier niveau
    if(is_null($cat_dest_id)){
      //to do
      return;
    }
    
    
    $sql = "SELECT cat_lft,cat_rgt 
            FROM ".$this->table." 
            WHERE blog_id='".$this->con->escape($this->blog_id)."'
            AND cat_id='".$this->con->escape($this->$cat_from_id)."';";
    $rs = $this->con->execute($sql);
    $rs->fetch();
    $origin_cat_lft = $rs->cat_lft;
    $origin_cat_rgt = $rs->cat_rgt;
    
    $sql = "SELECT cat_lft,cat_rgt 
        FROM ".$this->table." 
        WHERE blog_id='".$this->con->escape($this->blog_id)."'
        AND cat_id='".$this->con->escape($this->$cat_dest_id)."';";
    $rs = $this->con->execute($sql);
    $rs->fetch();
    $dest_cat_lft = $rs->cat_lft;
    $dest_cat_rgt = $rs->cat_rgt;
   
   //la categorie de destination ne peut pas être un enfant de celle d'origine
   if(($origin_cat_lft < $dest_cat_lft) && ($origin_cat_rgt < $dest_cat_lft)){
    return false;
   }
   
   
    $this->con->begin();
    try{
      //agrandir la categorie de destination
      $sql = "UPDATE ".$this->table."
              SET cat_rgt = cat_rgt + ".(int)( $origin_cat_rgt - $origin_cat_lft )."
              WHERE blog_id='".$this->con->escape($this->blog_id)."'
              AND cat_id='".$this->con->escape($this->$cat_dest_id)."';";
      $this->con->execute($sql);
      
      //déplacer tout ce qui doit l'être suite à cet aggrandissement
      $sql = "UPDATE ".$this->table."
              SET cat_lft = cat_lft + ".(int)( $origin_cat_rgt - $origin_cat_lft )."
                  cat_rgt = cat_rgt + ".(int)( $origin_cat_rgt - $origin_cat_lft )."
              WHERE blog_id='".$this->con->escape($this->blog_id)."'
              AND cat_lft >".$dest_cat_rgt.";";
      $this->con->execute($sql);
      
      //si la categorie d'origine était après, elle vient d'etre déplacée
      //mettre à jour sa position
      if($origin_cat_lft > $dest_cat_lft){
        $of= $origin_cat_lft;
        $origin_cat_lft =  $origin_cat_rgt;
        $origin_cat_rgt = $origin_cat_rgt + $origin_cat_rgt - $of;
      }
      
      //déplacer les categories enfants
    
      if($dest_cat_rgt > $origin_cat_lft){
        //addition
        $sql = "UPDATE ".$this->table."
                SET cat_lft = cat_lft + ".(int)($dest_cat_rgt - $origin_cat_lft).",
                cat_rgt = cat_rgt + ".(int)($dest_cat_rgt - $origin_cat_lft)."
                WHERE blog_id='".$this->con->escape($this->blog_id)."'
                AND cat_lft BETWEEN ".$origin_cat_lft." AND ".$origin_cat_rgt.";";
        $this->con->execute($sql);
                
      }else{
        //soustraction
        $sql = "UPDATE ".$this->table."
                SET cat_lft = cat_lft - ".(int)($origin_cat_lft - $dest_cat_rgt).",
                cat_rgt = cat_rgt + ".(int)($origin_cat_lft - $dest_cat_rgt)."
                WHERE blog_id='".$this->con->escape($this->blog_id)."'
                AND cat_lft BETWEEN ".$origin_cat_lft." AND ".$origin_cat_rgt.";";
        $this->con->execute($sql);
      }
      
      $dest_cat_rgt = $dest_cat_rgt + $origin_cat_rgt - $origin_cat_lft;
      
      if($deleteOriginCat){
        $sql = "DELETE FROM ".$this->table."
                WHERE blog_id='".$this->con->escape($this->blog_id)."'
                AND cat_id='".$this->con->escape($this->$cat_from_id)."';";
        $this->con->execute($sql);
        $unShiftSteps = $origin_cat_rgt - $origin_cat_rgt  + 1;
      
      }else{
        //reduire la cat d'origine
        $sql = "UPDATE ".$this->table."
                SET cat_rgt= cat_lft + 1
                WHERE blog_id='".$this->con->escape($this->blog_id)."'
                AND cat_id='".$this->con->escape($this->$cat_from_id)."';";
        $this->con->execute($sql);
        $unShiftSteps = $origin_cat_rgt - $origin_cat_rgt  - 1;
      }
      //Décaller
      $sql = "UPDATE ".$this->table."
              SET cat_lft = cat_lft - ". $unShiftSteps .",
                  cat_rgt = cat_rgt - ". $unShiftSteps ."
              WHERE blog_id='".$this->con->escape($this->blog_id)."'
              AND cat_lft > ".$origin_cat_lft.";";
      $this->con->execute($sql);
    
      $this->con->commit();
    }catch (Exception $e) {
      $this->con->rollback();
      error_log($e);
      return false;
    }
  
  }
  
  protected function getCatLftRgt($cat_id){
    foreach($this->catsLftRgt as $cat){
      if($cat['cat_id'] == $cat_id){
        return $cat;
      }
    }
    return false;
  }
  /*
  * Delete the given category
  * And reorganize the orders of the categories
  * /!\ this function does not care about chields posts
  */
  public function deleteCategory($cat_id,$deleteSubs = false)
  {
    $this->con->begin();
    try{
      if($deleteSubs){
        //delete subs cats
        $sql = "DELETE subs.* FROM 
                ".$this->table." AS subs,
                ".$this->table." AS parent
                WHERE  subs.blog_id='".$this->con->escape($this->blog_id)."'
                AND parent.cat_id='".$this->con->escape($cat_id)."'
                AND subs.cat_lft BETWEEN parent.cat_lft AND parent.cat_rgt;";
        $this->con->execute($sql);
        //unshift followin cats before delete it
        $sql = "UPDATE
                  ".$this->table." followings,
                  ".$this->table." parent
                SET
                  followings.cat_lft = followings.cat_lft - parent.cat_lft - 1,
                  followings.cat_rgt = followings.cat_rgt - parent.cat_lft - 1
                WHERE
                  followings.blog_id='".$this->con->escape($this->blog_id)."'
                  AND parent.cat_id='".$this->con->escape($cat_id)."'
                  AND followings.cat_lft > parent.cat_lft;";
        $this->con->execute($sql);
      }else{
        //chields cats will be upped
        //unshift cats
        $sql = "UPDATE 
                  ".$this->table." cats,
                  ".$this->table." parent
                SET
                  cats.cat_lft = CASE WHEN cats.cat_lft BETWEEN parent.cat_lft AND parent.cat_rgt THEN cats.cat_lft - 1
                                      WHEN cats.cat_lft > parent.cat_rgt THEN cats.cat_lft - 2
                                      ELSE cats.cat_lft
                                  END,
                  cats.cat_rgt = CASE WHEN cats.cat_lft BETWEEN parent.cat_lft AND parent.cat_rgt THEN cats.cat_rgt - 1
                                      WHEN cats.cat_lft > parent.cat_rgt THEN cats.cat_rgt - 2
                                      ELSE cats.cat_rgt
                                  END
                WHERE
                  cats.blog_id='".$this->con->escape($this->blog_id)."'
                  AND parent.cat_id='".$this->con->escape($cat_id)."'
                  AND cats.cat_lft > parent.cat_lft;";
          
          $this->con->execute($sql);
      }
    //simply del the cat
      $sql = "
        DELETE FROM ".$this->table."
        WHERE blog_id='" . $this->con->escape($this->blog_id) . "'
        AND cat_id='". $this->con->escape($cat_id)."';";
      $this->con->execute($sql);
      $this->con->commit();
      return true;
      
    }catch (Exception $e) {
      $this->con->rollback();
      error_log($e);
      return false;
    }
  }
  public function cat_titleExists($cat_title){
    foreach($this->cats as $cat){
      if($cat['cat_title'] == $cat_title){
        return true;
      }
    }
    return false;
  
  }
  public function __construct($core)
  {
    /*
    * Create all the categories tree on an array
    */
    $this->core =& $core;
    $this->con =& $core->con;
    $this->blog_id = $core->blog->id;
    $this->table = $core->prefix.'category';
    $this->add_condition = array('blog_id' => "'".$this->con->escape($this->blog_id)."'");

    //all categories on an array

    $sql = "SELECT 
                    cat_id, cat_title, cat_url, cat_desc, cat_lft, cat_rgt 
                FROM ".$this->table." 
                WHERE blog_id='".$this->blog_id."'
                ORDER BY cat_lft ASC;";

    //The ORDER BY on the left property, make the returned rows are on the good order,
    //But we still have to calculate their level and position

    $rs = $this->con->select($sql);
    $cats = array();
    $catsLftRgt = array();
    $index = 0;
    $nbeCatsLevel0 = 0;
    
    while($rs->fetch()){
      $betterParentLevel = -1;
      $betterParentId = -1;
      $countPrevious = 0;
      foreach($catsLftRgt as $potentialParent){
          if(
            ($rs->cat_lft > $potentialParent['cat_lft'])
            && ($rs->cat_lft < $potentialParent['cat_rgt'])
            && ($betterParentLevel < $potentialParent['cat_level'])
          )
          {
            $betterParentLevel = $potentialParent['cat_level'];
            $betterParentId = $potentialParent['cat_id'];
            $betterParentIndex = $countPrevious;
          }
          
          $countPrevious++;
      }
      
      //Split data on two arrays
      //the goal is to no expose via api cat_lft and cat_rgt
      $cats[$index] = array(
        'cat_id'     => (int)$rs->cat_id,
        'cat_title'  => $rs->cat_title,
        'cat_url'    => $rs->cat_url,
        'cat_desc'   => $rs->cat_desc
      );
      $catsLftRgt[$index] = array(
        'cat_lft'   => (int)$rs->cat_lft,
        'cat_rgt'   => (int)$rs->cat_rgt,
        'cat_id'    => (int)$rs->cat_id,
        'cat_level' => (int)$betterParentLevel + 1,
        'count_childs'  => 0
      );
      
      $cats[$index]['cat_level'] = (int)$betterParentLevel + 1;
      if($betterParentId == -1){
        $catsLftRgt[$index]['cat_parent_id'] = $cats[$index]['cat_parent_id'] = null;
        $catsLftRgt[$index]['cat_position'] = $cats[$index]['cat_position'] = (int)$nbeCatsLevel0;
        $nbeCatsLevel0++;
      }else{
        $catsLftRgt[$index]['cat_parent_id'] = $cats[$index]['cat_parent_id'] = (int)$betterParentId;
        $catsLftRgt[$index]['cat_position'] = $cats[$index]['cat_position'] = $catsLftRgt[$betterParentIndex]['count_childs'];
        
        $catsLftRgt[$betterParentIndex]['count_childs']++;
      }
      $index++;
    }
    $this->cats = $cats;
    $this->catsLftRgt = $catsLftRgt;
  }
}
