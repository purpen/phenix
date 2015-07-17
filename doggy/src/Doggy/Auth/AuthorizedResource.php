<?php
/**
 * 实现本接口的Action需要通过用户的Authentication来对访问
 * 当前Action的方法进行授权控制
 * 
 */
interface Doggy_Auth_AuthorizedResource extends Doggy_Auth_Resource {
    const PRIV_NONE='__NONE__';
    const PRIV_CUSTOM='__CUSTOM__';
    const PRIV_AUTHORIZED='__AUTHORIZED__';
    /**
     * 返回当前Resource的唯一标识,这个标识和ACL中的resource应对应
     *
     */
    function getResourceId();
    
    /**
     * 返回当前Resource method<=>权限(Privilege)映射表,
     * 通过这个映射表，将当前的action的method映射到
     * privilege中去
     * 
     * 映射表的结构如下:
     * 
     * return array(
     * 	//Action的method名称,
     *  //* 标识默认
     * 	'method_name'=> array(
     * 		//方法对应的权限名，若省略，默认和method_name相同
     * 		//特殊:
     * 		//Doggy_Auth_AuthorizedResource::PRIV_AUTHORIZED 表示登录后即可无须特别权限
     * 		//Doggy_Auth_AuthorizedResource::PRIV_CUSTOM 表示使用自定义的授权方法(custom定义的方法)来检验权限
     * 		//Doggy_Auth_AuthorizedResource::PRIV_NONE 表示匿名权限，即跳过权限检查（注，并不常用，仅用于某些特殊情况需要跳过指定的默认规则
     * 		'privilege'=>'method_name'
     * 		//若privilege是PRIV_CUSTOM,则此处可以
     * 		//指定action的一个方法来执行权限校验方法
     *      //这个方法的函数原型如下:
     * 		// 
     * 		// public function customPrivilegeCheck($authentication,$methodName){
     * 		//
     * 		// 返回一个boolean值
     * 		'custom'=>'customPrivilegeCheck'
     *  )
     * );
     * 
     * @example return array(
     * 	'*'=>array()
     * ); 
     * 
     * @example return array(
     * 	'edit'=>array(
     * 		'privilege'=>'editContent'
     * 	)
     * );
     * 
     * @example return array(
     * 	'edit'=>array(
     * 		'privilege'=>'editContent'
     * 		'validate_method'=>'checkMethod'
     * 	)
     * );
     * @return array
     */
    function getPrivilegeMap();
}
/**vim:sw=4 et ts=4 **/
?>