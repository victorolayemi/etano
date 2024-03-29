<?php
/******************************************************************************
Etano
===============================================================================
File:                       processors/photo_settings.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require '../includes/common.inc.php';
require _BASEPATH_.'/includes/user_functions.inc.php';
require _BASEPATH_.'/includes/tables/user_photos.inc.php';
require _BASEPATH_.'/skins_site/'.get_my_skin().'/lang/photos.inc.php';
check_login_member('upload_photos');

if (is_file(_BASEPATH_.'/events/processors/photo_settings.php')) {
	include _BASEPATH_.'/events/processors/photo_settings.php';
}

$error=false;
$qs='';
$qs_sep='';
$topass=array();
$nextpage='my_photos.php';
if ($_SERVER['REQUEST_METHOD']=='POST') {
	$input=array();
// get the input we need and sanitize it
	foreach ($user_photos_default['types'] as $k=>$v) {
		$input[$k]=sanitize_and_format_gpc($_POST,$k,$__field2type[$v],$__field2format[$v],array());
	}
	if (empty($input['is_main'])) {
		$input['is_main']=0;
	}
	if (!empty($_POST['return'])) {
		$input['return']=sanitize_and_format_gpc($_POST,'return',TYPE_STRING,$__field2format[FIELD_TEXTFIELD] | FORMAT_RUDECODE,'');
		$nextpage=$input['return'];
	}

	if (isset($_on_after_post)) {
		for ($i=0;isset($_on_after_post[$i]);++$i) {
			call_user_func($_on_after_post[$i]);
		}
	}

	if (!$error) {
		$input['caption']=remove_banned_words($input['caption']);
		$query="SELECT `photo_id`,`caption`,`is_main`,`photo`,`status` FROM `{$dbtable_prefix}user_photos` WHERE `photo_id` IN ('".join("','",array_keys($input['caption']))."') AND `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		$old_captions=array();
		$old_main=0;
		$photos=array();
		$statuses=array();
		while ($rsrow=mysql_fetch_assoc($res)) {
			$old_captions[$rsrow['photo_id']]=sanitize_and_format($rsrow['caption'],TYPE_STRING,$__field2format[TEXT_DB2DB]);
			$photos[$rsrow['photo_id']]=$rsrow['photo'];
			if (!empty($rsrow['is_main'])) {
				$old_main=$rsrow['photo_id'];
			}
			$statuses[$rsrow['photo_id']]=$rsrow['status'];
		}
		$captions_changed=array();
		foreach ($input['caption'] as $photo_id=>$caption) {
			if ($caption!=$old_captions[$photo_id]) {
				$captions_changed[$photo_id]=1;
			}
		}

		$now=gmdate('YmdHis');
		$config=get_site_option(array('manual_photo_approval'),'core_photo');
		if (!empty($input['is_main']) && $input['is_main']!=$old_main && !isset($input['is_private'][$input['is_main']])) {
			$query="UPDATE `{$dbtable_prefix}user_photos` SET `is_main`=0 WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
// if photo approvals are automatic then we can make this photo the main photo now. Otherwise it will have to be done upon approval!!!
			if (empty($config['manual_photo_approval']) || (isset($statuses[$input['is_main']]) && $statuses[$input['is_main']]==STAT_APPROVED)) {
				$query="UPDATE `{$dbtable_prefix}user_profiles` SET `_photo`='".$photos[$input['is_main']]."',`last_changed`='".gmdate('YmdHis')."' WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
				if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
				// this sucks...the code below is taken from on_after_approve_photo(). In the future, when new functionality that depends on the main photo will be added, we'll have to change the code there and here too.
				$query="UPDATE `{$dbtable_prefix}blog_posts` SET `last_changed`='$now' WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
				if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
				$query="UPDATE `{$dbtable_prefix}comments_blog` SET `last_changed`='$now' WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
				if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
				$query="UPDATE `{$dbtable_prefix}comments_photo` SET `last_changed`='$now' WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
				if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			}
			if ($old_main==0) {
				add_member_score($_SESSION[_LICENSE_KEY_]['user']['user_id'],'add_main_photo');
			}
		}
		if (isset($input['is_private'][$old_main])) {
			$query="UPDATE `{$dbtable_prefix}user_photos` SET `is_main`=0 WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$query="UPDATE `{$dbtable_prefix}user_profiles` SET `_photo`='',`last_changed`='".gmdate('YmdHis')."' WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			// this sucks...the code below is taken from on_after_approve_photo(). In the future, when new functionality that depends on the main photo will be added, we'll have to change the code there and here too.
			$query="UPDATE `{$dbtable_prefix}blog_posts` SET `last_changed`='$now' WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$query="UPDATE `{$dbtable_prefix}comments_blog` SET `last_changed`='$now' WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$query="UPDATE `{$dbtable_prefix}comments_photo` SET `last_changed`='$now' WHERE `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			add_member_score($_SESSION[_LICENSE_KEY_]['user']['user_id'],'del_main_photo');
		}

		foreach ($input['caption'] as $photo_id=>$caption) {
			$query="UPDATE `{$dbtable_prefix}user_photos` SET `is_private`=".(isset($input['is_private'][$photo_id]) ? 1 : 0).",`allow_comments`=".(isset($input['allow_comments'][$photo_id]) ? 1 : 0).",`last_changed`='$now'";
			if ($input['is_main']==$photo_id) {
				$query.=",`is_main`=1";
			} else {
				$query.=",`is_main`=0";
			}
			if (isset($captions_changed[$photo_id])) {
				$query.=",`caption`='$caption'";
				if (!empty($config['manual_photo_approval'])) {
					$query.=",`status`=".STAT_PENDING;
				} else {
					// leave as it was - whatever it was.
//					$query.=",`status`=".STAT_APPROVED;
				}
			}
			$query.=" WHERE `photo_id`=$photo_id AND `fk_user_id`='".$_SESSION[_LICENSE_KEY_]['user']['user_id']."'";
			if (isset($_on_before_update)) {
				for ($i=0;isset($_on_before_update[$i]);++$i) {
					call_user_func($_on_before_update[$i]);
				}
			}
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			if (isset($_on_after_update)) {
				for ($i=0;isset($_on_after_update[$i]);++$i) {
					call_user_func($_on_after_update[$i]);
				}
			}
		}
		$topass['message']['type']=MESSAGE_INFO;
		$topass['message']['text']=$GLOBALS['_lang'][92];
	}
}
$nextpage=_BASEURL_.'/'.$nextpage;
redirect2page($nextpage,$topass,'',true);
