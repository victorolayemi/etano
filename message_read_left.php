<?php
/******************************************************************************
newdsb
===============================================================================
File:                       message_read_left.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://forum.datemill.com
*******************************************************************************
* See the "softwarelicense.txt" file for license.                             *
******************************************************************************/

$tpl->set_file('left_content','message_read_left.html');
$loopfolders=array();
$i=0;
foreach ($my_folders as $k=>$v) {
	$loopfolders[$i]['fid']=$k;
	$loopfolders[$i]['folder']=$v;
	++$i;
}
$tpl->set_loop('loopfolders',$loopfolders);
$tpl->process('left_content','left_content',TPL_LOOP | TPL_OPTIONAL);
$tpl->drop_loop('loopfolders');
?>