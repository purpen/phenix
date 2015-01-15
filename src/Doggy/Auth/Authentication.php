<?php
/**
 * Doggy_Auth_Authentication
 *
 * Authentication用于标识和确定某个用户的身份极其对应的授权状态和附加信息
 *
 */
class Doggy_Auth_Authentication {
   
    private $_acl;
    private $_identity;
    private $_credentials;
    private $_authenticated=false;

    /**
     * 设置身份标识
     * 
     * 身份标识用于唯一确定当前授权对象的身份，一般为用户名,账号等。
     *
     * @param  string $value
     */
    function setIdentity($value){
        $this->_identity=$value;
        return $this;
    }
    /**
     * 返回身份标识
     *
     * @return string
     */
    function getIdentity(){
        return $this->_identity;;
    }
    /**
     * 
     * 返回当前的身份凭据
     * 
     * 身份凭据是确保身份正确和不被他人冒用，一般是身份对应的密码，口令，也可以是
     * 加密令牌。
     *
     * @return string
     */
    function getCredentials(){
        return $this->_credentials;
    }
    /**
     * 设置身份凭据
     *
     * @param string $credentials
     * @return Doggy_Auth_Authentication
     */
    function setCredentials($credentials){
        $this->_credentials = $credentials;
        return $this;
    }
    /**
     * 设置当前对象的ACL(访问权限列表)
     *
     * @param  Doggy_Auth_Acl $value
     * @return Doggy_Auth_Authentication
     */
    function setAcl($value){
        $this->acl=$value;
        return $this;
    }
    /**
     * 返回ACL
     *
     * @return Doggy_Auth_Acl
     */
    function getAcl(){
        return $this->acl;
    }
    /**
     * 返回当前信息是否已经过认证
     *
     * @return boolean
     */
    function isAuthenticated(){
        return $this->_authenticated;
    }
    /**
     * 标志当前信息为已认证状态
     *
     * @param bool $isAuthenticated
     * @return Doggy_Auth_Authentication
     *
     */
    function setAuthenticated($isAuthenticated){
        $this->_authenticated=$isAuthenticated;
        return $this;
    }
    
    public function __toString(){
        return 'Authentication:id=>['.$this->getIdentity().']';
    }
}
/**vim:sw=4 et ts=4 **/
?>