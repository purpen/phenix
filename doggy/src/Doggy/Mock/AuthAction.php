<?php
class Doggy_Mock_AuthAction extends Doggy_Mock_Action implements Doggy_Auth_Authentication_Action{
    protected $url;
    public function login(){
        return $this->resultCode;
    }
    public function deny(){
        return $this->resultCode;
    }
    public function logout(){
        return $this->resultCode;
    }
    public function register(){
        return $this->resultCode;
    }
    /**
     * @return Doggy_Mock_AuthAction
     */
    public function _setNextUrl($url){
        $this->url=$url;
        return $this;
    }
    public function getNextUrl(){
        return $this->url;
    }
}
/**vim:sw=4 et ts=4 **/
?>