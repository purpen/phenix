var cTime; //倒计时
var count = 0; //点击数 
var find = false; //是否中奖
var code, amount; //红包获取
var egg_value = []; //蛋值
var gift = []; //已得的红包
var gift_value = 0; //红包总值
var num_gift = 0; //红包个数
var name = "菜鸟"; //封号
var percent = 0; //百分比
var uid = $("input[name='user_id']").val(); //用户id
var nickname = $("input[name='nickname']").val(); //用户昵称
var version = 1; //游戏版本
var gate_url  = 'http://m.taihuoniao.com/app/api';

$(function () {
    //newgame();
    popWin('init_box', '');
    $('.play').css('display','block');

    $('form :input').blur(function () {
        if ($(this).is('#tel')) {
            if (!mobieCheck(this.value)) {
                var errorMsg = "<font class='cred'>请输入正确手机号！</font>";
                $('#reg_tips').html(errorMsg);
            } else $('#reg_tips').empty();
        }
        if ($(this).is('#pwd')) {
            if (this.value == "" || this.value.length < 6) {
                var errorMsg = "<font class='cred'>请输入至少6位密码！</font>";
                $('#reg_tips').html(errorMsg);
            } else {
                $('#reg_tips').empty();;
            }
        }
    });
    
    $('.share_close').click(function(){
        $('.guide').css('display','none');
    });
});

function newgame() {
    $('.play').css('display','none');
    $('#egg_acount').html(0);
    close('init_box');
    init();
//    playAud();
}

function init() {
    var n = 64;
    egg_value = [];
    //给每个蛋赋值
    for (var i = 0; i < n; i++) {
        egg_value["cell" + i] = 0;
    }

    cTime = 9;
    count = 0;
    find = false;

//    $('#grid-container div').attr('class', 'grid-cell');
    //获取后台数据
    $.ajax({
        type: 'GET',
        url: gate_url+'/gateway/bonus',
        //url: 'data.html',
        dataType: "json",
        success: function (reward) {
            var success = reward.success;
            var is_error = reward.is_error;
            var message = reward.message;

            if (success == true) {
                code = reward.data.code;
                amount = reward.data.amount;
                //随机藏红包
                var m = Math.round(Math.random() * (n - 1));
                egg_value["cell" + m] = amount;

                //开始倒计时
                TimeClose();

            } else {
                alert('获取红包失败！');
            }
        },
        error: function () {
            // view("异常！");    
            alert("服务器繁忙，请稍后再试!");
        }
    });

}

function TimeClose() {
    this.showtime.innerHTML = cTime;
    cTime--;
    if (cTime <= -1) {
        gameover();
        return;
    }
    window.setTimeout('TimeClose()', 1000);
}

function showResult(id) {
    //判断时间是否到 时间到，上传结果，保存已有红包
    if (cTime < 0) {
        return;
    }

    var egg = $('#' + id);
    //判断是不是点过
    if (egg.attr('class') == 'grid-cell') {
        playAud();
        count++;
        $('#egg_acount').html(count);
        if (egg_value[id] != 0) {
            find = true;
            gift[code] = amount;
            gift_value += amount;
            num_gift++;
            $('.getgift').html(num_gift);
            egg.attr('class', 'gift-cell');
        } else
            egg.attr('class', 'click-cell');
    }
    if(count == 64){
        cTime = 0;
        gameover();
    }

}

function gameover() {
    if (find == true) {
        var url = gate_url+'/gateway/game_result?version=' + version + '&uid=' + uid + '&bonus='+ code +':1';
    } else {
        var url = gate_url+'/gateway/game_result?version=' + version + '&uid=' + uid + '&bonus='+ code +':0';
    }

    pauseAud();
    $.ajax({
        type: 'GET',
        url: url,
        success: function () {
            //弹出分享窗口
            if (count <= 10) {
                var tip = "你果真是个<font class='color01'>菜鸟</font>！o(╯□╰)o，分享到朋友圈，没准他们还不如你！！";
                name = "菜鸟";
                percent = fRandomBy(1, 5);
            }
            else if (count <= 32) {
                var tip = "你果真是个<font class='color01'>菜鸟</font>！o(╯□╰)o，分享到朋友圈，没准他们还不如你！！";
                name = "菜鸟";
                percent = fRandomBy(40, 60);
            } else if (count <= 48) {
                var tip = "<font class='color01'>鸟护卫</font>！鸟蛋诚可贵，打碎价更高，还有近一半的鸟蛋没有完碎哦，分享到朋友圈，让你的鸟友鼓励你O(∩_∩)O！";
                name = "鸟护卫";
                percent = fRandomBy(71, 80);

            } else if (count <= 56) {
                var tip = "看前面的黑洞洞，定是那贼巢穴，待俺赶上前去，杀他个干干净净，<font class='color01'>鸟都尉</font>加油，几个鸟蛋算个啥，分享到朋友圈，让他们瞧瞧你的厉害！";
                name = "鸟都尉";
                percent = fRandomBy(81, 90);
            } else if (count <= 63) {
                var tip = "哇塞，您离鸟中之王仅有一步之遥，再接再厉，<font class='color01'>鸟将军</font>雄起！敢不敢分享到朋友圈，挑战你的鸟友？";
                name = "鸟将军";
                percent = fRandomBy(91, 99);
            } else {
                var tip = "<font class='color01'>鸟王</font>大人，万岁万岁万万岁，分享到朋友圈，叫子民来参拜！";
                name = "鸟王";
                percent = "100%";
            }

            popWin('share_box', tip);
        },

        error: function () {
            // view("异常！");    
            alert("服务器繁忙，请稍后再试!");
        }
    });
}

function mobieCheck(mobile) {
    var pattern = /^1[3|5|8]\d{9}$/;
    if (pattern.test(mobile)) {
        return true;
    }
    return false;
}

function getCode() {
    var mobile = $('#tel').val();
    if (!mobieCheck(mobile)) {
        $('#reg_tips').html("<font class='cred'>请输入正确手机号！</font>");
        return;
    }

    $.ajax({
        type: 'GET',
        url: gate_url+'/auth/verify_code?version=' + version + '&mobile=' + mobile,
        dataType: "json",
        success: function (result) {
            $('#reg_tips').html("<font class='cgreen'>"+result.message+"</font>");
//            $('.identifying_btn').removeAttr("disabled");
        },
        error: function () {
            // view("异常！");    
            alert("服务器繁忙，请稍后再试!");
        }
    });
}

function register() {
    var mobile = $('#tel').val();
    var password = $('#pwd').val();
    var verify_code = $('#identifycode').val();
    if (!mobieCheck(mobile)) {
        return;
    }

    $.ajax({
        type: 'GET',
        url: gate_url+'/auth/register?version=' + version + '&mobile=' + mobile + '&password=' + password + '&verify_code=' + verify_code,
        //url: 'data.html',
        dataType: "json",
        success: function (result) {
            alert(result.message);
            var success = result.success;
            if (success == true) {
                uid = result.data.id;
                nickname = result.data.nickname;
                close('reg_box');
                got_bonus();
            }
        },
        error: function () {
            // view("异常！");    
            alert("服务器繁忙，请稍后再试!");
        }
    });
}

function login(mobile, password) {
    var mobile = $('#log_tel').val();
    var password = $('#log_pwd').val();
    if (!mobieCheck(mobile)) {
        return;
    }
    $.ajax({
        type: 'GET',
        url: gate_url+'/auth/login?version=' + version + '&mobile=' + mobile + '&password=' + password,
        dataType: "json",
        success: function (result) {
            var success = result.success;
            if (success == true) {
                uid = result.data.id;
                //alert(uid);
                nickname = result.data.nickname;
                //alert(nickname);
                $('form#loginform').fadeOut();
                got_bonus();
            } else {
                $('#login_tips').html("<font class='cred'>" + result.message + "</font>");
            }
        },
        error: function () {
            // view("异常！");    
            alert("服务器繁忙，请稍后再试!");
        }
    });
}

function got_bonus() {
    if(cTime > 0){
        return;
    }
    if (num_gift == 0) {
        alert("还没有获得红包");
        return;
    }
    if (uid == 0) {
        //弹出登陆
        popWinOther('reg_box');
        return;
    }
    var bonus = '';
    for (var key in gift) {
        if (bonus == '')
            bonus = key;
        else {
            bonus += ';' + key;
        }
    }
    popWinOther('reg_box');
    $('form#loginform').hide();
    $('form#regform').hide();
    $('form#getbonusform').css("display", "block");
    $.ajax({
        type: 'GET',
        url: gate_url+'/gateway/got_bonus?version=' + version + '&uid=' + uid + '&bonus=' + bonus,
        //url: 'data.html',
        dataType: "json",
        success: function (result) {

            //alert(result.message);
            var success = result.success;
            if (success == true) {
                //领取成功后相关操作
                gift_value = 0;
                num_gift = 0;
                gift = [];
                $('.getgift').html(num_gift);
            }
            $('#got_tips').html(result.message);
            
        },
        error: function () {
            alert("服务器繁忙，请稍后再试!");
        }
    });
}

function share() {
    if (typeof WeixinJSBridge == "undefined") {
        alert(" 请先通过微信搜索 太火鸟 添加太火鸟为好友，通过微信分享游戏 :) ");
    } else {
        $('.guide').css({
            'display':'block',
            'width':$(window).width(),
            'height':$(window).height()
        });
        WeixinJSBridge.invoke("shareTimeline", {
            "title": "我在《玩蛋去》中，玩碎了" + count + "个蛋，打败了" + percent + "%的鸟友，获封" + name + "，你的头衔比我高么？",
            "desc" :"",
            "link": "http://m.taihuoniao.com/games",
            "img_url": "http://frstatic.qiniudn.com/images/egg/share.jpg",
            "img_width": "640",
            "img_height": "640"
        });

    }
}
document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
    // 分享到朋友圈
    WeixinJSBridge.on('menu:share:timeline', function(argv){
        close('init_box');
        share();
   	});
});

function getTop(id) {
    $this = $('.' + id);
    var browserHeight = $(window).height();

    //弹出窗口的高度
    var popWindowHeight = $this.height();
    var headerHeight = $('header').height();
    //top的值＝浏览器可视区域的高度／2－弹出窗口的高度／2
    var positionTop = browserHeight / 2 - popWindowHeight / 2 - headerHeight;
    return positionTop;
}

function getLeft(id) {
    $this = $('.' + id);
    var browserWidth = $(window).width();
    //弹出窗口的宽度
    var popWindowWidth = $this.width();
    //left的值＝浏览器可视区域的宽度／2－弹出窗口的宽度／2
    var positionLeft = browserWidth / 2 - popWindowWidth / 2;
    return positionLeft;
}


function popWin(id, tip) {
    closeAll();
    $this = $('.' + id);
    var positionTop = getTop(id);
    var positionLeft = getLeft(id);

    var oMask = '<div class="mask"></div>'
    var maskWidth = $(document).width();
    var maskHeight = $(window).height();

    $this.show().animate({
        'left': positionLeft + 'px',
        'top': positionTop + 'px'
    }, 500);

    $('.text').html(tip);

    $('.p_mask').append(oMask);
    $('.mask').width(maskWidth).height(maskHeight);

}
function closeAll(){
    close("tips_box");
    close("share_box");
    close("reg_box");
}
function popWinOther(id) {

    $this = $('.' + id);
    closeAll();
   
    var positionTop = getTop(id);
    var positionLeft = getLeft(id);

    var oMask = '<div class="mask"></div>'
    var maskWidth = $(window).width();
    var maskHeight = $(document).height() - $('header').height();

    $this.show().animate({
        'left': positionLeft + 'px',
        'top': positionTop + 'px'
    }, 500);
    $('.p_mask').append(oMask);
    $('.mask').width(maskWidth).height(maskHeight);
    
}

function close(id) {
    var popWindow = $('.' + id);
    popWindow.css('display', 'none');
    $('.mask').remove();
}

function reset() {
    $('form :input').val("");
}

function getReg() {
    $('form#loginform').fadeOut("fast", function () {
        $('form#regform').show();
    });
}

var _audio = document.getElementById("myaudio");

function playAud() {
    _audio.currentTime = 0;
    _audio.play();
}

function pauseAud() {
    _audio.currentTime = 0;
    _audio.pause();
}

function fRandomBy(under, over) {
    return parseInt(Math.random() * (over - under + 1) + under);
}

function exit() {
    if(cTime > 0){
        return;
    }
    if (num_gift == 0) {
        esc();
        return;
    } else {
        var msg = "您有" + num_gift + "个红包(共" + gift_value + "元）未领取！退出将清空您的红包！"; 
        popWin("tips_box", msg);
    }
    
}

function esc() {
    WeixinJSBridge.call('closeWindow');
}

function againGame() {
    closeAll(); 
    $('.play').css('display','block');
    $('#grid-container div').attr('class', 'grid-cell');
}
