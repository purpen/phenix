<?php
/**
 * An interceptor is a stateless class that follows the interceptor pattern,
 * Interceptors may choose to either short-circuit the ActionInvocation execute ,
 * or it may choose to do some processing before and/or after delegating the rest of the procesing .
 *
 * @version $Id: Interceptor.php 6369 2007-10-18 07:17:33Z night $
 * @author Pan Fan
 */
interface  Doggy_Dispatcher_Interceptor {
    /**
     * Called after an interceptor is created, but before any requests are processed.
     *
     */
    function init();
    /**
     * Allows the Interceptor to do some processing on the request before and/or after the rest of the processing of the
     * request by the {@link Doggy_Dispatcher_ActionInvocation} or to short-circuit the processing and just return a String return code.
     *
     * @param Doggy_Dispatcher_ActionInvocation $invocation
     * @return string
     */
    function intercept(Doggy_Dispatcher_ActionInvocation $invocation);
    /**
     * Called to let an interceptor clean up any resources it has allocated.
     *
     */
    function destory();
}
?>