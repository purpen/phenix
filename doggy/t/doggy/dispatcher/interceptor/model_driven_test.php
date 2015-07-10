<?php
class Doggy_Dispatcher_Interceptor_MockAction extends Doggy_Mock_Action implements Doggy_Dispatcher_Action_Interface_ModelDriven {
    private $name=null;
    private $age=-1;
    private $_model=null;

    public function setAge($v){
        $this->age = $v;
        return $this;
    }
    public function getAge(){
        return $this->age;
    }
    public function setName($v){
        $this->name = $v;
        return $this;
    }
    public function getName(){
        return $this->name;
    }
    /**
     * @return Doggy_Dispatcher_Interceptor_MockModel
     */
    public function wiredModel(){
        if(is_null($this->_model)){
            $this->_model = new Doggy_Dispatcher_Interceptor_MockModel();
        }
        return $this->_model;
    }
    public function execute(){
        return Doggy_Dispatcher_Constant_Action::NONE;
    }
}
class Doggy_Dispatcher_Interceptor_MockModel extends Doggy_ActiveRecord_Base{
    protected $tableName='doggy';
    public function setName($v){
        return $this->set('name',$v);
    }
    public function getName(){
        return $this->get('name');
    }
    public function setAge($v){
        return $this->set('age',$v);
    }
    public function getAge(){
        return $this->get('age');
    }
}
?>