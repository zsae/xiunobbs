
// 5 分钟内最大发帖量 20
if($this->_user['groupid'] > 5) {
	$maxposts = 20; 
	
	$mypostlist = $this->mypost->get_list_by_uid($uid, 1, $maxposts);
	
	if(count($mypostlist) >= $maxposts) {
	
		$last = array_pop($mypostlist);
		$lastpost = $this->post->read($last['fid'], $last['pid']);
		if($_SERVER['time'] - $lastpost['dateline'] < 300) {
			$this->message('系统启用了防止灌水插件，5分钟内发帖量不能超过20篇，如果对您带来麻烦，我们表示抱歉。', 0);
		}
	}
	
	// 同一篇主题回帖的限制
	$totalpage = ceil($thread['posts'] / $this->conf['pagesize']);
	$postlist = $this->post->index_fetch(array('fid'=>$fid, 'tid'=>$tid, 'page'=>$totalpage), array(), 0, 20);
	foreach($postlist as $v) {
		if($_SERVER['time'] - $v['dateline'] < 120) $maxposts--;
	}
	if($maxposts <= 10) {
		$this->message('系统启用了防止灌水插件，2分钟内同一篇主题的回帖量不能超过10篇，如果对您带来麻烦，我们表示抱歉。', 0);
	}
}