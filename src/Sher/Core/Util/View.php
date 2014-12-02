<?php
/**
 * View显示处理类
 * Html过滤器,清除用户输入的特殊字符，以及敏感词语。
 * @author purpen
 */
class Sher_Core_Util_View extends Doggy_Exception {
	
	/**
	 * 返回安全字符
	 */
	public static function safe($val){
		$val = self::replace_wrap($val);
		return $val;
	}
	
	/**
	 * 替换文本域换行标识符
	 */
	public static function replace_wrap($val){
		return preg_replace('/\n+/', '<br>', $val);
	}
	
	/**
	 * 过滤所有不安全的元素
	 * 
	 * @return string
	 */
	public static function filtor($content){
		//过滤代码
		//$content = self::replaceCode($content);
		//清除不健康词语
		$content = self::clearWords($content);
		
		return $content;
	}
	
	/**
	 * 替换特殊字符或不安全代码
	 * 
	 * @return string
	 */
	public static function replaceCode($content){
		
		 $rules = array(
		     "/s+/",
		     "/<(/?)(script|i?frame|style|html|body|title|link|meta|?|%)([^>]*?)>/isU",
		     "/(<[^>]*)on[a-zA-Z]+s*=([^>]*>)/isU"
		 );
		 
		 $reptxt = array(
		     " ",
             " ", //如果要直接清除不安全的标签，这里可以留空
             " "
		 );
		 
		 $content = preg_replace($rules,$reptxt,$content);
		 
		 return $content;
	}
	
	/**
	 * 清除敏感或不健康词语
	 * 
	 * @return string
	 */
	public static function clearWords($content){
		$bad_string = Doggy_Config::$vars['app.word.filter'];
		if(!empty($bad_string)){
			$content = preg_replace("/$bad_string/i","", $content);
		} 
		return $content;
	}

  /**
   * api转义html
   */
  public static function api_transf_html($arr){
    foreach($arr as $k=>$v){
      if (is_string($v)){
        $arr[$k] = htmlspecialchars_decode($v);
      }else if (is_array($v)) { //若为数组，则再转义.
        $arr[$k] = self::api_transf_html($v);
      }
    }
    return $arr;
  }
	
}
?>
