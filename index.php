<?php
if (!defined('DC_CONTEXT_ADMIN')) { return; }

// Setting default parameters if missing configuration
$core->blog->settings->addNamespace('rest');
if (is_null($core->blog->settings->rest->rest_active)) {
  try {
    $core->blog->settings->rest->put('rest_active',false,'boolean',true);
    $core->blog->settings->rest->put('rest_send_cors_headers',true,'boolean',true);
    $core->blog->triggerBlog();
    http::redirect($p_url);
  }
  catch (Exception $e) {
    $core->error->add($e->getMessage());
  }
}

//a form is submitted
if (!empty($_POST['saveconfig'])){
  try
  {
    $core->blog->settings->addNameSpace('rest');
    $active = (empty($_POST['active'])) ? false : true;
    $core->blog->settings->rest->put('rest_active',$active,'boolean');
  
    $sendHeaders = (empty($_POST['sendHeaders'])) ? false : true;
    $core->blog->settings->rest->put('rest_send_cors_headers',$sendHeaders,'boolean');
    
    dcPage::addSuccessNotice(__('Configuration successfully updated.'));
    http::redirect($p_url);
  }catch (Exception $e)
  {
    $core->error->add($e->getMessage());
  }



    http::redirect($p_url.'&saveconfig=1');
 }

// Getting current parameters
$active = (boolean)$core->blog->settings->rest->rest_active;
$sendHeaders =(boolean)$core->blog->settings->rest->rest_send_cors_headers;


?><html>
<head>
  <title><?php echo __('REST API configuration'); ?></title>
</head>
<body>

  <h2><?php echo __('Your API key');?></h2>
  <?php 
  // Settings form is only available for super admins.
  if($core->auth->isSuperAdmin()): 
  ?>
  <h2><?php echo __('API Settings');?></h2>
    <form method="post" action="<?php http::getSelfURI(); ?>">
    <?php echo $core->formNonce(); ?>
    <p>
      <?php echo form::checkbox('active', 1, $active); ?>
      <label class="classic" for="active">&nbsp;<?php echo __('Enable REST API');?></label>
    </p>
    <p>
      <?php echo form::checkbox('sendHeaders', 1, $sendHeaders); ?>
      <label class="classic" for="sendHeaders">&nbsp;<?php echo __('Send the Cross Origin Domain http headers');?></label>
    </p>
      <p> To do: list here all api access points and acl</p>
    

    <p><input type="submit" name="saveconfig" value="<?php echo __('Save configuration'); ?>" /></p>
  </form>


<?php endif; ?>
  <h2><?php echo __('Documentation');?></h2>

  </body>
  </html>
