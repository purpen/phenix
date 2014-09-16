/*
 * js 
 */

var sher = {
		visitor : {},
		url : {},
		redirect: function(url,delay) {
	        setTimeout(function(){
				window.location = url;
			},delay);
	    },
	    show_error_note: function(msg,delay) {
	    	sher.show_notify_bar(msg,'error',delay);
	    },
	    show_ok_note:function(msg,delay) {
	    	sher.show_notify_bar(msg,'ok',delay);
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

/*
 * 初始化,设置常用的ajax hook
 */
sher.initial = function(){
    
	/* 此类为确认后执行的ajax操作 */	
	$('body').on('click', 'a.confirm-request', function(){
		if(confirm('确认执行这个操作吗?')){
        	$.get($(this).attr('href'));
        }
        return false;
	});
    
    /* 此类为ajax链接 */ 
	$('body').on('click', 'a.ajax', function(){
		$.get($(this).attr('href'));
        return false;
	});
    
    /* 此类的a形式的submit按钮,点击即相当于表单提交 */
	$('body').on('click', 'a.submit-button', function(){
		try {
           $(this).parent('form').submit();
       	} catch(e){}
       	return false;
	});
	
	/* 此类为hash链接 */ 
	$('body').on('click', 'a.ajax-hash', function(){
		var hash = this.hash && this.hash.substr(1);
	    if (hash != ""){
	        eval(hash + '.call(this);');
	    }
        return false;
	});
	
	/* 此类表单使用ajax提交 */
	$('form.ajax-form').livequery(function(){
		$(this).ajaxForm();
	});
	
	/* 隐藏某层 */
	$('body').on('click', 'a.close', function(){
		try {
           $(this).parent().fadeOut();
       	} catch(e){}
       	return false;
	});
	
	$('select.select-filter').livequery(function(){
		$(this).select2();
	});
	
	/*显示隐藏块*/
	sher.showbox();
	
	sher.hide_cake_box();
};

sher.showbox = function() {
	$('body').on('click', '.showbox', function(){
		var el =  this.hash && this.hash.substr(1);
	    $target = $('#'+el);
        if($target.hasClass('show')){
            $target.slideUp('slow', function(){
                $target.removeClass('show');
            });
        }else{
            $target.slideDown('slow', function(){
                $(this).addClass('show');
            });
        }
        return false;
	});
};

/**
 * 10秒后，隐藏短语
 */
sher.hide_cake_box = function(){
	setTimeout(function(){
		$('#cake-box').fadeOut('slow');
	}, 10000);
};

/*
 * 登录,注册页 
 */
sher.build_auth_page = function() {
    /* 登录表单验证 */
    $('#login-form').validate({
        rules:{
           account:{
               required:true
           },
           password:{
               required:true,
               minlength:6
           }
        },
        messages: {
        	account:'邮件格式不对,请输入您注册时填写的邮件',
        	password:'请输入正确的登录密码（6位以上字符)'
        },
        submitHandler: function(form) {
            $(form).ajaxSubmit();
        }
    });
    
    /*注册表单验证*/
    $('#register-form').validate({
        onkeyup:false,
		focusCleanup:true,
		rules:{
           account:{
               required:true
           },
           password:{
               required:true
           },
           password_confirm:{
        	   equalTo:"#password", 
               required:true
           },
           invite_code: {
               required:true
           }
        },
		messages: {
			account:{
				 required: '请填写你的常用手机号码',
			},
			password: {
			    required: '密码还没有填写',
			    minlength:jQuery.validator.format('密码长度需要{0}位以上')
			},
			password_confirm:{
			    required: '确认密码还没有填写',
			    minlength:jQuery.validator.format('密码长度需要{0}位以上'),
				equalTo: "两次输入密码不一致"
			},
			invite_code: {
			    required: '请提供有效的邀请码'
			}
		},
		errorPlacement: function(error, element) {
			$(element).parent().append(error);
        },
		submitHandler: function(form) {
            $(form).ajaxSubmit();
        }
    });

};

/**
 * 修改个人资料
 */
sher.build_profile_page = function() {
	$('#profile-form').validate({
        onkeyup:false,
		focusCleanup:true,
		rules:{
           nickname:{
               required:true
           },
           sex:{
               required:true
           },
           marital:{
               required:true
           },
           realname: {
               required:true
           },
		   year: {
               required:true
           },
		   mouth: {
               required:true
           },
		   day: {
               required:true
           }
        },
		messages: {
			nickname:{
				 required: '请填写你的昵称',
			},
			sex: {
			    required: '请选择你的性别',
			},
			marital:{
			    required: '请选择你的婚姻状况',
			},
			realname: {
			    required: '请填写你的真实姓名',
			},
			year: {
			    required: '请填写你的出生年份',
			},
			mouth: {
			    required: '请填写你的出生月份',
			},
			day: {
			    required: '请填写你的出生日期',
			},
		},
		errorPlacement: function(error, element) {
			$(element).parent().append(error);
        },
		submitHandler: function(form) {
            $(form).ajaxSubmit();
        }
    });
};


/**
 * 全局变量声明
 */
var wps_width=0, wps_height=0, wps_ratio=1;
var scale_width=580, scale_height=0;
var crop_width=0, crop_height=0;

/**
 * hook image area select
 */
sher.hook_imgarea_select = function(){
	scale_height = parseInt(wps_height*scale_width/wps_width);
	ias = $('img#avatar-photo').imgAreaSelect({
		aspectRatio: '1:1',
		x1: 0, 
		y1: 0, 
		x2: 290, 
		y2: 290,
		handles: true,
		fadeSpeed: 200,
		instance: true,
		onSelectChange: sher.preview,
		onSelectEnd: sher.updateAreaSelect
	});
};

sher.preview = function(img, selection) {
	if (!selection.width || !selection.height){
		return;
	}
	$('#x1').val(selection.x1);
	$('#y1').val(selection.y1);
	$('#x2').val(selection.x2);
	$('#y2').val(selection.y2);
	$('#w').val(selection.width);
	$('#h').val(selection.height);
};

sher.updateAreaSelect = function() {
	// todo
};
/**
 * imgAreaSelect settings
 */
$.extend($.imgAreaSelect.prototype, {
    animateSelection: function (x1, y1, x2, y2, duration) {
        var fx = $.extend($('<div/>')[0], {
            ias: this,
            start: this.getSelection(),
            end: { x1: x1, y1: y1, x2: x2, y2: y2 }
        });
		
        $(fx).animate({
            cur: 1
        },
        {
            duration: duration,
            step: function (now, fx) {
                var start = fx.elem.start, end = fx.elem.end,
                    curX1 = Math.round(start.x1 + (end.x1 - start.x1) * now),
                    curY1 = Math.round(start.y1 + (end.y1 - start.y1) * now),
                    curX2 = Math.round(start.x2 + (end.x2 - start.x2) * now),
                    curY2 = Math.round(start.y2 + (end.y2 - start.y2) * now);
                fx.elem.ias.setSelection(curX1, curY1, curX2, curY2);
                fx.elem.ias.update();
            }
        });
    }
});

