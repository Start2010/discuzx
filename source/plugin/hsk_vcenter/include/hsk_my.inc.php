<?php

if(!defined('IN_DISCUZ') || !defined('IN_HSK')) {
	exit('Access Denied');
}

if($discuz_uid){
	$action = dhtmlspecialchars($_GET['action']);
}else{
	showmessage(lang('plugin/hsk_vcenter', 'nopermission'), '', array(), array('login' => true));
}

$navtitle = lang('plugin/hsk_vcenter', 'mycenter');
$manage_array = array('index', 'ulist', 'plist', 'tvlist', 'pay', 'favorites', 'report', 'cache');
foreach($manage_array as $val){
	$manage_array_str[$val] = lang('plugin/hsk_vcenter', "my_$val");
}

if(!in_array($action, $manage_array)){
	$action = 'index';
}

define('IN_MYCENTER', 1);
$navname = $manage_array_str[$action];
$navtitle = $navname." - $navtitle";
require DISCUZ_ROOT.PINC.'/my/hsk_'.$action.'.inc.php';
exit();

?>