<?php

$api_key = 'tn0GHPOxbK3hbJAygRPihJHqPKvhC2vw'; //super admin api_key, change it by your
$api_url = 'http://dotclear.localhost/rest'; //my local dev platform, change it by your
//testUser key DVsmYPmW6jvfk4kgak1krvbxcl1nGXMJ

function check_json_content($content,$aKeyToCheck){
  
  $arr=json_decode($content,true);
  
  if($aKeyToCheck === false){
    if (is_array($arr)){
        return true;
    }else{
        return false;
    }
  }
  if(isset($arr[$aKeyToCheck])){
    if(is_array($arr[$aKeyToCheck])){
      return json_encode($arr[$aKeyToCheck],true);
    }else{
      return $arr[$aKeyToCheck];
    }
  }else{
    return false;
  }
}

function test($url, $method, $body, $expectedCode, $expectedKeyOnResponse, $x_dc_key){

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  
  if($x_dc_key <> ''){
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('x_dc_key: '.$x_dc_key));
  }
  if($body <> ''){
     curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
  }
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch)['http_code'];
  
  if($httpCode <> $expectedCode){
    echo "\nQuery type ".$method." on url ".$url." didn't return the expected code.\n".
         "return: ".$httpCode." Expected: ".$expectedCode."\n".
         "Response content:\n".$response;
    //on va pas plus loin, ça pourrait mettre la m... dans la suite
    die();
    return;
  }
  
  $r = check_json_content($response ,$expectedKeyOnResponse);
  if($r === false){
    echo "\nQuery type ".$method." on url ".$url." JSON parse error or missing propertie.\n".
         "return code: ".$httpCode."\n".
         "Response content:\n".$response;
    //on va pas plus loin, ça pourrait mettre la m... dans la suite
    die();
    return;
  }
  curl_close($ch);
  return $r;
}


$allTests = array(
  array(
    'title'                 => 'test 404 page',
    'url'                   =>  $api_url.'/kgdghui',
    'method'                => 'GET',
    'expectedResponseCode'  => '404',
    'expectedKeyOnResponse' => 'error',
    'body'                  => '',
    'x_dc_key'              => '',
    'saveAs'                => ''

  ),
  array(
    'title'                 => 'test specs Method',
    'url'                   =>  $api_url.'/specs',
    'method'                => 'GET',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'swagger',
    'body'                  => '',
    'x_dc_key'              => '',
    'saveAs'                => ''

  ),
    array(
    'title'                 => 'get /blogs without api_key',
    'url'                   =>  $api_url.'/blogs',
    'method'                => 'GET',
    'expectedResponseCode'  => '403',
    'expectedKeyOnResponse' => 'error',
    'body'                  => '',
    'x_dc_key'              => '',
    'saveAs'                => ''

  ),
  array(
    'title'                 => 'get /blogs with wrong api_key',
    'url'                   =>  $api_url.'/blogs',
    'method'                => 'GET',
    'expectedResponseCode'  => '403',
    'expectedKeyOnResponse' => 'error',
    'body'                  => '',
    'x_dc_key'              => '7777777777777',
    'saveAs'                => ''

  ),
    array(
    'title'                 => 'get /blogs with good api_key',
    'url'                   =>  $api_url.'/blogs',
    'method'                => 'GET',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => false, //is a single list
    'body'                  => '',
    'x_dc_key'              => $api_key,
    'saveAs'                => ''

  ),
  //creer un blog qui servira aux tests suivants
    array(
    'title'                 => 'post /blogs/ with good api_key',
    'url'                   =>  $api_url.'/blogs',
    'method'                => 'POST',
    'expectedResponseCode'  => '201',
    'expectedKeyOnResponse' => 'id', //is a single list
    'body'                  => json_encode(array(
                                      "blog_id" => "test-api",
                                        "blog_name" => "Test de l'API",
                                        "blog_url" => "http://test.localhost/",
                                        "blog_desc"=> "un test"
                                )),
    'x_dc_key'              => $api_key,
    'saveAs'                => 'blog_id'

  ),
  
  //test JSON deffectueux
  array(
    'title'                 => 'post /blogs/ with good api_key and bad JSON',
    'url'                   =>  $api_url.'/blogs',
    'method'                => 'POST',
    'expectedResponseCode'  => '400',
    'expectedKeyOnResponse' => 'error',
    'body'                  => '{"ce n\'est pas du" => "JSON"}',
    'x_dc_key'              => $api_key,
    'saveAs'                => ''

  ),
  
  //get blog Properties
  array(
    'title'                 => 'Blogs /blogs/%blog_id%  with good api_key',
    'url'                   =>  $api_url.'/blogs/%blog_id%',
    'method'                => 'GET',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'blog_url',
    'body'                  => '',
    'x_dc_key'              => $api_key,
    'saveAs'                => ''

), 

  //Patch blog properties with error ON JSON
  array(
    'title'                 => 'PATCH /blogs/%blog_id%  with with error ON JSON',
    'url'                   =>  $api_url.'/blogs/%blog_id%',
    'method'                => 'PATCH',
    'expectedResponseCode'  => '400',
    'expectedKeyOnResponse' => 'error',
    'body'                  => '{JSON IS FUN}',
    'x_dc_key'              => $api_key,
    'saveAs'                => ''

),

  array(
    'title'                 => 'PATCH /blogs/%blog_id%  with with error ON  api_key',
    'url'                   =>  $api_url.'/blogs/%blog_id%',
    'method'                => 'PATCH',
    'expectedResponseCode'  => '403',
    'expectedKeyOnResponse' => 'error',
    'body'                  => '{"blog_name": "Patched Blog name"}',
    'x_dc_key'              => 'heyHey!',
    'saveAs'                => ''

),

    array(
    'title'                 => 'PATCH /blogs/%blog_id% without error',
    'url'                   =>  $api_url.'/blogs/%blog_id%',
    'method'                => 'PATCH',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'message',
    'body'                  => '{"blog_name": "Patched Blog name"}',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''

),
  
//PUT
  //Patch blog properties with error ON JSON
  array(
    'title'                 => 'PUT /blogs/%blog_id%  with with error ON JSON',
    'url'                   =>  $api_url.'/blogs/%blog_id%',
    'method'                => 'PUT',
    'expectedResponseCode'  => '400',
    'expectedKeyOnResponse' => 'error',
    'body'                  => '{JSON IS FUN}',
    'x_dc_key'              => $api_key,
    'saveAs'                => ''
  ),

  array(
    'title'                 => 'PUT /blogs/%blog_id%  with with error ON  api_key',
    'url'                   =>  $api_url.'/blogs/%blog_id%',
    'method'                => 'PUT',
    'expectedResponseCode'  => '403',
    'expectedKeyOnResponse' => 'error',
    'body'                  => '{"blog_id": "%blog_id%","blog_url":"https://plop.local/", "blog_name": "Patched Blog name", "blog_desc": "blahblah"}',
    'x_dc_key'              => 'heyHey!',
    'saveAs'                => ''
    ),

    array(
    'title'                 => 'PUT/blogs/%blog_id% without error',
    'url'                   =>  $api_url.'/blogs/%blog_id%',
    'method'                => 'PUT',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'message',
    'body'                  => '{"blog_id": "%blog_id%","blog_url":"https://plop.local/", "blog_name": "Patched Blog name", "blog_desc": "blahblah"}',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
    ),
    
    //get settings
   array(
    'title'                 => 'GET /%blog_id%/settings without error',
    'url'                   =>  $api_url.'/%blog_id%/settings',
    'method'                => 'GET',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'system',
    'body'                  => '',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
    //get settings
   array(
    'title'                 => 'GET /%blog_id%/settings/system without error',
    'url'                   =>  $api_url.'/%blog_id%/settings/system',
    'method'                => 'GET',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'url_scan',
    'body'                  => '',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
   array(
    'title'                 => 'GET /%blog_id%/settings/system/url_scan without error',
    'url'                   =>  $api_url.'/%blog_id%/settings/system/url_scan',
    'method'                => 'GET',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'value',
    'body'                  => '',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
      array(
    'title'                 => 'GET /%blog_id%/settings/HEYHEY/url_scan without error',
    'url'                   =>  $api_url.'/%blog_id%/settings/HEYHEY/url_scan',
    'method'                => 'GET',
    'expectedResponseCode'  => '404',
    'expectedKeyOnResponse' => 'code',
    'body'                  => '',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
   
   array(
    'title'                 => 'POST /%blog_id%/settings/test without error',
    'url'                   =>  $api_url.'/%blog_id%/settings/test',
    'method'                => 'POST',
    'expectedResponseCode'  => '201',
    'expectedKeyOnResponse' => 'code',
    'body'                  => '[{"id":"test","value":"hey","type":"string"},{"id":"test2","value":"hey","type":"string"}]',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
      array(
    'title'                 => 'POST /%blog_id%/settings/test without api key',
    'url'                   =>  $api_url.'/%blog_id%/settings/test',
    'method'                => 'POST',
    'expectedResponseCode'  => '403',
    'expectedKeyOnResponse' => 'code',
    'body'                  => '[{"id":"test2","value":"hey","type":"string"}]',
    'x_dc_key'              =>  '',
    'saveAs'                => ''
   ),
    array(
    'title'                 => 'POST /%blog_id%/settings/test with fail JSON',
    'url'                   =>  $api_url.'/%blog_id%/settings/test',
    'method'                => 'POST',
    'expectedResponseCode'  => '400',
    'expectedKeyOnResponse' => 'code',
    'body'                  => '[{"id"=> Hey"test2","value":"hey","type":"string"}]',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
   
   //delete the settings
    array(
    'title'                 => 'DELETE /%blog_id%/settings/test/test2 with key error',
    'url'                   =>  $api_url.'/%blog_id%/settings/test/test2',
    'method'                => 'DELETE',
    'expectedResponseCode'  => '403',
    'expectedKeyOnResponse' => 'code',
    'body'                  => '',
    'x_dc_key'              =>  'lkjmlhkjb:b:kjb',
    'saveAs'                => ''
   ),
   array(
    'title'                 => 'DELETE /%blog_id%/settings/test/tsdfLJKt2 with name error',
    'url'                   =>  $api_url.'/%blog_id%/settings/test/tsdfLJKt2',
    'method'                => 'DELETE',
    'expectedResponseCode'  => '404',
    'expectedKeyOnResponse' => 'code',
    'body'                  => '',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
   
    array(
    'title'                 => 'DELETE /%blog_id%/settings/test/test2 without error',
    'url'                   =>  $api_url.'/%blog_id%/settings/test/test2',
    'method'                => 'DELETE',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'code',
    'body'                  => '',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
   
   //create a POST
   
   array(
    'title' => 'Create a post /%blog_id%/post',
    'url' => $api_url.'/%blog_id%/post',
    'method'  => 'POST',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'id',
    'body'  => json_encode(array(
        "post_title" => "New Post",
        "post_format"=> "wiki",
        "post_content"=> "!!!Pouette \n hey",
        "post_content_xhtml"=> "string",
        "post_status"=> "Pending",
        "post_tags" => array('plip','plap')
    )),
    'x_dc_key'              =>  $api_key,
    'saveAs'                => 'post_id'
    
   ),
   //plusieurs billets d'un coup
   array(
    'title' => 'Create many post /%blog_id%/post',
    'url' => $api_url.'/%blog_id%/post',
    'method'  => 'POST',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'message',
    'body'  => json_encode(array(
      array(
        "post_title" => "New Post2",
        "post_format"=> "wiki",
        "post_content"=> "!!!Pouette \n hey",
        "post_content_xhtml"=> "string",
        "post_status"=> "Pending",
      ),
      array(
        "post_title" => "New Post3",
        "post_format"=> "wiki",
        "post_content"=> "!!!Pouette \n\n!!hey\n heu...",
        "post_content_xhtml"=> "string",
        "post_status"=> "Pending",
      )
    )),
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
    
   ),
   
   //create a post with a new category
  array(
    'title' => 'Create a post /%blog_id%/post with a new cat',
    'url' => $api_url.'/%blog_id%/post',
    'method'  => 'POST',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'message',
    'body'  => json_encode(array(
        "post_title" => "New Post4",
        "post_format"=> "wiki",
        "post_content"=> "!!!Pouette \n hey",
        "post_content_xhtml"=> "string",
        "post_status"=> "Pending",
        "new_cat_id"=> "TestingCat",
    )),
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),  
   
      //create a post with an existing category
  array(
    'title' => 'Create a post /%blog_id%/post with an existing cat',
    'url' => $api_url.'/%blog_id%/post',
    'method'  => 'POST',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'message',
    'body'  => json_encode(array(
        "post_title" => "New Post5",
        "post_format"=> "wiki",
        "post_content"=> "!!!Pouette \n hey",
        "post_content_xhtml"=> "string",
        "post_status"=> "Pending",
        "cat_id"=> 1,
    )),
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
   
   //create a post with a new sub category
  array(
    'title' => 'Create a post /%blog_id%/post',
    'url' => $api_url.'/%blog_id%/post',
    'method'  => 'POST',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'message',
    'body'  => json_encode(array(
        "post_title" => "New Post6",
        "post_format"=> "wiki",
        "post_content"=> "!!!Pouette \n hey",
        "post_content_xhtml"=> "string",
        "post_status"=> "Pending",
        "new_cat_parent" => 1,
        "new_cat_id"=> "Testing sub Cat",
    )),
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
   
   array(
    'title' => 'Create a post /%blog_id%/post with all parameters',
    'url' => $api_url.'/%blog_id%/post',
    'method'  => 'POST',
    'expectedResponseCode'  => '200',
    'expectedKeyOnResponse' => 'message',
    'body'  => json_encode(array(
        "post_title" => "New Post6",
        "post_format"=> "wiki",
        "post_content"=> "!!!Pouette \n hey",
        "post_content_xhtml"=> "string",
        "post_status"=> "Pending",
        "new_cat_parent" => 1,
        "new_cat_id"=> "Testing sub Cat 2",
        "post_dt" => '2013-04-19 05:06:07',
        "post_password" => 'toto',
        "post_url"  => "newPost",
        "post_lang" => "de",
        "post_excerpt"  => "blahblah",
        "post_notes"  => "heu...",
        "post_selected" => true,
        "post_open_comment" => true,
        "post_open_tb"  => true,
    )),
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
   
   
   //Créer une méta (un tag)
   array(
    "title"     => 'Create a  post meta /%blog_id%/metas',
    'url'       => $api_url.'/%blog_id%/metas',
    'method'    => 'POST',
    'expectedResponseCode'  => '201',
    'expectedKeyOnResponse' => 'id',
    'body'  => '{ "meta_id": "lol", "meta_type": "tag", "post_id": %post_id% }',
    'x_dc_key'              =>  $api_key,
    'saveAs'                => ''
   ),
   /*
     "post_title": "string",
  "post_format": "string",
  "post_content": "string",
  "post_content_xhtml": "string",
  "post_status": "Pending",
  "cat_id": 0,
  "new_cat_id": "string",
  "new_cat_parent_id": 0,
  "new_cat_desc": "string",
  "new_cat_url": "string",
  "post_dt": "string",
  "post_password": "string",
  "post_url": "string",
  "post_lang": "string",
  "post_excerpt": "string",
  "post_excerpt_xhtml": "string",
  "post_notes": "string",
  "post_selected": true,
  "post_open_comment": true,
  "post_open_tb": true,
  "post_words": [
    null
  ]
  */
   
   
   /*
   ,
  //remove blog test
    array(
      'title'                 => 'Blogs /blogs/%blog_id%  with good api_key',
      'url'                   =>  $api_url.'/blogs/%blog_id%',
      'method'                => 'DELETE',
      'expectedResponseCode'  => '201',
      'expectedKeyOnResponse' => 'message', //is a single list
      'body'                  => '',
      'x_dc_key'              => $api_key,
      'saveAs'                => ''

    ),
    */
);

$saveIds = array();
foreach($allTests as $oneTest){

  //replaces
  foreach($oneTest as $key => $value){
    foreach($saveIds as $find => $replace){
      $value = $oneTest[$key] = str_replace('%'.$find.'%', $replace, (string)$value);
    }
    
  }
  
  
  echo "\nTesting ".$oneTest['title']." ".$oneTest['url']." method ". $oneTest['method'];
  $t = test(
              $oneTest['url'],
              $oneTest['method'],
              $oneTest['body'],
              $oneTest['expectedResponseCode'], 
              $oneTest['expectedKeyOnResponse'],
              $oneTest['x_dc_key']
              );
  echo "\nSUCCESS ".$t;
  
  if ($oneTest['saveAs'] <> ''){   
    $saveIds[$oneTest['saveAs']] = $t;
  }

}
