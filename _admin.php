<?php

if (!defined('DC_CONTEXT_ADMIN')) { return; }

$_menu['Blog']->addItem(__('Rest API'),
    'plugin.php?p=rest',
    urldecode(dcPage::getPF('rest/rest_api.png')),
    preg_match('/plugin.php\?p=rest(&.*)?$/',$_SERVER['REQUEST_URI']),
    $core->auth->check('contentadmin',$core->blog->id));
