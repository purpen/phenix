<?php
/**
 * 实现Modeldriven接口的Action基础类
 *
 * @deprecated
 */
class  Doggy_Dispatcher_Action_ModelDriven extends Doggy_Dispatcher_Action_Base implements Doggy_Dispatcher_Action_Interface_ModelDriven {
    private $model;
    protected $modelClass='Doggy_ActiveRecord_Base';
    /**
     * Wired model
     *
     * @return Doggy_ActiveRecord_Base
     */
    public function wiredModel(){
        if(is_null($this->model)){
            $this->model = new $this->modelClass();
        }
        return $this->model;
    }
}
?>