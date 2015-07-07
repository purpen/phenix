<?php
/**
 * Run before_invoke AOP.
 * 
 * @author n.s.
 */
interface DoggyX_Action_BeforeInvoke {
    public function before_invoke($invoke_method);
}