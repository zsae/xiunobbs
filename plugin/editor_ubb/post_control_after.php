
	private function bbcode2html($s) {
		$s = str_replace(array("\t", '   ', '  '), array('&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;'), $s);
		$s = nl2br($s);
		$s = preg_replace('#(<br\s*/?>\s*){3,999}#', '<br /><br />', $s);
		
		$s = str_replace(array(
			'[b]', '[/b]','[i]', '[i=s]', '[/i]', '[u]', '[/u]', '[/color]', '[/size]', '[/font]', 
			'[p]', '[/p]', '[/align]', '[/list]', '[/td]', '[/tr]', '[/table]', '[td]', '[tr]', '[table]', 
			'[hr]', '[quote]', '[/quote]', '[hide]', '[/hide]'), array(
			'<b>', '</b>', '<i>', '<i>', '</i>', '<u>', '</u>', '</font>', '</font>', '</font>', 
			'<p>', '</p>', '</div>', '</ul>', '</td>', '</tr>', '</table>', '<td>', '<tr>', '<table>', 
			'<hr />', '<div class="quote">', '</div>', '', ''), $s);
		$s = preg_replace('#\[em:([0-9]+):\]#i', '', $s);
		$s = preg_replace('#\[quote\]([^[]*?)\[/quote\]#i', '<div class="bg2 border shadow">\\1</div>', $s);
		$s = preg_replace('#\[color=([^]]+)\]#i', '<font color="\\1">', $s);
		$s = preg_replace('#\[size=(\w+)\]#i', '<font size="\\1">', $s);
		$s = preg_replace('#\[font=([^]]+)\]#i', '<font="\\1">', $s);
		$s = preg_replace('#\[align=([^]]+)\]#i', '<div align="\\1">', $s);
		$s = preg_replace('#\[table=([^]]+)\]#i', '<table width="\\1">', $s);
		$s = preg_replace('#\[td=([^]]+)\]#i', '<td width="\\1">', $s);
		$s = preg_replace('#\[tr=([^[]+)\]#i', '<tr>', $s);
		$s = preg_replace('#\[p=([^]]+)\]#i', '<p>', $s);
		$s = preg_replace('#\[list=([^]]+)\]#i', '<ul>', $s);
		$s = preg_replace('#\{:[^}]+:\}#i', '', $s);
		$s = preg_replace('#\[\*\](.*?)\r\n#i', '<li>\\1</li>', $s);
		$s = preg_replace('#\[url\](.*?)\[\/url\]#i', "<a href=\"\\1\" target=\"_blank\">\\1</a>", $s);
		$s = preg_replace('#\[url=([^]]+?)\](.*?)\[\/url\]#i', "<a href=\"\\1\" target=\"_blank\">\\2</a>", $s);
		$s = preg_replace('#\[backcolor=([^]]+?)\]([^[]*?)\[\/backcolor\]#i', "<div style=\"background: \\1\">\\2</div>", $s);
		$s = preg_replace('#\[indent\]([^[]*?)\[\/indent\]#i', "<ul>\\1</ul>", $s);
		$s = preg_replace('#\[img\]([^[]*?)\[/img\]#i', '<img src="\\1" />', $s);
		$s = preg_replace('#\[img=(\d+),(\d+)\]([^[]*?)\[/img\]#i', '<img src="\\3" width="\\1" height="\\2" />', $s);
		
		$s = preg_replace('#\[attach\]([^[]*?)\[/attach\]#i', '', $s);
		
		$s = preg_replace('#\[media=\w+,(\d+),(\d+)\]([^[]*?)\[/media\]#i', '[media=\\1,\\2]\\3[/media]', $s);
		
		$s = preg_replace('#\[flash]([^[]*?)\[/flash\]#i', '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase=" http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="400" height="300">
				<param name="wmode" value="transparent" />
				<param name="quality" value="high" />
				<param name="menu" value="false" />
				<param name="loop" value="false" />
				<param name="AutoStart " value="true" />
				<param name="src" value="\\1" />
				<embed src="\\1" quality="high" AutoStart="true" loop="false" width="400" height="300" name="firefoxhead" allowFullScreen="yes" wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" swLiveConnect="true" />
			</object>', $s);
		
		$s = preg_replace('#\[(media|swf|flash)=(\d+),(\d+)\]([^[]*?)\[/\\1\]#i', '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase=" http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="\\2" height="\\3">
			<param name="wmode" value="transparent" />
			<param name="quality" value="high" />
			<param name="menu" value="false" />
			<param name="loop" value="false" />
			<param name="AutoStart " value="true" />
			<param name="src" value="\\4" />
			<embed src="\\4" quality="high" AutoStart="true" loop="false" width="\\2" height="\\3" name="firefoxhead" allowFullScreen="yes" wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" swLiveConnect="true" />
		</object>', $s);
		
	return $s;
}
