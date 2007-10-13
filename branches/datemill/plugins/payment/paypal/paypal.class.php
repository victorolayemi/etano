<?php
/******************************************************************************
Etano
===============================================================================
File:                       plugins/payment/paypal/paypal.class.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require_once _BASEPATH_.'/includes/interfaces/ipayment.class.php';

class payment_paypal extends ipayment {
	var $paypal_server='www.paypal.com';
	var $module_code='paypal';
	var $from_paypal=array('defaults'=>array(	'residence_country'=>'',
												'first_name'=>'',
												'last_name'=>'',
												'business'=>'',
												'receiver_email'=>'',
												'payer_email'=>'',
												'txn_id'=>'',
												'txn_type'=>'',
												'payment_status'=>'',
												'mc_gross'=>'',
												'mc_currency'=>'',
												'verify_sign'=>'',
												'test_ipn'=>0,
												'recurring'=>0,
												'item_number'=>0,
												'custom'=>0
											),
							'types'=>	array(	'residence_country'=>FIELD_TEXTFIELD,
												'first_name'=>FIELD_TEXTFIELD,
												'last_name'=>FIELD_TEXTFIELD,
												'business'=>FIELD_TEXTFIELD,
												'receiver_email'=>FIELD_TEXTFIELD,
												'payer_email'=>FIELD_TEXTFIELD,
												'txn_id'=>FIELD_TEXTFIELD,
												'txn_type'=>FIELD_TEXTFIELD,
												'payment_status'=>FIELD_TEXTFIELD,
												'mc_gross'=>FIELD_FLOAT,
												'mc_currency'=>FIELD_TEXTFIELD,
												'verify_sign'=>FIELD_TEXTFIELD,
												'test_ipn'=>FIELD_INT,
												'recurring'=>FIELD_INT,
												'item_number'=>FIELD_INT,
												'custom'=>FIELD_INT
											));

	function payment_paypal() {
		$this->ipayment();
		$this->_init();
	}


	function get_buy_button($payment=array()) {
		$this->_set_payment($payment);
		$myreturn='<form action="https://'.$this->form_page.'/cgi-bin/webscr" method="post" id="payment_paypal">
		<input type="hidden" name="cmd" value="_xclick-subscriptions" />
		<input type="hidden" name="business" value="'.$this->config['paypal_email'].'" />
		<input type="hidden" name="return" value="'._BASEURL_.'/thankyou.php?p=paypal" />
		<input type="hidden" name="notify_url" value="'._BASEURL_.'/processors/ipn.php?p=paypal" />
		<input type="hidden" name="cancel_return" value="'._BASEURL_.'" />
		<input type="hidden" name="item_name" value="'.$this->payment['subscr_name'].'" />
		<input type="hidden" name="item_number" value="'.$this->payment['subscr_id'].'" />
		<input type="hidden" name="custom" value="'.$this->payment['user_id'].'" />
		<input type="hidden" name="quantity" value="1" />
		<input type="hidden" name="no_shipping" value="1" />
		<input type="hidden" name="no_note" value="1" />
		<input type="hidden" name="rm" value="2" />
		<input type="hidden" name="currency_code" value="'.$this->payment['currency'].'" />
		<input type="hidden" name="p3" value="'.$this->payment['duration'].'" />
		<input type="hidden" name="t3" value="DAY" />
		<input type="hidden" name="a3" value="'.$this->payment['price'].'" />
		<input type="hidden" name="sra" value="1" />';
		if ($this->payment['is_recurent']==1) {
			$myreturn.='<input type="hidden" name="src" value="1" />';
		}
		$myreturn.='<input type="submit" class="button" value="Buy with PayPal" />
		</form>';
		return $myreturn;
	}


	function redirect2gateway($payment=array()) {
		$this->_set_payment($payment);
		$topass=array(	'cmd'=>'_xclick-subscriptions',
						'business'=>$this->config['paypal_email'],
						'return'=>_BASEURL_.'/thankyou.php?p='.$this->module_code,
						'notify_url'=>_BASEURL_.'/processors/ipn.php?p='.$this->module_code,
						'cancel_return'=>_BASEURL_,
						'item_name'=>$this->payment['subscr_name'],
						'item_number'=>$this->payment['subscr_id'],
						'custom'=>$this->payment['user_id'],
						'quantity'=>1,
						'no_shipping'=>1,
						'no_note'=>1,
						'rm'=>2,
						'currency_code'=>$this->payment['currency'],
						'p3'=>$this->payment['duration'],
						't3'=>'DAY',
						'a3'=>$this->payment['price'],
						'sra'=>1);
		if ($this->payment['is_recurent']==1) {
			$topass['src']=1;
		}
		post2page('https://'.$this->paypal_server.'/cgi-bin/webscr',$topass,true);
	}


	function thankyou(&$tpl) {
		$myreturn='';
		$tpl->set_file('gateway_text','thankyou_paypal.html');
		return $myreturn;
	}


	function ipn() {
/*
		ob_start();
		print_r($_REQUEST);
		$debug_text=ob_get_contents();
		ob_end_clean();
		$fp=fopen('/tmp/ipn.txt','ab');
		fwrite($fp,$debug_text."\n-------\n\n");
*/
		header('Status: 200 OK');
		$myreturn=false;
		$gateway_text='';
		global $dbtable_prefix;
		$input=array();
		foreach ($this->from_paypal['types'] as $k=>$v) {
			$input[$k]=sanitize_and_format_gpc($_POST,$k,$GLOBALS['__field2type'][$v],$GLOBALS['__field2format'][$v],$this->from_paypal['defaults'][$k]);
		}

		$postipn='cmd=_notify-validate&'.array2qs($_POST,array('p'));
		$header="POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header.='Host: '.$this->paypal_server."\r\n";
		$header.="Content-Type: application/x-www-form-urlencoded\r\n";
		$header.='Content-Length: '.strlen($postipn)."\r\n";
		$header.="Connection: close\r\n\r\n";
		$socket=fsockopen($this->paypal_server,80,$errno,$errstr,30);
		if ($socket) {
			fputs($socket,$header.$postipn."\r\n\r\n");
			$reply='';
			$headerdone=false;
			while(!feof($socket)) {
				$line=fgets($socket);
				if (strcmp($line,"\r\n")==0) {
					// read the header
					$headerdone=true;
				} elseif ($headerdone) {
					// header has been read. now read the contents
					$reply.=$line;
				}
			}
			fclose ($socket);
			$reply=trim($reply);
			if (strcasecmp($reply,'VERIFIED')==0 || strcasecmp($reply,'VERIFIED')!=0) {
				if (strcasecmp($input['business'],$this->config['paypal_email'])==0 || strcasecmp($input['receiver_email'],$this->config['paypal_email'])==0) {
					$query="SELECT `".USER_ACCOUNT_ID."` as `user_id`,`".USER_ACCOUNT_USER."` as `user` FROM `".USER_ACCOUNTS_TABLE."` WHERE `".USER_ACCOUNT_ID."`=".$input['custom'];
					if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
					if (mysql_num_rows($res)) {
						$real_user=mysql_fetch_assoc($res);
						if (strcasecmp($input['txn_type'],'web_accept')==0 || strcasecmp($input['txn_type'],'send_money')==0 || strcasecmp($input['txn_type'],'subscr_payment')==0) {
							if (strcasecmp($input['payment_status'],'Completed')==0) {
								$query="SELECT `subscr_id`,`price`,`m_value_to`,`duration` FROM `{$dbtable_prefix}subscriptions` WHERE `subscr_id`=".$input['item_number']." AND `is_visible`=1";
								if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
								if (mysql_num_rows($res)) {
									$real_subscr=mysql_fetch_assoc($res);
									if (number_format($real_subscr['price'],2)==number_format($input['mc_gross'],2)) {
										if ($input['test_ipn']!=1 || ($this->config['demo_mode']==1 && $input['test_ipn']==1)) {
											require_once(_BASEPATH_.'/includes/iso3166.inc.php');
											if (isset($iso3166[$input['residence_country']])) {
												$input['country']=$iso3166[$input['residence_country']];
											}
											$this->check_fraud($input);
											if (!empty($real_subscr['duration'])) {
												// if the old subscription is not over yet, we need to extend the new one with some days
												$query="SELECT a.`payment_id`,UNIX_TIMESTAMP(a.`paid_until`) as `paid_until`,b.`price`,b.`duration` FROM `{$dbtable_prefix}payments` a LEFT JOIN `{$dbtable_prefix}subscriptions` b ON a.`fk_subscr_id`=b.`subscr_id` WHERE a.`fk_user_id`=".$real_user['user_id']." AND a.`refunded`=0 AND a.`is_active`=1 AND a.`is_subscr`=1 AND a.`m_value_to`>2 ORDER BY a.`paid_until` DESC LIMIT 1";
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
												if (mysql_num_rows($res)) {
													$rsrow=mysql_fetch_assoc($res);
													if ((int)$rsrow['paid_until']>(int)time()) {
														$remaining_days=((int)$rsrow['paid_until']-(int)time())/86400;  //86400 seconds in a day
														if ($remaining_days>0) {
															$remaining_value=(((int)$rsrow['price'])/((int)$rsrow['duration']))*$remaining_days;
															$day_value_new=((int)$real_subscr['price'])/((int)$real_subscr['duration']);
															$days_append=round($remaining_value/$day_value_new);
															$real_subscr['duration']=(int)$real_subscr['duration'];
															$real_subscr['duration']+=$days_append;
														}
													}
												}
											}
											// all old active subscriptions end now!
											$query="UPDATE `{$dbtable_prefix}payments` SET `paid_until`=CURDATE(),`is_active`=0 WHERE `fk_user_id`=".$real_user['user_id']." AND `is_active`=1 AND `is_subscr`=1";
											if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
											// insert the new subscription
											$query="INSERT INTO `{$dbtable_prefix}payments` SET `is_active`=1,`fk_user_id`=".$real_user['user_id'].",`_user`='".$real_user['user']."',`gateway`='paypal',`is_subscr`=1,`fk_subscr_id`=".$real_subscr['subscr_id'].",`gw_txn`='".$input['txn_id']."',`name`='".$input['first_name'].' '.$input['last_name']."',`country`='".$input['country']."',`email`='".$input['payer_email']."',`m_value_to`=".$real_subscr['m_value_to'].",`amount_paid`='".$input['mc_gross']."',`is_suspect`=".(int)$this->is_fraud.",`suspect_reason`='".$this->fraud_reason."',`paid_from`=CURDATE(),`date`=now()";
											if (!empty($real_subscr['duration'])) {
												$query.=",`paid_until`=CURDATE()+INTERVAL ".$real_subscr['duration'].' DAY';
											}
											if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
											if (!$this->is_fraud) {
												$query="UPDATE `".USER_ACCOUNTS_TABLE."` SET `membership`=".$real_subscr['m_value_to']." WHERE `".USER_ACCOUNT_ID."`=".$real_user['user_id'];
												if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
												$myreturn=true;
												require_once _BASEPATH_.'/includes/general_functions.inc.php';
												add_member_score($real_user['user_id'],'payment');
											}
										} else {
											// a demo transaction when we're not in demo mode
											require_once _BASEPATH_.'/includes/classes/log_error.class.php';
											new log_error(array('module_name'=>get_class($this),'text'=>'Demo transaction when demo is not enabled: '.array2qs($_POST)));
										}
									} else {
										// paid price doesn't match the subscription price
										require_once _BASEPATH_.'/includes/classes/log_error.class.php';
										new log_error(array('module_name'=>get_class($this),'text'=>'Invalid amount paid: '.array2qs($_POST)));
									}
								} else {
									// if the subscr_id was not found
									require_once _BASEPATH_.'/includes/classes/log_error.class.php';
									new log_error(array('module_name'=>get_class($this),'text'=>'Invalid subscr_id received after payment: '.array2qs($_POST)));
								}
							} else {
								require_once _BASEPATH_.'/includes/classes/log_error.class.php';
								new log_error(array('module_name'=>get_class($this),'text'=>'Payment status not Completed: '.$input['payment_status']."\n".array2qs($_POST)));
							}
						} elseif (strcasecmp($input['txn_type'],'subscr_eot')==0) {
							$query="SELECT `payment_id` FROM `{$dbtable_prefix}payments` WHERE `fk_user_id`=".$real_user['user_id']." AND `fk_subscr_id`=".$input['item_number']." ORDER BY `payment_id` DESC LIMIT 1";
							if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
							if (mysql_num_rows($res)) {
								$payment_id=mysql_result($res,0,0);
								$query="UPDATE `{$dbtable_prefix}payments` SET `paid_until`=CURDATE() WHERE `payment_id`=$payment_id";
								if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
							} else {
								// invalid eot.
							}
						} else {
							// unhandled txn_type
							require_once _BASEPATH_.'/includes/classes/log_error.class.php';
							new log_error(array('module_name'=>get_class($this),'text'=>'Unhandled txn_type (probably not an error): '.$input['txn_type']."\n".array2qs($_POST)));
						}
					} else {
						// if the user_id was not found
						require_once _BASEPATH_.'/includes/classes/log_error.class.php';
						new log_error(array('module_name'=>get_class($this),'text'=>'Invalid user_id received after payment: '.array2qs($_POST)));
					}
				} else {
					require_once _BASEPATH_.'/includes/classes/log_error.class.php';
					new log_error(array('module_name'=>get_class($this),'text'=>'Payment was not made into our account: '.array2qs($_POST)));
				}
			} elseif (strcasecmp($reply,'INVALID')==0) {
				require_once _BASEPATH_.'/includes/classes/log_error.class.php';
				new log_error(array('module_name'=>get_class($this),'text'=>'Transaction verification with paypal server failed as invalid: '.array2qs($_POST)));
			} else {
				require_once _BASEPATH_.'/includes/classes/log_error.class.php';
				new log_error(array('module_name'=>get_class($this),'text'=>'Transaction verification with paypal server failed with unknown code: '.$reply.' '.array2qs($_POST)));
			}
		} else {
			// socket problem
			require_once _BASEPATH_.'/includes/classes/log_error.class.php';
			new log_error(array('module_name'=>get_class($this),'text'=>'Connection to paypal server failed. '.array2qs($_POST)));
		}
//fclose($fp);
	}


	function _init() {
		$this->config=get_site_option(array(),$this->module_code);
		if ($this->config['demo_mode']==1) {
			$this->paypal_server='www.sandbox.paypal.com';
//			$this->paypal_server='www.eliteweaver.co.uk';
		}
	}
}