<?php
/**
 * Mark an action is  visitor aware, common usage is implement
 * a lite version authorize action.
 *
 * note: port from czone project
 */
interface DoggyX_Action_VisitorAware {
    /**
     * This method will be called before any other action method.
     * If set $handle is true, the follow call will be intercepted and back.
     *
     * @param string $invoke_method 
     * @param array $visitor
     * @param string $handle 
     * @return string
     */
    public function check_visitor($invoke_method, $visitor, &$handle);
}