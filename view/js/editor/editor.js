/*
 * Copyright (C) xiuno.com
 */

// 优化了加载顺序，表情最后加载。

// 避免重复加载
if(!$.editor) {

// function extended jquery
$.fn.editor = function(settings) {
	
	// this = elements <TEXTAREA>, ...
	settings = $.extend({
		width: null,
		height: null,
		onmax: null,		/* 最大化触发的事件 */
		onmin: null,		/* 最小化触发的事件 */
		onctrlenter: null,	/* 提交事件 CTRL+ENTER */
		baseurl: ''
	}, settings);
	
	// 第一个 <TEXTAREA> 宽度、高度为准
	if(!settings.width) settings.width = this.width(); // this[0].style.width ? parseInt(this[0].style.width) : 
	if(!settings.height) settings.height = this.height() + 19;// footer 的高度！
	
	this.each(function() {
		// 可能需要匿名函数闭包，未测试。
		//(function() {})();
		// this = element: <TEXTAREA>
		if(!$.nodeName(this, 'TEXTAREA')) return;
		var editor = new $.editor(this, settings);
		if(editor.init()) {
			this.editor = editor;
		}
	});
	return this;
}

// $.editor() 真正实现 ---------->
$.editor = function(textarea, settings) {
	//var xn_font_familys = [{name:'宋体', command:'SimSun'}, {name:'黑体', command:'SimHei'}, {name:'楷体', command:'KaiTi_GB2312'}, {name:'微软雅黑', command:'Microsoft YaHei'}];
	var _this = this;			// editor class, not textarea
	var _iframe, _win, _doc, _body;		// 注意：这里的 _iframe 是 <iframe> ，不是<div class="iframe">
	var editor, toolbar, footer, menu;	// jquery
	var _textarea = textarea; 		// (非 jquery 对象)
	this.settings = settings; 		// (非 jquery 对象)
	var _issource = true;			// 是否为 source 模式，与 text 模式对应 , public
	//var ie_selection;			// ie selection	, 保存IE的选中。
	//var ie_bookmark;			// ie selection	, 保存IE的选中。 private
	//this.bookmark = null;				// bookmark
	//var ie_cursor_pos = {left:0, top:0};	// ie 光标位置
	//var upload_queue_error = 0;		// 上传错误标志位（标示每轮）
	
	var is_pasting = false;			// 是否正在粘贴，ie 会两次激活 beforepaste

	this.init = function() {
		var s = '<div class="editor">\
				<div class="toolbar">\
					<a class="bold" href="javascript:void(0)" title="加粗"></a>\
					<a class="italic" href="javascript:void(0)" title="倾斜"></a>\
					<a class="underline" href="javascript:void(0)" title="下划线"></a>\
					<a class="fontsize" href="javascript:void(0)"></a>\
					<a class="fontcolor" href="javascript:void(0)" title="字体颜色"></a>\
					<a class="link" href="javascript:void(0)" title="超链接"></a>\
					<a class="unlink" href="javascript:void(0)" title="去除超链接"></a>\
					\
					<a class="sep" href="javascript:void(0)" ></a>\
					\
					<a class="justifyleft" href="javascript:void(0)" title="左对齐"></a>\
					<a class="justifycenter" href="javascript:void(0)" title="居中对齐"></a>\
					<a class="justifyright" href="javascript:void(0)" title="右对齐"></a>\
					<a class="justifyfull" href="javascript:void(0)"></a>\
					\
					<a class="sep" href="javascript:void(0)"></a>\
					\
					<a class="marklist" href="javascript:void(0)" title="项目符号"></a>\
					<a class="numberlist" href="javascript:void(0)" title="编号"></a>\
					\
					<a class="sep" href="javascript:void(0)"></a>\
					<a class="insertcode" href="javascript:void(0)" title="插入代码"></a>\
					<a class="face" href="javascript:void(0)"></a>\
					<a class="sep" href="javascript:void(0)"></a>\
					\
					<a class="video" href="javascript:void(0)"></a>\
					<a class="image" href="javascript:void(0)" title="上传图片"></a>\
					<a class="imageloading" href="javascript:void(0)" title="上传图片进度"><span class="imageprocess"><span class="imageprocess_body"></span></span><img src="view/js/editor/loading.gif" width="18" height="18" /></a>\
					<a class="file" href="javascript:void(0)" title="上传文件"></a>\
					\
				</div>\
				\
				<div class="iframe"><iframe designMode="on" contentEditable="true" frameborder="0"></iframe></div>\
				<div class="footer">\
					<table width="100%" cellspacing="0" cellpadding="0"><tr>\
						<td width="30%"></td>\
						<td width="40%" align="center"><a class="icon icon-zoom"></a></td>\
						<td width="30%" align="right" class="grey" ><input type="checkbox" name="" class="toggle_source" />源代码\
						</td>\
					</tr></table>\
				</div>\
				<div class="menu">\
					<div class="fontsize" style="display: none;">\
						<a title="1" href="javascript: void(0)"><font size="1">1号字体</font></a>\
						<a title="2" href="javascript: void(0)"><font size="2">2号字体</font></a>\
						<a title="3" href="javascript: void(0)"><font size="3">3号字体</font></a>\
						<a title="4" href="javascript: void(0)"><font size="4">4号字体</font></a>\
						<a title="5" href="javascript: void(0)"><font size="5">5号字体</font></a>\
						<a title="6" href="javascript: void(0)"><font size="6">6号字体</font></a>\
						<a title="7" href="javascript: void(0)"><font size="7">7号字体</font></a>\
					</div>\
					<div class="fontcolor" style="display: none;">\
						<a href="javascript: void(0)" title="#FFFFFF" style="background: #FFFFFF;"></a>\
						<a href="javascript: void(0)" title="#CCCCCC" style="background: #CCCCCC;"></a>\
						<a href="javascript: void(0)" title="#C0C0C0" style="background: #C0C0C0;"></a>\
						<a href="javascript: void(0)" title="#999999" style="background: #999999;"></a>\
						<a href="javascript: void(0)" title="#666666" style="background: #666666;"></a>\
						<a href="javascript: void(0)" title="#333333" style="background: #333333;"></a>\
						<a href="javascript: void(0)" title="#000000" style="background: #000000;"></a>\
						<a href="javascript: void(0)" title="#FFCCCC" style="background: #FFCCCC;"></a>\
						<a href="javascript: void(0)" title="#FF6666" style="background: #FF6666;"></a>\
						<a href="javascript: void(0)" title="#FF0000" style="background: #FF0000;"></a>\
						<a href="javascript: void(0)" title="#CC0000" style="background: #CC0000;"></a>\
						<a href="javascript: void(0)" title="#990000" style="background: #990000;"></a>\
						<a href="javascript: void(0)" title="#660000" style="background: #660000;"></a>\
						<a href="javascript: void(0)" title="#330000" style="background: #330000;"></a>\
						<a href="javascript: void(0)" title="#FFCC99" style="background: #FFCC99;"></a>\
						<a href="javascript: void(0)" title="#FF9966" style="background: #FF9966;"></a>\
						<a href="javascript: void(0)" title="#FF9900" style="background: #FF9900;"></a>\
						<a href="javascript: void(0)" title="#FF6600" style="background: #FF6600;"></a>\
						<a href="javascript: void(0)" title="#CC6600" style="background: #CC6600;"></a>\
						<a href="javascript: void(0)" title="#993300" style="background: #993300;"></a>\
						<a href="javascript: void(0)" title="#663300" style="background: #663300;"></a>\
						<a href="javascript: void(0)" title="#FFFF99" style="background: #FFFF99;"></a>\
						<a href="javascript: void(0)" title="#FFFF66" style="background: #FFFF66;"></a>\
						<a href="javascript: void(0)" title="#FFCC66" style="background: #FFCC66;"></a>\
						<a href="javascript: void(0)" title="#FFCC33" style="background: #FFCC33;"></a>\
						<a href="javascript: void(0)" title="#CC9933" style="background: #CC9933;"></a>\
						<a href="javascript: void(0)" title="#996633" style="background: #996633;"></a>\
						<a href="javascript: void(0)" title="#663333" style="background: #663333;"></a>\
						<a href="javascript: void(0)" title="#FFFFCC" style="background: #FFFFCC;"></a>\
						<a href="javascript: void(0)" title="#FFFF33" style="background: #FFFF33;"></a>\
						<a href="javascript: void(0)" title="#FFFF00" style="background: #FFFF00;"></a>\
						<a href="javascript: void(0)" title="#FFCC00" style="background: #FFCC00;"></a>\
						<a href="javascript: void(0)" title="#999900" style="background: #999900;"></a>\
						<a href="javascript: void(0)" title="#666600" style="background: #666600;"></a>\
						<a href="javascript: void(0)" title="#333300" style="background: #333300;"></a>\
						<a href="javascript: void(0)" title="#99FF99" style="background: #99FF99;"></a>\
						<a href="javascript: void(0)" title="#66FF99" style="background: #66FF99;"></a>\
						<a href="javascript: void(0)" title="#33FF33" style="background: #33FF33;"></a>\
						<a href="javascript: void(0)" title="#33CC00" style="background: #33CC00;"></a>\
						<a href="javascript: void(0)" title="#009900" style="background: #009900;"></a>\
						<a href="javascript: void(0)" title="#006600" style="background: #006600;"></a>\
						<a href="javascript: void(0)" title="#003300" style="background: #003300;"></a>\
						<a href="javascript: void(0)" title="#99FFFF" style="background: #99FFFF;"></a>\
						<a href="javascript: void(0)" title="#33FFFF" style="background: #33FFFF;"></a>\
						<a href="javascript: void(0)" title="#66CCCC" style="background: #66CCCC;"></a>\
						<a href="javascript: void(0)" title="#00CCCC" style="background: #00CCCC;"></a>\
						<a href="javascript: void(0)" title="#339999" style="background: #339999;"></a>\
						<a href="javascript: void(0)" title="#336666" style="background: #336666;"></a>\
						<a href="javascript: void(0)" title="#003333" style="background: #003333;"></a>\
						<a href="javascript: void(0)" title="#CCFFFF" style="background: #CCFFFF;"></a>\
						<a href="javascript: void(0)" title="#66FFFF" style="background: #66FFFF;"></a>\
						<a href="javascript: void(0)" title="#33CCFF" style="background: #33CCFF;"></a>\
						<a href="javascript: void(0)" title="#3366FF" style="background: #3366FF;"></a>\
						<a href="javascript: void(0)" title="#3333FF" style="background: #3333FF;"></a>\
						<a href="javascript: void(0)" title="#000099" style="background: #000099;"></a>\
						<a href="javascript: void(0)" title="#000066" style="background: #000066;"></a>\
						<a href="javascript: void(0)" title="#CCCCFF" style="background: #CCCCFF;"></a>\
						<a href="javascript: void(0)" title="#9999FF" style="background: #9999FF;"></a>\
						<a href="javascript: void(0)" title="#6666CC" style="background: #6666CC;"></a>\
						<a href="javascript: void(0)" title="#6633FF" style="background: #6633FF;"></a>\
						<a href="javascript: void(0)" title="#6600CC" style="background: #6600CC;"></a>\
						<a href="javascript: void(0)" title="#333399" style="background: #333399;"></a>\
						<a href="javascript: void(0)" title="#330099" style="background: #330099;"></a>\
						<a href="javascript: void(0)" title="#FFCCFF" style="background: #FFCCFF;"></a>\
						<a href="javascript: void(0)" title="#FF99FF" style="background: #FF99FF;"></a>\
						<a href="javascript: void(0)" title="#CC66CC" style="background: #CC66CC;"></a>\
						<a href="javascript: void(0)" title="#CC33CC" style="background: #CC33CC;"></a>\
						<a href="javascript: void(0)" title="#993399" style="background: #993399;"></a>\
						<a href="javascript: void(0)" title="#663366" style="background: #663366;"></a>\
						<a href="javascript: void(0)" title="#330033" style="background: #330033;"></a>\
					</div>\
					<div class="insertcode" style="display: none">\
						类型：<select><option value="">普通</option><option value="text">文本</option><option value="html">HTML</option><option value="css">CSS</option><option value="javascript">Javascript</option><option value="php">PHP</option><option value="java">JAVA</option><option value="csharp">C#</option><option value="python">Python</option><option value="vb">VB</option><option value="perl">Perl</option><option value="sql">SQL</option><option value="cpp">C/C++</option></select> <br />\
						<textarea></textarea><br />\
						<input type="button" value="插入" style="margin-top: 8px;" class="insert" />\
						<input type="button" value="关闭" style="margin-top: 8px;" class="close" />\
					</div>\
					<div class="link" style="display: none">\
						网址：<input type="text" size="54" value="" class="href" /> <br />\
						文字：<input type="text" size="54" value="" class="text" /> <br />\
						<input type="button" value="插入" style="margin-top: 8px; margin-left: 32px;" class="insert" />\
						<input type="button" value="关闭" style="margin-top: 8px; margin-left: 4px;" class="close" />\
					</div>\
					<div class="video" style="display: none">\
						<table width="540"><tr><td width="60">视频网址：</td><td width="450"><input type="text" size="64" value="http://" class="url" /></td></tr>\
						<tr><td align="right">宽度：</td><td><input type="text" size="4" class="w" value="876" />高度：<input type="text" size="4" class="h" value="454" /></td></tr>\
						<tr><td></td><td><input type="button" value="插入" class="insert" style="margin-top: 8px; margin-left: 2px;" /><input type="button" value="关闭" class="close" style="margin-top: 8px; margin-left: 2px;" /><span class="grey">【提示】：请粘贴以 .swf 结尾的网址。</span></td></tr></table>\
					</div>\
					<div class="face" style="display: none">\
					</div>\
				</div>\
			</div>';
		
		// 在视觉上替换掉原来的 textarea 节点
		editor = $(s).appendTo($(textarea).parent());
		editor.show();
		
		// 隐藏 textarea
		$('.iframe', editor).append($(textarea));
		$(textarea).hide();
		
		//$(textarea).appendTo('.iframe', editor).hide();	// 这样ID会重复，奇怪的BUG。
		$(textarea).keyup(function() {_this.save()});
		
		// 如果 textarea 没有ID，分配一个随机id
		if(!textarea.id) {
			textarea.id = Math.Random();
		}
		_this._textarea = textarea;
		_this.editor = editor;
		_this.toolbar = toolbar = $('.toolbar', editor);
		_this.footer = footer = $('.footer', editor);
		_this.menu = menu = $('.menu', editor);
		
		// 添加 iframe 初始化内容
		_iframe = $('iframe', editor).get(0);
		_win = _iframe.contentWindow;
		_doc = _win.document;
		//var baseadd = _this.settings.baseurl ? '' : '';// <base href="' +  + '" />
		var baseadd = '';
		// 针对ie, 这里不能加 <!doctype , 否则不能编辑, document.documentElement 设置为可编辑模式也不行，只能使用非标准模型。
		_doc.write('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-CN" dir="ltr" style="overflow: auto"><head>'+baseadd+'<meta content="text/html; charset=UTF-8" http-equiv="Content-Type"/><link rel="stylesheet" href="' + _this.settings.baseurl + 'iframe.css"/></head><body spellcheck="false" hidefocus="false" contentEditable="yes" designMode="on" style="overflow: auto; min-height: '+(_this.settings.height - 60)+'px">'+$(textarea).val()+'</body><script>function myfocus(){if(navigator.userAgent.indexOf("Firefox")>0){window.focus();}else{document.body.focus();}} </script></html>');
		_doc.close();
		_body = _doc.body;
		
		// 设置宽度，高度
		_this.set_width(_this.settings.width);
		_this.set_height(_this.settings.height);
		
		if($.browser.msie) {
			//_doc.contentEditable = 'true';
			_body.contentEditable = 'true';
		} else {
			_doc.designMode = 'On';
		}
		
		// 可视化编辑模式
		_issource = false;
		
		// toolbar 添加事件
		$('a.undo', toolbar).click(function() {_this.exec_cmd('Undo');});
		$('a.bold', toolbar).click(function() {_this.exec_cmd('Bold');});
		$('a.italic', toolbar).click(function() {_this.exec_cmd('Italic');});
		$('a.underline', toolbar).click(function() {_this.exec_cmd('Underline');});
		$('a.fontsize', toolbar).html('字体大小').click(function() {
			_this.hide_menu();
			$(this).xn_menu($('.fontsize', menu), 0, 1000);
		});
		$('div.fontsize a', menu).click(function() {
			var size = $(this).attr('title');
			_this.set_fontsize(size);
		});
		
		$('a.fontcolor', toolbar).click(function() {
			_this.hide_menu();
			$(this).xn_menu($('.fontcolor', menu), 0, 1000);
		});
		$('div.fontcolor a', menu).click(function() {
			var color = $(this).attr('title');
			_this.set_fontcolor(color);
		});
		
		// start link
		var rangelink = null;
		var trange = null;
		$('a.link', toolbar).click(function() {
			_this.save_bookmark();
			_this.hide_menu();
			trange = _this.get_range();
			var pelement = is_ie ? trange.parentElement() : trange.startContainer;
			rangelink = $(pelement).closest('a').get(0);
			var href = rangelink ? rangelink.href : 'http://';
			var rangetext = trange ? (is_ie ? trange.text : trange.toString()) : '链接';
			var linktext = rangelink ? rangelink.innerHTML : rangetext;
			$('div.link input.href', menu).val(href).focus();
			$('div.link input.text', menu).val(linktext);
			$(this).xn_menu($('div.link', menu), 0, 1000000);
			setTimeout(function() {
				$('div.link input.href', menu).select();
			}, 100);
		});
		$('a.unlink', toolbar).click(function() {
			_this.exec_cmd('unlink');
		});
		
		$('div.link input.insert', menu).click(function() {
			$(rangelink, _doc).remove();
			var href = $('div.link input.href', menu).val();
			var linktext = $('div.link input.text', menu).val();
			var s = href ? '<a href="'+href+'" target="_blank">'+linktext+'</a>' : linktext;
			_this.load_bookmark();
			_this.paste(s);
		        _this.hide_menu();
		        _this.save();
		});
		$('div.link input.close', menu).click(function() {
			 _this.hide_menu();
		});
		// end link
		
		// insertcode
		$('a.insertcode', toolbar).click(function() {
			_this.save_bookmark();
			_this.hide_menu();
			//$('div.insertcode select', menu).val('').focus();
			//$('div.insertcode textarea', menu).val('');
			$(this).xn_menu($('div.insertcode', menu), 0, 1000000);
			$('div.insertcode textarea', menu).focus();
		});
		$('div.insertcode input.insert', menu).click(function() {
			var type = $('div.insertcode select', menu).val();
			var s = $('div.insertcode textarea', menu).val();
			if(type == "") {
				s = htmlspecialchars(s);
				s = nl2br(s);
				s = '<div class="code">'+s+'</div><br />';
			} else {
				s = '<pre class="brush:'+type+'; tab-size:8">'+htmlspecialchars(s)+'</pre><br />';
			}
			//var s = href ? '<a href="'+href+'" target="_blank">'+linktext+'</a>' : linktext;
			_this.load_bookmark();
			_this.hide_menu();
			_this.paste(s);
		        _this.save();
		});
		$('div.insertcode input.close', menu).click(function() {
			 _this.hide_menu();
		});
		//$('a.insertcode', toolbar).click(function() {_this.add_code();});

		$('a.justifyleft', toolbar).click(function() {_this.exec_cmd('JustifyLeft');});
		$('a.justifycenter', toolbar).click(function() {_this.exec_cmd('JustifyCenter');});
		$('a.justifyright', toolbar).click(function() {_this.exec_cmd('JustifyRight');});
		$('a.justifyfull', toolbar).click(function() {_this.exec_cmd('JustifyFull'); _this.clear_format();});
		$('a.marklist', toolbar).click(function() {_this.exec_cmd('InsertUnorderedList');});
		$('a.numberlist', toolbar).click(function() {_this.exec_cmd('InsertOrderedList');});
		
		$('a.video', toolbar).click(function() {
			_this.save_bookmark();
			_this.hide_menu();
			$(this).xn_menu($('.video', menu), 0, 5000);
			setTimeout(function() {
				$('.video input.url', menu).focus().select();
			}, 100);
		});
		$('.video input.insert', menu).click(function() {
			_this.load_bookmark();
			var url = $('.video input.url', menu).val();
			var width = $('.video input.w', menu).val();
			var height = $('.video input.h', menu).val();
			_this.add_video(url, width, height);
			return true;
		});
		$('.video input.close', menu).click(function() {
			_this.hide_menu();
			return true;
		});
		
		// 最后加载表情
		$(function() {
			var facehtml = '';
			for(var i=1; i<=30; i++) facehtml += '<a href="javascript: void(0)"><img src="' + _this.settings.baseurl + i + '.gif" /></a>';
		
			$('div.face', menu).html(facehtml);
			$('a.face', toolbar).click(function() {
				_this.save_bookmark();
				_this.hide_menu();
				$(this).xn_menu($('div.face', menu), 0, 1000);
			});
			$('div.face a', menu).click(function() {
				_this.load_bookmark();
				var s = $(this).html();
				_this.paste(s);
				_this.hide_menu();
				$('div.face', menu).hide();
			});
		});
		
		// toolbar 添加事件 预留，剪切，复制，粘贴
		$('a.cut', toolbar).click(function() {_this.exec_cmd('Cut');});
		$('a.copy', toolbar).click(function() {_this.exec_cmd('Copy');});
		$('a.paste', toolbar).click(function() {_this.exec_cmd('Paste');});
		
		$('input.toggle_source', footer).click(function() {
			_this.toggle_source(this.checked);
		});
		
		$('a.icon-zoom', footer).click(function() {
			_this.zoom();
		});
		
		
		// 加载 image swfupload  file swfupload
		if(settings.onhook) {
			settings.onhook(_this);
		}
		
		if($.browser.msie || $.browser.safari) {
			$(_doc).keypress(function(e) {return _this.fix_ie_br(e);});
		}
		$(_doc).keyup(function(e) {
			if(settings.onctrlenter) {
				if(e.ctrlKey && e.which == 13 || e.which == 10) {
					settings.onctrlenter(_this);
					return false;
				}
			}
			_this.save();
			_this.check_toolbar();
		});
		$(_doc).mouseup(function() {
			_this.save();
			_this.check_toolbar();
		});
		
		$(_doc).keydown(function(e) {return _this.fix_tab(e);});
		
		// ie / ff onpaste 不一样，jquery 已经帮我们解决。
		
		/*
		if($.browser.opera) {
			$(_body).bind('keydown',function(e){
				if(e.ctrlKey && e.which === 86) {
					_this.clear_paste(e);
				}
			});
		}
		*/
		
		$(_body).bind('paste',  function(e) {
			var e = e ? e : window.event;
			_this.clear_paste(e);
			//return false;
		});
		
		// 加载完成以后
		$(function() {
			//_this._focus();
		});
		
		// 加载初始化的HTML
		if(textarea.value) {
			_this.set(textarea.value);
		} else if($.pdata('editor_html_' + textarea.id)) {
			_this.set($.pdata('editor_html_' + textarea.id));
		}
		
		// 需要加载完以后再 focus()，否则FF下有问题
		
		// 查找父节点 dialog, 关联 dialog 的最大化，最小化
		if(_this.settings.onmax) {
			//var parentdialog = $.find_parent_dialog(textarea);
			var parentdialog = $(textarea).closest('div.dialog').get(0);
			if(parentdialog) {
				pdialog = parentdialog.dialog;
				pdialog.settings.onmin = function() {_this.settings.onmin(_this);};
				pdialog.settings.onmax = function() {_this.settings.onmax(_this);};
			}
		}

		return true;
	}
	
	this.clear_paste = function(e) {
		if(is_ie) {
			var bookmark = _this.save_bookmark();
			
			var __iframe = _doc.createElement('iframe');
			__iframe.id = "__iframeid";
			__iframe.style.width = "1px";
			__iframe.style.height = "1px";
			__iframe.style.position = "absolute";
			__iframe.style.border = "none";
			__iframe.style.left = "-1000px";
			__iframe.style.top = $(_body).scrollTop() + 'px';
			_body.appendChild(__iframe);
			__win = __iframe.contentWindow;
			__doc = __win.document;
			__body = __doc.body;
			__doc.designMode = "On";
			__doc.open();
			__doc.write("<html><body></body></html>");
			__doc.close();
			__win.focus();
			
			/* ie 工作不正常
			__doc.write("<html><body><div id=\"pasteid\">xxxx</div></body></html>");
			var pastid = __doc.getElementById('pasteid');
			var rng = __doc.body.createTextRange();
			rng.moveToElementText(pastid);
			rng.collapse(true);
			rng.select();
			*/
			
			__doc.execCommand("Paste", false, null);
			_win.focus();
			var s = __doc.body.innerHTML;
			
			s = _this.fix_link(s);
			s = _this.fix_officeword(s);
			
			var rng = bookmark.rng;
			rng.select();
			rng.pasteHTML(s);
			
			if(e.preventDefault) e.preventDefault();
			if(e.returnValue) e.returnValue = false;
		
			_this.save();
		} else {
			
			$('#__iframeid', _doc).remove();
			var __div = _doc.createElement('div');
			__div.id = "__iframeid";
			__div.style.width = "1px";
			__div.style.height = "1px";
			__div.style.position = "absolute";
			__div.style.border = "none";
			__div.style.left = "-1000px";
			__div.style.top = $(_body).scrollTop() + 'px';
			__div.innerHTML = '\uFEFF';
			_doc.body.appendChild(__div);
			
			_win.focus();
			var oldsel = _win.getSelection();
			var oldrng = oldsel.getRangeAt(0);
			var newrng = _doc.createRange();	// 创建新的 Range 对象
			
			newrng.selectNodeContents(__div);
			//newrng.setStart(__div.firstChild, 0);
			//newrng.setEnd(__div.firstChild, 1);
			oldsel.removeAllRanges();
			oldsel.addRange(newrng);
			
			//alert(1);
			setTimeout(function() {
				//alert(2);
				var s = _doc.getElementById('__iframeid').innerHTML;
				if (__div.innerHTML === '\uFEFF') {
					var s = '';
					_doc.body.removeChild(__div);
					return;
				}
				s = _this.fix_link(s);
				s = _this.fix_officeword(s);
				// resotre old range
				oldsel.removeAllRanges();
				oldsel.addRange(oldrng);
			
				// fix chrome
				if(s.indexOf('id="__iframeid"') != -1) {
					s = s.replace(/<div\s+id="__iframeid"[^>]*?>([\s\S]*?)<\/div>/ig, '<p>$1</p>');
				}
				
				__div.innerHTML = s;
				
				_doc.execCommand('inserthtml', false, s);
				_doc.body.removeChild(__div);
				
				_this.save();
			}, 0);
		}
		
	}
	
	this.save_bookmark = function() {
		_this._focus();
		var rng = _this.get_range();
		rng = rng.cloneRange ? rng.cloneRange() : rng;
		window.xn_bookmark = {'top' : $(_body).scrollTop(), 'rng' : rng};
		return window.xn_bookmark;
	}
	
	this.load_bookmark = function() {
		_this._focus();
		if(window.xn_bookmark) {
			var rng = window.xn_bookmark.rng;
			if(is_ie) {
				rng.select();
			} else {
				var sel = _this.get_selection();
				sel.removeAllRanges();
				sel.addRange(rng);
			}
			$(_body).scrollTop(window.xn_bookmark.top);
			window.xn_bookmark =  null;
			return rng;
		}
		return null;
	}
	
	this.get_selection = function() {
		return _doc.selection ? _doc.selection : _win.getSelection();
	}
		
	this.create_range = function() {
		return _doc.body.createTextRange ? _doc.body.createTextRange() : _doc.createRange();
	}
	
	this.get_range = function() {
		var sel = _this.get_selection();
		if(is_ie) {
			range = sel.createRange();
		} else {
			var range = sel.createRange ? sel.createRange() : sel.rangeCount > 0 ? sel.getRangeAt(0) : null;
		}
		return range;
	}
	
	// width 可能为 98% 1024 两种数据格式，这里只考虑像素
	this.set_width = function(width) {
		// 设置宽度，高度
		editor.width(width - 2);
		$(_iframe).width(width - 2);
		$(textarea).width(width - 2);
	}
	
	this.set_height = function(height) {
		editor.height(height);
//		alert('height:' + height + 'toolbar height:' + $('div.toolbar', editor).outerHeight() + 'footer height:' + $('div.footer', editor).outerHeight() + ' toolbar height' + $('div.toolbar', editor).height());
		var toolbarh = $.browser.msie && $.browser.version == '6.0' ? 25 :  $('div.toolbar', editor).show().outerHeight();
		toolbarh = toolbarh ? toolbarh : 25;
		$(_iframe).height(height - toolbarh - $('div.footer', editor).outerHeight());
		$(textarea).height(height - 18);	// 18 为 footer 高度
	}
	
	// 设置 toolbar 选中状态
	this.check_toolbar = function() {
		var cmds = new Array('bold', 'italic', 'underline', 'justifyleft', 'justifycenter', 'justifyright');
		for(var i=0; i<cmds.length; i++) {
			try{
				var status = _doc.queryCommandState(cmds[i]);
				$('a.'+cmds[i], _this.toolbar).toggleClass('checked', status);
			} catch(e) {}
		}
		
		var cmds = new Array('fontsize', 'forecolor');//'fontname'
		for(var i=0; i<cmds.length; i++) {
			var value = _doc.queryCommandValue(cmds[i]);
			if(cmds[i] == 'forecolor') {
				value = _this.dec_to_rgb(value);
				$('a.fontcolor', _this.toolbar).css('background-color', '#' + value);
				// 设置颜色
			} else if(cmds[i] == 'fontsize') {
				value2 = parseInt(value);
				$('a.fontsize', _this.toolbar).html(value ? value + '号字体' : '字体大小');
			}
		}
	}
	
	this.set = function(html) {
		$(_body).html(html)
		$(textarea).val(html);
		_this.save();
	}
	
	this.get = function() {
		return _issource ? $(textarea).val() : $(_body).html();
	}
	
	// 保存HTML到 textarea
	this.save = function() {
		var s = _this.get();
		
		// fix chrome bug
		if(s == '' || s == ' ' || s == "\r\n") {
			s = '<div><br /></div>';
		}
		
		if(_issource) {
			$(_doc.body).html(s);
		} else {
			$(textarea).val(s);
		}
		$.pdata('editor_html_' + textarea.id, s);
	}
	
	this.exec_cmd = function(cmd, arg) {
		if(!arg) arg = null;
		//_this._focus();
		_doc.execCommand(cmd, false, arg);
		_this.save();
		_this.check_toolbar();
	}
	
	this._focus = function() {
		_win.focus();
	        setTimeout(_win.myfocus, 50);
	        //_win.DOMwindow.myfocus();
	        return;
		if(_issource) {
			textarea.focus();
		} else {
			if($.browser.msie) {
				_doc.body.focus();
			} else {
				_win.focus();
			}
		}
	}
	
	// select: 是否覆盖选中的区域
	
	this.paste = function(s, overwrite) {
		//_this._focus();
		var sel = _this.get_selection();
		var range = _this.get_range();
		if(is_ie) {
			range.select();
			range.execCommand('Delete');
			try {
				// 如果没有选中范围，则会产生异常, todo: 待解决
				range.pasteHTML(s);
			} catch(e) {
				// 会有安全提示
				_doc.execCommand('paste' , '' , s );
				//alert('操作失败，请您尽量选中连续区域再试试。');
			}
		} else {
			if(overwrite) {
				var old = range.cloneRange();
				range.deleteContents();
				var newnode = range.createContextualFragment(s + '<span id="range_start" width="1" height="1" style="overflow: hidden;" ></span>');
				range.insertNode(newnode);
				range.selectNode($('#range_start', _doc)[0]);
				sel.removeAllRanges();
				range.setStart(old.startContainer, old.startOffset); // 起始点向前移动到最初的点
				sel.addRange(range);
				$('#range_start', _doc).remove();
			} else {
				try {
					var old = range.cloneRange();
					range.deleteContents();
					var newnode = range.createContextualFragment(s + '<span id="range_start" width="1" height="1" style="overflow: hidden;" ></span>');
					range.insertNode(newnode);
					
					range.selectNode($('#range_start', _doc)[0]);
					range.setStartBefore($('#range_start', _doc).get(0));
					range.setEndAfter($('#range_start', _doc).get(0));
					
					sel.removeAllRanges();
					sel.addRange(range);
					$('#range_start', _doc).remove();
				} catch(e) {
					alert(e.message);
				}
				// 会转义 \n 到 <br/> 影响代码高亮插件！
				//_doc.execCommand('InsertHtml' , '' , s);
			}
		}
		
		// 延迟保存?
		_this.save();
	}
	
	this.set_fontcolor = function(color) {
		
		// 设置选中状态的字体颜色
		_this.load_bookmark();
		_this.exec_cmd('ForeColor', color);
		
		$('a.fontcolor', _this.toolbar).css('background-color', color);
		
		// 隐藏控件
		$('div.fontcolor', _this.menu).hide();
	}
	
	this.set_fontsize = function(size) {
		_this.exec_cmd('FontSize', size);
		$('a.fontsize', _this.toolbar).html(size ? size + '号字体' : '字体大小');
		
		// 隐藏控件
		$('div.fontsize', _this.menu).hide();
	}
	
	this.add_link = function(url) {
		_this.paste('<a href="' + url + '" target="_blank">' + url + '</a>');
		
		// 初始化内容
		$('.link input[type=text]', _this.menu).val('');
		
		// 隐藏控件
		$('.link', _this.menu).hide();
	}
	
	this.add_video = function(url, width, height) {
		width = intval(width);
		height = intval(height);
		var s = "<embed wmode=\"transparent\" src=\""+url+"\" style=\"z-index:0;\" width=\""+width+"\" height=\""+height+"\" type=\"application/x-shockwave-flash\" class=\"border\" />";
		_this.paste(s);
		$('div.video', _this.menu).hide();
	};
	
	this.add_code = function() {
		// 插入代码，弹出层
		/*
		if($.browser.mozilla || $.browser.safari) {
			var s = '<br /><div class="code"><p>&nbsp;</p></div><br />';
		} else {
			var s = '<br /><div class="code"><p></p></div><br />';
		}
		_this._focus();
		_this.paste(s);
		*/
	};

	this.clear_format = function() {
		if(window.confirm('您确定自动调整文章格式吗？将会去掉字体颜色、大小、加粗、下划线、对齐、多余的换行等格式。')) {
			var html = _this.get();
			html = html.replace(/<\/?SPAN( [^>]*)*>/gi, "" );
			html = html.replace(/<\/?font( [^>]*)*>/gi, "" );
			html = html.replace(/<\/?b( [^>]*)*>/gi, "" );
			html = html.replace(/<\/?i( [^>]*)*>/gi, "" );
			html = html.replace(/<\/?u( [^>]*)*>/gi, "" );
			html = html.replace(/<\/?em( [^>]*)*>/gi, "" );
			html = html.replace(/<\/?strong( [^>]*)*>/gi, "" );
			
			html = html.replace(/<(\w[^>]*)[^>]*?class=([^ |>]*)([^>]*)/gi, "<$1$3");	// Remove Class attributes
			html = html.replace(/<(\w[^>]*)[^>]*?style=\"([^"]*)\"([^>]*)/gi, "<$1$3");	// Remove Style attributes
			html = html.replace(/<(\w[^>]*)[^>]*?lang=([^ |>]*)([^>]*)/gi, "<$1$3");	// Remove Lang attributes
			
			html = html.replace(/<\\?\?xml[^>]*>/gi, "");				// Remove XML elements and declarations
			//html = html.replace(/<\/?\w+:[^>]*>/gi, "");				// Remove Tags with XML namespace declarations:
			html = html.replace(/<p[^>]*><\/p>/gi, "<br />" );
			html = html.replace(/<p[^>]*>&nbsp;<\/p>/gi, "<br />" );
			html = html.replace(/(<br\s*\/?>){2,}/gi, "<br />" );
			// 多个换行
			_this.set(html);
			_this.save();
		}
	}
	
	// 粘贴的网址，加上外部链接，新窗口打开
	this.fix_link = function(s) {
		s = s.replace(/^(http:\/\/\S+)$/ig, '<a href="$1" target="_blank">$1</a>');
		s = s.replace(/<a([^>]+)href=['"]?([^\s'">]+)['"]?([^>]+)>([^<]+)<\/a>/ig, function(all, v1, href, v2, text) {
			v1 = v1.replace(/target\s*=\s*['"]\w+['"]?/ig, '');
			v2 = v2.replace(/target\s*=\s*['"]\w+['"]?/ig, '');
			return '<a'+v1+'href="'+href+'"'+v2+' target="_blank">'+text+'</a>';
		});
		
		return s;
	}
	
	// 清除 word 格式
	this.fix_officeword = function(s) {
		if(s.match(/mso-cellspacing:/i) || s.match(/<v|o:\w+/i) || s.match(/<w:WordDocument>/i) || s.match(/<!{1}--\[if gte mso \d+\]>/i)) {
			var allowtags = ['table', 'tbody', 'tr', 'td', 'th', 'div', 'p', 'br', 'a', 'img', 'h1', 'h2', 'h3', 'h4', 'h5', 'hr'];// 'span', 
			var allattrs = ['width', 'height', 'href', 'src', 'align', 'border', 'cellspaceing', 'cellspadding', 'border'];
			s = s.replace(/<!.*?>/img, '');
			s = s.replace(/<style[^>]*>[\s\S]+?<\/style>/ig, '');
			s = s.replace(/<xml[^>]*>[\s\S]+?<\/xml>/ig, '');
			s = s.replace(/\s+/ig, ' ');
			// 白名单过滤
			s = s.replace(/<([\w\-:]+)\s*([^>]*)>/ig, function(all, tag, attrs) {
				// 保留 table tr td p br a标签
				// 保留 width href align 属性
				// 不保留 font size class style ....
				tag = tag.toLowerCase();
				if($.inArray(tag, allowtags) == -1) {
					return '';
				}
				
				attrs = $.trim(attrs);
				attrs = attrs.replace(/(\w+)\s*=\s*['"]?([^'"]*)['"]?/ig, function(all, name, value) {
					name = $.trim(name.toLowerCase());
					if($.inArray(name, allattrs) == -1) {
						return '';
					} else {
						if(name == 'border') {
							return 'border="1"';
						} else {
							return all;
						}
					}
				});
				attrs = $.trim(attrs);
				
				return '<'+tag+''+(attrs ? ' ' : '')+attrs+'>';
			});
			s = s.replace(/<\/([\w\-:]+)\s*>/ig, function(all, tag) {
				if($.inArray(tag, allowtags) == -1) {
					return '';
				} else {
					return all;
				}
			});
		}
		return s;
	}
	
	this.toggle_source = function(status) {
		if(status) {
			$('div.toolbar', editor).hide();
			$(_iframe).hide();
			$(textarea).show();
			_issource = true;
		} else {
			$('div.toolbar', editor).show();
			$(_iframe).show();
			$(textarea).hide();
			_issource = false;
		}
		//_this._focus();
	}
	
	this.zoom = function() {
		// 放大编辑器
		$(_iframe).height($(_iframe).height() + 150);
		$(textarea).height($(textarea).height() + 150);
		$(editor).height($(editor).height() + 150);
		/*
		if($(_iframe).width() < 960) {
			$(_iframe).width(960);
			$(textarea).width(960);
			$(editor).width(960);
		}
		*/
	}
	
	this.fix_ie_br = function(e) {
		var e = e ? e : window.event;
		if(e.keyCode == 13) {
			var rng = _doc.selection.createRange();		//获得光标位置
			rng.pasteHTML("<br />");			//在光标处添加<BR>
			rng.collapse(false);				//设置插入点
			rng.select();
			return false;
		} else {
			return true;
		}
	}
	
	this.fix_tab = function(e) {
		var e = e ? e : window.event;
		var keycode = e.keyCode ? e.keyCode : (e.which ? e.which : e.charCode);
		if(keycode == 9) {
			_this.paste('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
			return false;
		} else {
			return true;
		}
	}
	
	this.hide_menu = function() {
		// 隐藏第一级的DIV
		$('>div', $(_this.menu)).hide();
	}
			
	this.dec_to_rgb = function(value) {
		if($.browser.msie) {
			var hex_string = "";
			for(var hexpair = 0; hexpair < 3; hexpair++) {
				var myByte = value & 0xFF;         // get low byte
				value >>= 8;			   // drop low byte
				var nybble2 = myByte & 0x0F;	   // get low nybble (4 bits)
				var nybble1 = (myByte >> 4) & 0x0F;// get high nybble
				hex_string += nybble1.toString(16);// convert nybble to hex
				hex_string += nybble2.toString(16);// convert nybble to hex
			}
			return hex_string.toUpperCase();
		} else {
			var matches = value.match(/^rgb\s*\(([0-9]+),\s*([0-9]+),\s*([0-9]+)\)$/);
			if(matches) {
				var hex = (matches[1] < 16 ? '0' : '') + parseFloat(matches[1]).toString(16) + (matches[2] < 16 ? '0' : '') + parseFloat(matches[2]).toString(16) + (matches[3] < 16 ? '0' : '') + parseFloat(matches[3]).toString(16);
				return hex.toUpperCase();
			} else {
				return 'FFFFFF';
			}
		}
	}
	
	return this;
}


}
