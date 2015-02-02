<?php
/**
 * Lite action base, new implements
 */
class  Doggy_Dispatcher_Action_Lite implements Doggy_Dispatcher_Action  {
    public $stash= array();
    
    /**
     * forward to a smarty view
     *
     * @param string $tpl 
     * @param string $content_type 
     * @param string $charset 
     * @return string
     */
    public function to_smarty($tpl,$content_type='text/html',$charset=null) {
        $this->stash['_view']['template'] = $tpl;
        $this->stash['_view']['content_type'] = $content_type;
        $this->stash['_view']['charset'] = $charset;
        return 'smarty';
    }
    /**
     * forward to a DT view
     *
     * @param string $tpl 
     * @param string $content_type 
     * @param string $charset 
     * @return string
     */
    public function to_dt($tpl,$content_type=null,$charset=null) {
        $this->stash['_view']['template'] = $tpl;
        $this->stash['_view']['content_type'] = $content_type;
        $this->stash['_view']['charset'] = $charset;
        return 'dt';
    }
    /**
     * forward to json result view
     *
     * @return string
     */
    public function to_json($error_code=null,$error_message=null,$json_data=null) {
        $this->stash['_view']['error_code'] = $error_code;
        $this->stash['_view']['error_message'] = $error_message;
        if (!is_null($json_data)) {
            $this->stash['_json'] = $json_data;
        }
        return 'json';
    }
    /**
     * forward to raw data result view
     *
     * @param string $data 
     * @param string $content_type 
     * @param string $charset 
     * @return string
     */
    public function to_raw($data,$content_type='text/html',$charset='utf-8',$status_code=200) {
        $this->stash['_view']['data'] = $data;
        $this->stash['_view']['content_type'] = $content_type;
        $this->stash['_view']['charset'] = $charset;
        $this->stash['_view']['status_code'] = (int) $status_code;
        return 'raw';
    }
    
    /**
     * Response 404 error page
     *
     * @return string
     */
    public function to_error_404($error_message='File Not Found') {
        return $this->to_http_code(404,$error_message);
    }
    
    /**
     * Response 403 error page
     *
     * @param string $error_message 
     * @return void
     */
    public function to_error_403($error_message='403/Forbidden') {
        return $this->to_http_code(403,$error_message);
    }
    
    /**
     * Just reponse a http code
     *
     * @param string $error_code
     * @param string $error_message 
     * @return string
     */
    public function to_http_code($error_code,$error_message) {
        $this->stash['_view']['data'] = $error_message;
        $this->stash['_view']['status_code'] = (int) $error_code;
        $this->stash['_view']['content_type'] = 'text/html';
        return 'raw';
    }
    
    /**
     * redirect to another url
     *
     * @param string $url 
     * @param string $http_code 
     * @return string
     */
    public function to_redirect($url,$http_code=302) {
        $this->stash['_view']['url'] = $url;
        $this->stash['_view']['code'] = $http_code;
        return "redirect";
    }
    
    /**
     * forward to a static file view
     *
     * @param string $file_path 
     * @param string $content_type 
     * @return string
     */
    public function to_file($file_path,$content_type=null) {
        $this->stash['_view']['path'] = $file_path;
        $this->stash['_view']['content_type'] = $content_type;
        return "file";
    }
    
    /**
     * forward to calling another action.
     *
     * @param string $action 
     * @param string $method 
     * @param string $merge_stash if true, then action stash will copy to new action
     * @return string
     */
    public function forward($action,$method='execute',$merge_stash=true) {
        if (is_string($action)) {
            $action = new $action();
        }
        $action->stash = & $this->stash;
        return $action->$method();
    }
    
    
    public function execute() {
        return 'none';
    }
}
?>