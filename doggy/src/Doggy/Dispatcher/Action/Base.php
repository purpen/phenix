<?php
/**
 * 1.2 style action base, for compatible,deprecated.
 * 
 * @deprecated
 */
class  Doggy_Dispatcher_Action_Base extends Doggy_Dispatcher_Action_Lite  {
    
    public $stash = null;
    /**
     * 返回SmartyResult
     *
     * @param string $tplId Smarty模板ID
     * @param string $contentType
     * @param string $charset
     * @return string
     */
    protected function smartyResult($tplId,$contentType='text/html',$charset='utf-8'){
        if (is_null($this->stash)) {
            $this->stash = array();
        }
        return $this->to_smarty($tplId,$contentType,$charset);
    }
    /**
     * 返回JQuery (taconite xml document)Result
     *
     * @param string $tplId 模板(Smarty格式)ID
     * @param boolean $crossDomain 是否是跨域回调
     * @param string  $callback 回调函数名称,空则回调客户端$.taconite
     * 
     * @return string
     */
    protected function jqueryResult($tplId,$crossDomain=false,$callback=null){
        if (is_null($this->stash)) {
            $this->stash = array();
        }
        $this->stash['_view']['template'] = $tplId;
        $this->stash['_view']['crossDomain'] = $crossDomain;
        $this->stash['_view']['callback'] = $callback;
        return 'jquery';
    }
    /**
     * 返回Json Result
     *
     * @return string
     */
    protected function jsonResult($errorCode=null,$errorMessage=null,$json_data=null){
        if (is_null($this->stash)) {
            $this->stash = array();
        }
        
        return $this->to_json($errorCode,$errorMessage,$json_data);
    }
    /**
     * 返回重定向到指定url的result
     * 
     * @param string $url
     * @param int $code
     * @return string
     */
    protected function redirectResult($url,$code=302){
        if (is_null($this->stash)) {
            $this->stash = array();
        }
        
        return $this->to_redirect($url,$code);
    }
    /**
     * 输出指定文件的result
     * 
     * @param string $data
     * @param string $content_type
     * @return string
     */
    protected function fileResult($path,$content_type=null){
        if (is_null($this->stash)) {
            $this->stash = array();
        }
        
        return $this->to_file($path,$content_type);
    }
    /**
     * 直接输出指定的数据的result
     * 
     * @param string $data
     * @param string $content_type
     * @return string 
     */
    protected function rawResult($data,$content_type=null,$chaset=null){
        if (is_null($this->stash)) {
            $this->stash = array();
        }
        
        return $this->to_raw($data,$content_type,$chaset);
    }
    
    protected function dtResult($tplId,$contentType='text/html',$charset='utf-8'){
        if (is_null($this->stash)) {
            $this->stash = array();
        }
        
        return $this->to_dt($tplId,$contentType,$charset);
    }
    
    /**
     * 
     * Returns current action invocation context
     * @return Doggy_Dispatcher_Context
     */
    protected function getContext(){
        return Doggy_Dispatcher_Context::getContext();
    }
    /**
     * Put value into action context
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function putContext($key,$value){
        if (is_null($this->stash)) {
            $this->stash = array();
        }
        $this->stash[$key] = $value;
    }
    /**
     * Put value into result context
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    protected function putResult($key,$value){
        if (is_null($this->stash)) {
            $this->stash = array();
        }
        $this->stash['_view'][$key] = $value;
    }
}
?>