<?php
/**
 * 观察者接口
 */
interface Doggy_Event_Observer{
    /**
     * 当订阅的主题被更新时回调此方法
     *
     * @param mixed $message Subject的消息
     */
    function update($message);
}
/**vim:sw=4 et ts=4 **/
?>