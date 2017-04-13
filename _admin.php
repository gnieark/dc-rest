<?php

if (!defined('DC_CONTEXT_ADMIN')) { return; }
// dead but useful code, in order to have translations

$_menu['Blog']->addItem(__('Rest API'),
		'plugin.php?p=rest',
		urldecode(dcPage::getPF('rest/rest_api_256x256.png')),
		preg_match('/plugin.php\?p=rest(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('contentadmin',$core->blog->id));
