<?php
/******************************************************************************
Etano
===============================================================================
File:                       includes/logs.inc.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
*******************************************************************************/

define('_PUNISH_ERROR_',1);
define('_PUNISH_BANUSER_',2);
define('_PUNISH_BANIP_',3);
define('_PUNISH_UPGRADE_',4);
define('_PUNISH_BANEMAIL_',5);
$accepted_punishments=array(_PUNISH_ERROR_=>'Sorry page',_PUNISH_UPGRADE_=>'Membership Upgrade Options',_PUNISH_BANUSER_=>'Ban user',_PUNISH_BANIP_=>'Ban IP',_PUNISH_BANEMAIL_=>'Ban email');

function log_user_action(&$log) {
	global $dbtable_prefix;
	$query="INSERT INTO `{$dbtable_prefix}site_log` SET `fk_user_id`='".$log['user_id']."',`user`='".$log['user']."',`m_value`='".$log['membership']."',`level_code`='".$log['level']."',`ip`='".$log['ip']."',`time`='".gmdate('YmdHis')."',`sess`='".$log['sess']."'";
	@mysql_query($query);
}


function rate_limiter(&$log) {
	$myreturn=false;
	global $dbtable_prefix;
	$where='';
	if (!empty($log['user_id'])) {
		$where=" AND `fk_user_id`='".$log['user_id']."'";
	} else {
		$where=" AND `ip`='".$log['ip']."' AND `sess`='".$log['sess']."'";
	}
	$query="SELECT `limit`,`interval`,`punishment`,`fk_lk_id_error_message` FROM `{$dbtable_prefix}rate_limiter` WHERE `level_code`='".$log['level']."' AND `m_value`='".$log['membership']."'";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	$punish=array();
	while ($rsrow=mysql_fetch_assoc($res)) {
		$query="SELECT count(*) FROM `{$dbtable_prefix}site_log` WHERE `level_code`='".$log['level']."' AND `time`>=DATE_SUB('".gmdate('YmdHis')."',INTERVAL ".$rsrow['interval']." MINUTE) $where";
		if (!($res2=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		if (mysql_result($res2,0,0)>=$rsrow['limit']) {
			$punish[$rsrow['punishment']]=$rsrow['fk_lk_id_error_message'];
		}
	}

	if (isset($punish[_PUNISH_BANIP_])) {
		$query="INSERT IGNORE INTO `{$dbtable_prefix}site_bans` SET `ban_type`="._PUNISH_BANIP_.",`what`='".$log['ip']."',`reason`='".$punish[_PUNISH_BANIP_]."'";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		regenerate_ban_array();
	}
	if (isset($punish[_PUNISH_BANUSER_])) {
		$query="INSERT IGNORE INTO `{$dbtable_prefix}site_bans` SET `ban_type`="._PUNISH_BANUSER_.",`what`='".$log['user']."',`reason`='".$punish[_PUNISH_BANUSER_]."'";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		regenerate_ban_array();
	}
	if (isset($punish[_PUNISH_BANEMAIL_])) {
		$query="INSERT IGNORE INTO `{$dbtable_prefix}site_bans` SET `ban_type`="._PUNISH_BANEMAIL_.",`what`='".$log['email']."',`reason`='".$punish[_PUNISH_BANEMAIL_]."'";
		if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
		regenerate_ban_array();
	}
	if (isset($punish[_PUNISH_ERROR_])) {
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']=isset($GLOBALS['_lang'][$punish[_PUNISH_ERROR_]]) ? $GLOBALS['_lang'][$punish[_PUNISH_ERROR_]] : '';
		redirect2page('info.php',$topass);
	} elseif (isset($punish[_PUNISH_UPGRADE_])) {
		$topass['message']['type']=MESSAGE_ERROR;
		$topass['message']['text']=isset($GLOBALS['_lang'][$punish[_PUNISH_UPGRADE_]]) ? $GLOBALS['_lang'][$punish[_PUNISH_UPGRADE_]] : '';
		redirect2page(_BASEURL_.'/info.php?type=access',$topass,'',true);
	}
	return $myreturn;
}


function regenerate_ban_array() {
	require_once _BASEPATH_.'/includes/classes/fileop.class.php';
	global $dbtable_prefix;
	$query="SELECT `ban_type`,`what` FROM `{$dbtable_prefix}site_bans` GROUP BY `what`";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	$_bans=array();
	while ($rsrow=mysql_fetch_row($res)) {
		$_bans[$rsrow[0]][]=$rsrow[1];
	}
	$towrite="<?php\n";
	if (!empty($_bans[_PUNISH_BANIP_])) {
		$towrite.='$_bans[_PUNISH_BANIP_]=array(\''.join("','",$_bans[_PUNISH_BANIP_])."');\n";
	}
	if (!empty($_bans[_PUNISH_BANUSER_])) {
		$towrite.='$_bans[_PUNISH_BANUSER_]=array(\''.join("','",$_bans[_PUNISH_BANUSER_])."');\n";
	}
	if (!empty($_bans[_PUNISH_BANEMAIL_])) {
		$towrite.='$_bans[_PUNISH_BANEMAIL_]=array(\''.join("','",$_bans[_PUNISH_BANEMAIL_])."');\n";
	}
	$fileop=new fileop();
	$fileop->file_put_contents(_BASEPATH_.'/includes/site_bans.inc.php',$towrite);
}
