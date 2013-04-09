<?php

$db_data = array(
	'group'=> array (
		array('groupid'=>0, 'name'=>'游客组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>0, 'allowpost'=>0, 'allowattach'=>0, 'allowdown'=>0, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		
		array('groupid'=>1, 'name'=>'管理员组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>10000, 'maxgolds'=>10000, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>1, 'allowdigest'=>1, 'allowupdate'=>1, 'allowdelete'=>1, 'allowmove'=>1, 'allowbanuser'=>1, 'allowdeleteuser'=>1, 'allowviewip'=>1),
		array('groupid'=>2, 'name'=>'超级版主组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>200, 'maxgolds'=>200, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>1, 'allowdigest'=>1, 'allowupdate'=>1, 'allowdelete'=>1, 'allowmove'=>1, 'allowbanuser'=>1, 'allowdeleteuser'=>1, 'allowviewip'=>1),
		array('groupid'=>4, 'name'=>'版主组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>50, 'maxgolds'=>50, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>1, 'allowdigest'=>1, 'allowupdate'=>1, 'allowdelete'=>1, 'allowmove'=>1, 'allowbanuser'=>1, 'allowdeleteuser'=>0, 'allowviewip'=>1),
		array('groupid'=>5, 'name'=>'实习版主组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>1, 'allowdigest'=>1, 'allowupdate'=>1, 'allowdelete'=>1, 'allowmove'=>1, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		
		array('groupid'=>6, 'name'=>'待验证用户组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>0, 'allowpost'=>0, 'allowattach'=>0,  'allowdown'=>0, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		array('groupid'=>7, 'name'=>'禁止用户组', 'creditsfrom'=>0, 'creditsto'=>0, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>0, 'allowthread'=>0, 'allowpost'=>0, 'allowattach'=>0, 'allowdown'=>0, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		
		array('groupid'=>11, 'name'=>'一级用户组', 'creditsfrom'=>0, 'creditsto'=>50, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		array('groupid'=>12, 'name'=>'二级用户组', 'creditsfrom'=>50, 'creditsto'=>200, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		array('groupid'=>13, 'name'=>'三级用户组', 'creditsfrom'=>200, 'creditsto'=>1000, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		array('groupid'=>14, 'name'=>'四级用户组', 'creditsfrom'=>1000, 'creditsto'=>10000, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
		array('groupid'=>15, 'name'=>'五级级用户组', 'creditsfrom'=>10000, 'creditsto'=>10000000, 'maxcredits'=>0, 'maxgolds'=>0, 'allowread'=>1, 'allowthread'=>1, 'allowpost'=>1, 'allowattach'=>1, 'allowdown'=>1, 'allowtop'=>0, 'allowdigest'=>0, 'allowupdate'=>0, 'allowdelete'=>0, 'allowmove'=>0, 'allowbanuser'=>0, 'allowdeleteuser'=>0, 'allowviewip'=>0),
	), 
	'user'=> array(
		array('uid'=>1, 'regip'=>'12345554', 'regdate'=>0, 'username'=>'admin', 'password'=>'d14be7f4d15d16de92b7e34e18d0d0f7', 
			'salt'=>'99adde', 'email'=>'admin@admin.com', 'groupid'=>1, 'golds'=>0, 'digests'=>0, 'avatar'=>0, 'threads'=>0, 
			'posts'=>0, 'myposts'=>0, 'credits'=>0, 'follows'=>0, 'followeds'=>0, 
			'newpms'=>0, 'newfeeds'=>0, 'homepage'=>'', 'accesson'=>0, 
			'lastactive'=>0),
		array('uid'=>2, 'regip'=>'12345554', 'regdate'=>0, 'username'=>'系统', 'password'=>'d14be7f4d15d16de92b7e34e18d0d0f7', 
			'salt'=>'99adde', 'email'=>'system@admin.com', 'groupid'=>11, 'golds'=>0, 'digests'=>0, 'avatar'=>0, 'threads'=>0, 
			'posts'=>0, 'myposts'=>0, 'credits'=>0, 'follows'=>0, 'followeds'=>0, 
			'newpms'=>0, 'newfeeds'=>0, 'homepage'=>'', 'accesson'=>0, 
			'lastactive'=>0),
			
	),
	'forum'=> array(
		array('fid'=>1, 'name'=>'默认分类一', 'rank'=>0, 'threads'=>0, 'posts'=>0, 'digests'=>0, 'todayposts'=>0, 'lasttid'=>0, 'brief'=>'默认大区介绍', 'icon'=>'', 'accesson'=>0, 'modids'=>'', 'modnames'=>'', 'toptids'=>'', 'lastcachetime'=>0, 'status'=>1, 'orderby'=>0, 'seo_title'=>'', 'seo_keywords'=>''),
		array('fid'=>2, 'name'=>'默认版块二', 'rank'=>0, 'threads'=>0, 'posts'=>0, 'digests'=>0, 'todayposts'=>0, 'tops'=>0, 'lasttid'=>0, 'brief'=>'默认版块介绍', 'icon'=>'', 'accesson'=>0, 'modids'=>'', 'modnames'=>'', 'toptids'=>'', 'lastcachetime'=>0, 'status'=>1, 'orderby'=>0, 'seo_title'=>'', 'seo_keywords'=>''),
		array('fid'=>3, 'name'=>'默认版块三', 'rank'=>0, 'threads'=>0, 'posts'=>0, 'digests'=>0, 'todayposts'=>0, 'tops'=>0, 'lasttid'=>0, 'brief'=>'默认版块介绍', 'icon'=>'', 'accesson'=>0, 'modids'=>'', 'modnames'=>'', 'toptids'=>'', 'lastcachetime'=>0, 'status'=>1, 'orderby'=>0, 'seo_title'=>'', 'seo_keywords'=>''),
	)
);

$db_index = array(
	'group'=>array(array('groupid'=>1)),
	'user'=>array(array('uid'=>1), array('username'=>1), array('email'=>1)),
	'user_access'=>array(array('uid'=>1)),
	'forum'=>array(array('fid'=>1)),
	'forum_access'=>array(array('fid'=>1, 'groupid'=>1), array('fid'=>1)),
	'thread_type'=>array(array('fid'=>1), array('typeid'=>1)),
	'thread'=>array(array('tid'=>1), array('fid'=>1, 'lastpost'=>1), array('fid'=>1, 'digests'=>1, 'tid'=>-1)),
	'post'=>array(array('fid'=>1, 'pid'=>1), array('fid'=>1, 'tid'=>1, 'page'=>1)),
	'attach'=>array(array('aid'=>1), array('fid'=>1, 'tid'=>1), array('fid'=>1, 'pid'=>1), array('uid'=>1, 'isimage'=>1)),
	'attach_download'=>array(array('uid'=>1, 'fid'=>1, 'aid'=>1), array('fid'=>1, 'aid'=>1), array('uploaduid'=>1, 'dateline'=>0)),
	'mypost'=>array(array('uid'=>1, 'fid'=>1, 'pid'=>1), array('uid'=>1, 'fid'=>1, 'tid'=>1), array('uid'=>1, 'pid'=>0)),
	'online'=>array(array('sid'=>1), array('lastvisit'=>1), array('uid'=>1)),
	'friendlink'=>array(array('linkid'=>1), array('type'=>1, 'rank'=>0)),
	'pmnew'=>array(array('recvuid'=>1, 'senduid'=>1), array('recvuid'=>1, 'count'=>1)),
	'pmcount'=>array(array('uid1'=>1, 'uid2'=>1)),
	'pm'=>array(array('pmid'=>1), array('uid1'=>1, 'uid2'=>1, 'pmid'=>1)),
	'follow'=>array(array('uid'=>1, 'fuid'=>1), array('uid'=>1), array('fuid'=>1)),
	'pay'=>array(array('payid'=>1), array('uid'=>1)),
	'modlog'=>array(array('logid'=>1), array('uid'=>1, 'logid'=>1), array('fid'=>1, 'tid'=>1)),
	'rate'=>array(array('rateid'=>1), array('uid'=>1, 'rateid'=>1), array('fid'=>1, 'pid'=>1), array('uid'=>1, 'ymd'=>1)),
	'kv'=>array(array('k'=>1)),
	'stat'=>array(array('year'=>1, 'month'=>1, 'day'=>1)),
	'runtime'=>array(array('k'=>1)),
	
);