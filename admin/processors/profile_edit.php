<?php
/******************************************************************************
newdsb
===============================================================================
File:                       admin/processors/profile_edit.php
$Revision: 21 $
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://forum.datemill.com
*******************************************************************************
* See the "softwarelicense.txt" file for license.                             *
******************************************************************************/

require_once '../../includes/sessions.inc.php';
require_once '../../includes/classes/phemplate.class.php';
require_once '../../includes/vars.inc.php';
require_once '../../includes/admin_functions.inc.php';
require_once '../../includes/field_functions.inc.php';
db_connect(_DBHOSTNAME_,_DBUSERNAME_,_DBPASSWORD_,_DBNAME_);
allow_dept(DEPT_ADMIN);

$error=false;
$qs='';
$qs_sep='';
$topass=array();
$input=array();
$nextpage='';
if ($_SERVER['REQUEST_METHOD']=='POST') {
	$input=array();
// get the input we need and sanitize it
	$input['uid']=sanitize_and_format_gpc($_POST,'uid',TYPE_INT,0,0);
	$input['return']=rawurldecode(sanitize_and_format_gpc($_POST,'return',TYPE_STRING,$__html2format[_HTML_TEXTFIELD_],''));

	$on_changes=array();
	$ch=0;
	foreach ($_pfields as $field_id=>$field) {
		if ($field['editable']) {
			switch ($field['html_type']) {

				case _HTML_DATE_:
					$input[$field['dbfield'].'_month']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_month',TYPE_INT,0,0);
					$input[$field['dbfield'].'_day']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_day',TYPE_INT,0,0);
					$input[$field['dbfield'].'_year']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_year',TYPE_INT,0,0);
					if (!empty($input[$field['dbfield'].'_year']) && !empty($input[$field['dbfield'].'_month']) && !empty($input[$field['dbfield'].'_day'])) {
						$input[$field['dbfield']]=$input[$field['dbfield'].'_year'].'-'.str_pad($input[$field['dbfield'].'_month'],2,'0',STR_PAD_LEFT).'-'.str_pad($input[$field['dbfield'].'_day'],2,'0',STR_PAD_LEFT);
					}
					if (isset($field['fn_on_change'])) {
						$on_changes[$ch]['fn']=$field['fn_on_change'];
						$on_changes[$ch]['param2']=array('year'=>$input[$field['dbfield'].'_year'],'month'=>$input[$field['dbfield'].'_month'],'day'=>$input[$field['dbfield'].'_day']);
						$on_changes[$ch]['param3']=$field['dbfield'];
						++$ch;
					}
					break;

				case _HTML_LOCATION_:
					$input[$field['dbfield'].'_country']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_country',TYPE_INT,0,0);
					$input[$field['dbfield'].'_state']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_state',TYPE_INT,0,0);
					$input[$field['dbfield'].'_city']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_city',TYPE_INT,0,0);
					$input[$field['dbfield'].'_zip']=sanitize_and_format_gpc($_POST,$field['dbfield'].'_zip',TYPE_STRING,$__html2format[_HTML_TEXTFIELD_],'');
					if (isset($field['fn_on_change'])) {
						$on_changes[$ch]['fn']=$field['fn_on_change'];
						$on_changes[$ch]['param2']=array('country'=>$input[$field['dbfield'].'_country'],'state'=>$input[$field['dbfield'].'_state'],'city'=>$input[$field['dbfield'].'_city'],'zip'=>$input[$field['dbfield'].'_zip']);
						$on_changes[$ch]['param3']=$field['dbfield'];
						++$ch;
					}
					break;

				default:
					$input[$field['dbfield']]=sanitize_and_format_gpc($_POST,$field['dbfield'],$__html2type[$field['html_type']],$__html2format[$field['html_type']],'');
					if (isset($field['fn_on_change'])) {
						$on_changes[$ch]['fn']=$field['fn_on_change'];
						$on_changes[$ch]['param2']=$input[$field['dbfield']];
						$on_changes[$ch]['param3']=$field['dbfield'];
						++$ch;
					}

			}
			// check for input errors
			if (isset($field['required']) && ((empty($input[$field['dbfield']]) && $field['html_type']!=_HTML_LOCATION_) || ($field['html_type']==_HTML_LOCATION_ && empty($input[$field['dbfield'].'_country'])))) {
				$error=true;
				$topass['message']['type']=MESSAGE_ERROR;
				$topass['message']['text']='The fields outlined below are required and must not be empty';
				$input['error_'.$field['dbfield']]='red_border';
			}
		}
	}

	if (!$error) {
		$query="SELECT `fk_user_id` FROM `{$dbtable_prefix}user_profiles` WHERE `fk_user_id`='".$input['uid']."'";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		$is_update=false;
		if (mysql_num_rows($res)) {
			$is_update=true;
		}
		if ($is_update) {
			$query="UPDATE `{$dbtable_prefix}user_profiles` SET `last_changed`='".gmdate('YmdHis')."',`status`='".PSTAT_APPROVED."'";
		} else {
			$query="INSERT INTO `{$dbtable_prefix}user_profiles` SET `fk_user_id`='".$_SESSION['user']['user_id']."',`last_changed`='".gmdate('YmdHis')."',`status`='".PSTAT_APPROVED."'";
		}
		foreach ($_pfields as $field_id=>$field) {
			if ($field['editable']) {
				if ($field['html_type']==_HTML_LOCATION_) {
					$query.=",`".$field['dbfield']."_country`='".$input[$field['dbfield'].'_country']."',`".$field['dbfield']."_state`='".$input[$field['dbfield'].'_state']."',`".$field['dbfield']."_city`='".$input[$field['dbfield'].'_city']."',`".$field['dbfield']."_zip`='".$input[$field['dbfield'].'_zip']."'";
				} else {
					if (isset($input[$field['dbfield']])) {
						$query.=',`'.$field['dbfield']."`='".$input[$field['dbfield']]."'";
					}
				}
			}
		}
		if ($is_update) {
			$query.=" WHERE `fk_user_id`='".$input['uid']."'";
		}
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}

		for ($i=0;isset($on_changes[$i]);++$i) {
			if (function_exists($on_changes[$i]['fn'])) {
				eval($on_changes[$i]['fn'].'($input[\'uid\'],$on_changes[$i][\'param2\'],$on_changes[$i][\'param3\']);');
			}
		}

		$topass['message']['type']=MESSAGE_INFO;
		$topass['message']['text']='Member profile has been changed.';
	} else {
		$nextpage=_BASEURL_.'/admin/profile_edit.php';
// 		you must replace '\r' and '\n' strings with <enter> in all textareas like this:
//		$input['x']=preg_replace(array('/([^\\\])\\\n/','/([^\\\])\\\r/'),array("$1\n","$1"),$input['x']);
		$input=sanitize_and_format($input,TYPE_STRING,FORMAT_HTML2TEXT_FULL | FORMAT_STRIPSLASH);
		$topass['input']=$input;
	}
}

if (empty($nextpage)) {
	$nextpage=_BASEURL_.'/admin/member_search.php';
	if (isset($input['return']) && !empty($input['return'])) {
		$nextpage=_BASEURL_.'/admin/'.$input['return'];
	}
}
redirect2page($nextpage,$topass,$qs,true);
?>