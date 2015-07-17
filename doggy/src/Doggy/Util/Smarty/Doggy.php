<?php
/**
 * 常用的smarty helping函数
 *
 */
abstract class Doggy_Util_Smarty_Doggy {
    
    /**
     * 
     * @param array $params
     * @param Doggy_Util_Smarty_Base
     * @return string
     */
    public static function smarty_function_explode($params,$smarty){
        $var=null;
        $value=null;
        $separator=',';
        extract($params,EXTR_IF_EXISTS);
        if(empty($var)){
            throw new Doggy_Util_Smarty_Exception(__METHOD__ .'::var cannot be null');
        }
        $smarty->assign($var,explode($separator,$value));
    }
    /**
     * Flip an array by given key-field
     *
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     */
    public static function smarty_function_array_flip_key($params, &$smarty){
        $from=array();
        $key=null;
        $var=null;
        $field=null;
        extract($params,EXTR_IF_EXISTS);
        if(empty($var)){
            throw new Doggy_Util_Smarty_Exception(__METHOD__ .'::var cannot be null');
        }
        if(empty($key)){
            throw new Doggy_Util_Smarty_Exception(__METHOD__ .'::key cannot be null');
        }
        $map=array();
        reset($from);
        for($i=0;$i<count($from);$i++){
            $k = $from[$i][$key];
            $map[$k] = empty($field)?$from[$i]:$from[$i][$field];
        }
        $smarty->assign($var,$map);
    }

    /**
     * array_filter
     *
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     */
    public static function smarty_function_array_filter($params, &$smarty){
        $var=null;
        $key=null;
        $value=null;
        $from=null;
        extract($params,EXTR_IF_EXISTS);
        if(is_null($var)){
            throw new Doggy_Util_Smarty_Exception(__METHOD__ .'::var cannot be null');
        }
        if(is_null($key)||is_null($value)||!is_array($from)){
            $smarty->assign($var,$from);
        }
        $result=array();
        foreach ($from as $v) {
            if($v[$key]==$value) $result[] = $v;
        }
        $smarty->assign($var,$result);

    }
    /**
     * array_rage
     *
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     */
    public static function smarty_function_array_range($params, &$smarty){
        $max=null;
        $min=1;
        $var=null;
        extract($params,EXTR_IF_EXISTS);
        if(is_null($var)){
            throw new Doggy_Util_Smarty_Exception(__METHOD__ .'::var cannot be null');
        }
        $result = range($min,$max);
        $smarty->assign($var,$result);
    }
    /**
     * Assign var,support assign array
     *
     * {var test=array(5,4,3) test2=array(6,7,8)}
     *
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     */
    public static function smarty_function_set($params, &$smarty){
        foreach ($params as $k => $v) {
            if(preg_match('/^\s*array\s*\(\s*(.*)\s*\)\s*$/s',$v,$matches)){
                eval('$v=array('.str_replace("\n", "", $matches[1]).');');
            }else if (preg_match('/^\s*range\s*\(\s*(.*)\s*\)\s*$/s',$v,$matches)){
                eval('$v=range('.str_replace("\n", "", $matches[1]).');');
            }
            $smarty->assign($k,$v);
        }
    }

    /**
     * 
     * extracts an array into template variables just
     *           like the standard PHP extract() function
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     */
    public static function smarty_function_extract($params, $smarty){
        if (empty($params["value"])) {
           throw new Doggy_Util_Smarty_Exception("extract: missing 'value' parameter");
        }
        $smarty->assign($params["value"]);
    }
    /**
     * 输出当前页面的css
     * 
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     * @return string
     */
    public static function smarty_function_page_css($params,$smarty){
        $page_css = $smarty->get_template_vars('page_css');
        if(empty($page_css)){
            return;
        }
        $app_css_uri = Doggy_Config::get('runtime.uri.css');
        $content ='';
        foreach ($page_css as $css){
            $path = $css['path'];
            $media= $css['media'];
            $content.='<link rel="stylesheet" type="text/css" href="'.$app_css_uri."/$path\" media=\"$media\" />\n";
        }
        return $content;
    }
    
    /**
     * 为当前页面追加一个css
     * 
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     * @return string
     */
    public static function smarty_function_add_css($params,$smarty){
        $path=null;
        $media='all';
        extract($params,EXTR_IF_EXISTS);
        if(is_null($path)){
            throw new Doggy_Util_Smarty_Exception(__FUNCTION__.'::path is null!');
        }
        $pathes = explode(',',$path);
        $page_css = $smarty->get_template_vars('page_css');
        if(is_null($page_css)){
            $page_css=array();
        }
        foreach($pathes as $p){
            $page_css[] = array('path'=>$p,'media'=>$media);
        }
        $smarty->assign('page_css',$page_css);
    }
    
    /**
     * 为当前页面追加一个js
     * 
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     * @return string
     */
    public static function smarty_function_add_js($params,$smarty){
        $path=null;
        extract($params,EXTR_IF_EXISTS);
        if(is_null($path)){
            throw new Doggy_Util_Smarty_Exception(__FUNCTION__.'::path is null!');
        }
        $pathes = explode(',',$path);
        $page_js = $smarty->get_template_vars('page_js');
        if(is_null($page_js)){
            $page_js=array();
        }
        foreach($pathes as $p){
            $page_js[] = $p;
        }
        $smarty->assign('page_js',$page_js);
    }
    
    /**
     * 输出当前页面的js
     * 
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     * @return string
     * 
     */
    public static function smarty_function_page_js($params,$smarty){
        $page_js = $smarty->get_template_vars('page_js');
        if(empty($page_js)){
            return;
        }
        $app_js_uri = Doggy_Config::get('runtime.uri.js');
        $content ='';
        foreach ($page_js as $path){
            $content.='<script type="text/javascript" src="'.$app_js_uri."/$path\"></script>\n";
        }
        return $content;
    }
    /**
     * 
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     * @return string
     */
    public static function smarty_function_page_nocache($params,$smarty){
        $response=Doggy_Dispatcher_Context::getContext()->getResponse();
        $response->setRawHeader('Cache-Control:no-store,no-cache, must-revalidate,private,pre-check=0, post-check=0, max-age=0,max-stale=0')
        ->setRawHeader('Expires:Mon, 23 Jan 1978 12:52:30 GMT')
        ->setRawHeader('Pragma:no-cache');
    }
    
    /**
     * 
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     * @return string
     */
    public static function smarty_function_sessionId($params,$smarty){
        return Doggy_Session_Context::getSessionId();
    }
	/**
     * 为flash上传组件生成认证token
     * @param array $params
     * @param Doggy_Util_Smarty_Base $smarty
     * @return string
     */
    public static function smarty_function_swfUploadToken($params,$smarty){
        $context = Doggy_Session_Context::getContext();
        $token = hash('md5',rand());
        $context->set('swf_upload_token',$token);
        return $token;
    }
    /**
     * 检验SwfUploadKey是否合法
     */
    public static function validSwfUploadKey($key){
        $context = Doggy_Session_Context::getContext();
        $key2=$context->get('swf_upload_token');
        return $key == $key2;
    }
}
/**vim:sw=4 et ts=4 **/
?>