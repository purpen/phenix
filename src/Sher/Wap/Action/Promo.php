<?php
/**
 * 活动专题页面
 * @author purpen
 */
class Sher_Wap_Action_Promo extends Sher_Wap_Action_Base {
    public $stash = array(
      'page'=>1,
      'sort'=>0,
      'target_id'=>0,
    );
    
    protected $exclude_method_list = array('execute', 'test', 'coupon', 'dreamk', 'chinadesign', 'momo', 'watch', 'year_invite','year','jd','xin','six','zp','zp_share','qixi','hy','din','request','rank', 'fetch_bonus','idea','idea_sign','draw','jdzn','common_sign','db_bonus','coin','coin_submit','hy_sign','rank2','comment_vote_share','sign','xy','mf','source','zces','holiday','hoshow','cappa','android_download','sign_app','zzces','send_bonus','fiu','load_up_img','ym','eleven','theme','fiuinvite','tshare','teeth','lottery','double','esthetics','intelligence','outdoor','clothes','receive_zongzi','receive_zongzi_ok','wx_article','wx_active','wx_try','wx_report','wx_product','wx_zc','wx_cooperate', 'wx_cooperate_success','qsyd3', 'd3ingo_zj','wx_proxy','d3in_tv_download');

    /**
     * 网站入口
     */
    public function execute(){
      //return $this->coupon();
    }

    /**
     * d3in-TV 下载
     */
    public function d3in_tv_download(){
        $url = "https://s3.taihuoniao.com/download/D3IN_TV_V0.8.0.9.apk";
        return $this->to_redirect($url);
    }

    /**
     * 微信授权代理
     */
    public function wx_proxy() {

        $appid = '';
        $scope = 'snsapi_login';
        $state = '';
        $code = '';
        $redirect_uri = '';
        $device = '';
        $protocol = '';
        if (Sher_Core_Helper_Util::is_https()) {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }
        if (isset($_GET['device'])) {
            $device = $_GET['device'];
        }
        if (isset($_GET['appid'])) {
            $appid = $_GET['appid'];
        }
        if (isset($_GET['state'])) {
            $state = $_GET['state'];
        }
        if (isset($_GET['redirect_uri'])) {
            $redirect_uri = $_GET['redirect_uri'];
        }
        if (isset($_GET['code'])) {
            $code = $_GET['code'];
        }
        if (isset($_GET['scope'])) {
            $scope = $_GET['scope'];
        }
        if ($code == 'test') {
            exit;
        }
        if (empty($code)) {
            $authUrl = '';
            if ($device == 'pc') {
                $authUrl = 'https://open.weixin.qq.com/connect/qrconnect';
            } else {
                $authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize';
            }
            $options = array(
                $authUrl,
                '?appid=' . $appid,
                '&redirect_uri=' . urlencode($protocol . '://' . $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']),
                '&response_type=code',
                '&scope=' . $scope,
                '&state=' . $state,
                '#wechat_redirect'
              );
            //把redirect_uri先写到cookie
            header(implode('', array(
                "Set-Cookie: redirect_uri=",
                urlencode($redirect_uri),
                "; path=/; domain=",
                Sher_Core_Helper_Util::get_domain(),
                "; expires=" . gmstrftime("%A, %d-%b-%Y %H:%M:%S GMT", time() + 60),
                "; Max-Age=" + 60,
                "; httponly"
              )));
            header('Location: ' . implode('', $options));
        } else {
            if (isset($_COOKIE['redirect_uri'])) {
                $back_url = urldecode($_COOKIE['redirect_uri']);
                header('Location: ' . implode('', array(
                        $back_url,
                        strpos($back_url, '?') ? '&' : '?',
                        'code=' . $code,
                        '&state=' . $state
                      )));
            }
        }
    
    
    }

    /**
     * 浙江传统产业设计再造计划金华站对接会
     */
    public function d3ingo_zj() {
        //微信分享
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
        return $this->to_html_page('wap/promo/d3ingo_zj.html');  
    }

    /**
     * 奇思勇动3
     */
    public function qsyd3(){
        //微信分享
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
        return $this->to_html_page('wap/promo/qsyd3.html'); 
    }


    /**
     * 公众号-商务合作提交成功页
     */
    public function wx_cooperate_success() {
      return $this->to_html_page('wap/promo/wx_cooperate_success.html');   
    }

    /**
     * 公众号-商务合作
     */
    public function wx_cooperate() {

      $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
      switch($kind){
        case 1:
          $this->stash['kind_label'] = '商品合作';
          break;
        case 2:
          $this->stash['kind_label'] = '众筹合作';
          break;
        case 3:
          $this->stash['kind_label'] = '销售合作';
          break;
        case 4:
          $this->stash['kind_label'] = '市场合作';
          break;
        case 5:
          $this->stash['kind_label'] = '投资合作';
          break;
        default:
          $this->stash['kind_label'] = '商品合作';
      }
      $this->stash['kind'] = $kind;
     
      // 图片上传参数
      $this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
      $this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_COOPERATE;
      $this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_WX_COOPERATE;
      $this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();

      return $this->to_html_page('wap/promo/wx_cooperate.html');
    }

    /**
     * 公众号-文章
     */
    public function wx_article() {
      $mark = 'wx_article';
      $item = array();
      $model = new Sher_Core_Model_Column();
      $column = $model->first(array('mark'=>$mark));
      if(!empty($column)) {
        $item = $column['item'];
      }
      $this->stash['item'] = $item;
      $this->stash['page_title_suffix'] = '精品文章';
      return $this->to_html_page('wap/promo/wx_article.html');    
    }

    /**
     * 公众号-活动
     */
    public function wx_active() {
      $mark = 'wx_active';
      $item = array();
      $model = new Sher_Core_Model_Column();
      $column = $model->first(array('mark'=>$mark));
      if(!empty($column)) {
        for($i=0;$i<count($column['item']);$i++){
          $d = $column['item'][$i];
          $status = '进行中';
          if($d['end_time']){
            $now = time();
            $end = strtotime($d['end_time']);
            if($end - $now < 0) $status = '已结束';
          }
          $column['item'][$i]['status'] = $status;
        }
        $item = $column['item'];
      }
      $this->stash['item'] = $item;
      $this->stash['page_title_suffix'] = '最新活动';
      return $this->to_html_page('wap/promo/wx_active.html');    
    }

    /**
     * 公众号-试用
     */
    public function wx_try() {
      $mark = 'wx_try';
      $item = array();
      $model = new Sher_Core_Model_Column();
      $try_model = new Sher_Core_Model_Try();
      $column = $model->first(array('mark'=>$mark));
      if(!empty($column)) {
        for($i=0;$i<count($column['item']);$i++){
          $d = $column['item'][$i];
          $try = array();
          if($d['target_id']){
            $try = $try_model->extend_load((int)$d['target_id']);
            if($try){
              $try['cover_url'] = $try['cover']['thumbnails']['resp']['view_url'];
              switch($try['step_stat']) {
              case 0:
                $try['state_style'] = 'callback';
                break;
              case 1:
                $try['state_style'] = 'apply';
                break;
              case 2:
                $try['state_style'] = 'recoe';
                break;
              case 3:
                $try['state_style'] = 'callback';
                break;
              case 5:
                $try['state_style'] = 'end';
                break;
              default:
                $try['state_style'] = 'callback';
              }        
            }
          }
          if(empty($try)) {
            $try['title'] = $d['title'];
            $try['cover_url'] = $d['cover_url'];
            $try['wap_view_url'] = $d['url'];
          }
          $column['item'][$i]['try'] = $try;
        }
        $item = $column['item'];
      }
      $this->stash['item'] = $item;
      $this->stash['page_title_suffix'] = '免费试用';
      return $this->to_html_page('wap/promo/wx_try.html');    
    }

    /**
     * 公众号-评测报告
     */
    public function wx_report() {
      $mark = 'wx_report';
      $item = array();
      $model = new Sher_Core_Model_Column();
      $topic_model = new Sher_Core_Model_Topic();
      $column = $model->first(array('mark'=>$mark));
      if(!empty($column)) {
        for($i=0;$i<count($column['item']);$i++){
          $d = $column['item'][$i];
          $topic = array();
          if($d['target_id']){
            $topic = $topic_model->extend_load((int)$d['target_id']);
            if($topic){
              $topic['cover_url'] = $topic['cover']['thumbnails']['resp']['view_url'];
              $topic['nickname'] = $topic['user']['nickname'];
            }
          }
          if(empty($topic)) {
            $topic['title'] = $d['title'];
            $topic['cover_url'] = $d['cover_url'];
            $topic['wap_view_url'] = $d['url'];
          }
          $column['item'][$i]['topic'] = $topic;
        }
        $item = $column['item'];
      }
      $this->stash['item'] = $item;
      $this->stash['page_title_suffix'] = '评测报告';
      return $this->to_html_page('wap/promo/wx_report.html');    
    }

    /**
     * 公众号-精选产品
     */
    public function wx_product() {
      $mark = 'wx_product';
      $item = array();
      $model = new Sher_Core_Model_Column();
      $product_model = new Sher_Core_Model_Product();
      $column = $model->first(array('mark'=>$mark));
      if(!empty($column)) {
        for($i=0;$i<count($column['item']);$i++){
          $d = $column['item'][$i];
          $product = array();
          if($d['target_id']){
            $product = $product_model->extend_load((int)$d['target_id']);
            if($product){
              $product['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
            }
          }
          if(empty($product)) {
            $product['title'] = $d['title'];
            $product['cover_url'] = $d['cover_url'];
            $product['wap_view_url'] = $d['url'];
          }
          $column['item'][$i]['item'] = $product;
        }
        $item = $column['item'];
      }
      $this->stash['item'] = $item;
      $this->stash['page_title_suffix'] = '精选商品';
      return $this->to_html_page('wap/promo/wx_product.html');    
    }

    /**
     * 公众号-众筹
     */
    public function wx_zc() {
      $mark = 'wx_zc';
      $item = array();
      $model = new Sher_Core_Model_Column();
      $column = $model->first(array('mark'=>$mark));
      if(!empty($column)) {
        $item = $column['item'];
        for($i=0;$i<count($item);$i++){
          $d = $item[$i];
          $is_over = false;
          $last_time = 0;
          $unit = '';
          if($d['end_time']){
            $now = time();
            $end = strtotime($d['end_time']);
            $last_temp = $end - $now;
            if($last_temp < 0){
              $is_over = true;
            }else{
              if($last_temp < 86400){
                $unit = '小时';
                $last_time = ceil($last_temp/3600);
              }else{
                $unit = '天';
                $last_time = ceil($last_temp/86400);
              }
            }
          }
          $item[$i]['is_over'] = $is_over;
          $item[$i]['last_time'] = $last_time . $unit;
        }
      }
      $this->stash['item'] = $item;
      $this->stash['page_title_suffix'] = '众筹新品';
      return $this->to_html_page('wap/promo/wx_zc.html');    
    }

    /**
     * 领粽子表单提交
     */
    public function receive_zongzi() {
        //微信分享
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
        return $this->to_html_page('wap/promo/receive_zongzi.html');      
    }
    /**
     * 领粽子表单提交成功页面
     */
    public function receive_zongzi_ok() {
        return $this->to_html_page('wap/promo/receive_zongzi_ok.html');      
    }

    /**
     * 年会礼品-家居篇
     */
    public function clothes(){
        //微信分享
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
        return $this->to_html_page('wap/promo/clothes.html'); 
    }
    /**
     * 年会礼品-出行篇
     */
    public function outdoor(){
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
        return $this->to_html_page('wap/promo/outdoor.html'); 
    }

    /**
     * 年会礼品-美学篇
     */
    public function esthetics(){
        //微信分享
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
        return $this->to_html_page('wap/promo/esthetics.html'); 
    }

    /**
     * 年会礼品-智能篇
     */
    public function intelligence(){
        //微信分享
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
        return $this->to_html_page('wap/promo/intelligence.html'); 
    }

    /**
     * 2016双12
     */
    public function double(){
        //微信分享
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
        return $this->to_html_page('wap/promo/double.html');
    }

    /**
     * 双12抽奖
     */
    public function lottery(){
        //微信分享
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
        return $this->to_html_page('wap/promo/lottery.html');
    }

    public function ajax_lottery(){

        if(!$this->visitor->id){
            return $this->ajax_json('请先登录！', true);
        }
        $user_id = $this->visitor->id;
        $from_to = 2;
        $kind = 1;
        $target_id = 1;

        // 验证是否还能抽奖
        $model = new Sher_Core_Model_ActiveDrawRecord();
        $result = $model->check_can_draw($user_id, $target_id, 1);
        if(!$result['success']){
            return $this->ajax_json($result['message'], true); 
        }

        //prize表示奖项内容，v表示中奖几率(若数组中七个奖项的v的总和为100，如果v的值为1，则代表中奖几率为1%，依此类推)
        $prize_arr = array(
            //'0' => array('id' => 1, 'event'=>3, 'prize' => '云马C1智行车', 'v' => 5),
            //'1' => array('id' => 2, 'event'=>3, 'prize' => '小黄鸭', 'v' => 1), // 200个
            //'2' => array('id' => 3, 'event'=>3, 'prize' => '素士牙刷', 'v' => 5),
            '3' => array('id' => 4, 'event'=>2, 'prize' => '30元优惠券', 'v' => 25),
            //'4' => array('id' => 5, 'event'=>3, 'prize' => '电动螺丝刀', 'v' => 5),
            //'5' => array('id' => 6, 'event'=>3, 'prize' => 'KALAR便携筷子', 'v' => 1),  // 200个
            //'6' => array('id' => 7, 'event'=>3, 'prize' => '卡片移动电源', 'v' => 1),   // 100个
            '7' => array('id' => 8, 'event'=>2, 'prize' => '10元优惠券', 'v' => 25),
        );
        foreach ($prize_arr as $k=>$v) {
            $arr[$v['id']] = $v['v'];
        }

        $rand = '';
        $proSum = array_sum($arr); //概率数组的总概率精度 

        foreach ($arr as $k => $v) { //概率数组循环
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $v) {
                $rand = $k;
                break;
            } else {
                $proSum -= $v;
            }
        }
        unset($proArr);
        $prize_id = $rand;

        foreach($prize_arr as $k=>$v){ //获取前端奖项位置
            if($v['id'] == $prize_id){
             $prize_site = $k;
             break;
            }
        }
        $res = $prize_arr[$prize_id -1]; //中奖项 

        $data['prize_name'] = $res['prize'];
        $data['event'] = $res['event'];
        $data['prize_site'] = $prize_site;//前端奖项从-1开始
        $data['prize_id'] = $prize_id;


        if($result['obj']){
            $sid = (string)$result['obj']['_id'];
            $row = array(
                'draw_times' => 2,
                'event' => $data['event'],
                'number_id' => $data['prize_id'],
                'title' => $data['prize_name'],
                'desc' => '',
                'state' => in_array($data['event'], $model->need_contact_user_event()) ? 0 : 1,
            );
            $ok = $model->update_set($sid, $row);
        }else{
            //当前日期
            $today = (int)date('Ymd');
            $row = array(
                'user_id' => $user_id,
                'target_id' => $target_id,
                'day' => $today,
                'event' => $data['event'],
                'ip' => Sher_Core_Helper_Auth::get_ip(),
                'number_id' => $data['prize_id'],
                'title' => $data['prize_name'],
                'desc' => '',
                'state' => in_array($data['event'], $model->need_contact_user_event()) ? 0 : 1,
                'from_to' => $from_to,
                'kind' => $kind,
            );
            //$ok = true;
            $ok = $model->apply_and_save($row);
            if($ok){
                // 获取抽奖记录ID
                $active_draw_record = $model->get_data();
                $sid = (string)$active_draw_record['_id'];  
            }
        }

        // 记录抽奖ID
        $data['sid'] = $sid;

        if(!$ok){
            return $this->ajax_json("操作失败，请重试!", true);
        }

        // 直接送红包
        if($data['event']==2){
            if($data['prize_id']==4){   // 30元优惠券
                $this->give_bonus($user_id, 'FIU_DROW', array('count'=>1, 'xname'=>'FIU_DROW', 'bonus'=>'C', 'min_amounts'=>'B'));
            }elseif($data['prize_id']==8){  // 10元优惠券
                $this->give_bonus($user_id, 'FIU_DROW', array('count'=>1, 'xname'=>'FIU_DROW', 'bonus'=>'G', 'min_amounts'=>'K'));
            }
        }

        return $this->ajax_json('success', false, null, $data);
    }

    public function teeth(){
      $this->stash['page_title_suffix'] = '素士新品';
      //微信分享
      $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
      $timestamp = $this->stash['timestamp'] = time();
      $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
      $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
      $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
      $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
      $this->stash['wxSha1'] = sha1($wxOri);
      return $this->to_html_page('wap/promo/teeth.html');
    }

    //fiu分享邀请好友
    public function fiuinvite(){

        $this->stash['page_title_suffix'] = 'Fiu';

      //微信分享
      $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
      $timestamp = $this->stash['timestamp'] = time();
      $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
      $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
      $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
      $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
      $this->stash['wxSha1'] = sha1($wxOri);

      $invite = isset($this->stash['invite'])?$this->stash['invite']:0;
      $code = isset($this->stash['invite_code'])?$this->stash['invite_code']:0;
      $this->stash['user'] = null;

        $this->stash['is_current_user'] = false;
        $this->stash['yes_login'] = false;
        if($this->visitor->id){
          $this->stash['yes_login'] = true;
        }
        if($code){
            //通过邀请码获取邀请者ID
            $user_invite_id = Sher_Core_Util_View::fetch_invite_user_id($code);
            if($user_invite_id){
              $mode = new Sher_Core_Model_User();
              $user = $mode->extend_load((int)$user_invite_id);
              if($user){
                //判断是否为当前用户
                if($this->stash['yes_login']==true && (int)$this->visitor->id==$user['_id']){
                  $this->stash['is_current_user'] = true;
                }
                $this->stash['user'] = $user;
              }
            }       
        }

        //如果邀请码不是当前用户,刷新页面换为自己的邀请码
        if($this->stash['yes_login']==true){
          if($this->stash['is_current_user']==false){
            //$current_invite_code = Sher_Core_Util_View::fetch_invite_user_code($this->visitor->id);
            //$redirect_url = Doggy_Config::$vars['app.url.wap.promo'].'/fiuinvite?invite_code='.$current_invite_code; 
            //return $this->to_redirect($redirect_url);    
          }
        }
        return $this->to_html_page('wap/promo/fiuinvite.html');
    }

    //商城分享邀请好友
    public function tshare(){
    //微信分享
      $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
      $timestamp = $this->stash['timestamp'] = time();
      $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
      $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
      $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
      $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
      $this->stash['wxSha1'] = sha1($wxOri);

      // 获取用户邀请码 
      if($this->visitor->id){
          $this->stash['invite_code'] = Sher_Core_Util_View::fetch_invite_user_code($this->visitor->id);
      }else{
        $this->stash['invite_code'] = null;
      }
      return $this->to_html_page('wap/promo/tshare.html');
    }

    //商城专题
    public function theme(){
      //微信分享
      $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
      $timestamp = $this->stash['timestamp'] = time();
      $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
      $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
      $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
      $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
      $this->stash['wxSha1'] = sha1($wxOri);
      return $this->to_html_page('wap/promo/theme.html');
    }

    //双11 淘宝
    public function eleven(){
        // 统计兑吧
        $model = new Sher_Core_Model_ViewStat();
        $ok = $model->add_record(2);
        return $this->to_html_page('wap/promo/eleven.html');
    }

    //云马
    public function ym(){
      $this->stash['page_title_suffix'] = '大咖齐聚，马力出行';
      // 记录浏览数
        $num_mode = new Sher_Core_Model_SumRecord();
        $num_mode->add_record('23', 'view_count', 4, 4); 

        //微信分享
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
      return $this->to_html_page('wap/promo/ym.html');
    }

    /**
     * Fiu 下载地址（跳转）
     */
    public function fiu(){
        $url = 'http://m.taihuoniao.com/fiu';
        return $this->to_redirect($url);
    }
	
    /**
     * andorid下载地址
     */
    public function android_download(){
      $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
      $url = 'http://frstatic.qiniudn.com/app-release_009.apk';
      $url = 'http://frstatic.qiniudn.com/download/app_release_010.apk';
  		return $this->to_redirect($url);
    }
	
	/**
	  *披肩
	  */
	public function cappa(){
    // 记录浏览数
	    $num_mode = new Sher_Core_Model_SumRecord();
	    $num_mode->add_record('22', 'view_count', 4, 4); 

		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/cappa.html');
	}
	
    /**
     * 过年神曲活动
     *  date: 2016/01/27
     */
    public function holiday(){

		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/holiday.html');
	}
	
    /**
     * 过年神曲活动
     *  date: 2016/01/28
     */
    public function hoshow(){
		// 记录浏览数
	    $num_mode = new Sher_Core_Model_SumRecord();
	    $num_mode->add_record('20', 'view_count', 4, 4); 

		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		$from = isset($this->stash['from']) ? $this->stash['from'] : 0;
		
		switch( $from ){
			case 'xngqlc':
				$this->stash['xngqlc'] = true;
				break;
			case 'gxgx':
				$this->stash['gxgx'] = true;
				break;
			case 'nzh':
				$this->stash['nzh'] = true;
				break;
			case 'hlzgn':
				$this->stash['hlzgn'] = true;
				break;
			case 'xnh':
				$this->stash['xnh'] = true;
				break;
			case 'csl':
				$this->stash['csl'] = true;
				break;
			case 'nwjx':
				$this->stash['nwjx'] = true;
				break;
			case 'gxfc':
				$this->stash['gxfc'] = true;
				break;
			case 'fcff':
				$this->stash['fcff'] = true;
				break;
			default:
				return $this->to_html_page('wap/promo/holiday.html');
			
		}
		
		return $this->to_html_page('wap/promo/hoshow.html');
	}
	
  /**
   * 2016 CES
   *  @author tianshuai
   *  date: 2016/01/05
   */
  public function zces(){
		// 记录浏览数
    $num_mode = new Sher_Core_Model_SumRecord();
    $num_mode->add_record('19', 'view_count', 4, 4); 

		//微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);

		return $this->to_html_page('wap/promo/zces.html');
  }

  public function zzces(){

		//微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);

		return $this->to_html_page('wap/promo/zzces.html');
  }
	
	
	/**
	  * 微波移动电源
	 **/
	public function source(){
		$this->stash['page_title_suffix'] = '神卜鸟仙：你的2016运势大揭秘';
		// 记录浏览数
	    $num_mode = new Sher_Core_Model_SumRecord();
	    $num_mode->add_record('18', 'view_count', 4, 4); 

    // 统计来源－－触宝
    if(isset($this->stash['from']) && $this->stash['from']=='cb'){
 	    $view_stat_mode = new Sher_Core_Model_ViewStat();
      $ok = $view_stat_mode->add_record(1);
    }
		
		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/source.html');
	}
	
	
	/**
	  *奶爸辣妈
	  */
	public function mf(){
		$this->stash['page_title_suffix'] = '你是几颗星的辣妈or奶爸？';
		// 记录浏览数
	    $num_mode = new Sher_Core_Model_SumRecord();
	    $num_mode->add_record('17', 'view_count', 4, 4); 
		
		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/mf.html');
	}
	
	/**
	  *小蚁
	  */
	public function xy(){
    // 记录浏览数
    $num_mode = new Sher_Core_Model_SumRecord();
    $num_mode->add_record('16', 'view_count', 4, 4); 

		//微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/xiaoyi.html');
	}	
	
  /**
   * 评论投票分享
   */
  public function comment_vote_share(){
  
    $comment_id = isset($this->stash['comment_id']) ? $this->stash['comment_id'] : null;

    $redirect_url = Doggy_Config::$vars['app.url.wap']; 
    if(empty($comment_id)){
      return $this->to_redirect($redirect_url); 
    }

    $comment_model = new Sher_Core_Model_Comment();
    $comment = $comment_model->load($comment_id);
    if(empty($comment)){
      return $this->to_redirect($redirect_url);   
    }

    $comment_type = $comment['type'];
    $target_id = $comment['target_id'];
    $comment_mark = sprintf("%s-%d", $target_id, $comment_type);

    $block_str = Sher_Core_Util_View::load_block('comment_vote_content');
    if(empty($block_str)){
      return $this->to_redirect($redirect_url);    
    }

    $result = array();

    $list_arr = explode(';;', $block_str);
    for($i=0;$i<count($list_arr);$i++){
      $item_arr = explode('||', $list_arr[$i]);
      if($item_arr[0]==$comment_mark){
        $result = array(
          'target_id' => $target_id,
          'comment_type' => $comment_type,
          'title' => $item_arr[1],
          'desc' => $item_arr[2],
          'cover_url' => $item_arr[3],
          'banner_url' => $item_arr[4],
          'evt' => $item_arr[5],
          'view_url' => sprintf(Doggy_Config::$vars['app.url.wap.social.show'], $target_id, 0),
          'love_count' => $comment['love_count'],
        );
      }
    }

    if(empty($result)){
      return $this->to_redirect($redirect_url);     
    }

    // 判断是否是当前用户
    $is_current_user = false;
    if($this->visitor->id){
      if($this->visitor->id==$comment['user_id']){
        $is_current_user = true;
      }
    }

    $this->stash['is_current_user'] = $is_current_user;
    $this->stash['result'] = $result;

    //微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);

    return $this->to_html_page('wap/promo/comment_vote.html');
  }

	/**
	 * 创造提交 
	 */
	public function coin_submit(){
    session_start();
    if(!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code'])){
      $_SESSION['captcha_code'] = md5(microtime(true));
    }
    $this->stash['captcha_code'] = $_SESSION['captcha_code'];
		$this->stash['page_title_suffix'] = '[ 创 x 造 ]';
		return $this->to_html_page('wap/promo/coin_submit.html');
	}
	
	/**
	 * 创造 
	 */
	public function coin(){
		$this->stash['page_title_suffix'] = '[ 创 x 造 ]';
      // 记录浏览数
      $num_mode = new Sher_Core_Model_SumRecord();
      $num_mode->add_record('12', 'view_count', 4, 4); 

			//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		
		return $this->to_html_page('wap/promo/coin.html');
	}
	
  /**
   * 兑吧抽奖送红包活动
   */
  public function db_bonus(){
    $this->stash['is_obtain'] = false;
    // 验证是否登录用户 
    if($this->visitor->id){
      // 判断用户是否已领取
      $attend_model = new Sher_Core_Model_Attend();
      $data = array(
        'user_id' => $this->visitor->id,
        'target_id' => 6,
        'event' => 5,
      );
      $attend = $attend_model->first($data);

      // 验证用户是否已领过红包 
      if(!empty($attend)){
        $this->stash['is_obtain'] = true;
      }

    }
    
    return $this->to_html_page('wap/promo/db_bonus.html');
  }

  /**
   * 送红包活动
   */
  public function send_bonus(){
    $this->stash['is_obtain'] = false;
    // 验证是否登录用户 
    if($this->visitor->id){
      // 判断用户是否已领取
      $attend_model = new Sher_Core_Model_Attend();
      $data = array(
        'user_id' => $this->visitor->id,
        'target_id' => 7,
        'event' => 5,
      );
      $attend = $attend_model->first($data);

      // 验证用户是否已领过红包 
      if(!empty($attend)){
        $this->stash['is_obtain'] = true;
      }

    }
    
    return $this->to_html_page('wap/promo/send_bonus.html');
  }
	
	/**
	 * 造逆 
	 */
	public function jdzn(){
			$this->stash['page_title_suffix'] = '『造·逆』一场逆向思维的智能硬件营销论剑';

      // 记录浏览数
      $num_mode = new Sher_Core_Model_SumRecord();
      $num_mode->add_record('11', 'view_count', 4, 4); 

			//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
			return $this->to_html_page('wap/promo/jdzn.html');
	}

	/**
	 * 签到 抽奖
	 */
	public function sign(){
		$this->set_target_css_state('page_social');

    // 记录兑吧来的用户，统计注册量
    if(isset($this->stash['from']) && $this->stash['from']=='db'){
      // 存cookie
      @setcookie('from_origin', '2', time()+3600, '/');
      $_COOKIE['from_origin'] = '2';
      @setcookie('from_target_id', '4', time()+3600, '/');
      $_COOKIE['from_target_id'] = '4';

      // 统计点击数量
      $dig_model = new Sher_Core_Model_DigList();
      $dig_key = Sher_Core_Util_Constant::DIG_THIRD_DB_STAT;

      $dig = $dig_model->load($dig_key);
      if(empty($dig) || !isset($dig['items']["view_04"])){
        $dig_model->update_set($dig_key, array("items.view_04"=>1), true);     
      }else{
        // 增加浏览量
        $dig_model->inc($dig_key, "items.view_04", 1);
      }
      
    }

		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
    $this->stash['provinces'] = $provinces;
    $this->stash['day'] = date('Ymd');

    //微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/sign.html');
	}

	/**
	 * 签到 抽奖 for APP
	 */
	public function sign_app(){
    $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
    $from_to = isset($this->stash['from_to']) ? $this->stash['from_to'] : 0;
    $app_key = isset($this->stash['app_key']) ? $this->stash['app_key'] : null;
    $sign = isset($this->stash['sign']) ? $this->stash['sign'] : null;

    // 检查用户是否存在或登录
    $sign_options = array(
      'uuid' => $uuid,
      'from_to' => $from_to,
      'app_key' => $app_key,
      'sign' => $sign,
    );

		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
    $this->stash['provinces'] = $provinces;
    $this->stash['day'] = date('Ymd');

		return $this->to_html_page('wap/promo/sign_app.html');
	}
	
	
	public function draw(){

    // 判断是否为微信浏览器
    if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
      // 设置cookie
      @setcookie('auth_return_url', Doggy_Config::$vars['app.url.wap.promo'].'/draw', 0, '/');
      $is_weixin = true;
    }else{
      $is_weixin = false;
    }

		// 获取session id
    $service = Sher_Core_Session_Service::instance();
    $sid = $service->session->id;

    // 微信登录参数
    $wx_params = array(
      'app_id' => Doggy_Config::$vars['app.wx.app_id'],
      'redirect_uri' => $redirect_uri = urlencode(Doggy_Config::$vars['app.url.domain'].'/app/wap/weixin/call_back'),
      'state' => md5($sid),
    );

		//微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);

    $this->stash['is_weixin'] = $is_weixin;
    $this->stash['wx_params'] = $wx_params;

		return $this->to_html_page('wap/promo/draw.html');
	}	
	
	/**
	 * idea
	 */
	public function idea(){
		$this->stash['page_title_suffix'] = '金投赏巅峰对话：从广告营销看工业设计的跨界融合';
		//微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/idea.html');
	}
	
	
  /**
   * 手机扫码送红包
   */
  public function fetch_bonus(){

    if(!$this->visitor->id){
      $redirect_url = Doggy_Config::$vars['app.url.wap'].'/auth/login'; 
      echo '<script>window.location="'. $redirect_url .'"</script>';exit;
    }
    $redirect_url = Doggy_Config::$vars['app.url.wap'].'/my/bonus'; 

    $user_id = $this->visitor->id;
    $xname = 'SQR';

    // 获取红包
    $bonus = new Sher_Core_Model_Bonus();

    // 先判断用户是否领取过该红包
    $has_one = $bonus->first(array('user_id'=>$user_id, 'xname'=>'SQR'));
    if($has_one){
      return $this->to_redirect($redirect_url);   
    }

    $result_code = $bonus->pop($xname);
    
    // 获取为空，重新生产红包
    while(empty($result_code)){
      //指定生成红包
      $bonus->create_specify_bonus(5, $xname, 'C', 'C', 0);
      $result_code = $bonus->pop($xname);
      // 跳出循环
      if(!empty($result_code)){
        break;
      }
    }
    
    // 赠与红包 使用默认时间30天
    $end_time = 0;
    $code_ok = $bonus->give_user($result_code['code'], $user_id, $end_time);
    return $this->to_redirect($redirect_url); 
  }
	/**
	* 2015 辣妈奶爸神嘴pk
	*/
	public function rank2(){
		$this->stash['size'] = isset($this->stash['size']) ? (int)$this->stash['size'] : 20;

    $dig_model = new Sher_Core_Model_DigList();
    $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_03;

    $this->stash['id'] = 1;
    $this->stash['count_01'] = $count_01 = 0;
    $this->stash['count_02'] = $count_02 = 0;
    $this->stash['total_count'] = 0;
    $this->stash['view_count'] = 0;
    $this->stash['comment_count'] = 0;

    $dig = $dig_model->load($dig_key);
    if(empty($dig)){
      $dig_model->update_set($dig_key, array('items.id'=>1, 'items.count_01'=>0, 'items.count_02'=>0, 'items.total_count'=>0, 'items.view_count'=>0, 'items.comment_count'=>0), true);     
    }else{
      $this->stash['count_01'] = $count_01 = $dig['items']['count_01'];
      $this->stash['count_02'] = $count_02 = $dig['items']['count_02'];
      $this->stash['total_count'] = $dig['items']['total_count'];
      $this->stash['view_count'] = $dig['items']['view_count'];
      $this->stash['comment_count'] = $dig['items']['comment_count'];
    }

    //  判断用户是否已投票
    $this->stash['has_support'] = 0;
    $this->stash['support_cid'] = 0;
    if($this->visitor->id){
      $mode_attend = new Sher_Core_Model_Attend();
      $attend = $mode_attend->first(array('user_id'=>$this->visitor->id, 'target_id'=>5, 'event'=>5));
      if(!empty($attend)){
        $this->stash['has_support'] = 1;
        $this->stash['support_cid'] = $attend['cid'];     
      }   
    }

    // 增加浏览量
    $dig_model->inc($dig_key, "items.view_count", 1);

		// 评论参数
		$comment_options = array(
		  'comment_target_id' =>  5,
		  'comment_target_user_id' => 0,
		  'comment_type'  =>  10,
		  'comment_pager' =>  '',
		  //是否显示上传图片/链接
		  'comment_show_rich' => 1,
		);
		$this->_comment_param($comment_options);

		$pager_url = sprintf("%s/promo/rank2?sort=%d&page=#p##comment_top", Doggy_Config::$vars['app.url.wap'], $this->stash['sort']);
		$this->stash['pager_url'] = $pager_url;

		//微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['share_url'] = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/rank2.html');
	}
	/**
	 * 2015 云马Ｃ1神嘴pk
	 */
	public function rank(){
    $this->stash['size'] = isset($this->stash['size']) ? (int)$this->stash['size'] : 20;

    $dig_model = new Sher_Core_Model_DigList();
    $dig_key = Sher_Core_Util_Constant::DIG_SUBJECT_YMC1_01;

    $this->stash['id'] = 1;
    $this->stash['count_01'] = $count_01 = 0;
    $this->stash['count_02'] = $count_02 = 0;
    $this->stash['total_count'] = 0;
    $this->stash['view_count'] = 0;
    $this->stash['comment_count'] = 0;

    $dig = $dig_model->load($dig_key);
    if(empty($dig)){
      $dig_model->update_set($dig_key, array('items.id'=>1, 'items.count_01'=>0, 'items.count_02'=>0, 'items.total_count'=>0, 'items.view_count'=>0, 'items.comment_count'=>0), true);     
    }else{
      $this->stash['count_01'] = $count_01 = $dig['items']['count_01'];
      $this->stash['count_02'] = $count_02 = $dig['items']['count_02'];
      $this->stash['total_count'] = $dig['items']['total_count'];
      $this->stash['view_count'] = $dig['items']['view_count'];
      $this->stash['comment_count'] = $dig['items']['comment_count'];
    }

    //  判断用户是否已投票
    $this->stash['has_support'] = 0;
    $this->stash['support_cid'] = 0;
    if($this->visitor->id){
      $mode_attend = new Sher_Core_Model_Attend();
      $attend = $mode_attend->first(array('user_id'=>$this->visitor->id, 'target_id'=>1, 'event'=>5));
      if(!empty($attend)){
        $this->stash['has_support'] = 1;
        $this->stash['support_cid'] = $attend['cid'];     
      }   
    }

    // 增加浏览量
    $dig_model->inc($dig_key, "items.view_count", 1);

		// 评论参数
		$comment_options = array(
		  'comment_target_id' =>  1,
		  'comment_target_user_id' => 0,
		  'comment_type'  =>  10,
		  'comment_pager' =>  '',
		  //是否显示上传图片/链接
		  'comment_show_rich' => 1,
		);
		$this->_comment_param($comment_options);

		$pager_url = sprintf("%s/promo/rank?sort=%d&page=#p##comment_top", Doggy_Config::$vars['app.url.wap'], $this->stash['sort']);
		$this->stash['pager_url'] = $pager_url;

		//微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['share_url'] = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);

		return $this->to_html_page('wap/promo/rank.html');
	}
	
	/**
	 *  注册分享页面
	 */
	public function request(){
		$this->stash['page_title_suffix'] = '太火鸟送你红包100元，马上点击查看！';
    $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$redirect_url = Doggy_Config::$vars['app.url.wap'];

    if(empty($user_id)){
      return $this->show_message_page('缺少请求参数！', $redirect_url);   
    }

    $this->stash['is_current_user'] = false;
    if($this->visitor->id){
      if($this->visitor->id == $user_id){
        $this->stash['is_current_user'] = true;       
      }
    }

    $user_model = new Sher_Core_Model_User();
    $user = $user_model->extend_load($user_id);
    if(empty($user)){
      return $this->show_message_page('用户不存在！', $redirect_url);    
    }

    //当前用户邀请码
    $this->stash['invite_code'] = Sher_Core_Util_View::fetch_invite_user_code($user_id);

    $this->stash['user'] = $user;

		//微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['share_url'] = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/request.html');
	}
	
	public function din(){
		$this->stash['page_title_suffix'] = 'D3in';
		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);

      $active_id = 12024;
      //$active_id = 4;
      $active_model = new Sher_Core_Model_Active();
      $active = $active_model->load($active_id);

      $redirect_url = Doggy_Config::$vars['app.url.wap.active'];

      if(empty($active) || $active['deleted']){
        return $this->show_message_page('访问的活动不存在或已被删除！', $redirect_url);
      }

      if($active['state']==0){
        return $this->show_message_page('该活动已被禁用！', $redirect_url); 
      }

      $this->stash['is_attend'] = false;
      $this->stash['user_info'] = array();
      //验证用户是否已报名
      if ($this->visitor->id){
        $this->stash['user_info'] = &$this->stash['visitor'];
        $mode_attend = new Sher_Core_Model_Attend();
        $this->stash['is_attend'] = $mode_attend->check_signup($this->visitor->id, $active['_id'], 1);
      }

      $this->stash['d3in_h5'] = true;
      $this->stash['active'] = $active;

		  return $this->to_html_page('wap/promo/din.html');
	}
	
	/**
	 * 招聘
	 */
	public function hy(){
		$this->stash['page_title_suffix'] = '绝密行动 代号“火眼”';

    // 记录浏览数
    $num_mode = new Sher_Core_Model_SumRecord();
    $num_mode->add_record('7', 'view_count', 4, 4); 

		//微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/hy.html');
	}
	
	/**
	 * 2015 七夕
	 */
	public function qixi(){
		return $this->to_html_page('wap/promo/qixi.html');
	}
	
	/**
	 * 招聘
	 */
	public function zp(){
		$this->stash['page_title_suffix'] = '年轻多金潜力股，求扑倒！';
		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/promo/zp.html');
	}
	
	/**
	 * 69
	 */
	public function xin(){
		
		$this->stash['page_title_suffix'] = '69';
		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		
		return $this->to_html_page('wap/promo/xin.html');
	}
	
	/**
	 * 69
	 */
	public function six(){
		
		$this->stash['page_title_suffix'] = '69';
		//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		
		return $this->to_html_page('wap/promo/six.html');
	}
	
	/**
	 * 深圳蛋年专题
	 */
	public function sz(){
		return $this->to_html_page('wap/promo/sz.html');
	}
	
	/**
	 * 京东
	 */
	public function jd(){
    //微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);

		return $this->to_html_page('wap/promo/jd.html');
	}
	
	/**
	 * 千万红包
	 */
	public function year(){
		$code = isset($this->stash['invite_code'])?$this->stash['invite_code']:0;
    $this->stash['is_current_user'] = false;
    $this->stash['yes_login'] = false;
    if($this->visitor->id){
      $this->stash['yes_login'] = true;
    }
    //通过邀请码获取邀请者ID
    $user_invite_id = Sher_Core_Util_View::fetch_invite_user_id($code);
    if($user_invite_id){
      $mode = new Sher_Core_Model_User();
      $user = $mode->find_by_id((int)$user_invite_id);
      if($user){
        //判断是否为当前用户
        if($this->stash['yes_login']==true && (int)$this->visitor->id==$user['_id']){
          $this->stash['is_current_user'] = true;
        }
      }
    }
    //如果邀请码不是当前用户,刷新页面换为自己的邀请码
    if($this->stash['yes_login']==true){
      if($this->stash['is_current_user']==false){
        $current_invite_code = Sher_Core_Util_View::fetch_invite_user_code($this->visitor->id);
        $redirect_url = Doggy_Config::$vars['app.url.wap.promo'].'/year?invite_code='.$current_invite_code; 
        return $this->to_redirect($redirect_url);    
      }
    }
		return $this->to_html_page('wap/promo/oneyear.html');
	}
	
	/**
	 * 千万红包
	 */
	public function coupon(){
		$total_times = 3;
		
		// 验证领取次数
		$current_data = date('Ymd', time());
		$cache_key = sprintf('bonus_%s_%d', $current_data, $this->visitor->id);
		$redis = new Sher_Core_Cache_Redis();
		$times = (int)$redis->get($cache_key);
		
		$this->stash['left_times'] = $total_times - $times;
		
		// 检测是否还有红包
		$bonus = new Sher_Core_Model_Bonus();
		$query = array(
			'used' => Sher_Core_Model_Bonus::USED_DEFAULT,
			'status' => Sher_Core_Model_Bonus::STATUS_OK,
		);
		$result = $bonus->first($query);
		if(!empty($result)){
			$has_bonus = true;
		}else{
			$has_bonus = false;
		}
		$this->stash['has_bonus'] = $has_bonus;
		
		return $this->to_html_page('wap/tweleve.html');
	}
	
	/**
	 *造梦者空气净化器
	 */
	public function dreamk(){
		$product_id = Doggy_Config::$vars['app.comeon.product_id'];
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$product_id);
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		// 验证是否还有库存
		$product['can_saled'] = $model->can_saled($product);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $product_id);
		
		$this->stash['product'] = $product;

	    $this->stash['is_time'] = false;
	    if($product['can_saled']){
	      if($product['snatched_time']<time()){
	        $this->stash['is_time'] = true;
	      }
	    }
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
    	$this->stash['has_address'] = false;
		
		// 验证是否预约
		if($this->visitor->id){
			$cache_key = sprintf('mask_%d_%d', $product_id, $this->visitor->id);
			$redis = new Sher_Core_Cache_Redis();
		    $appointed = $redis->get($cache_key);
		    //是否有默认地址
		    $addbook_model = new Sher_Core_Model_AddBooks();
		    $addbook = $addbook_model->first(array('user_id'=>$this->visitor->id));
		    if(!empty($addbook)){
		        $this->stash['has_address'] = true;
		    }
		}else{
			$appointed = false;
		}
		$this->stash['appointed'] = $appointed;
		
		return $this->to_html_page('wap/dreamk.html');
	}
	
	/**
	 * 获取红包
	 */
	public function got_bonus(){		
		$total_times = 3;
		// 验证领取次数
		$current_data = date('Ymd', time());
		$cache_key = sprintf('bonus_%s_%d', $current_data, $this->visitor->id);
		
		$redis = new Sher_Core_Cache_Redis();
		$times = $redis->get($cache_key);
		
		// 设置初始化次数
		if(!$times){
			$times = 0;
		}
		if($times >= $total_times){
			return $this->ajax_note('今天3次机会已用完，明天再来吧！', true);
		}
		
		// 获取红包
		$bonus = new Sher_Core_Model_Bonus();
		$result = $bonus->pop('T9');
		
		if(empty($result)){
			return $this->ajax_note('红包已抢光了,等待下次机会哦！', true);
		}
		
		// 获取为空，重新生产红包
		/*
		while(empty($result)){
			$bonus->create_batch_bonus(10);
			$result = $bonus->pop('T9');
			// 跳出循环
			if(!empty($result)){
				break;
			}
		}*/
		
		// 赠与红包
		$ok = $bonus->give_user($result['code'], $this->visitor->id);
		if($ok){
			$times += 1;
			$left_times = $total_times - $times;
			
			// 设置次数
			$redis->set($cache_key, $times++);
			
			$this->stash['left_times'] = $left_times;
		}
		
		$this->stash['bonus'] = $result;
		
		return $this->to_taconite_page('ajax/bonus_ok.html');
	}

  	/**
   	 * 55杯-支持原创－专题
     */
  public function chinadesign(){
    //微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    if(empty($_SERVER['QUERY_STRING'])){
        $url = $this->stash['current_url'] = Doggy_Config::$vars['app.url.wap'].'/promo/chinadesign';  
    }else{
        $url = $this->stash['current_url'] = Doggy_Config::$vars['app.url.wap'].'/promo/chinadesign?'.$_SERVER['QUERY_STRING'];   
    }
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
    return $this->to_html_page('wap/chinadesign.html');
  }
	
	/**
	 * 陌陌新年专题
	 */
	public function momo(){
		$product_ids = array(1082995029,1011468351,1060500658,1060600664,1120700122);
		$relate_ids = array(1111556004,1120700195,1120666085,1092169929,1121112153,1120874607);
		
		$this->stash['product_ids'] = $product_ids;
		$this->stash['relate_ids'] = $relate_ids;
		return $this->to_html_page('wap/momo.html');
	}
	
	/**
	 * watch
	 */
	public function watch(){
    $model = new Sher_Core_Model_SubjectRecord();
    $query['target_id'] = 1;
		$query['event'] = Sher_Core_Model_SubjectRecord::EVENT_APPOINTMENT;
    //预约虚拟数量---取块内容
    $invented_num = Sher_Core_Util_View::load_block('apple_watch_invented_num', 1);
    if(!empty($invented_num)){
      $invented_num = (int)$invented_num;
    }else{
      $invented_num = 0;   
    }
    //统计预约数量---有性能问题,时间紧迫,过后再调整
    $this->stash['appoint_count'] = $model->count($query) + $invented_num;

    //判断当前用户是否预约过
    $is_appoint = false;
    if($this->visitor->id){
      $this->stash['user_info'] = &$this->stash['visitor'];
      $is_appoint = $model->check_appoint($this->visitor->id, 1);
    }

    $this->stash['is_appoint'] = $is_appoint;
		return $this->to_html_page('wap/promo/watch.html');
	}

  /**
   * 用户补全资料并预约
   */
  public function ajax_appoint(){
    if(!isset($this->stash['target_id'])){
			return $this->ajax_note('请求失败,缺少必要参数', true);
    }

    $r_model = new Sher_Core_Model_SubjectRecord();

    $is_appoint = $r_model->check_appoint($this->visitor->id, (int)$this->stash['target_id']);
    if($is_appoint){
 			return $this->ajax_note('不能重复预约!', true);  
    }

    if(isset($this->stash['is_user_info']) && (int)$this->stash['is_user_info']==1){
      if(empty($this->stash['realname']) || empty($this->stash['phone'])){
 			  return $this->ajax_note('请求失败,缺少用户必要参数', true); 
      }

      $user_data = array();
      $user_data['profile']['realname'] = $this->stash['realname'];
      $user_data['profile']['phone'] = $this->stash['phone'];

      try {
        //更新基本信息
        $user_ok = $this->visitor->save($user_data);
        if(!$user_ok){
          return $this->ajax_note("更新用户信息失败", true);  
        }
      } catch (Sher_Core_Model_Exception $e) {
        Doggy_Log_Helper::error('Failed to active attend update profile:'.$e->getMessage());
        return $this->ajax_note("更新失败:".$e->getMessage(), true);
      }
    
    }

    $data = array();
    $data['user_id'] = (int)$this->visitor->id;
    $data['target_id'] = (int)$this->stash['target_id'];
    $data['event'] = Sher_Core_Model_SubjectRecord::EVENT_APPOINTMENT;
    try{
      $ok = $r_model->add_appoint($data['user_id'], $data['target_id']);
      if($ok){
		    return $this->to_taconite_page('ajax/promo_appoint_ok.html');
      }else{
  			return $this->ajax_note('预约失败!', true);   
      }  
    }catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save subject_record appoint failed: ".$e->getMessage());
 			return $this->ajax_note('预约失败.!', true); 
    }
  }

  /**
   * 周年庆邀请好友单页面
   */
  public function year_invite(){
    $code = isset($this->stash['invite_code'])?$this->stash['invite_code']:0;
    $this->stash['user'] = null;
    $this->stash['is_current_user'] = false;
    $this->stash['yes_login'] = false;
    //通过邀请码获取邀请者ID
    if($code){
      $user_invite_id = Sher_Core_Util_View::fetch_invite_user_id($code);
      if($user_invite_id){
        $mode = new Sher_Core_Model_User();
        $user = $mode->find_by_id((int)$user_invite_id);
        if($user){
          $this->stash['user'] = $user;
          //判断是否为当前用户
          if($this->visitor->id){
            $this->stash['yes_login'] = true;
            if((int)$this->visitor->id==$user['_id']){
              $this->stash['is_current_user'] = true;
            }
          }
        }
      }
    }
		return $this->to_html_page('wap/promo/year_invite.html');
  }

  /**
   * 京东报名-普通用户
   */
  public function sign_jd(){
    
    return $this->to_html_page('wap/promo/sign_jd.html');
  
  }

  /**
   * 金投赏－－报名
   */
  public function idea_sign(){
    
    if($this->visitor->id){
      $this->stash['user_id'] = $this->visitor->id;
    }else{
      $this->stash['user_id'] = 0;
    }
    return $this->to_html_page('wap/promo/idea_sign.html');
  
  }

  /**
   * 京东报名-参展用户
   */
  public function sign_t_jd(){
    $row = array();
    $this->stash['mode'] = 'create';

    $callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
    $this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
    $this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;

    $this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
    $this->stash['pid'] = new MongoId();
  
    $this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_CONTACT;

		$this->stash['contact'] = $row;
    
    return $this->to_html_page('wap/promo/sign_t_jd.html');
  
  }

  /**
   * 保存京东报名
   */
  public function save_sign_jd(){

    $model = new Sher_Core_Model_SubjectRecord();

    $is_sign = $model->check_appoint($this->visitor->id, 2, 3);

    if($is_sign){
      return $this->ajax_note('您已报名!', true);
    }

    if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['company']) || empty($this->stash['job'])){
      return $this->ajax_note('请求失败,缺少用户必要参数!', true);
    }

    $user_data = array();
    $user_data['profile']['realname'] = $this->stash['realname'];
    $user_data['profile']['phone'] = $this->stash['phone'];
    $user_data['profile']['company'] = $this->stash['company'];
    $user_data['profile']['job'] = $this->stash['job'];

    try {
      //更新基本信息
      $user_ok = $this->visitor->save($user_data);
      if(!$user_ok){
        return $this->ajax_note('更新用户信息失败!', true);
      }
    } catch (Sher_Core_Model_Exception $e) {
      return $this->ajax_note("更新失败:".$e->getMessage(), true);
    }

    $data = array();
    $data['user_id'] = (int)$this->visitor->id;
    $data['target_id'] = 2;
    $data['event'] = 3;
    try{
      $ok = $model->apply_and_save($data);
      if($ok){
        $redirect_url = Doggy_Config::$vars['app.url.wap.promo'].'/jd';
    	  $this->stash['is_error'] = false;
        $this->stash['show_note_time'] = 3000;
    	  $this->stash['note'] = '申请已提交,审核通过后我们将第一时间短信通知您!';
		    $this->stash['redirect_url'] = $redirect_url;
		    return $this->to_taconite_page('ajax/note.html');
      }else{
        return $this->ajax_note('报名失败!', true);
      }  
    }catch(Sher_Core_Model_Exception $e){
      return $this->ajax_note('报名失败!'.$e->getMessage(), true);
    }
  
  }

  /**
   * 保存报名/预约用户信息
   */
  public function save_sign(){

    $target_id = isset($this->stash['target_id'])?(int)$this->stash['target_id']:0;
    $event = isset($this->stash['event'])?$this->stash['event']:1;

    if(empty($target_id)){
      return $this->ajax_note('参数不存在!', true);   
    }

    $model = new Sher_Core_Model_SubjectRecord();
    $is_sign = $model->check_appoint($this->visitor->id, $target_id, $event);

    if($is_sign){
      return $this->ajax_note('您已经参与,不能重复操作!', true);
    }

    if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['company']) || empty($this->stash['job'])){
      return $this->ajax_note('请求失败,缺少用户必要参数!', true);
    }

    $user_data = array();
    $user_data['profile']['realname'] = $this->stash['realname'];
    $user_data['profile']['phone'] = $this->stash['phone'];
    $user_data['profile']['company'] = $this->stash['company'];
    $user_data['profile']['job'] = $this->stash['job'];

    try {
      //更新基本信息
      $user_ok = $this->visitor->save($user_data);
      if(!$user_ok){
        return $this->ajax_note('更新用户信息失败!', true);
      }
    } catch (Sher_Core_Model_Exception $e) {
      return $this->ajax_note("更新失败:".$e->getMessage(), true);
    }

    $data = array();
    $data['user_id'] = (int)$this->visitor->id;
    $data['target_id'] = $target_id;
    $data['event'] = $event;
    $data['info'] = $user_data['profile'];
    $data['option01'] = isset($this->stash['option01'])?(int)$this->stash['option01']:0;
    $data['option02'] = isset($this->stash['option02'])?(int)$this->stash['option02']:0;
    try{
      $ok = $model->apply_and_save($data);
      if($ok){
        if($target_id==3){
          $redirect_url = Doggy_Config::$vars['app.url.wap'].'/birdegg/sz_share';
    	    $this->stash['note'] = '申请已提交，我们会尽快短信通知您审核结果!';
        }else{
          $redirect_url = Doggy_Config::$vars['app.url.wap'];
    	    $this->stash['note'] = '操作成功!';
        }

    	  $this->stash['is_error'] = false;
        $this->stash['show_note_time'] = 2000;

		    $this->stash['redirect_url'] = $redirect_url;
		    return $this->to_taconite_page('ajax/note.html');
      }else{
        return $this->ajax_note('保存失败!', true);
      }  
    }catch(Sher_Core_Model_Exception $e){
      return $this->ajax_note('保存失败!'.$e->getMessage(), true);
    }
  
  }

	/**
	 * 判断用户是否重复分享
	 */
  public function zp_share(){

    $result = array('no_share'=>0, 'no_login'=>0, 'is_share'=>0, 'success'=>1, 'msg'=>'');

    if($this->visitor->id){
      $record_model = new Sher_Core_Model_SubjectRecord();
      // 是否分享过
      $is_share = $record_model->check_appoint($this->visitor->id, 4, 4);
      if($is_share){
        $result['is_share'] = 1;
      }else{
        // 送红包(30元,满99可用)
				$ok = $this->give_bonus($this->visitor->id, 'ZP', array('count'=>5, 'xname'=>'ZP', 'bonus'=>'C', 'min_amounts'=>'A'));
        if($ok){
          $record_model->add_appoint($this->visitor->id, 4, array('event'=>4));
          $result['no_share'] = 1;
        }else{
          $result['is_success'] = 0;
          $result['msg'] = '!';
          return $this->show_message_page('赠送失败!', Doggy_Config::$vars['app.url.wap']);
        }
      }
    }else{
      $result['no_login'] = 1;
    }
    $this->stash['result'] = $result;
    return $this->to_html_page('wap/promo/zp_share.html');
	}

  /**
   * 通用报名入口，直接注册
   */
  public function common_sign(){
    session_start();
    if(!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code'])){
      $_SESSION['captcha_code'] = md5(microtime(true));
    }
    $this->stash['captcha_code'] = $_SESSION['captcha_code'];

    $target_id = (int)$this->stash['target_id'];
    $event = isset($this->stash['event'])? (int)$this->stash['event'] : 3;
    $from_to = isset($this->stash['from_to'])? (int)$this->stash['from_to'] : 1;
    $redirect_url = Doggy_Config::$vars['app.url.wap'];

    if(empty($target_id) || empty($event)){
 			return $this->show_message_page('缺少请求参数！', $redirect_url);     
    }

    return $this->to_html_page('wap/promo/common_sign.html');
  
  }

  /**
   * 火眼报名入口，直接注册
   */
  public function hy_sign(){
    return $this->to_html_page('wap/promo/hy_sign.html');
  }


  /**
   * test
   */
  public function test(){
    return $this->to_html_page('wap/test.html'); 
  }

  //红包赠于
  protected function give_bonus($user_id, $xname, $options=array()){
    if(empty($options)){
      return false;
    }
    // 获取红包
    $bonus = new Sher_Core_Model_Bonus();
    $result_code = $bonus->pop($xname);
    
    // 获取为空，重新生产红包
    while(empty($result_code)){
      //指定生成红包
      $bonus->create_specify_bonus($options['count'], $options['xname'], $options['bonus'], $options['min_amounts']);
      $result_code = $bonus->pop($xname);
      // 跳出循环
      if(!empty($result_code)){
        break;
      }
    }
    
    // 赠与红包 使用默认时间7天 $end_time = strtotime('2015-06-30 23:59')
    $end_time = 0;
    $code_ok = $bonus->give_user($result_code['code'], $user_id, $end_time);
    return $code_ok;
  }

  /**
   * 评论参数
   */
  protected function _comment_param($options){
        $this->stash['comment_target_id'] = $options['comment_target_id'];
        $this->stash['comment_target_user_id'] = $options['comment_target_user_id'];
        $this->stash['comment_type'] = $options['comment_type'];
		// 评论的链接URL
		$this->stash['pager_url'] = isset($options['comment_pager'])?$options['comment_pager']:0;

        // 是否显示图文并茂
        $this->stash['comment_show_rich'] = isset($options['comment_show_rich'])?$options['comment_show_rich']:0;
		// 评论图片上传参数
		$this->stash['comment_token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['comment_domain'] = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$this->stash['comment_asset_type'] = Sher_Core_Model_Asset::TYPE_COMMENT;
		$this->stash['comment_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
  }

  /**
   * 获取启动图
   */
  public function load_up_img(){
      $img_url = '';
      //$img_url = 'http://frstatic.qiniudn.com/images/app_store_load.png';
      $img_url = 'http://frbird.qiniudn.com/asset/160803/57a1d1d8fc8b12304c8b85aa-1-hu.jpg';
      return $this->api_json('success', 0, array('img_url'=>$img_url));
  }
	
}

