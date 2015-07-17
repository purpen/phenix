<?php
class Doggy_ActiveRecord_ValidateException extends Doggy_ActiveRecord_Exception {
    private $errors=array();
    public function __construct($errors=null,$code=0){
        if(empty($errors)){
            $msg ='UNKNOWN validate error';
        } else{
            $msg = is_array($errors)?implode("\n",$errors):$errors;
        }
        $this->setDetailErrors($errors);
        parent::__construct($msg,$code);
    }
    /**
     * 设置具体的错误信息数组
     *
     * @param mixed $errors
     * @return Doggy_ActiveRecord_ValidateException
     */
    public function setDetailErrors($errors){
        if(is_array($errors)){
            $this->errors+=$errors;
        }else{
            $this->errors[] = $errors;
        }
        return $this;
    }
    /**
     * 返回所有的校验错误信息
     * 
     * @return array
     */
    public function getDetailErrors(){
        return $this->errors;
    }
}
/**vim:sw=4 et ts=4 **/
?>