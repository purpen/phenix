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
        
        $some_fields = array('_id'=>1,'nickname'=>1,'true_nickname'=>1,
        'state'=>1,'first_login'=>1,'profile'=>1,'city'=>1,'sex'=>1,'summary'=>1,
        'created_on'=>1,'email'=>1,'birthday'=>1,'medium_avatar_url'=>1, 'identify'=>1,
        'follow_count'=>1,'fans_count'=>1,'scene_count'=>1,'sight_count'=>1,'counter'=>1,
        'subscription_count'=>1,'sight_love_count'=>1, 'head_pic'=>1, 'ext_state'=>1,
        );
		
        // 重建数据结果
		$data = array();
		foreach($some_fields as $key=>$value){
            if(isset($user[$key])){
                $data[$key] = $user[$key];
            }
		}
        
      if(!isset($data['profile'])){
        $data['profile'] = array();
      }
        // 把profile提出来
        foreach($data['profile'] as $k=>$v){
            $data[$k] = $v;
        }
        unset($data['profile']);

        if(!isset($data['weixin'])){
            $data['weixin'] = '';   
        }
        if(!isset($data['im_qq'])){
            $data['im_qq'] = '';   
        }
        if(!isset($data['label'])){
            $data['label'] = '';   
        }
        if(!isset($data['expert_label'])){
            $data['expert_label'] = '';   
        }
        if(!isset($data['expert_info'])){
            $data['expert_info'] = '';   
        }

        if(!isset($data['interest_scene_cate'])){
            $data['interest_scene_cate'] = array();
        }

        if(!isset($data['age_group'])){
            $data['age_group'] = '';
        }

        if(!isset($data['assets'])){
            $data['assets'] = '';
        }

        if(!isset($data['summary']) || empty($data['summary'])){
            $data['summary'] = '';
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
        if(!isset($data['subscription_count'])){
            $data['subscription_count'] = 0;
        }
        if(!isset($data['sight_love_count'])){
            $data['sight_love_count'] = 0;
        }

        // 省份城市字符串输出
        $areas_arr = Sher_Core_Helper_Util::fetch_city($data['province_id'], $data['district_id']);
        $data['areas'] = !empty($areas_arr) ? $areas_arr : array();
    
        if(isset($data['identify'])){
          unset($data['identify']['d3in_volunteer']);
          unset($data['identify']['d3in_vip']);
          unset($data['identify']['d3in_tag']);
          unset($data['identify']['is_app_first_shop']);

          if(!isset($data['identify']['is_scene_subscribe'])){  // 是否首次订阅
              $data['identify']['is_scene_subscribe'] = 1;  //  不走用户完善信息流程
          }
          if(!isset($data['identify']['is_expert'])){ // 达人
              $data['identify']['is_expert'] = 0;
          }
          if(!isset($data['identify']['alliance_id'])){
              $data['identify']['alliance_id'] = '';    // 联盟账户ID
          }
          if(!isset($data['identify']['storage_id'])){
              $data['identify']['storage_id'] = '';    // 联盟账户ID
          }
        }else{
          $data['identify']['is_scene_subscribe'] = 1; //  不走用户完善信息流程
          $data['identify']['is_expert'] = 0;
          $data['identify']['alliance_id'] = '';
          $data['identify']['storage_id'] = '';
        }


        if(isset($data['counter'])){
          unset($data['counter']['notice_count']);
          unset($data['counter']['alert_count']);
          unset($data['counter']['comment_count']);
          unset($data['counter']['people_count']);

          $data['counter']['message_count'] = isset($data['counter']['message_count']) ? $data['counter']['message_count'] : 0;
          $data['counter']['fiu_comment_count'] = isset($data['counter']['fiu_comment_count']) ? $data['counter']['fiu_comment_count'] : 0;
          $data['counter']['fiu_notice_count'] = isset($data['counter']['fiu_notice_count']) ? $data['counter']['fiu_notice_count'] : 0;
          $data['counter']['fiu_alert_count'] = isset($data['counter']['fiu_alert_count']) ? $data['counter']['fiu_alert_count'] : 0;
          $data['counter']['fiu_bonus_count'] = isset($data['counter']['fiu_bonus_count']) ? $data['counter']['fiu_bonus_count'] : 0;

          // 总消息数量
          $data['counter']['message_total_count'] = $data['counter']['message_count'] + $data['counter']['fiu_comment_count'] + $data['counter']['fiu_notice_count'] + $data['counter']['fiu_alert_count'] + $data['counter']['fans_count'];

          // 订单
          $data['counter']['order_wait_payment'] = isset($data['counter']['order_wait_payment']) ? $data['counter']['order_wait_payment'] : 0;
          $data['counter']['order_ready_goods'] = isset($data['counter']['order_ready_goods']) ? $data['counter']['order_ready_goods'] : 0;
          $data['counter']['order_sended_goods'] = isset($data['counter']['order_sended_goods']) ? $data['counter']['order_sended_goods'] : 0;
          $data['counter']['order_evaluate'] = isset($data['counter']['order_evaluate']) ? $data['counter']['order_evaluate'] : 0;

          $data['counter']['order_total_count'] = $data['counter']['order_evaluate'] + $data['counter']['order_sended_goods'] + $data['counter']['order_ready_goods'] + $data['counter']['order_wait_payment'];

        }

        // 用户等级
        if(isset($data['ext_state']) && !empty($data['ext_state'])){
          $data['rank_id'] = $data['ext_state']['rank_id'];
          $data['rank_title'] = $data['ext_state']['user_rank']['title'];
          unset($data['ext_state']);
        }else{
          $data['rank_id'] = 1;
          $data['rank_title'] = '鸟列兵';
        }

        // 是否有头图
        $data['head_pic_url'] = '';
        if(isset($data['head_pic']) && !empty($data['head_pic'])){
          $asset_model = new Sher_Core_Model_Asset();
          $asset = $asset_model->extend_load($data['head_pic']);
          if($asset){
            $data['head_pic_url'] = $asset['thumbnails']['huge']['view_url'];
          }
          unset($data['head_pic']);
        }

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
      $filter[$k] = isset($user[$k]) ? $user[$k] : '';
    }

    return $filter;
    
	}

  /**
   * 过滤多余字段(list)
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
          $data[$i][$v] = isset($result[$i][$v]) ? $result[$i][$v] : '';
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
     * 过滤多余字段(single)
     * type=1, 允许出现的字段； type=2，不允许出现的字段
    */
    public static function filter_field($result, $options=array(), $type=1){
        if(empty($result)) return array();
        if(empty($options)) return $result;
        $data = array();
        $count = count($result);

        for($i=0;$i<count($options);$i++){
            $key = $options[$i];
            if($type==1){
                $data[$key] = isset($result[$key]) ? $result[$key] : '';
            }else{
                unset($result[$key]);
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
