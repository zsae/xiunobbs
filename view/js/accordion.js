/*
* Copyright (C) xiuno.com
*/

$.fn.accordion = function (settings) {
	//设置默认值；1.默认是否全部展开,
	settings = $.extend({
		allshow : false,
		numul   : 0
	}, settings);
	var jtitle = $("div > h3", this);
	var jbody = $("ul",this);

	if(settings.allshow){
		jtitle.next().show();
	} else {
		jbody.css({display:"none"});
		jbody.eq(settings.numul).show();
	}
	
	jtitle.click(function () {
		var jh3 = $(this);
		if(!settings.allshow) {
			jbody.not(jh3.next()).slideUp(200);
			jh3.next().slideToggle(200);
		} else {
			$('ul', jh3.parent()).toggle('fast');
		}
	});
}

