<?php
class Doggy_Util_Dispatcher {
    /**
     * 将参数数组装载到Action中
     * 
     * @param object $action
     * @param array $parameters
     */
    public static function applyParams($obj,$parameters){
        $native = method_exists($obj,'acceptableName');
        $stash = isset($obj->stash)?true:false;
        foreach($parameters as $param => $value){
            $method = 'set_'.$param;
			if(!is_array($value)){
				$value = htmlspecialchars($value, ENT_NOQUOTES);
			}else{
				// 循环检查数组的值
				for($i=0;$i<count($value);$i++){
					$value[$i] = htmlspecialchars($value[$i], ENT_NOQUOTES);
				}
			}
            if (method_exists($obj,$method)) {
                $obj->$method($value);
                continue;
            }
            if ($stash) {
                $obj->stash[$param] = $value;
                continue;
            }
            //compatible before 1.3.2
            $method  = 'set'.Doggy_Util_Inflector::camelize($param);
            $has = method_exists($obj,$method);
            if(!$has){
                continue;
            }
            $execute = $native?$obj->acceptableName($param):self::_acceptableName($param);
            if($execute) {
                $obj->$method($value);
            }
        }
    }
	/**
     * Detemine wheather a parameter name is acceptable to set
     *
     * @param string $name
     * @return boolean
     */
    protected static function _acceptableName($name){
        return !(substr($name,0,1) == '_');
    }
    /**
     * 重新将invocationcontext的参数装配到object中(model或者action)
     * 
     * @param Doggy_Dispatcher_Context $context
     * @param object $obj
     * 
     */
    public static function applyDispatcherContextParams($context,$obj){
        $parameters = $context->getRequest()->getParams();
        if(empty($parameters)) {
            return ;
        }
        self::applyParams($obj,$parameters);
    }
}
/**vim:sw=4 et ts=4 **/
?>
