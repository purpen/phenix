<?php
abstract class Doggy_Dispatcher_Result_Abstract implements Doggy_Dispatcher_Result {

    protected $buffer;
    protected $executed;

    /**
     * ActionInvocation with this result
     *
     * @var Doggy_Dispatcher_ActionInvocation
     */
    protected $invocation;

    function __construct(){
        $this->init();
    }

    /**
     * Run result
     *
     * @param Doggy_Dispatcher_ActionInvocation $invocation
     */
    public function execute(Doggy_Dispatcher_ActionInvocation $invocation){
        $this->invocation = $invocation;
        $this->render();
        if($this->executed){
            $invocation->getInvocationContext()->getResponse()->setBuffer($this->buffer);
        }
    }
    /**
     * Do nothing
     *
     */
    protected function init(){
    }
    abstract protected  function render();
    public function getBuffer(){
        return $this->buffer;
    }
    public function setBuffer($buffer){
        $this->buffer = $buffer;
        return $this;
    }
}
?>