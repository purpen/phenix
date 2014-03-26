<?php
if (!class_exists('PHPMailer')) {
    include_once 'phpmailer/class.phpmailer.php';
    include_once 'phpmailer/class.smtp.php';
}
/**
* Email related functions
*/
class Sher_Core_Helper_Email {

    public static function send_activation_email($nick_name,$account,$code,$ttl) {

    	$url = sprintf(Doggy_Config::$vars['app.url.active_account'],$code);

        $body = $nick_name.',你在'.date('Y-m-d H:i:s').'申请注册了帐号,帐号信息如下:'."\n";
        $body .="---------------------------------------------------------\n";
        $body .= '通行证帐号(登录邮箱):'.$account."\n";
        $body.="---------------------------------------------------------\n";
        $body.= "你需要点击以下地址来激活这个帐号:(如果以下链接不能点击,你需要把它复制到你浏览器的地址栏中)\n\n";
        $body.= $url;
        $body.="\n\n";
        $body.="如果这不是你本人亲自申请或者帐号已经可以使用,不用理会此邮件.\n";
        $body.="---------------------------------------------------------\n";
        $body.="小提示: 你需要在".date('Y-m-d',$ttl)."之前点击上述链接,过期将失效.\n";
        $body.="---------------------------------------------------------\n";
        $body.='本邮件为系统自动发送，请勿直接回复.'."\n";
        $body.=" 视觉中国灵感库(http://idea.chinavisual.com/) \n".date('Y-m-d');
        $subject="视觉中国灵感库注册确认邮件";

        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->Host = Doggy_Config::$vars['app.email.smtp.host'];
        if (isset(Doggy_Config::$vars['app.email.smtp.ssl']) && Doggy_Config::$vars['app.email.smtp.ssl']) {
            $mail->SMTPSecure = "ssl";
        }
        if (isset(Doggy_Config::$vars['app.email.smtp.port'])) {
            $mail->Port = Doggy_Config::$vars['app.email.smtp.port'];
        }

        if (isset(Doggy_Config::$vars['app.email.smtp.auth']) && Doggy_Config::$vars['app.email.smtp.auth']) {
            $mail->SMTPAuth = true;
            $mail->Username   = Doggy_Config::$vars['app.email.smtp.auth.user'];
            $mail->Password   = Doggy_Config::$vars['app.email.smtp.auth.password'];
        }

        $mail->Subject = $subject;

        if (isset(Doggy_Config::$vars['app.email.from'])) {
            $mail->setFrom(Doggy_Config::$vars['app.email.from']['address'],Doggy_Config::$vars['app.email.from']['name']);
        }
        $mail->CharSet='utf-8';
        $mail->Body = $body;
        $mail->AddAddress($account,$nick_name);
        if (@$mail->Send()) {
            Doggy_Log_Helper::info("activation email send to $account ok.");
            return true;
        }
        else {
            Doggy_Log_Helper::error("activation email failed to send to $account , msg:".$mail->ErrorInfo.' Host:'.$mail->Host.' Port:'.$mail->Port);
            return false;
        }
    }
}
?>