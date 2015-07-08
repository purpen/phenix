<?php
/**
 * 标识实现登录注销等授权的Action接口
 * 
 * 凡是实现本接口的action，可以用于Doggy_Dispatcher_Interceptor_Auth协作，当出现权限
 * 验证异常时，会被拦截到此Action
 * 
 */
interface Doggy_Auth_Authentication_Action{
    /**
     * 执行登录授权的方法
     * 
     * 如果当前用户尚未登录则会被调用此方法
     */
    public function login();
    /**
     * 权限被拒绝时调用此方法
     *
     */
    public function deny();
    
    /**
     * 注销登录
     */
    public function logout();
    /**
     * 注册新用户
     */
    public function register();
    /**
     * 设置登录注销后需要跳转的url
     */
    public function _setNextUrl($url);
}
/**vim:sw=4 et ts=4 **/
?>