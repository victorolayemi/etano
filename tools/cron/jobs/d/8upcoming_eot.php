<?php
$jobs[]='upcoming_eot';

function upcoming_eot() {
	global $dbtable_prefix;
	$config['days_before']=4;
	$query_strlen=20000;

	$now=gmdate('Ymd');
	$query="SELECT b.`email`,c.`_user` as `user` FROM `{$dbtable_prefix}payments` a,`".USER_ACCOUNTS_TABLE."` b,`{$dbtable_prefix}user_profiles` c WHERE a.`fk_user_id`=b.`".USER_ACCOUNT_ID."` AND a.`fk_user_id`=c.`fk_user_id` AND a.`paid_until`='$now'-INTERVAL ".$config['days_before']." DAY AND a.`is_active`=1 AND a.`is_recurring`=0";
	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	$alerts=array();
	while ($rsrow=mysql_fetch_assoc($res)) {
		$alerts[$rsrow['email']]=$rsrow;
	}
	if (!empty($alerts)) {
		$skin=get_default_skin_dir();
		$tpl=new phemplate(_BASEPATH_.'/skins_site/'.$skin.'/emails/','remove_nonjs');
		$tpl->set_file('temp','subscr_expire_alert.html');
		$tpl->set_var('tplvars',$tplvars);
		$subject=sprintf($GLOBALS['_lang'][218],_SITENAME_);
		$subject=sanitize_and_format($subject,TYPE_STRING,$GLOBALS['__field2format'][FIELD_TEXTFIELD]);
		$insert="INSERT INTO `{$dbtable_prefix}queue_email` (`to`,`subject`,`message_body`) VALUES ";
		$iquery=$insert;
		foreach ($alerts as $email=>$v) {
			$tpl->set_var('output',$v);
			$message_body=$tpl->process('','temp',TPL_LOOP | TPL_OPTLOOP | TPL_OPTIONAL | TPL_FINISH);
			$message_body=sanitize_and_format($message_body,TYPE_STRING,$GLOBALS['__field2format'][FIELD_TEXTAREA]);
			if (strlen($iquery)>$query_strlen) {
				$iquery=substr($iquery,0,-1);
				if (!($res=@mysql_query($iquery))) {trigger_error(mysql_error(),E_USER_ERROR);}
				$iquery=$insert;
			}
			$iquery.="('$email','$subject','$message_body'),";
		}
		if ($iquery!=$insert) {
			$iquery=substr($iquery,0,-1);
			if (!($res=@mysql_query($iquery))) {trigger_error(mysql_error(),E_USER_ERROR);}
		}
	}
	return true;
}
