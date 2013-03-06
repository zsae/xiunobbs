$sendpm = core::gpc('sendpm', 'P');
if($sendpm) {
	if(!empty($thread['recvpm']) && $thread['uid'] != $this->_user['uid']) {
		$pmmessage = strip_tags($message);
		$pmmessage = strip_tags($message);
		core::htmlspecialchars($pmmessage);
		$pmmessage = str_replace("<br />", "\r\n", $pmmessage);
		$pmmessage = str_replace("&nbsp;", " ", $pmmessage);
		$pmmessage = utf8::substr($pmmessage, 0, 64);
		$s = $this->_user['username']."回复了您的主题<a href=\"?thread-index-fid-$fid-tid-$tid-page-$page.htm\" target=\"_blank\">【".utf8::substr($thread['subject'], 0, 12)."】</a>：".$pmmessage;
		$this->pm->system_send($thread['uid'], $thread['username'], $s);
	}
}