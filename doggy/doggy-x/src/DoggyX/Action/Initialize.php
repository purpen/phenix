<?php
/**
 * Mark an action, before invoke any
 * its method, it must run _init first.
 * 
 * @author n.s.
 */
interface DoggyX_Action_Initialize {
    /**
     * 对Action执行通用的初始化工作.
     * 
     */
    public function _init();
}