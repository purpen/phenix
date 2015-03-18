<?php
/**
 * Mock for an Action
 *
 */
class Doggy_Mock_Action extends Doggy_Dispatcher_Action_Lite {
    protected $resultCode=Doggy_Dispatcher_Constant_Action::NONE;
    function execute(){
        return $this->resultCode;
    }
    /**
     * set mock result code
     *
     * @param string $code
     * @return Doggy_Mock_Action
     */
    public function setResultCode($code){
        $this->resultCode = $code;
        return $this;
    }
    public function getResultCode(){
        return $this->resultCode;
    }
    function __call($m,$a){
        return $this->resultCode;
    }
}
?>