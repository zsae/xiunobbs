
/*
 * Copyright (C) xiuno.com
 */

/*
	功能：基于JQuery 的 JSTree
		支持 ajax loading
		无限级别分类
		支持checkbox 多选
		支持添加删除节点（节点分隐式为 fold 和 doc，有 node[1] 控制）
		优化了的递归算法
	作者：axiuno@gmail.com
	日期：2012/2/13
	备注：
	修改：1. 针对IE6需要 fix class=isopen > 不支持
	用法：
		<div id="div1"></div>
		
		<script src="tree_data.js" type="text/javascript"></script>
		<script type="text/javascript">
		$('#div1').tree(json, {});
		</script>
		
*/

// 重复加载
if(!$.tree) {
	
// 真正的 tree 类, 
$.tree = function(div, json, settings) {
	this.json = json;
	_this = this;
	this.maxcateid = settings.maxcateid;	// 编辑模式用
	this.init = function() {
		var s = '<ul><li cateid="0" parentid="-1" id="'+_this._id('0')+'" ><span class="ticon line_top"></span><span class="ticon doc"></span><span class="text">根节点</span>' + (settings.mode == 'edit' ? '<span class="ticon add"></span>' : '');
		s += '<ul>';
		for(var i=0; i<json.length; i++) {
			var islast = json.length == i + 1;
			var space = '';
			s += this.recusive_construct(json[i], islast, space);
		}
		s += '</ul></li></ul>';
		$(div).html(s);
		
		// 追加事件，在 li 上追加事件，checkbox 需要返回 false.
		var jli = $('li', div);
		$('span.plus, span.book, span.text', div).live('click', function() {
			var _li = $(this).parent();
			// 排除根节点
			if(_li.attr('parentid') != -1) {
				_this.toggle_node(_li);
			}
			
			// 最后一级的 fold 才能被选择！ 
			if(_li.attr('cateid') != 0 && _li.attr('haschild') == '0' && _li.hasClass('isopen')) {
				if(settings.on_selected) settings.on_selected(_li, _this);
			}
			return true;
		});
		
		// checkbox 模式
		if(settings.mode == 'checkbox') {
			$('input[type=checkbox]', div).live('click', function() {
				var _li = $(this).parent();
				_this.toggle_node(_li);
			});
		}
		
		/*
		$('span.line, span.line_middle, span.line_bottom').live('click', function(e) {
			e.stopPropagation();
		});*/
		
		// 编辑模式下的事件
		if(settings.mode == 'edit') {
			/*
			$('input[type=text]', jli).live('click', function(e) {
				e.stopPropagation();
			});*/
			
			$('input[type=text]', jli).live('keyup', function() {
				var pcateid = $(this).parent().attr('parentid');
				var cateid = $(this).parent().attr('cateid');
				var catename = $(this).val();
				if(settings.on_update) {
					settings.on_update(cateid, pcateid, catename);
				}
				return false;
			});
			
			$('span.add', jli).live('click', function() {
				var pcateid = $(this).parent().attr('cateid');
				var cateid = ++_this.maxcateid;
				var catename = $('>input[type=text]', $(this)).val();
				_this.add_node(pcateid, ['', cateid, pcateid, cateid]);
				return false;
			});
			
			$('span.delete', jli).live('click', function() {
				var cateid = $(this).parent().attr('cateid');
				if(window.confirm('您确定删除吗？')) {
					// recall
					if(settings.on_delete) {
						if(settings.on_delete(cateid)) {
							_this.remove_node(cateid);
						} else {
							alert('删除失败！');
						}
					}
				}
				return false;
			});
			
			$('span.top', jli).live('click', function() {
				var jli1 = $(this).parent();
				var jli2 = jli1.prev();
				if(jli2.length == 0 || jli2.attr('nodeName') != 'LI') {
					return false;;
				}
				var cateid1 = jli1.attr('cateid');
				var cateid2 = jli2.attr('cateid');
				if(cateid1 > 0 && cateid2 > 0) {
					
					// 交换节点，交换 rank, before() after() 替代 swap()
					var rank = jli1.attr('rank');
					jli1.attr('rank', jli2.attr('rank'));
					jli2.attr('rank', rank);
					jli2.before(jli1);
					
					if(settings.on_top) {
						settings.on_top(cateid1, cateid2);
					}
				}
				return false;
			});
			
			
		}
		
		// 初始化打开状态
		this.init_open(jli);
		
		return true;
	};
	
	this.init_open = function(jli) {
		var arr = $.parseJSON($.pdata('tree_status'));
		if(!arr) return false;
		for(var i=0; i<arr.length; i++) {
			// 不需要递归向上逐层打开
			var nodeid = _this._id(arr[i]);
			$('#' + nodeid).addClass('isopen');
			$('>ul, >ul li', $('#' + nodeid)).show();
		}
	};
	
	// 递归构造树，递归完成后在决定打开状态，此处不构造文本节点，仅仅构造 fold 节点
	// space 不需要逐层递归获取，传参构造树会更快一些。
	this.recusive_construct = function(node, islast, space) {
		
		// 默认展开第一层
		var display = node[2] == 0 ? '' : 'style="display:none"';
		
		// 是否有孩子
		var haschild = node[4] && node[4].length > 0 ? '1' : '0';
		
		// nodeid
		var nodeid = _this._id(node[1]);
		
		// 构造节点HTML
		var s = '<li '+(islast ? 'class="islast"' : '')+' cateid="'+node[1]+'" parentid="'+node[2]+ '" haschild="'+haschild+'" rank="'+node[3]+'" id="'+nodeid+'" '+display+'>';
		s += space;
		s += '<span class="ticon plus"></span>';
		s += '<span class="ticon book"></span>';
		if(settings.mode == 'checkbox') {
			if(haschild == '0') s += '<input type="checkbox" value="'+node[1]+'" />';
			s += '<span class="text">'+node[0]+'</span>';
		} else if(settings.mode == 'edit') {
			s += '<input type="text" size="16" value="'+node[0]+'" />';
			s += '<span class="ticon add"></span>';
			s += '<span class="ticon delete"></span>';
			s += '<span class="ticon top"></span>';
		} else {
			s += '<span class="text">'+node[0]+'</span>';
		}
		
		// 遍历子节点
		if(haschild == '1')  {
			s += '<ul>';
			var spacex = islast ? '<span class="ticon space"></span>' : '<span class="ticon line"></span>';
			for(var i=0; i<node[4].length; i++) {
				islast = node[4].length == i + 1;
				s += this.recusive_construct(node[4][i], islast, space + spacex);
			}
			s += '</ul>';
		}
		s += '</li>';
		return s;
	};
	
	// 节点分为两种类型， fold 才能打开
	this.toggle_node = function(li, isopen, recusive) {
		//var haschild = $(li).attr('haschild') == '1';
		var status = typeof isopen != 'undefined' ? isopen : !$(li).hasClass('isopen');
		
		// open
		if(status) {
			$(li).addClass('isopen');
			$('>ul, >ul li', li).show();
		// close
		} else {
			$(li).removeClass('isopen');
			$('>ul, >ul li', li).hide();
		}
		// 保存状态
		var cateid = parseInt($(li).attr('cateid'));
		if(!isNaN(cateid))$.pdata_keep('tree_status', cateid, status);
		
		// 递归打开，非常影响速度！约定最外层为 div
		if(typeof recusive != 'undefined' && recusive) {
			var pli = $(li).parent().parent();
			if(pli.length > 0 && pli.attr('nodeName') != 'DIV') {
				this.toggle_node(pli, isopen, recusive);
				return;
			}
		}
	};
	
	// 添加孩子节点，节点类型由 node[1] 是否为空决定，为空则为 fold, 否则为 doc
	/*
		增加 fold 节点：
			add_node(126, ['节点', 127, 126]);
		增加 doc 节点: (doc 节点 ajax 请求获得，不记录状态)
			add_node(126, ['节点', 0, 126]);
	*/
	this.add_node = function(cateid, node) {
		var nodeid = _this._id(cateid);
		var jli = $('#'+nodeid);
		// 不是根节点
		if(jli.attr('cateid') == '0' && jli.attr('parentid') != '-1') return;
		
		// fold 类型的节点 ( parentid = -1 为根节点)
		
		var rli = null;//返回的 li
		// fold
		if(node[1]) {
			// 如果没有孩子
			if(jli.attr('haschild') == '0') {
				// 克隆父节点，我们需要它的空白，不需要 <li> 本身的属性等等
				var islast = jli.hasClass('islast');
				var cloneli = jli.clone();
				if(settings.mode != 'edit') {
					$('span.text', cloneli).html(node[0]);
				} else {
					$('input[type=text]', cloneli).val(node[0]).focus();
				}
				
				// 增加空白	
				var space = islast ? '<span class="ticon space"></span>' : '<span class="ticon line"></span>';
				$(space).insertBefore($('span.plus', cloneli));
				
				// 插入到 jli
				var nodeid = _this._id(node[1]);
				var s = '<li class="islast" cateid="'+node[1]+'" rank="'+node[3]+'" parentid="'+node[2]+'" haschild="0" id="'+nodeid+'">';
				s = '<ul>' + s + cloneli.html() + '</li></ul>';
				var t = $(s).appendTo(jli);
				rli = $('>ul >li', t);
				
				// 改变 jli 状态
				jli.attr('haschild', '1');
			// 如果有孩子
			} else {
				// 克隆最后一个孩子节点，交换 islast
				var jlast = $('>ul >li:last-child', jli);
				var cloneli = jlast.clone();
				$('ul', cloneli).remove();			// 清空节点
				if(settings.mode != 'edit') {
					$('span.text', cloneli).html(node[0]);
				} else {
					$('input[type=text]', cloneli).val(node[0]).focus();
				}
				
				// 插入到 jli
				var nodeid = _this._id(node[1]);
				var s = '<li class="islast" cateid="'+node[1]+'" rank="'+node[3]+'" parentid="'+node[2]+'" haschild="0" id="'+nodeid+'">' + cloneli.html() + '</li>';
				rli = $(s).appendTo(jlast.parent());
				
				// 改变 jli 状态
				jlast.removeClass('islast');	
			}
		// doc 节点
		} else {
			// 如果没有孩子, line_middle line_bottom (line_top 仅供 root 使用)
			if(jli.attr('haschild') == '0') {
				// 克隆父节点，我们需要它的空白，不需要 <li> 本身的属性等等
				var islast = jli.hasClass('islast');
				var cloneli = jli.clone();
				$('span.text', cloneli).html(node[0]);					// 改变节点html内容
				$('span.plus', cloneli).removeClass('plus').addClass('line_bottom');	// 替换 plus -> line
				$('span.book', cloneli).remove();					// 去掉 book
				
				// 增加空白
				var space = islast ? '<span class="ticon space"></span>' : '<span class="ticon line"></span>';
				$(space).insertBefore($('span.line_bottom', cloneli));
				
				// 插入到 jli
				var nodeid = _this._id(node[1]);
				var s = '<li class="islast" cateid="0" parentid="'+node[2]+'" haschild="0">';
				s = '<ul>' + s + cloneli.html() + '</li></ul>';
				
				
				var jnew = $(s).appendTo(jli);
				rli = $('>ul >li', jnew);
				/*
				$('span.text a', jnew).click(function(e) {
					e.stopPropagation();
				});
				*/
				// 改变 jli 状态
				jli.attr('haschild', '1');
			} else {
				// 克隆最后一个孩子节点，交换 islast
				var jlast = $('>ul >li:last-child', jli);
				var cloneli = jlast.clone();
				$('span.text', cloneli).html(node[0]);					// 改变节点html内容
				$('span.plus', cloneli).removeClass('plus').addClass('line_bottom');	// 替换 plus -> line
				$('span.book', cloneli).remove();
				
				// 插入到 jli
				var nodeid = _this._id(node[1]);
				var s = '<li class="islast" cateid="'+node[1]+'" parentid="'+node[2]+'" haschild="0">' + cloneli.html() + '</li>';
				
				var jnew = $(s).appendTo(jlast.parent());
				rli = jnew;
				/*
				$('span.text a', jnew).click(function(e) {
					e.stopPropagation();
				});
				*/
				// 改变 jli 状态
				$('span.line_bottom', jlast).removeClass('line_bottom').addClass('line_middle');	
			}
		}
		
		/*
		// 是否隐藏节点
		if(!jli.hasClass('isopen')) {
			$('>ul, >ul >li', jli).hide();
		}
		*/
		
		// 打开节点
		_this.toggle_node(jli, true);
		
		return rli;
		
	}
	
	this.remove_child = function(cateid) {
		var nodeid = _this._id(cateid);
		var jli = $('#'+nodeid);
		$('ul', jli).remove();
		jli.attr('haschild', '0');
	}
	
	this.remove_node = function(cateid) {
		var nodeid = _this._id(cateid);
		var jli = $('#'+nodeid);
		// 判断是否为最后一个
		if(jli.hasClass('islast')) {
			jli.prev().addClass('islast');	
		}
		jli.remove();
		
	}
	
	this._id = function(cateid) {
		return div.id + '_' + cateid;
	}
	
	// 返回格式： [1,2,3]
	this.get_checked_values = function() {
		var r = {'cateids':[], 'catenames':[]};
		$('input[type=checkbox]:checked', div).each(function() {
			r.cateids[r.cateids.length] = $(this).attr('value');
			r.catenames[r.catenames.length] = $(this).next().html();
		});
		return r;
	}
	
	this.close_all = function() {
		$('li', div).each(function() {
			if($(this).attr('parentid') != -1) {
				_this.toggle_node(this, false);
			}
		});
	}
	
	return this;
};

$.fn.tree = function(json, settings) {
	settings = $.extend({
		success: null,
		mode: null
	}, settings);
	
	this.each(function() {
		if(!$.nodeName(this, 'DIV')) return;
		var t = new $.tree(this, json, settings);
		if(t.init()) {
			this.tree = t;
		}
	});
	
	return this;
};


}