<?php
  /**
   * 易购网接口处理类
   *
   * @auth caowei@taihuoniao.com
   * 
   */
  class Sher_Core_Helper_Egou {
    
    public function index(){
      
      // 获取相关数据
      $try_status = $_COOKIE['is_try'];
      $love_status = $_COOKIE['is_love'];
      $stuff_status = $_COOKIE['is_stuff'];
      
      // 判断用户是否登陆
      if(!$this->visitor->id){
        return $this->ajax_notification('请登陆后重新再试', true);
      }
      
      // 判断用户是否完成任务
      if(!$try_status && !$love_status && !$stuff_status){
        return $this->ajax_notification('请完成任意一项任务后重新再试', true);
      }
      
      $egou_uid = $_COOKIE['egou_uid'];
      $egou_hid = $_COOKIE['egou_hid'];
      
      // 将用户信息插入数据库
      $model = Sher_Core_Model_Egou();
      $date = array();
      $date['eid'] = $egou_uid;
      $date['hid'] = $egou_hid;
      $date['user_id'] = $this->visitor->id;
      $ok = $model->create($date);
      
      if(!$ok){
        return $this->ajax_notification('用户信息插入失败,请重试!', true);
      }
      
      // 相关参数
      $key = "6888aMNnU161m19eaiviB578mY0775";
      $k = MD5($egou_uid.$egou_hid.date('Y-m-d',time()).$key);
      
      // 清除cookie值
      setcookie('is_try', '', time() - 3600);
      setcookie('is_love', '', time() - 3600);
      setcookie('is_stuff', '', time() - 3600);
      setcookie('egou_hid', '', time() - 3600);
      setcookie('egou_uid', '', time() - 3600);
      
      // 易购签到地址
      $url = "http://www.egou.com/club/qiandao/qiandao.htm?hid={$egou_hid}&k={$k}";
      return $this->to_taconite_page($url);
    }
  }