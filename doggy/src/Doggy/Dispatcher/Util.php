<?php
class Doggy_Dispatcher_Util {
    /**
     * Merge invocation stash and action stash
     *
     * This is just hack for back-compatible,don't use it anyway.
     * 
     * @param Dogy_Dispatcher_ActionInvocation $invocation 
     * @return array merged stash
     */
    public static function merge_stash($invocation) {
        $context_stash = $invocation->getInvocationContext()->getAll();
        $stash = $invocation->getAction()->stash;
        return array_merge($context_stash,$stash);
    }
}
?>