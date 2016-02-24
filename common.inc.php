<?php
/*
	[Destoon B2B System] Copyright (c) 2008-2010 Destoon.COM
	This is NOT a freeware, use is subject to license.txt
*/
define('DT_DEBUG', 0);
error_reporting(DT_DEBUG ? E_ALL : 0);
if(isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) exit('Request Denied');
@set_magic_quotes_runtime(0);
$MQG = get_magic_quotes_gpc();
foreach(array('_POST', '_GET') as $R) {
	if($$R) { foreach($$R as $k=>$v) { if(isset($$k) && $$k == $v) unset($$k); } }
}
if(defined('DT_REWRITE')) {
	$pstr = '';
	if($_SERVER['QUERY_STRING']) {
		if(preg_match("/^(.*)\.html$/", $_SERVER['QUERY_STRING'], $_match)) {
			$pstr = $_match[1];
		} else if(preg_match("/^(.*)\/$/", $_SERVER['QUERY_STRING'], $_match)) {
			$pstr = $_match[1];
		}
	} else if($_SERVER["REQUEST_URI"] != $_SERVER["SCRIPT_NAME"]) {
		$string = str_replace($_SERVER["SCRIPT_NAME"], '', $_SERVER["REQUEST_URI"]);
		if($string && preg_match("/^\/(.*)\/$/", $string, $_match)) $pstr = $_match[1];
	}
	if($pstr && strpos($pstr, '-') !== false) {
		$pstr = explode('-', $_match[1]);
		$pstr_count = count($pstr);
		if($pstr_count%2 == 1) --$pstr_count;
		for($i = 0; $i < $pstr_count; $i++) { $_GET[$pstr[$i]] = $MQG ? addslashes($pstr[++$i]) : $pstr[++$i]; }
	}
}
define('IN_DESTOON', true);
define('DT_ROOT', str_replace("\\", '/', dirname(__FILE__)));
require DT_ROOT.'/config.inc.php';
define('DT_PATH', $CFG['absurl'] ? $CFG['url'] : $CFG['path']);
define('DT_DOMAIN', $CFG['cookie_domain'] ? substr($CFG['cookie_domain'], 1) : '');
define('DT_CHMOD', $CFG['file_mod'] ? $CFG['file_mod'] : '');
define('DT_URL', $CFG['url']);
define('DT_LANG', $CFG['language']);
define('DT_KEY', $CFG['authkey']);
define('DT_CHARSET', $CFG['charset']);
define('DT_CACHE', $CFG['cache_dir'] ? $CFG['cache_dir'] : DT_ROOT.'/cache');
define('DT_SKIN', DT_PATH.'skin/'.$CFG['skin'].'/');
define('DT_PHP', '.php');
define('SKIN_PATH', DT_PATH.'skin/'.$CFG['skin'].'/');//For 2.x
define('VIP', $CFG['com_vip']);
define('errmsg', 'Invalid Request');
$L = array();
include DT_ROOT.'/lang/'.DT_LANG.'/lang.inc.php';
require DT_ROOT.'/version.inc.php';
require DT_ROOT.'/include/global.func.php';
require DT_ROOT.'/include/tag.func.php';
require DT_ROOT.'/api/im.func.php';
require DT_ROOT.'/api/extend.func.php';
if(!$MQG && $_POST) $_POST = daddslashes($_POST);
if(!$MQG && $_GET) $_GET = daddslashes($_GET);
$DT_PRE = $CFG['tb_pre'];
$DT_QST = $_SERVER['QUERY_STRING'];
$DT_TIME = time() + $CFG['timediff'];
$DT_IP = get_env('ip');
$DT_URL = get_env('url');
$DT_REF = get_env('referer');
if(function_exists('date_default_timezone_set')) date_default_timezone_set($CFG['timezone']);
header("Content-Type:text/html;charset=".DT_CHARSET);
require DT_ROOT.'/include/db_'.$CFG['database'].'.class.php';
require DT_ROOT.'/include/session.class.php';
require DT_ROOT.'/include/file.func.php';
if(!defined('DT_ADMIN')) {
	if(!empty($_SERVER['REQUEST_URI'])) {
		$uri = urldecode($_SERVER['REQUEST_URI']);
		if(strpos($uri, '<') !== false || strpos($uri, '"') !== false || strpos($uri, '0x') !== false) dalert(errmsg, 'goback');
	}
	if($_POST) $_POST = strip_sql($_POST);
	if($_GET) $_GET = strip_sql($_GET);
	$BANIP = cache_read('banip.php');
	if($BANIP) banip($BANIP);
	$destoon_task = '';
}
if($_POST) extract($_POST, EXTR_SKIP);
if($_GET) extract($_GET, EXTR_SKIP);
$db_class = 'db_'.$CFG['database'];
$db = new $db_class;
$db->halt = DT_DEBUG ? 1 : 0;
$db->pre = $CFG['tb_pre'];
$db->connect($CFG['db_host'], $CFG['db_user'], $CFG['db_pass'], $CFG['db_name'], $CFG['db_expires'], $CFG['db_charset'], $CFG['pconnect']);
$DT = $DEXT = $DCAT = $DTM = $MOD = array();
$CACHE = cache_read('module.php');
if(!$CACHE) {
	require_once DT_ROOT.'/admin/global.func.php';
	require_once DT_ROOT.'/include/cache.func.php';
    cache_all();
	$CACHE = cache_read('module.php');
}
$DT = $CACHE['dt'];
$MODULE = $CACHE['module'];
if(!defined('DT_ADMIN')) {
	if($DT['close']) message($DT['close_reason'].'&nbsp;');
	if($DT['defend_cc'] || $DT['defend_reload'] || $DT['defend_proxy']) include DT_ROOT.'/include/defend.inc.php';
}
unset($CACHE, $CFG['timezone'], $CFG['db_host'], $CFG['db_user'], $CFG['db_pass'], $db_class, $db_file);
if(!isset($moduleid)) {
	$moduleid = 1;
	$module = 'destoon';
} else if($moduleid == 1) {
	$module = 'destoon';
} else {
	$moduleid = intval($moduleid);
	isset($MODULE[$moduleid]) or dheader(DT_PATH);
	$module = $MODULE[$moduleid]['module'];
	$MOD = cache_read('module-'.$moduleid.'.php');
	include DT_ROOT.'/lang/'.DT_LANG.'/'.$module.'.inc.php';
}
($DT['gzip_enable'] && !$_POST && !defined('DT_WAP') && !headers_sent()) ? ob_start('ob_gzhandler') : ob_start();
$forward = isset($forward) ? urldecode($forward) : $DT_REF;
$action = isset($action) ? trim($action) : '';
$submit = isset($_POST['submit']) ? true : false;
if($submit) {
	isset($captcha) or $captcha = '';
	isset($answer) or $answer = '';
}
$page = isset($page) ? max(intval($page), 1) : 1;
$catid = isset($catid) ? intval($catid) : 0;
$itemid = isset($itemid) ? (is_array($itemid) ? $itemid : intval($itemid)) : 0;
$pagesize = $DT['pagesize'] ? $DT['pagesize'] : 30;
$offset = ($page-1)*$pagesize;
$kw = isset($kw) ? htmlspecialchars(str_replace(array("\'"), array(''), trim(urldecode($kw)))) : '';	
$keyword = $kw ? str_replace(array(' ', '*'), array('%', '%'), $kw) : '';
$today_endtime = strtotime(date('Y-m-d', $DT_TIME).' 23:59:59');
$seo_title = $head_title = $head_keywords = $head_description = '';
$head_keywords = $DT['seo_keywords'];
$head_description = $DT['seo_description'];
$_userid = $_message = $_money = $_credit = $_sms = 0;
$_username = $_company = $_passport = '';
$_groupid = 3;
$destoon_auth = get_cookie('auth');
if($destoon_auth) {
	$_dauth = explode("\t", decrypt($destoon_auth, md5(DT_KEY.$_SERVER['HTTP_USER_AGENT'])));
	$_userid = isset($_dauth[0]) ? intval($_dauth[0]) : 0;
	$_username = isset($_dauth[1]) ? trim($_dauth[1]) : '';
	$_groupid = isset($_dauth[2]) ? intval($_dauth[2]) : 3;
	if($_userid && !defined('DT_NONUSER')) {
		$_password = isset($_dauth[3]) ? trim($_dauth[3]) : '';
		$user = $db->get_one("SELECT username,passport,company,truename,password,groupid,email,message,sms,credit,money,loginip,admin,edittime FROM {$db->pre}member WHERE userid=$_userid");
		if($user && $user['password'] == $_password) {
			if($user['groupid'] == 2) dalert(lang('message->common_forbidden'));
			extract($user, EXTR_PREFIX_ALL, '');
			if($user['loginip'] != $DT_IP && ($DT['ip_login'] == 2 || ($DT['ip_login'] == 1 && defined('DT_ADMIN')))) {
				$_userid = 0; set_cookie('auth', '');
				dalert(lang('message->common_login', array($user['loginip'])), DT_PATH);
			}
		} else {
			$_userid = 0; set_cookie('auth', '');
		}
		unset($destoon_auth, $user, $_dauth, $_password);
	}
}
if($_userid == 0) $_groupid = 3;
if($_groupid == 1 && !defined('DT_ADMIN')) include DT_ROOT.'/module/member/admin.inc.php';
$MG = cache_read('group-'.$_groupid.'.php');
?>