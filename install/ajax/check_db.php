<?php
/******************************************************************************
Etano
===============================================================================
File:                       install/ajax/check_db.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

ini_set('include_path','.');
ini_set('session.use_cookies',1);
ini_set('session.use_trans_sid',0);
ini_set('date.timezone','GMT');	// temporary fix for the php 5.1+ TZ compatibility
ini_set('error_reporting',2047);
ini_set('display_errors',0);
define('_LICENSE_KEY_','');
require_once dirname(__FILE__).'/../../includes/sessions.inc.php';
require_once dirname(__FILE__).'/../../includes/sco_functions.inc.php';

$output='';
if (!empty($_POST['dbhost']) && !empty($_POST['dbuser']) && !empty($_POST['dbpass']) && !empty($_POST['dbname'])) {
	$dbhost=addslashes_mq($_POST['dbhost']);
	$dbuser=addslashes_mq($_POST['dbuser']);
	$dbpass=addslashes_mq($_POST['dbpass']);
	$dbname=addslashes_mq($_POST['dbname']);
	if (function_exists('mysql_connect')) {
		$link=@mysql_connect($dbhost,$dbuser,$dbpass);
		if ($link) {
			if (@mysql_select_db($dbname,$link)) {
				$output='Connection successfull';
			} else {
				$output='Database Host, user and password are ok but the database name is wrong.';
			}
			mysql_close($link);
		} else {
			$output='Database Host or user or password are wrong.';
		}
	} else {
		$output='Server configuration does not allow db connections.';
	}
} else {
	$output='You must fill in all parameters (DB host, user, password, name)';
}
echo $output;
