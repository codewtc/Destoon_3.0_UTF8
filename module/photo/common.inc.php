<?php 
defined('IN_DESTOON') or exit('Access Denied');
define('MD_ROOT', DT_ROOT.'/module/'.$module);
require DT_ROOT.'/include/module.func.php';
require MD_ROOT.'/global.func.php';
$CATEGORY = cache_read('category-'.$moduleid.'.php');
$ITEMS = cache_read('cateitem-'.$moduleid.'.php');
if($MOD['seo_keywords']) $head_keywords = $MOD['seo_keywords'];
if($MOD['seo_description']) $head_description = $MOD['seo_description'];
$table = $DT_PRE.$module;
$table_data = $DT_PRE.$module.'_data';
$table_item = $DT_PRE.$module.'_item';
?>