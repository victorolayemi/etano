<?php
/******************************************************************************
Etano
===============================================================================
File:                       tos.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://www.datemill.com/forum
*******************************************************************************
* See the "docs/licenses/etano.txt" file for license.                         *
******************************************************************************/

require 'includes/common.inc.php';
require _BASEPATH_.'/includes/user_functions.inc.php';
require _BASEPATH_.'/skins_site/'.get_my_skin().'/lang/tos.php';

$tpl=new phemplate(_BASEPATH_.'/skins_site/'.get_my_skin().'/','remove_nonjs');

$tpl->set_file('content','tos.html');
$tpl->process('content','content');

$tplvars['title']=$GLOBALS['_lang'][250];
$tplvars['page_title']=$GLOBALS['_lang'][250];
$tplvars['page']='tos';
$tplvars['css']='tos.css';
include 'frame.php';
