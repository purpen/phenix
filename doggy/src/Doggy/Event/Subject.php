<?php
/**
 * Subject接口
 */
interface Doggy_Event_Subject{
    /**
     * 添加一个监听器
     *
     * @param Doggy_Event_Observer $listener
     */
    function addListener($listener);
    /**
     * 移去指定的监听器
     * 
     * @param Doggy_Event_Observer $listener 
     */
    function removeListener($listener);
    /**
     * 通知到订阅本主题的全部监听器
     */
    function notifyListeners($message);
}
/**vim:sw=4 et ts=4 **/
?>