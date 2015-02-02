<?php
/**
 * Access Control List
 * 
 * ACL用于控制和保护
 */
class Doggy_Auth_Acl {
    private $_defaultAllowed=false;
    private $_resources=array();
    private $_deny_all=null;
    private $_allow_all=null;
    
    /**
     * 根据ACL中的allow和deny规则匹配检查，确定是否具有对指定resource和privilege的权限
     *
     * 
     * @param string $resource Resource Id 用于标识Resource接口的Id
     * @param string $privilege 权限名称
     * @param mixed $extraRuleData 附加的规则数据
     * 
     * @return boolean
     */
    public function isAllowed($resource,$privilege){
        if($this->_deny_all){
            return false;
        }
        if($this->_allow_all){
            return true;
        }
        if(!isset($this->_resources[$resource][$privilege])) {
            return $this->_defaultAllowed;
        }
        return $this->_resources[$resource][$privilege]['rule'];
    }
    
    
    /**
     * 从Acl列表中删除指定权限
     * 
     * @return Doggy_Auth_Acl
     */
    public function removeRule($resource,$privilege){
        unset($this->_resources[$resource][$privilege]);
        return $this;
    }
    /**
     * 在ACL中添加一条允许规则
     *
     * 
     * @param string $resource 资源对象的id
     * @param string $privilege 权限名称
     * @param mixed $$ruleData 附加的规则数据
     * @return Doggy_Auth_Acl
     */
    public function allow($resource,$privilege,$ruleData=null){
        $this->_resources[$resource][$privilege]['rule']=true;
        $this->_resources[$resource][$privilege]['data']=$ruleData;
        return $this;
    }
    /**
     * 设置为全部允许
     */
    public function allowAll(){
        $this->_deny_all=false;
        $this->_allow_all=true;
    }
   
    
    /**
     * 在ACL中添加一条禁止规则
     *
     * 如果重复添加，那么遵循的覆盖继承原则同allow方法
     * 
     * @param string $resource 资源对象的id
     * @param string $privilege 权限名称
     * @param mixed $$ruleData 附加的规则数据
     * @return Doggy_Auth_Acl
     */
    public function deny($resource,$privilege,$ruleData=null){
        $this->_resources[$resource][$privilege]['rule']=false;
        $this->_resources[$resource][$privilege]['data']=$ruleData;
        return $this;
    }
    /**
     * 设置为全部禁止
     * @return Doggy_Auth_Acl
     */
    public function denyAll(){
        $this->_allow_all=false;
        $this->_deny_all=true;
        return $this;
    }
    public function reset(){
        $this->_allow_all=false;
        $this->_deny_all=false;
        $this->_resources=array();
        return;
    }
    /**
     * 获得指定权限的规则数据
     * @param $resource string
     * @param $privilege
     * @return mixed
     */
    public function getRuleData($resource,$privilege){
        return isset($this->_resources[$resource][$privilege]['data'])?$this->_resources[$resource][$privilege]['data']:null;
    }
    /**
     * 设置默认规则为允许
     * @return Doggy_Auth_Acl
     */
    public function setDefaultAllowed(){
        $this->_defaultAllowed=true;
        return $this;
    }
    /**
     * 设置默认规则为阻止
     * @return Doggy_Auth_Acl
     */
    public function setDefaultDenied(){
        $this->_defaultAllowed=false;
        return $this;
    }
}
/**vim:sw=4 et ts=4 **/
?>