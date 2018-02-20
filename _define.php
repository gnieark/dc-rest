<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2017 Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

$this->registerModule(
  /* Name */          "rest",
  /* Description*/    "A JSON/REST API for Dotclear",
  /* Author */        "Gnieark",
  /* Version */        '0.0.6',
 /* Permissions */		'usage,contentadmin'
);
