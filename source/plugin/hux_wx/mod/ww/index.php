<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
include DISCUZ_ROOT.'./source/plugin/hux_wx/mod/ww/lang/lang.'.currentlang().'.php';
C::t('#hux_wx#hux_wx_action')->insert(array('openid'=>$openid,'action'=>'0','type'=>$keyword));
$string = $wwlang['city'];

if (CHARSET != 'utf-8' && !$wxsetting['code']) {
	$string = diconv($string,CHARSET,'utf-8');
}
$contentStr = $string;
?>