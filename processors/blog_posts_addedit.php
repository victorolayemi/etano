<?php
/******************************************************************************
Etano
===============================================================================
File:                       processors/blog_posts_addedit.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require '../includes/common.inc.php';
require _BASEPATH_.'/includes/user_functions.inc.php';
require _BASEPATH_.'/includes/tables/blog_posts.inc.php';
require _BASEPATH_.'/skins_site/'.get_my_skin().'/lang/blogs.inc.php';
check_login_member('write_blogs');

if (is_file(_BASEPATH_.'/events/processors/blog_posts_addedit.php')) {
	include _BASEPATH_.'/events/processors/blog_posts_addedit.php';
}

$error=false;
$qs='';
$qs_sep='';
$topass=array();
$nextpage='my_blog_posts.php';
if ($_SERVER['REQUEST_METHOD']=='POST') {
	$input=array();
	$blog_posts_default['defaults']['allow_comments']=0;
// get the input we need and sanitize it
	foreach ($blog_posts_default['types'] as $k=>$v) {
		$input[$k]=sanitize_and_format_gpc($_POST,$k,$__field2type[$v],$__field2format[$v],$blog_posts_default['defaults'][$k]);
	}
// max 2 empty lines
	$input['post_content']=preg_replace(array('/\\\r\\\n/','/(\\\n\s+\\\n)+/','/(\\\n){3,}/'),array('\n','\n','\n\n'),$input['post_content']);

	$input['fk_user_id']=$_SESSION[_LICENSE_KEY_]['user']['user_id'];
	if (!empty($_POST['return'])) {
		$input['return']=sanitize_and_format_gpc($_POST,'return',TYPE_STRING,$__field2format[FIELD_TEXTFIELD] | FORMAT_RUDECODE,'');
		$nextpage=$input['return'];
	}

// check for input errors
	if (empty($input['title'])) {
		$error=true;
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']=$GLOBALS['_lang'][17];
	}
	if (empty($input['post_content'])) {
		$error=true;
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']=$GLOBALS['_lang'][18];
	}

	if (!$error) {
		$input['title']=remove_banned_words($input['title']);
		$input['post_content']=remove_banned_words($input['post_content']);
		$config=get_site_option(array('manual_blog_approval'),'core_blog');
		$towrite=array();	// what to write in the cache file
		if (!empty($input['post_id'])) {
			$query="UPDATE `{$dbtable_prefix}blog_posts` SET `last_changed`='".gmdate('YmdHis')."'";
			if ($config['manual_blog_approval']) {
				// set to pending only if the title or content was changed.
				$query2="SELECT `title`,`post_content` FROM `{$dbtable_prefix}blog_posts` WHERE `post_id`=".$input['post_id'];
				if (!($res=@mysql_query($query2))) {trigger_error(mysql_error(),E_USER_ERROR);}
				if (mysql_num_rows($res)) {
					$rsrow=sanitize_and_format(mysql_fetch_assoc($res),TYPE_STRING,$__field2format[TEXT_DB2DB]);
					if (strcmp($rsrow['title'],$input['title'])!=0 || strcmp($rsrow['post_content'],$input['post_content'])!=0) {
						$query.=",`status`=".STAT_PENDING;
					}
				}
			} else {
				$query.=",`status`=".STAT_APPROVED;
			}
			foreach ($blog_posts_default['defaults'] as $k=>$v) {
				if (isset($input[$k])) {
					$query.=",`$k`='".$input[$k]."'";
					$towrite[$k]=$input[$k];
				}
			}
			$query.=" WHERE `post_id`=".$input['post_id'];
			if (isset($_on_before_update)) {
				for ($i=0;isset($_on_before_update[$i]);++$i) {
					call_user_func($_on_before_update[$i]);
				}
			}
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$topass['message']['type']=MESSAGE_INFO;
			$topass['message']['text']=$GLOBALS['_lang'][19];
			if (isset($_on_after_update)) {
				for ($i=0;isset($_on_after_update[$i]);++$i) {
					call_user_func($_on_after_update[$i]);
				}
			}
		} else {
			$now=gmdate('YmdHis');
			unset($input['post_id']);
			$query="INSERT INTO `{$dbtable_prefix}blog_posts` SET `_user`='".$_SESSION[_LICENSE_KEY_]['user']['user']."',`date_posted`='$now',`last_changed`='$now'";
			if ($config['manual_blog_approval']) {
				$query.=",`status`=".STAT_PENDING;
			} else {
				$query.=",`status`=".STAT_APPROVED;
			}
			foreach ($blog_posts_default['defaults'] as $k=>$v) {
				if (isset($input[$k])) {
					$query.=",`$k`='".$input[$k]."'";
					$towrite[$k]=$input[$k];
				}
			}
			if (isset($_on_before_insert)) {
				for ($i=0;isset($_on_before_insert[$i]);++$i) {
					call_user_func($_on_before_insert[$i]);
				}
			}
			if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
			$input['post_id']=mysql_insert_id();

			if (isset($_on_after_insert)) {
				for ($i=0;isset($_on_after_insert[$i]);++$i) {
					call_user_func($_on_after_insert[$i]);
				}
			}
			$topass['message']['type']=MESSAGE_INFO;
			if (empty($config['manual_blog_approval'])) {
				if (isset($_on_after_approve)) {
					$GLOBALS['post_ids']=array($input['post_id']);
					for ($i=0;isset($_on_after_approve[$i]);++$i) {
						call_user_func($_on_after_approve[$i]);
					}
				}
				$topass['message']['text']=$GLOBALS['_lang'][20];
			} else {
				$topass['message']['text']=$GLOBALS['_lang'][21];
			}
		}
		if (!isset($input['return']) || empty($input['return'])) {
			$qs.=$qs_sep.'bid='.$input['fk_blog_id'];
			$qs_sep='&';
			$nextpage.='?'.$qs;
		}
	} else {
		$nextpage='blog_posts_addedit.php';
// 		you must re-read all textareas from $_POST like this:
//		$input['x']=addslashes_mq($_POST['x']);
		$input['post_content']=addslashes_mq($_POST['post_content']);
		$input['return']=rawurlencode($input['return']);
		$input=sanitize_and_format($input,TYPE_STRING,FORMAT_HTML2TEXT_FULL | FORMAT_STRIPSLASH);
		$topass['input']=$input;
		if (isset($_on_error)) {
			for ($i=0;isset($_on_error[$i]);++$i) {
				call_user_func($_on_error[$i]);
			}
		}
	}
}
$nextpage=_BASEURL_.'/'.$nextpage;
redirect2page($nextpage,$topass,'',true);
