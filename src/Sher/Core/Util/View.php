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
	 * 替换文本域换行标识符-----nl2br()
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

  /**
   * html5 templet for app --no used
   */
  public static function api_templet($content, $type=1){
    $str = <<<EOF
    <!DOCTYPE HTML>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <title>     </title>
       <style type="text/css">
      body{
        margin:0;
        padding:0;
      }
      .course {
        margin: 0 10px;
      }
      .cont {
        word-break: break-all;
        word-wrap: break-word;
        padding-bottom: 10px;
      }
      .cont p {
        font-size: 1em;
        line-height: 1.5;
        margin-top: 0;
        margin-bottom: 10px;
      }
      .cont img{
        width:100%;
      }
      .cont a{
        border-bottom: 1px solid rgba(0,0,0,.1);
        color: #f36;
        cursor: pointer;
        text-decoration: none;
      }
      .ui.header{
        margin:1em 10px;
      }
      .ui.small.avatar.image img {
        border-radius: 50%;
        font-size: .9rem;
        width: 3em;
        margin-top: 10px;
      }
      .ui.header .content {
        font-size: 1.125em;
        line-height: 1.5;
        margin-left: .5em;
        margin-top: .5em;
        display: inline-block;
        vertical-align: top;
        font-weight: 700;
      }
      .ui.header .content .attribute {
        margin: 0 0 .75em;
        font-size: .925rem;
        color: rgba(0,0,0,.4);
      }
      .ui.header .sub.header {
        font-weight: 400;
        padding: 0;
        line-height: 1.2;
      }
      .sub.header.attribute a{
        color:#f36;
        text-decoration: none;
      }
      </style>
    </head>
    <body>
      {$content}
              
    </body>
    </html>
EOF;
    return $str;
  }

  /**
   * topic desc templet --no used
   */
  public static function api_topic_templet(&$topic=array()){
   $str = <<<EOF
      <div class="ui header">
        <a class="ui small avatar image"><img src="{$topic['user']['home_url']}" alt="{$topic['user']['nickname']}"></a>
        <div class="content">
          {$topic['title']}
          <div class="sub header attribute">
            <span class="category"><a href="{app_url_topic}/c{$topic['category']['_id']}" class="ui magenta link">{$topic['category']['title']}</a></span>&nbsp;|
            <span>{$topic['user']['nickname']} 发表于 {$topic['created_on_format']}</span>&nbsp;|
            <span>浏览数: {$topic['view_count']}</span>
          </div>
        </div>
      </div>
      <div class="course">
        <div class="cont">
            {$topic['description']}
        </div>					
      </div>
EOF;
    return self::api_templet($str);
  }

  /**
   * product desc templet -- no used
   */
  public static function api_product_templet(&$product=array()){
   $str = <<<EOF
      <div class="course">
        <div class="cont">
            {$product['content']}
        </div>					
      </div>
EOF;
    return htmlspecialchars(self::api_templet($str));
  }


  /**
   * 块内容显示
   * type=0,content内容原样输出，type=1 content内容过滤标签
   */
  public static function load_block($mark, $type=0){
    if(empty($mark)){
      return null;
    }
    $model = new Sher_Core_Model_Block();
    $block = $model->first(array('mark'=>$mark));
    if(empty($block)){
      return null;
    }
    if(!empty($block['content'])){
      $c = $block['content'];
      if($type==0){
        return $c;
      }elseif($type==1){
        return strip_tags(htmlspecialchars_decode($c));
      }else{
        return $c;
      }
    }
    return null;
  }

  /**
   *
   * @param string $string 原文或者密文
   * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
   * @param string $key 密钥
   * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
   * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
   * @example
   *   $a = authcode('abc', 'ENCODE', 'key');
   *   $b = authcode($a, 'DECODE', 'key');  // $b(abc)
   *
   *   $a = authcode('abc', 'ENCODE', 'key', 3600);
   *   $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
   *   可以替换特殊字符
   *   $txt = str_replace(array('+','/','='),array('-','_','.'),$invite_code);
   */
  public static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
   
      $ckey_length = 4;
   
      $key = md5($key ? $key : "thn");
      $keya = md5(substr($key, 0, 16));
      $keyb = md5(substr($key, 16, 16));
      $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
   
      $cryptkey = $keya.md5($keya.$keyc);
      $key_length = strlen($cryptkey);
   
      $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
      $string_length = strlen($string);
   
      $result = '';
      $box = range(0, 255);
   
      $rndkey = array();
      for($i = 0; $i <= 255; $i++) {
          $rndkey[$i] = ord($cryptkey[$i % $key_length]);
      }
   
      for($j = $i = 0; $i < 256; $i++) {
          $j = ($j + $box[$i] + $rndkey[$i]) % 256;
          $tmp = $box[$i];
          $box[$i] = $box[$j];
          $box[$j] = $tmp;
      }
   
      for($a = $j = $i = 0; $i < $string_length; $i++) {
          $a = ($a + 1) % 256;
          $j = ($j + $box[$a]) % 256;
          $tmp = $box[$a];
          $box[$a] = $box[$j];
          $box[$j] = $tmp;
          $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
      }
   
      if($operation == 'DECODE') {
          if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
              return substr($result, 26);
          } else {
              return '';
          }
      } else {
          return $keyc.str_replace('=', '', base64_encode($result));
      }
   
  }

  /**
   * 短网址
   * 
   */
  public static function url_short($url){
    if(!is_string( $url )) return false;
    $result   = sprintf("%u", crc32($url) );
    $shortUrl = '';
    $digitMsp = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','w','z',0,1,2,3,4,5,6,7,8,9,'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','W','Z');
    while( $result > 0 ){
        $s        = $result % 62;
        $result   = floor( $result / 62 );
        if( $s > 9 && $s < 36 )
            $s    += 10;
        $shortUrl .= $digitMsp[$s];
    }

    return $shortUrl;
  }

  /**
   * 通过邀请码获取用户ID
   */
  public static function fetch_invite_user_id($invite_code){
    if(empty($invite_code)){
      return false;
    }
    $mode = new Sher_Core_Model_User();
    $user = $mode->load(array('invite_code'=>$invite_code));
    if($user){
      return $user['_id'];
    }else{
      return false;
    }

  }

  /**
   * 通过用户ID获取邀请码
   */
  public static function fetch_invite_user_code($user_id){
    if(empty($user_id)){
      return false;
    }
    $mode = new Sher_Core_Model_User();
    $user = $mode->find_by_id((int)$user_id);
    if($user){
      if(!empty($user['invite_code'])){
        return $user['invite_code'];
      }else{
        //生成邀请码
        $code = self::url_short((string)$user['_id'].'-invite');
        //存入用户表
        $ok = $mode->update_set($user['_id'],array('invite_code' => $code));
        if($ok){
          return $code;     
        }else{
          return false;
        }
      }  
    }else{
      return false;
    }
  }

	
}
?>
