<?php
/**
 * Result that redirect to another url
 *
 * @todo
 */
class Doggy_Dispatcher_Result_Redirect extends Doggy_Dispatcher_Result_Abstract {
    protected function render(){
        $stash = Doggy_Dispatcher_Util::merge_stash($this->invocation);
        $url = $stash['_view']['url'];
        $code = $stash['_view']['code'];
        $this->invocation->getInvocationContext()->getResponse()->setRedirect($url,$code);
    }
}
/*vim:expandtab:tabstop=4:sw=4*/
?>