<?php
require_once '../../includes/common.inc.php';
db_connect(_DBHOST_,_DBUSER_,_DBPASS_,_DBNAME_);
require_once '../../includes/classes/phemplate.class.php';
require_once '../../includes/user_functions.inc.php';
require_once '../../includes/classes/fileop.class.php';

$short_blog_chars=400;
$config=get_site_option(array('bbcode_blogs','use_smilies'),'core_blog');

$fileop=new fileop();

$query="SELECT a.`post_id`,UNIX_TIMESTAMP(a.`date_posted`) as `date_posted`,a.`fk_user_id`,a.`_user` as `user`,a.`fk_blog_id`,a.`title`,a.`post_content`,b.`_photo` as `photo`,c.`blog_name` FROM `{$dbtable_prefix}blog_posts` a,`{$dbtable_prefix}user_profiles` b,`{$dbtable_prefix}user_blogs` c WHERE a.`fk_user_id`=b.`fk_user_id` AND a.`fk_blog_id`=c.`blog_id` AND a.`status`=".STAT_APPROVED;
//$query="SELECT a.`post_id`,UNIX_TIMESTAMP(a.`date_posted`) as `date_posted`,a.`fk_user_id`,a.`_user` as `user`,a.`fk_blog_id`,a.`title`,a.`post_content`,b.`_photo` as `photo` FROM `{$dbtable_prefix}blog_posts` a,`{$dbtable_prefix}user_profiles` b WHERE a.`fk_user_id`=b.`fk_user_id` AND a.`status`=".STAT_APPROVED." AND a.`last_changed`>=DATE_SUB('".gmdate('YmdHis')."',INTERVAL 12 MINUTE)";
if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
while ($blog=mysql_fetch_assoc($res)) {
	$blog['title']=sanitize_and_format($blog['title'],TYPE_STRING,$__field2format[TEXT_DB2EDIT]);
	$post_content_short=substr($blog['post_content'],0,strrpos(substr($blog['post_content'],0,$short_blog_chars),' '));
	$post_content_short=sanitize_and_format($post_content_short,TYPE_STRING,$__field2format[TEXT_DB2DISPLAY]);
	$blog['post_content']=sanitize_and_format($blog['post_content'],TYPE_STRING,$__field2format[TEXT_DB2DISPLAY]);
	if (!empty($config['bbcode_blogs'])) {
		$blog['post_content']=bbcode2html($blog['post_content']);
		$post_content_short=bbcode2html($post_content_short);
	}
	if (!empty($config['use_smilies'])) {
		$blog['post_content']=text2smilies($blog['post_content']);
		$post_content_short=text2smilies($post_content_short);
	}
	if (empty($blog['photo']) || !is_file(_PHOTOPATH_.'/t1/'.$blog['photo'])) {
		$blog['photo']='no_photo.gif';
	} else {
		$blog['has_photo']=true;
	}
	if (empty($blog['fk_user_id'])) {
		unset($blog['fk_user_id']);
	}

	$towrite='<?php $post='.var_export($blog,true).';';
	$fileop->file_put_contents(_CACHEPATH_.'/blogs/posts/'.$blog['post_id']{0}.'/'.$blog['post_id'].'.inc.php',$towrite);

	$blog['post_content']=$post_content_short;
	$towrite='<?php $post='.var_export($blog,true).';';
	$fileop->file_put_contents(_CACHEPATH_.'/blogs/posts/'.$blog['post_id']{0}.'/'.$blog['post_id'].'_short.inc.php',$towrite);
}
