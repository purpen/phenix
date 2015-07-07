<?php
/**
 * Doggy_Auth_Resource
 * 
 * 实现本接口的Action可以自动获得当前用户的Authentication信息
 * 
 */
interface Doggy_Auth_Resource{
    
    /**
     * 设置当前用户的Authentication
     *
     * Authentication将由Doggy_Dispatcher_Intercpetor_Auth通过调用此方法注入
     * 
     * @param Doggy_Auth_Authentication $authentication
     */
    function _setAuthentication(Doggy_Auth_Authentication $authentication);
}
/**vim:sw=4 et ts=4 **/
?>