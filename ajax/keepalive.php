<?php
/******************************************************************************
Etano
===============================================================================
File:                       ajax/keepalive.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require_once dirname(__FILE__).'/../includes/common.inc.php';
require_once dirname(__FILE__).'/../includes/user_functions.inc.php';

if (!empty($_SESSION[_LICENSE_KEY_]['user']['user_id'])) {
	check_login_member('all');
}
