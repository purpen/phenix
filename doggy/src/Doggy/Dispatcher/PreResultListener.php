<?php
/**
 * 接口
 *
 * 用于注册到Doggy_Dispatcher_ActionInvocation中,当在result被执行前beforeResult将被回调
 *
 * @version $Id: PreResultListener.php 6369 2007-10-18 07:17:33Z night $
 * @author Night
 *
 */
interface Doggy_Dispatcher_PreResultListener{
    function beforeResult(Doggy_Dispatcher_ActionInvocation $invocation,$resultCode);
}
?>