<?php
/**
 *  Action Mappping
 *
 * @author Pan Fan(a.k.a NightSailer<nightsailer@gmail.com>)
 * @version $Id: ActionMapping.php 6369 2007-10-18 07:17:33Z night $
 */
class Doggy_Dispatcher_ActionMapping extends Doggy_Object{
    private $namespace;
    private $method;
    private $name;
    private $params=array();
    private $class=null;

    /**
     * Set Namespace's value
     *
     * @param  string $value
     */
    function setNamespace($value){
        $this->namespace=$value;
        return $this;
    }
    /**
     * Returns Namespace's value
     *
     * @return string
     */
    function getNamespace(){
        return $this->namespace;
    }
    /**
     * Set Params's value
     *
     * @param  array $value
     */
    function setParams($value){
        $this->params=$value;
        return $this;
    }
    /**
     * Returns Params's value
     *
     * @return array
     */
    function getParams(){
        return $this->params;
    }
    /**
     * Set Name's value
     *
     * @param  string $value
     */
    function setName($value){
        $this->name=$value;
        return $this;
    }
    /**
     * Returns Name's value
     *
     * @return string
     */
    function getName(){
        return $this->name;
    }
    /**
     * Set Method's value
     *
     * @param  string $value
     */
    function setMethod($value){
        $this->method=$value;
    }
    /**
     * Returns Method's value
     *
     * @return string
     */
    function getMethod(){
        return $this->method;
    }

}
?>