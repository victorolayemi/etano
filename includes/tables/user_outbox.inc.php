<?php
/******************************************************************************
newdsb
===============================================================================
File:                       includes/tables/user_outbox.inc.php
$Revision$
Software by:                DateMill (http://www.datemill.com)
Copyright by:               DateMill (http://www.datemill.com)
Support at:                 http://forum.datemill.com
*******************************************************************************
* See the "softwarelicense.txt" file for license.                             *
******************************************************************************/

$user_outbox_default['defaults']=array('mail_id'=>0,'fk_user_id'=>0,'fk_user_id_to'=>0,'_user_to'=>'','subject'=>'','message_body'=>'','date_sent'=>'','message_type'=>0);
$user_outbox_default['types']=array('mail_id'=>_HTML_INT_,'fk_user_id'=>_HTML_INT_,'fk_user_id_to'=>_HTML_INT_,'_user_to'=>_HTML_TEXTFIELD_,'subject'=>_HTML_TEXTFIELD_,'message_body'=>_HTML_TEXTAREA_,'date_sent'=>_HTML_TEXTFIELD_,'message_type'=>_HTML_INT_);