/*
 * phenix base js 
 */

var phenix = {
		visitor : {},
		url : {},
		redirect: function(url,delay) {
	        setTimeout(function(){
				window.location = url;
			},delay);
	    },
	    show_error_note: function(msg,delay) {
	    	phenix.show_notify_bar(msg,'error',delay);
	    },
	    show_ok_note:function(msg,delay) {
	    	phenix.show_notify_bar(msg,'ok',delay);
	    },
	    show_notify_bar: function(msg,type,delay) {
            var class_name;
	        if(!type || type == 'ok'){
	        	type = 'ok';
				class_name = 'alert-success';
	        }else{
				type = 'error';
				class_name = 'alert-danger';
	        }
		    $.show_notify_bar({
	        	position		 : 'top',	
	        	removebutton     : true,
	        	message			 : msg,
	        	time			 : delay,
				class_name       : class_name,
	            container        : '#alert-box'
	        });
	    }
};

phenix.show_error_message = function(errors, ele) {
	var html = '<ul class="list">';
  	if ($.isArray(errors)) {
	  	$.each(errors, function(index, value) {
	    	html += '<li>' + value + '</li>';
	  	});
  	} else {
  		html += '<li>' + errors + '</li>';
  	}
  	html += '</ul>';
  	
	$('<div/>')
		.addClass('ui error message')
		.html(html)
		.prependTo(ele);
};

phenix.show_ok_message = function(msg, ele) {  	
	$('<div/>')
		.addClass('ui success message')
		.html(msg)
		.prependTo(ele);
};

phenix.before_submit = function() {
	$('.ui.submit.button').addClass('loading');
	$('.ui.error.message').remove();
	return true;
};

phenix.after_submit = function() {
	$('.ui.submit.button').removeClass('loading');
	return true;
};

/*
 * 初始化,设置常用的ajax hook
 */
phenix.initial = function(){
	$('.ui.checkbox').checkbox();
	
	$('.ui.selection.dropdown').dropdown();
	
	$('.ui.dropdown').dropdown();
};

/*
 * 登录,注册页 
 */
phenix.build_auth_page = function() {
    /* 登录表单验证 */
	$('#login-form').form({
		account: {
			identifier  : 'account',
			rules: [
				{
					type   : 'empty',
					prompt : '邮件格式不对,请输入您注册时填写的邮件'
				}
			]
		},
		password: {
			identifier  : 'password',
			rules: [
				{
					type   : 'empty',
					prompt : '请输入正确的登录密码'
				},
				{
					type   : 'length[6]',
					prompt : '登录密码为必须6位以上字符'
				}
			]
		}
	}, {
		inline : true,
		onSuccess: function(event){
			event.preventDefault();
			$(this).ajaxSubmit({
				dataType: 'json',
				beforeSubmit: function(){
					phenix.before_submit();
				},
				success: function(data){
					phenix.after_submit();
					
					if(data.is_error){
						$(event.target).addClass('error');
						phenix.show_error_message(data.message, event.target);
					}else{
						phenix.redirect(data.redirect_url);
					}
					
				}
			});
		}
	});
    
    /*注册表单验证*/
	$('#register-form').form({
		account: {
			identifier  : 'account',
			rules: [
				{
					type   : 'empty',
					prompt : '账户邮件不能为空'
				},
				{
					type   : 'email',
					prompt : '账户邮件格式不对'
				}
			]
		},
		password: {
			identifier  : 'password',
			rules: [
				{
					type   : 'empty',
					prompt : '请输入正确的登录密码'
				},
				{
					type   : 'length[6]',
					prompt : '登录密码必须6位以上字符'
				}
			]
		},
		password_confirm: {
			identifier  : 'password_confirm',
			rules: [
				{
					type   : 'empty',
					prompt : '请输入正确的确认密码'
				},
				{
					type   : 'match[password]',
					prompt : '两次输入密码不一致'
				}
			]
		},
		invite_code: {
			identifier  : 'invite_code',
			rules: [
				{
					type   : 'empty',
					prompt : '请输入邀请码'
				},
				{
					type   : 'length[24]',
					prompt : '邀请码无效格式，核对后重试'
				}
			]
		}
	}, {
		inline : true,
		onSuccess: function(event){
			event.preventDefault();
			$(this).ajaxSubmit({
				dataType: 'json',
				beforeSubmit: function(){
					phenix.before_submit();
				},
				success: function(data){
					phenix.after_submit();
					
					if(data.is_error){
						$(event.target).addClass('error');
						phenix.show_error_message(data.message, event.target);
					}else{
						phenix.redirect(data.redirect_url);
					}
					
				}
			});
		}
	});
};

// 处理批量附件
phenix.rebuild_batch_assets = function(id){
	var batch_assets = $('#batch_assets').val();
	if (batch_assets){
		var asset_ids = batch_assets.split(',');
		if ($.inArray(id, asset_ids) == -1){
			asset_ids.push(id);
			
			$('#batch_assets').val(asset_ids.join(','));
		}
	}else{
		$('#batch_assets').val(id);
	}
};

