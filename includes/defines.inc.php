<?php
define('_DBHOST_','localhost');// mysql server host name
define('_DBUSER_','datemill_new');// mysql database username
define('_DBPASS_','gicamica12');// mysql database password
define('_DBNAME_','datemill');// mysql database name
define('_SITENAME_','Datemill');// Your site name
define('_BASEURL_','http://www.datemill.com');// protocol required (http:// )
define('_BASEPATH_','/var/www/htdocs/datemill/html');// path on server to your
define('_PHOTOURL_',_BASEURL_.'/media/pics');// protocol required (http:// ). U
define('_PHOTOPATH_',_BASEPATH_.'/media/pics');// path on server to your member
define('_CACHEPATH_',_BASEPATH_.'/cache');// path on server to the cache folder
define('_FILEOP_MODE_','ftp');
define('_FTPHOST_','localhost');
define('_FTPPATH_','/html/');
define('_FTPUSER_','datemill');
define('_FTPPASS_','terebentina');
$dbtable_prefix='dsb_';
define('USER_ACCOUNTS_TABLE',"{$dbtable_prefix}user_accounts");
define('USER_ACCOUNT_ID','user_id');
define('USER_ACCOUNT_USER','user');
define('USER_ACCOUNT_PASS','pass');
define('PASSWORD_ENC_FUNC','md5');

define('_LICENSE_KEY_','1DCC8F32B28484352B8A34'); // md5()=
define('_INTERNAL_VERSION_','1.00');

$accepted_results_per_page=array(10=>10,5=>5,15=>15,20=>20);
$accepted_images=array('jpg','jpeg','png');
