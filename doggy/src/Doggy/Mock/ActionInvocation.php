<?php
class Doggy_Mock_ActionInvocation extends Doggy_Dispatcher_ActionInvocation {
    public function buildInteceptors($classes){
        return parent::buildInteceptors($classes);
    }
    public function buildResultListeners($classes){
        return parent::buildResultListeners($classes);
    }
}