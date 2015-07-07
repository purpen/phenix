<?php
/**
 * The ActionMapper is responsible for providing a mapping between HTTP requests and action invocation requests and
 * vice-versa.
 *
 * @author night
 * @version $Id: ActionMapper.php 6369 2007-10-18 07:17:33Z night $
 */
class Doggy_Dispatcher_ActionMapper {

    /**
     * Parse request and return mapped ActionMapping,if no valid action found,
     * return NULL
     *
     * This custom action mapper using the following format:
     *
     * FULL FORMATE:
     * http://HOST/MODULE_ALAIS/ACTION_NAME/METHOD/PARAM_NAME1/PARAM_VALUE1/PARAM_NAME2/PARAM_VALUE2
     *
     * DEFAULT METHOD FORMAT
     * http://HOST/MODULE_ALAIS/ACTION_NAME
     *
     * ID FORMAT
     * http://HOST/MODULE_ALAIS/ACTION_NAME/ID/PARAM_NAME2/PARAM_VALUE2
     *
     *
     *
     * @param Doggy_Dispatcher_Request_Http $request
     * @return Doggy_Dispatcher_ActionMapping
     */
    public static function parse($request){
        $uri = $request->getPathInfo();
        $uri= ltrim($uri,'/');
        $mapping = new Doggy_Dispatcher_ActionMapping();
        
        $routes = Doggy_Config::get('app.dispatcher.routes',array());

        $tokens =explode('/',$uri);
        $boot_module_id = Doggy_Config::$vars['app.modules.boot'];
        if(empty($tokens)) {
            $module_key = 'app.modules.'.$boot_module_id;
        } else{
            //module alias
            $alias = strtolower(urldecode(array_shift($tokens)));
            //lookup module's id
            if (!isset(Doggy_Config::$vars['app.modules.'.$alias])) {
                $module_key = isset($routes[$alias])?'app.modules.'.$routes[$alias]:'app.modules.'.$boot_module_id;
            }
            else {
                $module_key = 'app.modules.'.$alias;
            }
        }
        
        $module_info = Doggy_Config::get($module_key);
        
        if (empty($module_info)) {
            throw new Doggy_Dispatcher_Exception_IllegalRequest("Bad module:< $module_key >");
        }        
        
        if($module_info['state'] != 'on'){
            throw new Doggy_Dispatcher_Exception_IllegalRequest("module< $module_key > is disabled.");
        }
        
        $mapping->setNamespace($module_info['namespace']);
        //get action name
        $action_name = urldecode(array_shift($tokens));
        
        if(empty($action_name)){
            $action_name = $module_info['index_action'];
        }
        if(empty($action_name)){
            throw new Doggy_Dispatcher_Exception_IllegalRequest("Can't route request to any valid action!");
        }
        
        $mapping->setName($action_name);
        
        if(empty($tokens)){
            $method = 'execute';
        }else{
            $method = urldecode(array_shift($tokens));
            // $method = Doggy_Util_Inflector::methodlize($method);
        }
        
        $mapping->setMethod($method);

        $params = array();
        $nameOk = true;

        //check id format
        if(count($tokens)%2!=0 ){
            $paramName = 'id';
            $nameOk = false;
        }
        for($i=0;$i<count($tokens);$i++){
            if($nameOk){
                $paramName = urldecode($tokens[$i]);
                $nameOk = false;
            }else{
                $paramValue = urldecode($tokens[$i]);
                if(!empty($paramName)){
                    $params[$paramName] = $paramValue;
                }
                $nameOk = true;
            }
        }
        $mapping->setParams($params);
        return $mapping;
    }
    /**
     * Generate an internal url against given action_mapping information
     *
     * @param string $namespace
     * @param string $actionName
     * @param string $method
     * @param mixed $params
     * @return string
     */
    public static function generate($namespace,$actionName,$method=NULL,$params=NULL){

        $uri = '/'.$namespace.'/'.$actionName;
        if(!empty($method)){
            $uri.='/'.$method;
        }
        if(!empty($params)){
            if(is_array($params)){
                foreach($params as $name=>$value){
                    $uri.= '/'.urlencode($name).'/'.urlencode($value);
                }
            }else{
                $uri.= '/'.urlencode($params);
            }
        }
        return $uri;
    }
}
?>