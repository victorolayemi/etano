<?php
/******************************************************************************
Etano
===============================================================================
File:                       processors/contact.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require '../includes/common.inc.php';
require _BASEPATH_.'/includes/user_functions.inc.php';
require _BASEPATH_.'/skins_site/'.get_my_skin().'/lang/contact.inc.php';
check_login_member('contact');

if (is_file(_BASEPATH_.'/events/processors/contact.php')) {
	include _BASEPATH_.'/events/processors/contact.php';
}

$error=false;
$qs='';
$qs_sep='';
$topass=array();
$nextpage='contact.php';
if ($_SERVER['REQUEST_METHOD']=='POST') {
	$input=array();
// get the input we need and sanitize it
	$input['fk_user_id']=!empty($_SESSION[_LICENSE_KEY_]['user']['user_id']) ? $_SESSION[_LICENSE_KEY_]['user']['user_id'] : 0;
	$input['subject']=sanitize_and_format_gpc($_POST,'subject',TYPE_STRING,$__field2format[FIELD_TEXTFIELD],'');
	$input['name']=sanitize_and_format_gpc($_POST,'name',TYPE_STRING,$__field2format[FIELD_TEXTFIELD],'');
	$input['email']=sanitize_and_format_gpc($_POST,'email',TYPE_STRING,$__field2format[FIELD_TEXTFIELD],'');
	$input['message_body']=sanitize_and_format_gpc($_POST,'message_body',TYPE_STRING,FORMAT_STRIP_MQ | FORMAT_TRIM,'');

	if (empty($input['subject'])) {
		$error=true;
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']=$GLOBALS['_lang'][32];
	}
	if (empty($input['fk_user_id']) && empty($input['email'])) {
		$error=true;
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']=$GLOBALS['_lang'][33];
	}
	if (empty($input['message_body'])) {
		$error=true;
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']=$GLOBALS['_lang'][34];
	}

	if (!$error && empty($input['fk_user_id']) && get_site_option('use_captcha','core')) {
		$captcha=sanitize_and_format_gpc($_POST,'captcha',TYPE_STRING,0,'');
		if (!$error && (!isset($_SESSION['captcha_word']) || strcasecmp($captcha,$_SESSION['captcha_word'])!=0)) {
			$error=true;
			$topass['message']['type']=MESSAGE_ERROR;
			$topass['message']['text']=$GLOBALS['_lang'][24];
			$input['error_captcha']='red_border';
		}
	}
	unset($_SESSION['captcha_word']);

	if (!$error) {
		if (isset($_on_before_insert)) {
			for ($i=0;isset($_on_before_insert[$i]);++$i) {
				call_user_func($_on_before_insert[$i]);
			}
		}
		$config=get_site_option(array('mail_from','mail_crlf'),'core');
		if (!empty($input['fk_user_id'])) {
			$query="SELECT `email` FROM `".USER_ACCOUNTS_TABLE."` WHERE `user_id`=".$input['fk_user_id'];
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$input['email']=mysql_result($res,0,0);
		}
		require _BASEPATH_.'/includes/classes/phpmailer.class.php';
		$mail=new PHPMailer();
		$mail->IsHTML(false);
		$mail->From=$input['email'];
		$mail->Sender=$input['email'];
		if (isset($_SESSION[_LICENSE_KEY_]['user']['user'])) {
			$mail->FromName=$_SESSION[_LICENSE_KEY_]['user']['user'];
		} elseif (!empty($input['name'])) {
			$mail->FromName=$input['name'];
		} else {
			$mail->FromName='Contact form on '._SITENAME_;
		}
		if ($config['mail_crlf']) {
			$mail->LE="\r\n";
		} else {
			$mail->LE="\n";
		}
		$mail->IsMail();
		$mail->AddAddress($config['mail_from']);
		$mail->Subject=$input['subject'];
		$mail->Body=$input['message_body'];
		if (!$mail->Send()) {
			$myreturn=false;
			$topass['message']['type']=MESSAGE_ERROR;
			$topass['message']['text']=sprintf($GLOBALS['_lang'][35],$mail->ErrorInfo);
		} else {
			$topass['message']['type']=MESSAGE_INFO;
			$topass['message']['text']=$GLOBALS['_lang'][36];
		}
		if (!$error) {
			if (isset($_on_after_insert)) {
				for ($i=0;isset($_on_after_insert[$i]);++$i) {
					call_user_func($_on_after_insert[$i]);
				}
			}
		}
	}

	if ($error) {
		$input['message_body']=addslashes_mq($_POST['message_body']);
		$input=sanitize_and_format($input,TYPE_STRING,FORMAT_HTML2TEXT_FULL | FORMAT_STRIPSLASH);
		$topass['input']=$input;
		if (isset($_on_error)) {
			for ($i=0;isset($_on_error[$i]);++$i) {
				call_user_func($_on_error[$i]);
			}
		}
	}
}
redirect2page($nextpage,$topass,$qs);
