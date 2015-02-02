<?php
/**
 * Result通用接口,对应于一个View
 *
 * @version $Id: Result.php 6369 2007-10-18 07:17:33Z night $
 * @author Night
 */
interface Doggy_Dispatcher_Result{
    /**
     * Represents a generic interface for all action execution results, whether that be displaying a webpage, generating
     * an email, sending a IM message, etc.
     *
     */
    function execute(Doggy_Dispatcher_ActionInvocation $invocation);
}
?>