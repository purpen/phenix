<?php
/**
 * JsonView PreResult Listener
 * 
 * 如果某个Action在result context中置入json_view，并且当前用户是使用ajax方式来请求，则会将result
 * 强制切换为json result.
 * 
 * 用途：
 * 某些action操作,当用户使用浏览器点击时，我们希望用ajax来无刷新获取，而如果是搜索引擎或者用户直接通过浏览器
 * 来请求这个操作时,则需要使用正常的smarty模板。
 * 当这种情况下，action 只需要
 * <code>
 * $this->putResult('json_view',true);
 * </code>
 * 则这个Listener会自动根据请求对象的不同切换到json/smarty result
 * 
 * 
 */
class Doggy_Dispatcher_PreResultListener_JsonView implements Doggy_Dispatcher_PreResultListener {
    public function beforeResult(Doggy_Dispatcher_ActionInvocation $invocation,$resultCode){
        if($invocation->getInvocationContext()->getRequest()->isAjaxRequest()){
            if($invocation->getInvocationContext()->getResult('json_view')){
                if($resultCode != Doggy_Dispatcher_Constant_Action::JSON){
                    $invocation->setResultCode(Doggy_Dispatcher_Constant_Action::JSON);
                }
            }
        }   
    }
}
/**vim:sw=4 et ts=4 **/
?>