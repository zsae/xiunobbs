
// 5 分钟内最大发帖量 20
if($this->_user['groupid'] > 5) {
	$maxposts = 5; 
	
	$mypostlist = $this->mypost->get_list_by_uid($uid, 1, $maxposts);
	
	if(count($mypostlist) >= $maxposts) {
	
		$last = array_pop($mypostlist);
		$lastpost = $this->post->read($last['fid'], $last['pid']);
		if($_SERVER['time'] - $lastpost['dateline'] < 300) {
			$this->message('系统启用了防止灌水插件，5分钟内发帖量不能超过20篇，如果对您带来麻烦，我们表示抱歉。', 0);
		}
	}
}
	