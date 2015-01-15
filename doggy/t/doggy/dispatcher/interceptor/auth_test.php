<?php
class Doggy_Dispatcher_Interceptor_AuthTest_AuthResource extends Doggy_Mock_Action implements Doggy_Auth_AuthorizedResource {
    private $authen;
    private $ok=false;
    public function _setAuthentication(Doggy_Auth_Authentication $authentication){
        $this->authen = $authentication;
    }
    /**
     * @param Doggy_Auth_Authentication
     */
    public function checkPrivilege($authen,$method){
        return $this->ok;
    }
    public function setCustomResult($ok){
        $this->ok=$ok;
    }
    
    public function getResourceId(){
        return 'auth_resource';
    }
    public function getPrivilegeMap(){
        return array(
            'any_login'=>array(
            	'privilege'=>Doggy_Auth_AuthorizedResource::PRIV_AUTHORIZED
            ),
            'edit'=>array(),
            'delete'=>array(),
            'custom'=>array(
                'privilege'=>Doggy_Auth_AuthorizedResource::PRIV_CUSTOM,
                'custom'=>'checkPrivilege'
            )
        );
    }
}

class Doggy_Dispatcher_Interceptor_AuthTest_MockAuthenAction extends Doggy_Mock_Action implements Doggy_Auth_Authentication_Action{
    public function login(){
        return Doggy_Dispatcher_Constant_Action::NONE;
    }
    public function logout(){
        return Doggy_Dispatcher_Constant_Action::NONE;
    }
    public function register(){
        return Doggy_Dispatcher_Constant_Action::NONE;
    }
    public function deny(){
        return Doggy_Dispatcher_Constant_Action::NONE;
    }
    public function _setNextUrl($url){
    }
}

class Doggy_Dispatcher_Interceptor_AuthTest_MockAuthProvider  implements Doggy_Auth_Authentication_Provider {
    private $authen;
    
    /**
     * 撤消和失效指定的凭据信息
     *
     * @param Doggy_Auth_Authentication $authen
     */
    function revoke(Doggy_Auth_Authentication $authen=null){}
    /**
     * 创建一个未经过验证的授权凭证信息
     * 
     * @return Doggy_Auth_Authentication
     */
    function createAuthentication(){
        if(is_null($this->authen)){
            $authen = new Doggy_Auth_Authentication();
            $acl=new Doggy_Auth_Acl();
            $acl->allow('auth_resource','edit');
            $acl->deny('auth_resource','delete');
            $authen->setAcl($acl);
            $this->authen = $authen;
        }
        return $this->authen;
    }
    
}
?>