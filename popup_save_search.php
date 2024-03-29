<?php
/******************************************************************************
Etano
===============================================================================
File:                       popup_save_search.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require 'includes/common.inc.php';
require _BASEPATH_.'/includes/user_functions.inc.php';
check_login_member('save_searches');

$search=array();
if (isset($_SESSION['topass']['input'])) {
	$search=$_SESSION['topass']['input'];
} elseif (!empty($_GET['search'])) {
	$search['search']=$_GET['search'];
}

$tpl=new phemplate(_BASEPATH_.'/skins_site/'.get_my_skin().'/','remove_nonjs');
$tpl->set_file('content','popup_save_search.html');
$tpl->set_var('tplvars',$tplvars);
$tpl->set_var('search',$search);
$message=isset($message) ? $message : (isset($topass['message']) ? $topass['message'] : (isset($_SESSION['topass']['message']) ? $_SESSION['topass']['message'] : array()));
if (!empty($message)) {
	$message['type']=(!isset($message['type']) || $message['type']==MESSAGE_ERROR) ? 'message_error' : 'message_info';
	if (is_array($message['text'])) {
		$message['text']=join('<br>',$message['text']);
	}
	$message['text']='<div id="message_wrapper" class="'.$message['type'].'">'.$message['text'].'</div>';
	$tpl->set_var('message',$message['text']);
}
echo $tpl->process('','content',TPL_FINISH);
