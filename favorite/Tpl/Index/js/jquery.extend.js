/**
 * jquery弹窗插件
 * @author wenbin.wu@foxmail.com
 * @copyright 2012
 */
 
(function($) {
	/**
	 * 回车事件
	 * @param callback 回调函数
	 */
	$.fn.enter = function(callback) {
		//keyup事件
        return this.each(function() {
            $(this).keyup(function(e) {
                e = window.event || e;
                if(e.keyCode == 13) callback(this);
            });
        });
    };
	
	/**
	 * 绝对定位
	 * @param options.x 左上角x坐标
	 * @param options.y 左上角y坐标
	 * @param options.ex  x轴偏移量
	 * @param options.ey  y轴偏移量
	 */
	$.fn.fixed = function(options) {
		var default_options = {'x': 0, 'y': 0, 'ex': 0, 'ey': 0};
		options = $.extend({}, default_options, options);
		return this.each(function() {
			var $this = $(this);
			
			if(options.x == 'center') {
				options.x = ($(window).width() - $this.width()) /2;
			} else if(options.x == 'right') {
				options.x = $(window).width() - $this.width();
			} else {
				options.x = parseInt(options.x);
			}
			
			if(options.y == 'center') {
				options.y = ($(window).height() - $this.height()) / 2;
			} else if(options.y == 'bottom') {
				options.y = $(window).height() - $this.height();
			} else {
				options.y = parseInt(options.y);
			}
			//偏移量
			options.x = options.x + parseInt(options.ex);
			options.y = options.y + parseInt(options.ey);
			
			var dom = $(this)[0];
			if($.browser.msie && $.browser.version < 7) {
				$this.css({'position': 'absolute'});
				dom = '(document.documentElement || document.body)';
				$this[0].style.setExpression('left', 'eval(('+dom+').scrollLeft+'+options.x+')+"px"');
				$this[0].style.setExpression('top', 'eval(('+dom+').scrollTop+'+options.y+')+"px"');
				$('html').css({'background-image': 'url(about:blank)'});
				$('html').css({'background-attachment': 'fixed'});
			} else {
				$this.css({'position': 'fixed', 'left': options.x+'px', 'top': options.y+'px'});
			}
		});
	};
	
	/**
	 * 转义特殊字符
	 * @param str 输入字符串
	 * @return string
	 */
	function htmlspecialchars(str) {  
		str = str.replace(/&/g, '&amp;');
		str = str.replace(/</g, '&lt;');
		str = str.replace(/>/g, '&gt;');
		str = str.replace(/"/g, '&quot;');
		str = str.replace(/'/g, '&#039;');
		return str;
	}
	
	/** 
	 * 弹出窗口 
	 * @param url 请求url地址
	 * @param wait 等待时显示内容
	 */
	$.box = {}; //窗口对象
	$.box.pop = function(data) {
		//删除已有窗口
		$('#_box_bg_').remove();
		$('#_box_').remove();
		
		/** 遮盖层 */
		var _box_bg = $('<div id="_box_bg_"></div>');
		_box_bg.css({
			'width': $(window).width(),
			'height': $(window).height(),
			'background': 'black',
			'filter': 'alpha(opacity=0)',
			'opacity': '0'
		});
		$('body').append(_box_bg);
		_box_bg.fixed();
		
		var _box = $('<div id="_box_"></div>');
		_box.css({'position': 'absolute', 'visibility': 'hidden'});
		_box.html(data);
		$('body').append(_box);
		_box.fixed({x:'center', y:'center'});
		setTimeout(function() {
			_box.css({'visibility': 'visible'});
		}, 100);
		
		//绑定窗口调整事件
		$(window).resize(function() {
			$('#_box_bg_').css({
				'width': $(window).width(),
				'height': $(window).height(),
				'background': 'black',
				'filter': 'alpha(opacity=0)',
				'opacity': '0'
			});
			$('#_box_bg_').fixed();
			$('#_box_').fixed({x:'center',y:'center'});
		});
	};
	/**
	 * 关闭窗口
	 * @return void
	 */
	$.box.close = function() {
		$('#_box_bg_').remove();
		$('#_box_').remove();
	};
	
	/**
	 * 等待窗口
	 * @return void
	 */
	$.box.pop.wait = function () {
		var msg = '<div style="width: 100px; height: 22px; background: #DDDDDD; color: gray;'
		+'font: normal 12px/22px Verdana; text-align: center; border-radius: 4px;">Loading ...</div>';
		$.box.pop(msg);
	}
	
	/**
	 * Ajax UI
	 * @var object
	 */
	$.box.pop.ui = {};
	$.box.pop.ui.data = {}; //数据对象
	/** UI包裹容器 */
	$.box.pop.ui.wrap = ''
+		'<table class="ui" cellpadding="0" cellspacing="0">'
+			'<tr>'
+				'<td class="box-top-left"></td>'
+				'<td class="box-top"></td>'
+				'<td class="box-top-right"></td>'
+			'</tr>'
+			'<tr>'
+				'<td class="box-left"></td>'
+				'<td class="box-inner">'
+				'{html}'
+				'</td>'
+				'<td class="box-right"></td>'
+			'</tr>'
+			'<tr>'
+				'<td class="box-bottom-left"></td>'
+				'<td class="box-bottom"></td>'
+				'<td class="box-bottom-right"></td>'
+			'</tr>'
+		'</table>'
	;
	/** UI标题栏 */
	$.box.pop.ui.title = ''
+		'<div class="header">'
+			'<h2 id="__title__">{title}</h2>'
+			'<a id="__close__" href="javascript:void(0)" onclick="$.box.close()">×</a>'
+		'</div>'
	;
	/** UI弹出内容一般函数 */
	$.box.pop.ui.show = function(html, title) {
		html = $.box.pop.ui.wrap.replace('{html}', $.box.pop.ui.title.replace('{title}', title)+html);
		$.box.pop(html);
	}
	/** 操作:提示信息 */
	$.box.pop.ui.msg = function(msg) {
		html = '<div class="tip">'+msg+'</div>';
		$.box.pop.ui.show(html, '系统提示');
	}
	/** 操作:关于一切 */
	$.box.pop.ui.version = function() {
		html = '<div class="tip">— 只言片语 —<br />网址收藏有别于网址导航，网址收藏由用户自助操作、更新，形成一个强大的网址库。<br />使用本站搜索即可快速搜到你想要的网址。赶紧收藏网址吧，众人拾柴火焰高！<br />— 特别强调 —<br />网站后台有监控功能，请勿收藏游戏类等公安网规定禁止的网址，否则会被删除或者遮蔽用户！';
		$.box.pop.ui.show(html,'关于一切');
	}
	/** 操作:网站纪实 */
	$.box.pop.ui.about = function() {
		html = '<div class="tip">— 成长历史 —<br />2012年10月16日，专为公安内网设计打造的网址收藏站上线。版本号V1.0<br />— 小小团队 —<br />服务器运行保障：昆明市官渡分局  李雪松 ←→ 网站制作维护：南宁市马山县局 黄  军  </div>';
		$.box.pop.ui.show(html, '网站纪实');
	}
	/** 操作:用户登录 */
	$.box.pop.ui.login = function(submit) {
		if(submit) {
			var data = {};
			data.usr = $('input[name="usr"]').val();
			data.pwd = $('input[name="pwd"]').val();
			data.verify = $('input[name="verify"]').val();
			$.box.pop.wait();
			$.post($.box.pop.ui.data.loginUrl, data, function(log) {
				if(log.status) {
					location.reload();
				} else {
					$.box.pop.ui.msg('提示：'+log.info);
					$("#__close__").after('<a onclick="$.box.pop.ui.login()" href="javascript:void(0)">×</a>').remove();
				}
			}, 'json');
			return;
		}
		html = ''
+			'<div class="input">'
+				'<span>用户名：</span>'
+				'<input type="text" name="usr" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+			'</div>'
+			'<div class="input">'
+				'<span>密 码：</span>'
+				'<input type="password" name="pwd" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+			'</div>'
+			'<div class="input">'
+				'<span>验证码：</span>'
+				'<input class="verify" type="text" name="verify" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+				'<span class="verfiy"><a href="javascript:void(0)" onclick="$(\'#verify_img\').attr(\'src\', $.box.pop.ui.data.verifyUrl.replace(\'__T__\', new Date().getTime()));$(this).html($(this).html());"><img id="verify_img" style="height:24px; width:54px;" src="'+$.box.pop.ui.data.verifyUrl.replace('__T__', new Date().getTime())+'" /></a></span>'
+			'</div>'
+			'<div class="input submit">'
+				'<span>&nbsp;</span>'
+				'<a id="submit" class="button" href="javascript:void(0)" onclick="$.box.pop.ui.login(true)">登 录</a>'
+				'<span class="link">'
+					'<a href="javascript:void(0)" onclick="$.box.pop.ui.feed()">忘记密码?</a>'
+				'</span>'
+			'</div>'
		;
		if(parseInt($.box.pop.ui.data.loginStatus)) {
			$.box.pop.ui.msg('提示：你已经登录!');
			setTimeout(function() {location.reload();}, 500);
		}
		else {
			$.box.pop.ui.show(html, '用户登录');
			setTimeout(function() {
				$('input[name="usr"]').focus();
				$("#verify_img").parent().html($("#verify_img").parent().html());
			}, 120);
		}
	}
	/** 操作:用户注册 */
	$.box.pop.ui.register = function(submit) {
		if(submit) {
			var data = {};
			data.usr = $.trim($('input[name="usr"]').val());
			data.pwd = $.trim($('input[name="pwd"]').val());
			data.verify = $.trim($('input[name="verify"]').val());
			var pwd_re = $.trim($('input[name="pwd_re"]').val());

			if(!data.usr.match(/^[0-9a-z]{6,20}$/)) {
				$.box.pop.ui.msg('提示：用户名不得少于6位,且是数字或者字母!');
				$("#__close__").after('<a onclick="$.box.pop.ui.register()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(data.pwd !== pwd_re) {
				$.box.pop.ui.msg('提示：两次输入的密码不一致!');
				$("#__close__").after('<a onclick="$.box.pop.ui.register()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(data.pwd.length < 6) {
				$.box.pop.ui.msg('提示：密码不得少于6位!');
				$("#__close__").after('<a onclick="$.box.pop.ui.register()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(data.verify == '') {
				$.box.pop.ui.msg('提示：请输入验证码!');
				$("#__close__").after('<a onclick="$.box.pop.ui.register()" href="javascript:void(0)">×</a>').remove();
				return;
			}

			$.box.pop.wait();
			$.post($.box.pop.ui.data.registerUrl, data, function(log) {
				if(log.status) {
					$.box.pop.ui.msg('提示：'+log.info);
					$("#__close__").after('<a onclick="location.reload()" href="javascript:void(0)">×</a>').remove();
					setTimeout(function() {location.reload();}, 1000);
					return;
				} else {
					$.box.pop.ui.msg('提示：'+log.info);
					$("#__close__").after('<a onclick="$.box.pop.ui.register()" href="javascript:void(0)">×</a>').remove();
					return;
				}
			}, 'json');
			return;
		}
		var html = ''
+			'<div class="input" style="width: 430px;">'
+				'<span>用户名：</span>'
+				'<input type="text" name="usr" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+				'<span style="width: 180px; text-align: left; padding-left: 10px;">* 不少于<u><b>6</b></u>位，且是<b>数字</b>或<b>字母</b></span>'
+			'</div>'
+			'<div class="input" style="width: 430px;">'
+				'<span>密 码：</span>'
+				'<input type="password" name="pwd" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+				'<span style="width: 180px; text-align: left; padding-left: 10px;">* 不少于<u><b>6</b></u>位</span>'
+			'</div>'
+			'<div class="input" style="width: 430px;">'
+				'<span>重复密码：</span>'
+				'<input type="password" name="pwd_re" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+                               '<span style="width: 180px; text-align: left; padding-left: 10px;">* 与上一栏填写一致</span>'
+			'</div>'
+			'<div class="input">'
+				'<span>验证码：</span>'
+				'<input class="verify" type="text" name="verify" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+				'<span class="verfiy"><a href="javascript:void(0)" onclick="$(\'#verify_img\').attr(\'src\', $.box.pop.ui.data.verifyUrl.replace(\'__T__\', new Date().getTime()));$(this).html($(this).html());"><img id="verify_img" style="height:24px; width:54px;" src="'+$.box.pop.ui.data.verifyUrl.replace('__T__', new Date().getTime())+'" /></a></span>'
+			'</div>'
+			'<div class="input submit">'
+				'<span>&nbsp;</span>'
+				'<a id="submit" class="button" href="javascript:void(0)" onclick="$.box.pop.ui.register(true)">注 册</a>'
+				'<span class="link">'
+					'<a href="javascript:void(0)" onclick="$.box.pop.ui.login()">直接登录!</a>'
+				'</span>'
+			'</div>'
		;
		if($.box.pop.ui.data.loginStatus) {
			$.box.pop.ui.msg('提示：你已经登录!');
			setTimeout(function() {location.reload();}, 500);
		}
		else {
			$.box.pop.ui.show(html, '用户注册');
			setTimeout(function() {
				$('input[name="usr"]').focus();
				$("#verify_img").parent().html($("#verify_img").parent().html());
			}, 120);
		}
		
	}
	/** 操作:用户留言 */
	$.box.pop.ui.feed = function(submit) {
		if(submit) {
			var data = {};
			data.uname = $.trim($('input[name="uname"]').val());
			data.phone = $.trim($('input[name="phone"]').val());
			data.content = $.trim($('textarea[name="content"]').val());
			if(data.uname === '') {
				$.box.pop.ui.msg('提示：姓名不能为空!');
				$("#__close__").after('<a onclick="$.box.pop.ui.feed()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(!data.phone.match(/^[0-9\-\+]+$/)) {
				$.box.pop.ui.msg('提示：电话格式错误!');
				$("#__close__").after('<a onclick="$.box.pop.ui.feed()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(data.content === '') {
				$.box.pop.ui.msg('提示：请填写留言内容!');
				$("#__close__").after('<a onclick="$.box.pop.ui.feed()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			$.box.pop.wait();
			$.post($.box.pop.ui.data.feedUrl, data, function(log) {
				$.box.pop.ui.msg('提示：'+log.info);
				setTimeout(function() {$.box.close();}, 1000);
			}, 'json');
			return;
		}
		var html = ''
+			'<div class="input" style="width: 500px;">'
+				'<span>姓 名：</span>'
+				'<input type="text" name="uname" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+				'<span>电 话：</span>'
+				'<input type="text" name="phone" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+			'</div>'
+			'<div class="input textarea" style="width: 500px;">'
+				'<span>内 容：</span>'
+				'<textarea name="content" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')"></textarea>'
+			'</div>'
+			'<div class="input submit">'
+				'<span>&nbsp;</span>'
+				'<a id="submit" class="button" href="javascript:void(0)" onclick="$.box.pop.ui.feed(true)">留 言</a>'

+				'<span class="link">'
+					'<a href="'+$.box.pop.ui.data.feedSearchUrl+'" target="_blank">查询回复!</a>'
+					'&nbsp;&nbsp;* 忘记密码请在此留言!'
+				'</span>'
+			'</div>'
		;
		$.box.pop.ui.show(html, '用户留言');
		if($.box.pop.ui.data.loginStatus) {
			$('input[name="uname"]').val($.box.pop.ui.data._userData.uname);
			$('input[name="phone"]').val($.box.pop.ui.data._userData.phone);
			setTimeout(function() {$('textarea[name="content"]').focus();}, 120);
		} else {
			setTimeout(function() {$('input[name="uname"]').focus();}, 120);
		}
	}
	/** 编辑模板 */
	$.box.pop.ui.data.postHtml = ''
+		'<div class="input" style="width: 500px;">'
+			'<span>标 题：</span>'
+			'<input class="title" type="text" name="title" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+			'<span class="select">'
+				'<select name="sort"></select>'
+			'</span>'
+		'</div>'
+		'<div class="input textarea" style="width: 500px;">'
+			'<span>网 址：</span>'
+			'<textarea name="url" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')"></textarea>'
+		'</div>'
+		'<div class="input submit">'
+			'<span>&nbsp;</span>'
+			'<a id="__submit__" class="button" href="javascript:void(0)">{submit}</a>'
+			'<span class="link">'
+				'<a href="javascript:void(0)" onclick="_checked()">#私有#</a>'
+				'<em class="_check"></em><input type="hidden" name="_check" value="0" />'
+				'<script type="text/javascript">'
+					'function _checked() {'
+						'var _check = parseInt($(\'input[name="_check"]\').val());'
+						'$("em._check").html(_check ? "" : "√");'
+						'$(\'input[name="_check"]\').val(_check ? 0 : 1);'
+					'}'
+				'</script>'
+			'</span>'
+		'</div>'
	;
	/** 操作:添加收藏 */
	$.box.pop.ui.publish = function(submit) {
		if(submit) {
			var data = {};
			data.title = $.trim($('input[name="title"]').val());
			data.url = $.trim($('textarea[name="url"]').val());
			data.sort = $('select[name="sort"] option:selected').val();
			data.own = $('input[name="_check"]').val();
			if(data.title === '') {
				$.box.pop.ui.msg('提示：标题不能为空!');
				$("#__close__").after('<a onclick="$.box.pop.ui.publish()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(data.url === '') {
				$.box.pop.ui.msg('提示：网址不能为空!');
				$("#__close__").after('<a onclick="$.box.pop.ui.publish()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			$.box.pop.wait();
			$.post($.box.pop.ui.data.linkUrl, data, function(log) {
				$.box.pop.ui.msg('提示：'+log.info);
				if(log.status) {
					$("#__close__").after('<a onclick="$.box.pop.ui.setting()" href="javascript:void(0)">×</a>').remove();
					setTimeout(function() {location.reload();}, 1000);
				}
			}, 'json');
			return;
		}
		var html = $.box.pop.ui.data.postHtml.replace('{submit}', '发 布');
		$.box.pop.ui.show(html, '添加收藏');
		for(var i=0; i<$.box.pop.ui.data.sort.length; i++) {
			$('select[name="sort"]').append('<option value="'+$.box.pop.ui.data.sort[i].sid+'">'+htmlspecialchars($.box.pop.ui.data.sort[i].title)+'</option>');
		}
		$("#__submit__").click(function() {$.box.pop.ui.publish(true);});
	}
	/** 操作:编辑收藏 */
	$.box.pop.ui.edit = function(submit) {
		if(submit && typeof(submit) == 'boolean') {
			var data = {};
			data.title = $.trim($('input[name="title"]').val());
			data.url = $.trim($('textarea[name="url"]').val());
			data.sort = $('select[name="sort"] option:selected').val();
			data.own = $('input[name="_check"]').val();
			data.lid = $('input[name="lid"]').val();
			data.type = 'edit';
			
			if(data.title === '') {
				$.box.pop.ui.msg('提示：标题不能为空!');
				$("#__close__").after('<a onclick="$.box.pop.ui.edit('+data.lid+')" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(data.url === '') {
				$.box.pop.ui.msg('提示：网址不能为空!');
				$("#__close__").after('<a onclick="$.box.pop.ui.edit('+data.lid+')" href="javascript:void(0)">×</a>').remove();
				return;
			}
			$.box.pop.wait();
			$.post($.box.pop.ui.data.linkUrl, data, function(log) {
				$.box.pop.ui.msg('提示：'+log.info);
				if(log.status) {
					$("#__close__").after('<a onclick="$.box.pop.ui.setting()" href="javascript:void(0)">×</a>').remove();
					setTimeout(function() {location.reload();}, 1000);
				}
			}, 'json');
			return;
		}
		var lid = parseInt(submit);
		$.box.pop.wait();
		$.post($.box.pop.ui.data.getLinkUrl, {'lid': lid}, function(log) {
			if(!log.status) {
				$.box.pop.ui.msg(log.info);
				return;
			} else {
				var html = $.box.pop.ui.data.postHtml.replace('{submit}', '保 存');
				$.box.pop.ui.show(html, '编辑收藏');
				$('input[name="title"]').val(log.data.title);
				$('textarea[name="url"]').val(log.data.url);
				for(var i=0; i<$.box.pop.ui.data.sort.length; i++) {
					if(log.data.sort == $.box.pop.ui.data.sort[i].sid) {
						$('select[name="sort"]').append('<option value="'+$.box.pop.ui.data.sort[i].sid+'" selected="selected">'+htmlspecialchars($.box.pop.ui.data.sort[i].title)+'</option>');
					} else {
						$('select[name="sort"]').append('<option value="'+$.box.pop.ui.data.sort[i].sid+'">'+htmlspecialchars($.box.pop.ui.data.sort[i].title)+'</option>');
					}
				}
				if(parseInt(log.data.own)) _checked();
				$("#__submit__").click(function() {$.box.pop.ui.edit(true);});
				$("#__submit__").after('<input type="hidden" name="lid" value="'+lid+'" />');
			}
		}, 'json');
	}
	/** 操作:转发收藏 */
	$.box.pop.ui.save = function(submit) {
		if(submit && typeof(submit) == 'boolean') {
			var data = {};
			data.title = $.trim($('input[name="title"]').val());
			data.url = $.trim($('textarea[name="url"]').val());
			data.sort = $('select[name="sort"] option:selected').val();
			data.own = $('input[name="_check"]').val();
			data.lid = $('input[name="lid"]').val();
			
			if(data.title === '') {
				$.box.pop.ui.msg('提示：标题不能为空!');
				$("#__close__").after('<a onclick="$.box.pop.ui.save('+data.lid+')" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(data.url === '') {
				$.box.pop.ui.msg('提示：网址不能为空!');
				$("#__close__").after('<a onclick="$.box.pop.ui.save('+data.lid+')" href="javascript:void(0)">×</a>').remove();
				return;
			}
			$.box.pop.wait();
			$.post($.box.pop.ui.data.linkUrl, data, function(log) {
				$.box.pop.ui.msg('提示：'+log.info);
				if(log.status) {
					$("#__close__").after('<a onclick="$.box.pop.ui.setting()" href="javascript:void(0)">×</a>').remove();
					setTimeout(function() {location.reload();}, 1000);
				}
			}, 'json');
			return;
		}
		var lid = parseInt(submit);
		$.box.pop.wait();
		$.post($.box.pop.ui.data.getLinkUrl, {'lid': lid}, function(log) {
			if(!log.status) {
				$.box.pop.ui.msg(log.info);
				return;
			} else {
				var html = $.box.pop.ui.data.postHtml.replace('{submit}', '保 存');
				$.box.pop.ui.show(html, '转发收藏');
				$('input[name="title"]').val(log.data.title);
				$('textarea[name="url"]').val(log.data.url);
				for(var i=0; i<$.box.pop.ui.data.sort.length; i++) {
					if(log.data.sort == $.box.pop.ui.data.sort[i].sid) {
						$('select[name="sort"]').append('<option value="'+$.box.pop.ui.data.sort[i].sid+'" selected="selected">'+htmlspecialchars($.box.pop.ui.data.sort[i].title)+'</option>');
					} else {
						$('select[name="sort"]').append('<option value="'+$.box.pop.ui.data.sort[i].sid+'">'+htmlspecialchars($.box.pop.ui.data.sort[i].title)+'</option>');
					}
				}
				$("#__submit__").click(function() {$.box.pop.ui.save(true);});
				$("#__submit__").after('<input type="hidden" name="lid" value="'+lid+'" />');
			}
		}, 'json');
	}
	/** 操作:编辑资料 */
	$.box.pop.ui.setting = function(submit) {
		if(submit) {
			var data = {};
			data.uname = $.trim($('input[name="uname"]').val());
			data.phone = $.trim($('input[name="phone"]').val());
			data.area1 = $('select[name="area1"] option:selected').val();
			data.area2 = $('select[name="area2"] option:selected').val();
			data.area3 = $.trim($('textarea[name="area3"]').val());
			if(data.uname === '') {
				$.box.pop.ui.msg('提示：昵称不能为空!');
				$("#__close__").after('<a onclick="$.box.pop.ui.setting()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			//if(!data.phone.match(/^[0-9\-\+]+$/)) {
			if(!data.phone.match(/^\d{11}$/)) {
				$.box.pop.ui.msg('提示：电话格式错误!');
				$("#__close__").after('<a onclick="$.box.pop.ui.setting()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(data.area1 == '' || data.area3 == '' || data.area3 == '') {
				$.box.pop.ui.msg('提示：地址信息不能为空!');
				$("#__close__").after('<a onclick="$.box.pop.ui.setting()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			$.box.pop.wait();
			$.post($.box.pop.ui.data.settingUrl, data, function(log) {
				$.box.pop.ui.msg('提示：'+log.info);
				if(log.status) {
					$("#__close__").after('<a onclick="location.reload()" href="javascript:void(0)">×</a>').remove();
					setTimeout(function() {location.reload();}, 1000);
				}
				else $("#__close__").after('<a onclick="$.box.pop.ui.setting()" href="javascript:void(0)">×</a>').remove();
			}, 'json');
			return;
		}
		var html = ''
+			'<div class="input" style="width: 500px;">'
+				'<span>姓 名：</span>'
+				'<input type="text" name="uname" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+				'<span>手 机：</span>'
+				'<input type="text" name="phone" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+			'</div>'
+			'<div class="input" style="width: 500px;">'
+				'<span>地 区：</span>'
+				'<span class="select">'
+					'<select name="area1"></select>&nbsp;'
+					'<select name="area2"></select>'
+				'</span>'
+			'</div>'
+			'<div class="input textarea" style="width: 500px;">'
+				'<span>单 位：</span>'
+				'<textarea name="area3" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')"></textarea>'
+			'</div>'
+			'<div class="input submit">'
+				'<span>&nbsp;</span>'
+				'<a class="button" href="javascript:void(0)" onclick="$.box.pop.ui.setting(true)">保 存</a>'
+				'<span class="link">'
+					'<a href="javascript:void(0)" onclick="$.box.pop.ui.setpwd()">修改密码!</a>'
+					'&nbsp;&nbsp;* 请填写真实信息，手机为<u><b>11</b></u>位数字!'
+				'</span>'
+			'</div>'
		;
		$.box.pop.ui.show(html, '编辑资料');
		//地区
		var area = $.box.pop.ui.data.area;
		var area1 = $.box.pop.ui.data._userData.area1;
		var area2 = $.box.pop.ui.data._userData.area2;
		var first_key = '';
		var count = 0;
		for(var k in area) {
			if(count == 0) first_key = k;
			if(area1 == k) {
				var option = $('<option value="'+k+'" selected="selected">'+k+'</option>');
			} else {
				var option = $('<option value="'+k+'">'+k+'</option>');
			}
			$('select[name="area1"]').append(option);
			count++;
		}
		if(area[area1]) first_key = area1;
		for(var k in area[first_key]) {
			if(area2 == area[first_key][k]) {
				var option = $('<option value="'+area[first_key][k]+'" selected="selected">'+area[first_key][k]+'</option>');
			} else {
				var option = $('<option value="'+area[first_key][k]+'">'+area[first_key][k]+'</option>');
			}
			$('select[name="area2"]').append(option);
		}
		$('select[name="area1"]').change(function() {
			$('select[name="area2"]').empty();
			var key = $('select[name="area1"] option:selected').val();
			for(var k in area[key]) {
				var option = $('<option value="'+area[key][k]+'">'+area[key][k]+'</option>');
				$('select[name="area2"]').append(option);
			}
		});
		$('select[name="area1"]').css('width', 'auto');
		$('select[name="area2"]').css('width', 'auto');
		$('textarea[name="area3"]').val($.box.pop.ui.data._userData.area3);
		$('input[name="uname"]').val($.box.pop.ui.data._userData.uname);
		$('input[name="phone"]').val($.box.pop.ui.data._userData.phone);
		
		if(!$.box.pop.ui.data.isIntact) {
			$('#__title__').html('提示：请先完善个人资料~');
			$('#__close__').remove();
			$('span.link a').remove();
		}
	}
	/** 操作:修改密码 */
	$.box.pop.ui.setpwd = function(submit) {
		if(submit) {
			var data = {};
			data.pwd = $('input[name="pwd"]').val();
			data.pwd_new = $('input[name="pwd_new"]').val();
			var pwd_re = $('input[name="pwd_re"]').val();
			if(data.pwd === '') {
				$.box.pop.ui.msg('提示：请输入原密码!');
				$("#__close__").after('<a onclick="$.box.pop.ui.setpwd()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(data.pwd_new !== pwd_re) {
				$.box.pop.ui.msg('提示：两次密码不一致!');
				$("#__close__").after('<a onclick="$.box.pop.ui.setpwd()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			if(data.pwd_new === '') {
				$.box.pop.ui.msg('提示：请输入新密码!');
				$("#__close__").after('<a onclick="$.box.pop.ui.setpwd()" href="javascript:void(0)">×</a>').remove();
				return;
			}
			$.box.pop.wait();
			$.post($.box.pop.ui.data.setpwdUrl, data, function(log) {
				$.box.pop.ui.msg('提示：'+log.info);
			}, 'json');
			return;
		}
		var html = ''
+			'<div class="input">'
+				'<span>原密码：</span>'
+				'<input type="password" name="pwd" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+			'</div>'
+			'<div class="input">'
+				'<span>新密码：</span>'
+				'<input type="password" name="pwd_new" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+			'</div>'
+			'<div class="input">'
+				'<span>重复密码：</span>'
+				'<input type="password" name="pwd_re" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+			'</div>'
+			'<div class="input submit">'
+				'<span>&nbsp;</span>'
+				'<a class="button" href="javascript:void(0)" onclick="$.box.pop.ui.setpwd(true)">保 存</a>'
+				'<span class="link">'
+					'<a href="javascript:void(0)" onclick="$.box.pop.ui.setting()">返回!</a>'
+				'</span>'
+			'</div>'
		;
		$.box.pop.ui.show(html, '修改密码');
		setTimeout(function() {$('input[name="pwd"]').focus();}, 120);
	}
	/** 操作:搜索收藏 */
	$.box.pop.ui.search = function(submit) {
		if(submit) {
			var key = $.trim($('input[name="search"]').val());
			if(key == 'g: 搜索全站!') key = '';
			if(key.match(/^(g|G):/)) {
				key = $.trim(key.substr(2));
				if(key === '') {
					$.box.pop.ui.msg('提示：请输入关键字!');
					$("#__close__").after('<a onclick="$.box.pop.ui.search()" href="javascript:void(0)">×</a>').remove();
					return;
				}
				var url = $.box.pop.ui.data.searchHomeUrl.replace('__KEY__', encodeURIComponent(key));
			} else {
				if(key === '') {
					$.box.pop.ui.msg('提示：请输入关键字!');
					$("#__close__").after('<a onclick="$.box.pop.ui.search()" href="javascript:void(0)">×</a>').remove();
					return;
				}
				var url = $.box.pop.ui.data.searchUserUrl.replace('__KEY__', encodeURIComponent(key));
			}
			location.href = url;
			return;
		}
		var html = ''
+			'<div class="input submit">'
+				'<input class="search" type="text" name="search" onfocus="$(this).addClass(\'focus\')" onblur="$(this).removeClass(\'focus\')" />'
+				'<a class="button search" href="javascript:$.box.pop.ui.search(true)">搜 索</a>'
+			'</div>'
		;
		$.box.pop.ui.show(html, '搜索收藏');
		$('input[name="search"]').val('g: 搜索全站!');
		$('input[name="search"]').css('color', 'silver');
		$('input[name="search"]').focus(function() {
			if($('input[name="search"]').val() == 'g: 搜索全站!') {
				$('input[name="search"]').val('');
				$('input[name="search"]').css('color', 'black');
			}
		}).blur(function() {
			if($.trim($('input[name="search"]').val()) == '' || $.trim($('input[name="search"]').val()) == 'g:') {
				$('input[name="search"]').val('g: 搜索全站!');
				$('input[name="search"]').css('color', 'silver');
			}
		});
	}
	/** 操作:删除收藏 */
	$.box.pop.ui.del = function(lid) {
		$.box.pop.wait();
		$.post($.box.pop.ui.data.linkDelUrl, {'lid':lid}, function(log) {
			$.box.pop.ui.msg('提示：'+log.info);
			if(log.status) {
				$("#__close__").after('<a onclick="$.box.pop.ui.setting()" href="javascript:void(0)">×</a>').remove();
				setTimeout(function() {location.reload();}, 1000);
			}
		}, 'json');
	}
})(jQuery);
