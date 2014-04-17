<?php
/**
 * 系统通知消息类
 * 
 * @author purpen
 * @version $Id$
 */ 
class Sher_Core_Util_Notify extends Doggy_Exception {
    /**
     * 注册激活帐号邮件
     *
     * @param string $account
     * @param string $activation
     * @return void
     */
     public static function sendRegistActiveEmail($account, $activation, $extend_info=null){
        $subject = "太火鸟购物提醒【注册确认】";
        $url = "/app/eshop/profile/activate_email?activation=$activation";
        
        $body  = "<h3>亲爱的 $account ,欢迎加入太火鸟！</h3> <p>你可以在浏览器中输入或复制下列地址来激活: ";
        $body .= "<a href=".$url." target='_blank'>$url</a>。 ";
        $body .= "通过验证后，您将享受太火鸟给您提供的各项服务.</p>";
        $body .= self::getEmailTips();
        if(!empty($extend_info)){
            $body .= $extend_info;
        }
        
    }
    
    /**
     * 获取email共用提示信息
     * 
     * @return string
     */
    public static function getEmailTips(){
        $tips = "<p>---------------------------------------------------------</p>";
        $tips .= "<p>本邮件由太火鸟系统自动发送，请勿直接回复.</p>";
        $tips .= "<p>如果您有任何疑问或建议，请<a href='http://www.taihuoniao.com' target='_blank'>联系我们</a></p>";
        $tips .= "<p>太火鸟(<a href='http://www.taihuoniao.com' target='_blank'>www.taihuoniao.com</a>)-人生是场大设计</p>";
        
        return $tips;
    }
}
?>