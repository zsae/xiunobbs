/*
 * Copyright (C) xiuno.com
 */

// 需要优化加载顺序，表情最后加载。

// 重复加载
if(!$.editor) {

// 全局路径设置
var EDITOR_UPLOAD_SWF = 'js/swfupload/swfupload_9.swf';

// function extended jquery
$.fn.editor = function(settings) {
	
	// this = elements <TEXTAREA>, ...
	settings = $.extend({
		width: null,
		height: null,
		imageupload: null,
		videoupload: null,
		onmax: null,		/* 最大化触发的事件 */
		onmin: null,		/* 最小化触发的事件 */
		onctrlenter: null,	/* 提交事件 CTRL+ENTER */
		baseurl: '',
		filesizelimit: '4 MB'
	}, settings);
	
	// 第一个 <TEXTAREA> 宽度、高度为准
	if(!settings.width) settings.width = this.width(); // this[0].style.width ? parseInt(this[0].style.width) : 
	if(!settings.height) settings.height = this.height() + 19;// footer 的高度！
	
	this.each(function() {
		
		// this = element: <TEXTAREA>
		if(!$.nodeName(this, 'TEXTAREA')) return;
		var editor = new $.editor(this, settings);
		if(editor.init()) {
			this.editor = editor;
		}
	});
	return this;
}

// 向上查找父标签
function find_pnode(node, recall, times) {
	if(typeof times == 'undefined') times = 6;
	while(times-- != 0) {
		if(recall(node)) return node;
		if(node.parentNode) {
			node = node.parentNode;
		} else {
			return null;
		}
	}
	return null;
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
	var ie_selection;			// ie selection	, 保存IE的选中。
	var ie_bookmark;			// ie selection	, 保存IE的选中。 private
	var ie_cursor_pos = {left:0, top:0};	// ie 光标位置
	var upload_queue_error = 0;		// 上传错误标志位（标示每轮）
	
	this.save_selection = function(){
		if($.browser.msie) {
			var range = _doc.selection.createRange();
			if(range.getBookmark) {
				_this.ie_bookmark = range.getBookmark();
				_this.ie_cursor_pos = {left : range.offsetLeft, top : range.offsetTop};
			}
		}
	}
	
	this.restore_selection = function() {
		if($.browser.msie) {
			var range = _doc.body.createTextRange();
			range.moveToBookmark(_this.ie_bookmark);
			//range.collapse();
			range.select();
			return range;
		}
	}
	
	this.get_range = function() {
		if($.browser.msie) {
			range = _doc.selection.createRange();
			/*
			if(range.parentElement().document != _doc) {
				return null;
			}
			*/
		} else {
			var sel = _win.getSelection();
			range = sel.rangeCount > 0 ? sel.getRangeAt(0) : null;
		}
		return range;
	}
	
	this.init = function() {
		
		//alert(_this.settings.baseurl + EDITOR_UPLOAD_SWF);
		var s = '<div class="editor">\
				<div class="toolbar">\
					<a class="undo" href="javascript:void(0)" title="撤销"></a>\
					<a class="bold" href="javascript:void(0)" title="加粗"></a>\
					<a class="italic" href="javascript:void(0)" title="倾斜"></a>\
					<a class="underline" href="javascript:void(0)" title="下划线"></a>\
					<a class="fontsize" href="javascript:void(0)"></a>\
					<a class="fontcolor" href="javascript:void(0)" title="字体颜色"></a>\
					<a class="link" href="javascript:void(0)" title="超链接"></a>\
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
					<a class="insertcode" href="javascript:void(0)" title="插入代码"></a>\
					\
					<a class="sep" href="javascript:void(0)"></a>\
					<a class="face" href="javascript:void(0)"></a>\
					<a class="sep" href="javascript:void(0)"></a>\
					\
					<a class="video" href="javascript:void(0)"></a>\
					<a class="image" href="javascript:void(0)" title="上传文件"></a>\
					<a class="imageloading" href="javascript:void(0)"></a>\
					\
				</div>\
				\
				<div class="iframe"><iframe designMode="on" contentEditable="true" frameborder="0" style="overflow: hidden;"></iframe></div>\
				<div class="footer">\
					<div style="width: 60px; float: right; font-size:12px;">\
						<input type="checkbox" name="" class="toggle_source" />源代码</a>\
					</div>\
					<div style="width: 16px; float: right; cursor: pointer;">\
						<span class="ui-icon ui-icon-arrow-4-diag zoom"></span>\
					</div>\
				</div>\
				<div class="menu">\
					<div class="fontcolor" style="display: none;"></div>\
					<div class="fontsize" style="display: none;">\
						<a title="1" href="javascript: void(0)"><font size="1">1号字体</font></a>\
						<a title="2" href="javascript: void(0)"><font size="2">2号字体</font></a>\
						<a title="3" href="javascript: void(0)"><font size="3">3号字体</font></a>\
						<a title="4" href="javascript: void(0)"><font size="4">4号字体</font></a>\
						<a title="5" href="javascript: void(0)"><font size="5">5号字体</font></a>\
						<a title="6" href="javascript: void(0)"><font size="6">6号字体</font></a>\
						<a title="7" href="javascript: void(0)"><font size="7">7号字体</font></a>\
					</div>\
					<div class="link" style="display: none">\
						网址：<input type="text" size="54" value="" class="href" /> <br />\
						文字：<input type="text" size="54" value="" class="text" /> <br />\
						<input type="button" value="插入" style="margin-top: 8px; margin-left: 32px;" class="insert" />\
						<input type="button" value="关闭" style="margin-top: 8px; margin-left: 4px;" class="close" />\
					</div>\
					<div class="video" style="display: none">\
						视频网址：<input type="text" size="64" value="" /> <br /><input type="button" value="插入" class="insert" style="margin-top: 8px; margin-left: 2px;" /> <img src="' + _this.settings.baseurl + 'loading.gif" width="18" height="18" class="loading" style="display: none;" /><input type="button" value="关闭" class="close" style="margin-top: 8px; margin-left: 2px;" /><span class="grey">【提示】：请粘贴以 .swf 结尾的网址。</span>\
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
		_doc.write('<html style="overflow: hidden;"><head>'+baseadd+'<meta content="text/html; charset=UTF-8" http-equiv="Content-Type"/><link rel="stylesheet" href="' + _this.settings.baseurl + 'iframe.css"/></head><body spellcheck="false" style="overflow: auto;">'+$(textarea).val()+'</body></html>');
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
			$(this).xn_menu($('.fontsize', menu), 0, 5000);
		});
		$('.fontsize a', menu).click(function() {
			var size = $(this).attr('title');
			_this.set_fontsize(size);
		});
		
		$('.fontcolor', toolbar).click(function() {
			//_this.save_selection();
			_this.hide_menu();
			Jcolor(this).color(function(color) {
				_this.restore_selection();
				_this.set_fontcolor(color.substr(1, color.length));
			}, function(id) {
				//alert('close :'+ id);
			}, _this.settings.baseurl);
			$(this).xn_menu($('.fontcolor', menu), 0, 5000);
		});
		
		// start link
		var rangelink = null;
		var trange = null;
		$('a.link', toolbar).click(function() {
			_win.focus();
			_this.hide_menu();
			trange = _this.get_range();
			var pelement = $.browser.msie ? trange.parentElement() : trange.startContainer;
			rangelink = find_pnode(pelement, function(o) {
				if(typeof o == 'undefined') return false;
				return !!o && !!o.tagName && o.tagName.toLowerCase() == 'a'}
			);
			var href = rangelink ? rangelink.href : 'http://';
			var rangetext = trange ? ($.browser.msie ? trange.text : trange.toString()) : '链接';
			var linktext = rangelink ? rangelink.innerHTML : rangetext;
			$('div.link input.href', menu).val(href);
			$('div.link input.text', menu).val(linktext);
			$(this).xn_menu($('.link', menu), 0, 10000);
		});
		$('div.link input.insert', menu).click(function() {
			$(rangelink, _doc).remove();
			var href = $('.link input.href', menu).val();
			var linktext = $('.link input.text', menu).val();
			var s = href ? '<a href="'+href+'" target="_blank">'+linktext+'</a>' : linktext;
			_this.paste(s, true);
		        _this.hide_menu();
		        _this.save();
		});
		$('div.link input.close', menu).click(function() {
			 _this.hide_menu();
		});
		// end link

		$('a.justifyleft', toolbar).click(function() {_this.exec_cmd('JustifyLeft');});
		$('a.justifycenter', toolbar).click(function() {_this.exec_cmd('JustifyCenter');});
		$('a.justifyright', toolbar).click(function() {_this.exec_cmd('JustifyRight');});
		$('a.justifyfull', toolbar).click(function() {_this.exec_cmd('JustifyFull'); _this.clear_format();});
		$('a.marklist', toolbar).click(function() {_this.exec_cmd('InsertUnorderedList');});
		$('a.numberlist', toolbar).click(function() {_this.exec_cmd('InsertOrderedList');});
		$('a.insertcode', toolbar).click(function() {_this.add_code();});
		
		//--------------> 生成上传flash对象，支持多实例
		var upload_button_id   = textarea.id + '_upload_button';
		var upload_progress_id = textarea.id + '_progress_button';
		var upload_cancel_id   = textarea.id + '_cancel_button';
		var upload_swf_id   = textarea.id + '_swf_id';
		
		// toolbar image 插入 span
		$('a.image', toolbar).append('<div id="' + upload_button_id + '" style=" width: 49px; height: 22px; "></div>');
		$('a.image', toolbar).append('<span id="' + upload_progress_id + '" style="display: none;"></span>');
		$('a.image', toolbar).append('<input id="' + upload_cancel_id + '" type="button" value="Cancel" onclick="swfu.cancelQueue();" disabled="disabled" style="margin-left: 2px; font-size: 8pt; height: 29px; display: none" />');
		
		
		$('a.video', toolbar).click(function() {
			//_this.save_selection();
			_this.hide_menu();
			$(this).xn_menu($('.video', menu), 0, 5000);
			$('.video input[type=text]', menu).focus();
		});
		$('.video input.insert', menu).click(function() {
			var url = $('.video input[type=text]', menu).val();
			_this.add_video(url);
			return true;
		});
		$('.video input.close', menu).click(function() {
			_this.hide_menu();
			return true;
		});
		
		$('a.fontcolor', toolbar).click(function() {
			_this.hide_menu();
			$(this).xn_menu($('.fontcolor', menu), 0, 5000);
		});
		
		// 最后加载表情
		$(function() {
			var facehtml = '';
			for(var i=1; i<=30; i++) facehtml += '<a href="javascript: void(0)"><img src="' + _this.settings.baseurl + i + '.gif" /></a>';
		
			$('div.face', menu).html(facehtml);
			$('a.face', toolbar).click(function() {
				//_this.save_selection();
				_this.hide_menu();
				$(this).xn_menu($('.face', menu), 0, 5000);
			});
			$('div.face a', menu).click(function() {
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
		
		$('.zoom', footer).click(function() {
			_this.zoom();
		});
		
		if($.browser.msie || $.browser.safari) {
			$(_doc).keypress(function(e) {return _this.fix_ie_br(e);});
		}
		
		$(_doc).keyup(function(e) {
			if(settings.onctrlenter) {
				if(e.ctrlKey && e.which == 13 || e.which == 10) {
					settings.onctrlenter();
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
		//$(_doc).keydown(function(e) {return _this.fix_tab(e);});
		
		// ie / ff onpaste 不一样，jquery 已经帮我们解决。
		$(_body).bind('paste',  function() {
			setTimeout(function() {_this.save();}, 200);
		});
		
		// 加载完成以后
		$(function() {
			_this._focus();
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
			var parentdialog = $(textarea).closest('div.dialog').get(0);
			if(parentdialog) {
				pdialog = parentdialog.dialog;
				pdialog.settings.onmin = _this.settings.onmin;
				pdialog.settings.onmax = _this.settings.onmax;
			}
		}
		
		// 仅针对IE
		if($.browser.msie) {
			$(_doc).bind('beforedeactivate', function() {
				_this.save_selection();
			});
		}
		return true;
	}
	
	// width 可能为 98% 1024 两种数据格式
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
		$(textarea).height(height - 19);	// 19 为 footer 高度
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
				$('a.fontcolor', _this.toolbar).css('backgroundColor', '#' + value);
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
		if(_issource) {
			$(_body).html(s);
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
		if(_issource) {
			textarea.focus();
		} else {
			if($.browser.msie) {
				_iframe.focus();
			} else if($.browser.safari) {
				_iframe.focus();
			} else if($.browser.mozilla) {
				_iframe.focus();
			} else {
				_iframe.focus();
			}
		}
	}
	
	this.ie_get_range_move_size = function(s) {
		var js = $('<div>'+s+'</div>');
		var n = js.text().length;
		var nimg = $('img', js).length;
		if($.browser.msie && ($.browser.version == "9.0")) {
			n += nimg * 2;
		} else {
			n += nimg;// 图片长度占用2个字符
		}
		return n;
	}
	
	this.paste = function(s, select) {
		_win.focus();
		if($.browser.msie) {
			try {
				var range = _this.restore_selection();
			} catch(e) {
				var range = _doc.selection.createRange();
			}
			range.execCommand('Delete');
			try {
				range.pasteHTML(s);
			} catch(e) {
				alert('操作失败，请您尽量选中连续区域再试试。');
			}
			if(select) {
				var n = _this.ie_get_range_move_size(s);
				range.moveStart('character', -n);
				range.select();
			}
		} else {
			if(select) {
				var sel = _win.getSelection();
				var range = sel.getRangeAt(0);
				var old = range.cloneRange();
				range.deleteContents();
				var newnode = range.createContextualFragment(s + '<span id="range_start" width="0" height="0" ></span>');
				range.insertNode(newnode);
				
				range.selectNode($('#range_start', _doc)[0]);
				sel.removeAllRanges();
				range.setStart(old.startContainer, old.startOffset); // 起始点向前移动到最初的点
				sel.addRange(range);
				$('#range_start', _doc).remove();
			} else {
				_doc.execCommand('InsertHtml' , '' , s );
			}
				
			/* bad
			range.setStartBefore($('#range_start')[0]);
			range.setEndAfter($('#range_end')[0]);
			*/
			
			/* bad
			range.setStart(old.startContainer, old.startOffset);
			range.setEnd(old.endContainer, old.endOffset);
			*/
		}
		
		// 延迟保存?
		_this.save();
	}
	
	this.set_fontcolor = function(color) {
		
		// 设置选中状态的字体颜色
		_this.restore_selection();
		_this.exec_cmd('ForeColor', '#' + color);
		$('a.fontcolor', _this.toolbar).css('backgroundColor', '#' + color);
		
		// 隐藏控件
		//$('.fontcolor', _this.menu).hide();
	}
	
	this.set_fontsize = function(size) {
		_this.exec_cmd('FontSize', size);
		$('a.fontsize', _this.toolbar).html(size ? size + '号字体' : '字体大小');
		
		// 隐藏控件
		$('.fontsize', _this.menu).hide();
	}
	
	this.add_link = function(url) {
		_this.paste('<a href="' + url + '" target="_blank">' + url + '</a>');
		
		// 初始化内容
		$('.link input[type=text]', _this.menu).val('');
		
		// 隐藏控件
		$('.link', _this.menu).hide();
	}
	
	this.add_video = function(url) {
		
		var sep = _this.settings.videoupload.indexOf('?') == -1 ? '?' : '&';
		var requesturl = _this.settings.videoupload + sep + 'url=' + encodeURIComponent(url);
		
		// loading
		$('.video .loading', menu).show();
		$('.video input[type=button]', menu).attr('disabled', true);
		$.get(requesturl, null, function(s) {
			var json = json_decode(s);
			if(error = json_error(json)) {
				$('.video .loading', menu).hide();
				$('.video input[type=button]', menu).attr('disabled', false);
				alert('返回图片 URL 格式有误，返回数据：' + json);
				return false;
			}
			if(json.status <= 0) {
				_this.paste('<a href="' + url + '" target="_blank">' + url + '</a>');
				return false;
			} else {
				
				// 直接视频播放代码 <object ...>
				_this.paste(json.message);
				// 初始化内容
				$('.video input[type=text]', _this.menu).val('');
				
				// 隐藏控件
				$('.video', _this.menu).hide();
			}
		});
		return true;
	};
	
	this.add_code = function() {
		if($.browser.safari) {
			var s = '<br /><div class="code"><p>&nbsp;</p></div><br />';
		} else {
			var s = '<br /><div class="code"><p></p></div><br />';
		}
		_this.paste(s);
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
		_this._focus();
	}
	
	this.zoom = function() {
		// 放大编辑器
		$(_iframe).height($(_iframe).height() + 150);
		$(textarea).height($(textarea).height() + 150);
		if($(_iframe).width() < 960) {
			$(_iframe).width(960);
			$(textarea).width(960);
		}
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
	
	
	this.upload_file_queued = function(file) {
		//alert('upload_file_queued');
		
	}
	this.upload_queue_error = function(file, errorCode, message) {
		try { 
			switch (errorCode) { 
			case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
				alert("请选择小于 "+_this.settings.filesizelimit+" 的图片");
				$('.toolbar a.imageloading', $(_this.editor)).hide();
				_this.upload_queue_error = 1;
				break;
			}
			return false;
		} catch (ex) { 
			this.debug(ex);
		}
		return false;
	}
	this.upload_dialog_complete = function(numFilesSelected, numFilesQueued) {
		// 保存IE selection 鼠标选取
		//_this.save_selection();
		try {
			if (numFilesSelected > 0) {
				document.getElementById(this.customSettings.cancelButtonId).disabled = false;
				//$('a.image', toolbar).hide();				// hide() 后导致 swfupload 错误!!!
				//$('.toolbar a.image', $(_this.editor)).width(0);	// hide() 后导致 swfupload 错误!!!
				if(_this.upload_queue_error == 0) { 
					$('.toolbar a.imageloading', $(_this.editor)).html('<img src="' + _this.settings.baseurl + 'loading.gif" width="18" height="18" /><span class="imageprocess"><span class="imageprocess_body"></span></span>').show();
				}
				_this.upload_queue_error = 0;
			}
			
			/* I want auto start the upload and I can do that here */
			this.startUpload();
		} catch (ex)  {
	       		this.debug(ex);
		}
	}
	this.upload_start = function(file) {
		if(_this.upload_queue_error == 0) { 
			$('.toolbar a.imageloading', $(_this.editor)).html('<img src="' + _this.settings.baseurl + 'loading.gif" width="18" height="18" /><span class="imageprocess"><span class="imageprocess_body"></span></span>').show();
		}
		_this.upload_queue_error = 0;
		return true;
	}
	this.upload_progress = function(file, bytesLoaded, bytesTotal) {
		var w = Math.ceil((bytesLoaded / bytesTotal) * 26);
		$('span.imageprocess_body', editor).width(w);
	}
	this.upload_success = function(file, serverData) {
		var r = $.parseJSON(serverData);
		if(!r) {
			alert('返回数据格式有误：' + serverData);
			return true;
		}
		if(r.servererror) {
			alert(file + ':服务端错误:' + r.servererror);
			return true;
		}
		if(r.status == 0) {
			alert(r.message);
			return true;
		}
		if(r.status == 1) {
			var s = r.message;
			_this.paste(s);
			return true;
		}
	}
	this.upload_error = function(file, errorCode, message) {
		
	}
	
	this.upload_complete = function(file) {
	}
	
	this.upload_queue_complete = function(numFilesUploaded) {
		$('div.toolbar a.image', editor).width(49);
		$('div.toolbar a.imageloading', editor).hide();
	}
	
	return this;
}
	
}