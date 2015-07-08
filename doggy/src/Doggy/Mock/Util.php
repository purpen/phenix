<?php
/**
 * Mock 工具类
 * 
 * 提供便于构建测试用的mock对象的方法
 */
class Doggy_Mock_Util{
    /**
     * 构建一个mock action invocation对象，
     * 
     *
     * @return Doggy_Mock_ActionInvocation
     */
    public static function createMockAcitionInvocation(){
        $context = Doggy_Dispatcher_Context::getContext();
        $request = new Doggy_Mock_Request();
        $response = new Doggy_Mock_Response();
        $context->setRequest($request);
        $context->setResponse($response);
        $invocation = new Doggy_Mock_ActionInvocation($context,'Doggy_Mock_Action','execute');
        return $invocation;
    }
}
/**vim:sw=4 et ts=4 **/
?>