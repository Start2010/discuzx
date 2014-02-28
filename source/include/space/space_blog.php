<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_blog.php 32130 2012-11-14 09:20:40Z zhengqingpeng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$minhot = $_G['setting']['feedhotmin']<1?3:$_G['setting']['feedhotmin'];
$page = empty($_GET['page'])?1:intval($_GET['page']);
if($page<1) $page=1;
$id = empty($_GET['id'])?0:intval($_GET['id']);
$_G['colorarray'] = array('', '#EE1B2E', '#EE5023', '#996600', '#3C9D40', '#2897C5', '#2B65B7', '#8F2A90', '#EC1282');

if($id) {
	$blog = array_merge(
		C::t('home_blog')->fetch($id),
		C::t('home_blogfield')->fetch($id)
	);
	if($blog['uid'] != $space['uid']) {
		$blog = null;
	}
	//日志不存在
	if(!(!empty($blog) && ($blog['status'] == 0 || $blog['uid'] == $_G['uid'] || $_G['adminid'] == 1 || $_GET['modblogkey'] == modauthkey($blog['blogid'])))) {
		showmessage('view_to_info_did_not_exist');
	}
	//检查好友权限
	if(!ckfriend($blog['uid'], $blog['friend'], $blog['target_ids'])) {
		//没有权限
		require_once libfile('function/friend');
		$isfriend = friend_check($blog['uid']);
		space_merge($space, 'count');
		space_merge($space, 'profile');
		$_G['privacy'] = 1;
		require_once libfile('space/profile', 'include');
		include template('home/space_privacy');
		exit();
	} elseif(!$space['self'] && $blog['friend'] == 4 && $_G['adminid'] != 1) {
		//密码输入问题
		$cookiename = "view_pwd_blog_$blog[blogid]";
		$cookievalue = empty($_G['cookie'][$cookiename])?'':$_G['cookie'][$cookiename];
		if($cookievalue != md5(md5($blog['password']))) {
			$invalue = $blog;
			include template('home/misc_inputpwd');
			exit();
		}
	}

	if(!empty($_G['setting']['antitheft']['allow']) && empty($_G['setting']['antitheft']['disable']['blog'])) {
		helper_antitheft::check($id, 'bid');
	}

	$classarr = C::t('home_class')->fetch($blog['classid']);

	if($blog['catid']) {
		$blog['catname'] = C::t('home_blog_category')->fetch_catname_by_catid($blog['catid']);
		$blog['catname'] = dhtmlspecialchars($blog['catname']);
	}

	//处理视频标签
	require_once libfile('function/blog');
	$blog['message'] = blog_bbcode($blog['message']);

	$otherlist = $newlist = array();

	//作者的其他最新日志
	$otherlist = array();
	$query = C::t('home_blog')->fetch_all_by_uid($space['uid'], 'dateline', 0, 6);
	foreach($query as $value) {
		if($value['blogid'] != $blog['blogid'] && empty($value['friend']) && $blog['status'] == 0) {
			$otherlist[] = $value;
		}
	}

	//最新的日志
	$newlist = array();
	$query = C::t('home_blog')->fetch_all_by_hot($minhot, 'dateline', 0, 6);
	foreach($query as $value) {
		if($value['blogid'] != $blog['blogid'] && empty($value['friend']) && $blog['status'] == 0) {
			$newlist[] = $value;
		}
	}

	//评论
	$perpage = 20;
	$perpage = mob_perpage($perpage);

	$start = ($page-1)*$perpage;

	//检查开始数
	ckstart($start, $perpage);

	$count = $blog['replynum'];

	$list = array();
	if($count) {
		if($_GET['goto']) {
			$page = ceil($count/$perpage);
			$start = ($page-1)*$perpage;
		} else {
			$cid = empty($_GET['cid'])?0:intval($_GET['cid']);
		}

		$query = C::t('home_comment')->fetch_all_by_id_idtype($id, 'blogid', $start, $perpage, $cid);
		foreach($query as $value) {
			$list[] = $value;
		}

		if(empty($list) && empty($cid)) {
			$count = C::t('home_comment')->count_by_id_idtype($id, 'blogid');
			C::t('home_blog')->update($blog['blogid'], array('replynum'=>$count));
		}
	}

	//分页
	$multi = multi($count, $perpage, $page, "home.php?mod=space&uid=$blog[uid]&do=$do&id=$id#comment");

	//访问统计, 简单的防刷新
	if(!$_G['setting']['preventrefresh'] || !$space['self'] && $_G['cookie']['viewid'] != 'blog_'.$blog['blogid']) {
		C::t('home_blog')->increase($blog['blogid'], 0, array('viewnum' => 1));
		dsetcookie('viewid', 'blog_'.$blog['blogid']);
	}

	//表态
	$hash = md5($blog['uid']."\t".$blog['dateline']);
	$id = $blog['blogid'];
	$idtype = 'blogid';

	$maxclicknum = 0;
	loadcache('click');
	//表态分类
	$clicks = empty($_G['cache']['click']['blogid'])?array():$_G['cache']['click']['blogid'];

	foreach ($clicks as $key => $value) {
		$value['clicknum'] = $blog["click{$key}"];
		$value['classid'] = mt_rand(1, 4);
		if($value['clicknum'] > $maxclicknum) $maxclicknum = $value['clicknum'];
		$clicks[$key] = $value;
	}

	//点评
	$clickuserlist = array();
	foreach(C::t('home_clickuser')->fetch_all_by_id_idtype($id, $idtype, 0, 24) as $value) {
		$value['clickname'] = $clicks[$value['clickid']]['name'];
		$clickuserlist[] = $value;
	}
	$actives = array('me' =>' class="a"');

	$diymode = intval($_G['cookie']['home_diymode']);

    //标签显示处理
	$tagarray_all = $array_temp = $blogtag_array = $blogmetatag_array = array();
	$blogmeta_tag = '';
	$tagarray_all = explode("\t", $blog['tag']);
	if($tagarray_all) {
		foreach($tagarray_all as $var) {
			if($var) {
				$array_temp = explode(',', $var);
				$blogtag_array[] = $array_temp;
				$blogmetatag_array[] = $array_temp['1'];
			}
		}
	}
	$blog['tag'] = $blogtag_array;
	$blogmeta_tag = implode(',', $blogmetatag_array);

	$summary = cutstr(strip_tags($blog['message']), 140);
	$seodata = array('subject' => $blog['subject'], 'user' => $blog['username'], 'summary' => $summary, 'tags' => $blogmeta_tag);
	list($navtitle, $metadescription, $metakeywords) = get_seosetting('blog', $seodata);
	if(empty($navtitle)) {
		$navtitle = $blog['subject'].' - '.lang('space', 'sb_blog', array('who' => $blog['username']));
		$nobbname = false;
	} else {
		$nobbname = true;
	}
	if(empty($metakeywords)) {
		$metakeywords = $blogmeta_tag ? $blogmeta_tag : $blog['subject'];
	}
	if(empty($metadescription)) {
		$metadescription = $summary;
	}
	if(!$_G['setting']['relatedlinkstatus']) {
		$_G['relatedlinks'] = get_related_link('blog');
	} else {
		$blog['message'] = parse_related_link($blog['message'], 'blog');
	}

	include_once template("diy:home/space_blog_view");

} else {

	//系统分类
	loadcache('blogcategory');
	$category = $_G['cache']['blogcategory'];

	//默认显示
	if(empty($_GET['view'])) $_GET['view'] = 'we';

	//分页
	$perpage = 10;
	$perpage = mob_perpage($perpage);
	$start = ($page-1)*$perpage;
	ckstart($start, $perpage);

	//摘要截取
	$summarylen = 300;

	$classarr = array();
	$list = array();
	$userlist = array();
	$stickblogs = array();
	$count = $pricount = 0;

	$gets = array(
		'mod' => 'space',
		'uid' => $space['uid'],
		'do' => 'blog',
		'view' => $_GET['view'],
		'order' => $_GET['order'],
		'classid' => $_GET['classid'],
		'catid' => $_GET['catid'],
		'clickid' => $_GET['clickid'],
		'fuid' => $_GET['fuid'],
		'searchkey' => $_GET['searchkey'],
		'from' => $_GET['from'],
		'friend' => $_GET['friend']
	);
	$theurl = 'home.php?'.url_implode($gets);
	$multi = '';

	$f_index = $searchsubject = '';
	$uids = array();
	$need_count = true;
	$status = null;
	if($_GET['view'] == 'all') {
		if($_GET['order'] == 'hot') {
			$gthot = $minhot;

			$orderactives = array('hot' => ' class="a"');
		} else {
			$orderactives = array('dateline' => ' class="a"');
		}

		$status = 0;
	} elseif($_GET['view'] == 'me') {

		space_merge($space, 'field_home');
		$stickblogs = explode(',', $space['stickblogs']);
		$stickblogs = array_filter($stickblogs);
		$uids[] = $space['uid'];

		//分类
		$classid = empty($_GET['classid'])?0:intval($_GET['classid']);

		$privacyfriend = empty($_GET['friend'])?0:intval($_GET['friend']);
		$query = C::t('home_class')->fetch_all_by_uid($space['uid']);
		foreach($query as $value) {
			$classarr[$value['classid']] = $value['classname'];
		}

		//自定义识别
		if($_GET['from'] == 'space') $diymode = 1;
		$status = array(0, 1);
	} else {

		space_merge($space, 'field_home');

		if($space['feedfriend']) {

			$fuid_actives = array();

			//查看指定好友的
			require_once libfile('function/friend');
			$fuid = intval($_GET['fuid']);
			if($fuid && friend_check($fuid, $space['uid'])) {
				$uids[] = $fuid;
				$fuid_actives = array($fuid=>' selected');
			} else {
				$uids = explode(',', $space['feedfriend']);
				$theurl = "home.php?mod=space&uid=$space[uid]&do=$do&view=we";
				$f_index = 'dateline';
			}

			$query = C::t('home_friend')->fetch_all_by_uid($space['uid'], 0, 100, true);
			foreach($query as $value) {
				$userlist[] = $value;
			}
			$status = 0;
		} else {
			$need_count = false;
		}
	}

	$actives = array($_GET['view'] =>' class="a"');

	if($need_count) {
		//搜索
		if($searchkey = stripsearchkey($_GET['searchkey'])) {
			$searchsubject = $searchkey;
			$searchkey = dhtmlspecialchars($searchkey);
		}

		//系统分类
		$catid = empty($_GET['catid'])?0:intval($_GET['catid']);

		$count = C::t('home_blog')->count_all_by_search(null, $uids, null, null, $gthot, null, null, null, null, null, $privacyfriend, null, null, null, $classid, $catid, $searchsubject, true, $status);
		if($count) {
			$query = C::t('home_blog')->fetch_all_by_search(1, null, $uids, null, null, $gthot, null, null, null, null, null, $privacyfriend, null, null, null, 'dateline', 'DESC', $start, $perpage, $classid, $catid, $searchsubject, $f_index, false, $status);
		}
	}

	if($count) {
		foreach($query as $value) {
			if(ckfriend($value['uid'], $value['friend'], $value['target_ids']) && ($value['status'] == 0 || $value['uid'] == $_G['uid'] || $_G['adminid'] == 1)) {
				if(!empty($stickblogs) && in_array($value['blogid'], $stickblogs)) {
					continue;
				}
				if($value['friend'] == 4) {
					$value['message'] = $value['pic'] = '';
				} else {
					$value['message'] = getstr($value['message'], $summarylen, 0, 0, 0, -1);
				}
				$value['message'] = preg_replace("/&[a-z]+\;/i", '', $value['message']);
				if($value['pic']) $value['pic'] = pic_cover_get($value['pic'], $value['picflag']);
				$value['dateline'] = dgmdate($value['dateline']);
				$list[] = $value;
			} else {
				$pricount++;
			}
		}

		//分页
		$multi = multi($count, $perpage, $page, $theurl);
		if(!empty($stickblogs)) {
			$list = array_merge(blog_get_stick($space['uid'], $stickblogs, $summarylen), $list);
		}
	}

	dsetcookie('home_diymode', $diymode);

	if($_G['uid']) {
		if($_GET['view'] == 'all') {
			$navtitle = lang('core', 'title_view_all').lang('core', 'title_blog');
		} elseif($_GET['view'] == 'me') {
			$navtitle = lang('core', 'title_my_blog');
		} else {
			$navtitle = lang('core', 'title_friend_blog');

		}
	} else {
		if($_GET['order'] == 'hot') {
			$navtitle = lang('core', 'title_recommend_blog');
		} else {
			$navtitle = lang('core', 'title_newest_blog');
		}
	}
	if($space['username']) {
		$navtitle = lang('space', 'sb_blog', array('who' => $space['username']));
	}
	$metakeywords = $navtitle;
	$metadescription = $navtitle;
	$navtitle = helper_seo::get_title_page($navtitle, $_G['page']);

	space_merge($space, 'field_home');
	include_once template("diy:home/space_blog_list");

}

/**
 * 获得指定用户的置顶日志
 * @param <int> $uid
 * @param <array> $stickblogs
 * @return Array
 */
function blog_get_stick($uid, $stickblogs, $summarylen) {
	$list = array_flip($stickblogs);
	if($stickblogs) {
		$data_blog = C::t('home_blog')->fetch_all($stickblogs);
		$data_blogfield = C::t('home_blogfield')->fetch_all($stickblogs);
		foreach($data_blog as $curblogid=>$value) {
			$value = array_merge($value, (array)$data_blogfield[$curblogid]);
			$value['message'] = getstr($value['message'], $summarylen, 0, 0, 0, -1);
			$value['message'] = preg_replace("/&[a-z]+\;/i", '', $value['message']);
			if($value['pic']) $value['pic'] = pic_cover_get($value['pic'], $value['picflag']);
			$value['dateline'] = dgmdate($value['dateline']);
			$value['stickflag'] = true;
			$list[$value['blogid']] = $value;
		}
	}
	return $list;
}
?>