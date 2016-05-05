<?php
/**
 * 过滤字段
 * @author tianshuai
 *
 * @package default
 */
class Sher_Core_Helper_FilterFields {

    /**
     * 手机版字段过滤
     */
    public static function wap_user($user){
        
        $some_fields = array('_id'=>1,'account'=>1,'nickname'=>1,'true_nickname'=>1,
        'state'=>1,'first_login'=>1,'profile'=>1,'city'=>1,'sex'=>1,'summary'=>1,
        'created_on'=>1,'email'=>1,'birthday'=>1,'medium_avatar_url'=>1, 'identify'=>1,
        'follow_count'=>1,'fans_count'=>1,'scene_count'=>1,'sight_count'=>1,'counter'=>1,
        
        );
		
        // 重建数据结果
		$data = array();
		foreach($some_fields as $key=>$value){
            if(isset($user[$key])){
                $data[$key] = $user[$key];
            }
		}
        
        // 把profile提出来
        foreach($data['profile'] as $k=>$v){
          $data[$k] = $v;
        }
        unset($data['profile']);

        if(!isset($data['weixin'])){
          $data['weixin'] = null;   
        }
        if(!isset($data['im_qq'])){
          $data['im_qq'] = null;   
        }

        if(!isset($data['province_id'])){
          $data['province_id'] = 0;
        }
        if(!isset($data['district_id'])){
          $data['district_id'] = 0;
        }
        if(!isset($data['follow_count'])){
          $data['follow_count'] = 0;
        }
        if(!isset($data['fans_count'])){
          $data['fans_count'] = 0;
        }
        if(!isset($data['scene_count'])){
          $data['scene_count'] = 0;
        }
        if(!isset($data['sight_count'])){
          $data['sight_count'] = 0;
        }

        // 省份城市字符串输出
        $areas_arr = Sher_Core_Helper_Util::fetch_city($data['province_id'], $data['province_id']);
        $data['areas'] = !empty($areas_arr) ? implode(' ', $areas_arr) : null;
    
        if(!isset($data['identify'])){
            if(!isset($data['identify']['is_scene_subscribe'])){
                $data['identify']['is_scene_subscribe'] = 0;
            }
        }
        
        $data['counter']['message_count'] = isset($data['counter']['message_count']) ? $data['counter']['message_count'] : 0;
        $data['counter']['fiu_comment_count'] = isset($data['counter']['fiu_comment_count']) ? $data['counter']['fiu_comment_count'] : 0;
        $data['counter']['fiu_notice_count'] = isset($data['counter']['fiu_notice_count']) ? $data['counter']['fiu_notice_count'] : 0;
        $data['counter']['fiu_alert_count'] = isset($data['counter']['fiu_alert_count']) ? $data['counter']['fiu_alert_count'] : 0;

        // 总消息数量
        $data['counter']['message_total_count'] = $data['counter']['message_count'] + $data['counter']['fiu_comment_count'] + $data['counter']['fiu_notice_count'] + $data['counter']['fiu_alert_count'];

        // 订单
        $data['counter']['order_wait_payment'] = isset($data['counter']['order_wait_payment']) ? $data['counter']['order_wait_payment'] : 0;
        $data['counter']['order_ready_goods'] = isset($data['counter']['order_ready_goods']) ? $data['counter']['order_ready_goods'] : 0;
        $data['counter']['order_sended_goods'] = isset($data['counter']['order_sended_goods']) ? $data['counter']['order_sended_goods'] : 0;
        $data['counter']['order_evaluate'] = isset($data['counter']['order_evaluate']) ? $data['counter']['order_evaluate'] : 0;

        $data['counter']['order_total_count'] = $data['counter']['order_evaluate'] + $data['counter']['order_sended_goods'] + $data['counter']['order_ready_goods'] + $data['counter']['order_wait_payment'];

        return $data;
    }


  /**
   * 过滤列表页用户输入
   */
  public static function user_list($user, $options=array()) {
    if(empty($user)){
      return false;
    }

    $filter = array();

    $filter_arr = array('_id', 'nickname', 'home_url', 'small_avatar_url', 'symbol',
      'mini_avatar_url','medium_avatar_url','big_avatar_url',
    
    );

    $some_fields = array_merge($filter_arr, $options);

    foreach($some_fields as $k){
      $filter[$k] = isset($user[$k]) ? $user[$k] : null;
    }

    return $filter;
    
	}

  /**
   * 过滤多余字段
   * type=1, 允许出现的字段； type=2，不允许出现的字段
   */
  public static function filter_fields($result, $options=array(), $type=1){
    if(empty($result)) return array();
    if(empty($options)) return $result;
    $data = array();
    $count = count($result);
    for($i=0;$i<$count;$i++){
      foreach($options as $v){
        if($type==1){
          $data[$i][$v] = isset($result[$i][$v]) ? $result[$i][$v] : null;
        }elseif($type==2){
          unset($result[$i][$v]);
        }
      }
    }
    
    if($type==1){
      return $data;
    }elseif($type==2){
      return $result;
    }
  }

  /**
   * @from Think php extend.php
   * 过滤xss攻击
   * @param str $val
   * @return mixed
   */
  public static function remove_xss($val) {
    // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
    // this prevents some character re-spacing such as <java\0script>
    // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
    $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

    // straight replacements, the user should never need these since they're normal characters
    // this prevents like <IMG SRC=@avascript:alert('XSS')>
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';
    for ($i = 0; $i < strlen($search); $i++) {
      // ;? matches the ;, which is optional
      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

      // @ @ search for the hex values
      $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
      // @ @ 0{0,7} matches '0' zero to seven times
      $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
    }

    // now the only remaining whitespace attacks are \t, \n, and \r
    $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script',
                  'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
    $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut',
             'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 
             'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut',
             'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend',
             'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange',
             'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete',
             'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover',
             'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange','onreadystatechange',
             'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 
             'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
    $ra = array_merge($ra1, $ra2);

    $found = true; // keep replacing as long as the previous round replaced something
    while ($found == true) {
      $val_before = $val;
      for ($i = 0; $i < sizeof($ra); $i++) {
        $pattern = '/';
        for ($j = 0; $j < strlen($ra[$i]); $j++) {
          if ($j > 0) {
            $pattern .= '(';
            $pattern .= '(&#[xX]0{0,8}([9ab]);)';
            $pattern .= '|';
            $pattern .= '|(&#0{0,8}([9|10|13]);)';
            $pattern .= ')*';
          }
          $pattern .= $ra[$i][$j];
        }
        $pattern .= '/i';
        $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
        $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
        if ($val_before == $val) {
          // no replacements were made, so exit the loop
          $found = false;
        }
      }
    }
    return $val;
  }

}
