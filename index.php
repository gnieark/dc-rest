<?php

if (!defined('DC_CONTEXT_ADMIN')) { return; }


$apiKey = new ApiKey;

// Setting default parameters if missing configuration
$core->blog->settings->addNamespace('rest');
if (is_null($core->blog->settings->rest->rest_active)) {
	try {
		$core->blog->settings->rest->put('rest_active',false,'boolean',true);
		$core->blog->settings->rest->put('rest_is_open',false,'boolean',true);
		$core->blog->settings->rest->put('rest_send_cors_headers',true,'boolean',true);
		$core->blog->triggerBlog();
		http::redirect($p_url);
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}
// Getting current parameters
$active = (boolean)$core->blog->settings->rest->rest_active;
$openApi = (boolean)$core->blog->settings->rest->rest_is_open;
$sendHeaders =(boolean)$core->blog->settings->rest->rest_send_cors_headers;

//Sousmission Formulaire parametres
if ((!empty($_POST['saveconfig'])) && ($core->auth->isSuperAdmin())) {
	try
	{
		$core->blog->settings->addNameSpace('rest');
		$active = (empty($_POST['active'])) ? false : true;
		$core->blog->settings->rest->put('rest_active',$active,'boolean');
		
		$openApi = (empty($_POST['open'])) ? false : true;
		$core->blog->settings->rest->put('rest_is_open',$openApi,'boolean');
		
		$sendHeaders = (empty($_POST['sendHeaders'])) ? false : true;
		$core->blog->settings->rest->put('rest_send_cors_headers',$sendHeaders,'boolean');
		
		dcPage::addSuccessNotice(__('Configuration successfully updated.'));
		http::redirect($p_url);
	}catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}
//Sousmission Formulaire Reset API Key
if(!empty($_POST['resetApiKey'])){
	$core->blog->settings->addNameSpace('rest');
	$apiKey -> new_key($core->auth->userID());
	dcPage::addSuccessNotice(__('Your new key is').' '.$apiKey->key);
}


?>
<html>
<head>
	<title>Rest API config</title>
</head>
<body>
	<h2>Documentation</h2>
            <p><a href="<?php echo $core->blog->url."rest/documentation"; ?>">Go to the Swagger documentation</a></p>
	<h2><?php echo __('Your API key');?></h2>
	<?php echo $apiKey-> get_dc_admin_form($core->auth->userID()); ?>

<?php 
//Seulement si administrateur:
if($core->auth->isSuperAdmin()): 
?>
	<h2><?php echo __('Rest API configuration'); ?></h2>
		<form method="post" action="<?php http::getSelfURI(); ?>">
		<p>
			<?php echo form::checkbox('active', 1, $active); ?>
			<label class="classic" for="active">&nbsp;<?php echo __('Enable REST API');?></label>
		</p>
		<p>
			<?php echo form::checkbox('open', 1, $openApi); ?>
			<label class="classic" for="open">&nbsp;<?php echo __('API is open');?></label>
		</p>
		<p class="info"><?php echo __("If checked, few methods as GET will be allowed to externals users without API key. 
		However, they won't be able to request for non public content."); ?></p> 
		<?php echo $core->formNonce(); ?>
		<p>
			<?php echo form::checkbox('sendHeaders', 1, $sendHeaders); ?>
			<label class="classic" for="sendHeaders">&nbsp;<?php echo __('Send Coors headers');?></label>
		</p>
		<p>
			<input type="submit" name="saveconfig" value="<?php echo __('Save configuration'); ?>" />
		</p>
		
		</from>
<?php 
endif;
?>
</body>
</html>