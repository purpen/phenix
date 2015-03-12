<?php
class Doggy_Mock_AuthProvider  implements Doggy_Auth_Authentication_Provider {
    /**
     * @var Doggy_Auth_Authentication
     */
    private $authen;
    
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
    }
    /**
     * 创建一个未经过验证的授权凭证信息
     * 
     * @return Doggy_Auth_Authentication
     */
    public function createAuthentication(){
        if(is_null($this->authen)){
            $authen = new Doggy_Auth_Authentication();
            $acl=new Doggy_Auth_Acl();
            $authen->setAcl($acl);
            $this->authen = $authen;
        }
        return $this->authen;
    }
    public function getAcl(){
        return $this->authen->getAcl();
    }
    public function setAuthentication($authen){
        $this->authen = $authen;
        return $this;
    }
}
/**vim:sw=4 et ts=4 **/
?>