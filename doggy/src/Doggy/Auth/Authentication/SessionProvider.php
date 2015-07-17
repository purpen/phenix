<?php
/**
 * A simple session provider.
 *
 */
class Doggy_Auth_Authentication_SessionProvider implements Doggy_Auth_Authentication_Provider {
    const AUTH_SESSION_KEY='__auth_session_key';
    /**
     * 撤消和失效指定的凭据信息
     *
     * @param Doggy_Auth_Authentication $authen
     */
    public function revoke(Doggy_Auth_Authentication $authen=null){
        if(is_null($authen)){
            $authen = $this->createAuthentication();
        }
        $authen->setAuthenticated(false);
        $context = Doggy_Session_Context::getContext();
        $context->set(self::AUTH_SESSION_KEY,null);
    }
    
    /**
     * 创建一个未经过验证的授权凭证信息
     * 
     * @return Doggy_Auth_Authentication
     */
    public function createAuthentication(){
        $context = Doggy_Session_Context::getContext();
        $authen = $context->get(self::AUTH_SESSION_KEY);
        if(!is_null($authen)){
            return $authen;
        }
        $authen = new Doggy_Auth_Authentication();
        $authen->setAcl(new Doggy_Auth_Acl());
        $authen->setAuthenticated(false);
        $context->set(self::AUTH_SESSION_KEY,$authen);
        return $authen;
    }
}
?>