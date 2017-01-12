<?php
/**
 * 首页,列表页面
 */
class Sher_App_Action_Index extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
		'sort'=>'latest',
		'rank'=>'day',
		'q'=>'',
		'ref'=>'',
		// 邀请码
		'l'=>'',
	);
	
	// 一个月时间
	protected $month =  2592000;
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute', 'welcome', 'home', 'coupon', 'fire', 'goccia', 'dm', 'activity', 'verify_code', 'contact', 'comeon','egg','egou','egou_api','fiu','fiu_download', 'surl');
	
	protected $admin_method_list = array();
	
	/**
	 * 网站入口
	 */
	public function execute(){		
		return $this->home();
	}
	
	/**
	 * 欢迎首页
	 */
	public function welcome(){
		return $this->to_html_page('page/welcome.html');
	}

	/**
	 * Fiu
	 */
	public function fiu(){
		$this->set_target_css_state('page_fiu');
		return $this->to_html_page('page/fiu/index.html');
	}

	/**
	 * 双12活动专题
	 */
	public function twelve(){
		return $this->to_html_page('page/pubindex.html');
	}
	
	/**
	 * 蛋年专题
	 */
	public function egg(){
		return $this->to_html_page('page/pubbirdegg.html');
	}
	
    /**
     * 首页
     * @return string
     */
    public function home() {
		
		// 易购网入口部分
		if((isset($this->stash['uid']) && !empty($this->stash['uid'])) && (isset($this->stash['hid']) && !empty($this->stash['hid']))){
			
			// 清除cookie值
			setcookie('egou_uid', '', time() - 3600, '/');
			setcookie('egou_hid', '', time() - 3600, '/');
			setcookie('egou_finish', '', time() - 3600, '/');
			
			$uid = $this->stash['uid'];
			$hid = $this->stash['hid'];
			
			// 判断e购用户是否已经参加过活动
			$model = new Sher_Core_Model_Egoutask();
			$egou_show = 0;
			
			$date = array();
			$date['uid'] = $uid;
			$date['hid'] = $hid;
			$result = $model->find($date);
			
			if(!$result){
				// 将易购用户信息保存至cookie
				@setcookie('egou_uid', $uid, 0, '/');
				$_COOKIE['egou_uid'] = $uid;
				@setcookie('egou_hid', $hid, 0, '/');
				$_COOKIE['egou_hid'] = $hid;
				$egou_show = 1;
			}
			
			$this->stash['egou_show'] = $egou_show;
		}
		
		//var_dump($_COOKIE);
    /**
    if(isset($_COOKIE['egou_finish'])){
 		  $this->stash['egou_finish'] = $_COOKIE['egou_finish'];   
    }else{
  	  $this->stash['egou_finish'] = '';    
    }
     */

    if($this->visitor->id){
      //当前用户邀请码
      $invite_code = Sher_Core_Util_View::fetch_invite_user_code($this->visitor->id);
      $this->stash['user_invite_code'] = $invite_code;   
    }else{
      // 如果存在邀请码，存cookie
      if(isset($this->stash['user_invite_code']) && !empty($this->stash['user_invite_code'])){
        // 将邀请码保存至cookie
        @setcookie('user_invite_code', $this->stash['user_invite_code'], 0, '/');
        $_COOKIE['user_invite_code'] = $this->stash['user_invite_code'];   
      }   
    }

		$this->set_target_css_state('page_home');

        // 商品推荐列表---取块内容
        $product_ids = Sher_Core_Util_View::load_block('index_product_stick', 1);
        $products = array();
        if($product_ids){
            $product_model = new Sher_Core_Model_Product();
            $id_arr = explode(',', $product_ids);
            foreach(array_slice($id_arr, 0, 8) as $i){
                $product = $product_model->extend_load((int)$i);
                if(!empty($product)){
                    array_push($products, $product);
                }
            }
        }
        $this->stash['products'] = $products;
        // 商品图片alt显示标签第一个
        $this->stash['product_alt_tag'] = 1;
		
        return $this->to_html_page('page/home.html');
    }
	
	/**
	 * 征集令
	 */
	public function fire(){
		$this->set_target_css_state('page_fire');
		return $this->to_html_page('page/fire.html');
	}
	
	/**
	 * 即上线，线开抢
	 */
	public function comeon(){
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
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
		
		// 验证是否预约
		if($this->visitor->id){
			$cache_key = sprintf('appoint_%d_%d', $product_id, $this->visitor->id);
			$redis = new Sher_Core_Cache_Redis();
			$appointed = $redis->get($cache_key);
		}else{
			$appointed = false;
		}
		$this->stash['appointed'] = $appointed;
		
		return $this->to_html_page('page/noodles.html');
	}
	
	/**
	 * Goccia单品
	 */
	public function goccia(){
		$this->gen_login_token();
		return $this->to_html_page('page/goccia.html');
	}
	
	/**
	 * DM 单品
	 */
	public function dm(){
		return $this->to_html_page('page/dm.html');
	}
	
	/**
	 * 显示列表
	 */
	protected function _display_user_list($sex=1){
		// 仅允许搜索单身用户
		$this->stash['marital'] = Sher_Core_Model_User::MARR_SINGLE;
		$this->stash['sex'] = $sex;
		$this->stash['only_ok'] = 1;
		
		return $this->to_html_page('page/user_list.html');
	}

	/**
	 * 发送手机验证码
	 */
	public function verify_code() {
		$phone = $this->stash['phone'];
		$code = Sher_Core_Helper_Auth::generate_code();
		
		$verify = new Sher_Core_Model_Verify();
		$ok = $verify->create(array('phone'=>$phone,'code'=>$code, 'expired_on'=>time()+600));
		if($ok){
			// 开始发送
			Sher_Core_Helper_Util::send_register_mms($phone, $code, 3);
		}
		
		return $this->to_json(200,'正在发送');
	}
	
	/**
	 * 生成临时token
	 */
	protected function gen_login_token() {
        $service = DoggyX_Session_Service::instance();
        $token = Sher_Core_Helper_Auth::generate_random_password();
        $service->session->login_token = $token;
        $this->stash['login_token'] = $token;
    }
	
	/**
	 *  易购回调接口
	 */
	public function egou_api(){
		
		$hid = $this->stash['hid'];
		$start_time = $this->stash['startdate'];
		$end_time = $this->stash['enddate'];
		
		if(empty($hid) || empty($start_time) || empty($end_time)){
			return false;
		}
		
		$start_time = strtotime($start_time.' 00:00:00');
		$end_time = strtotime($end_time.' 23:59:59');
		
		$option = array();
		$option['hid'] = (int)$hid;
		$option['addtime'] = array('$gte' => $start_time, '$lte' => $end_time);
		
		$model = new Sher_Core_Model_Egoutask();
		$result = $model->find($option);
		
		if(!count($result)){
			return false;
		}
		
		$date = array();
		foreach($result as $k=>$v){
			$date[$k]['uid'] = $v['uid'];
			$date[$k]['hid'] = $v['hid'];
			$date[$k]['addtime'] = date('Y-m-d',$v['addtime']);
		}
		
		echo json_encode($date);
	}

    /**
     * fiu 下载
     */
    public function fiu_download(){
        $url = "http://frstatic.qiniudn.com/download/app-release_1.8.1.apk";
        return $this->to_redirect($url);
    }

    /**
     * 短网址
     */
    public function surl(){
        $code = isset($this->stash['code']) ? $this->stash['code'] : null;
		$redirect_url = Doggy_Config::$vars['app.url.domain'];
		if(empty($code)){
			return $this->show_message_page('缺少请求参数！', $redirect_url);
		}

        $model = new Sher_Core_Model_SUrl();
        $surl = $model->find_by_code($code);
		if(empty($surl)){
			return $this->show_message_page('地址不存在！', $redirect_url);
		}
		if($surl['status']==0){
			return $this->show_message_page('无效的地址！', $redirect_url);
		}

        // 更新
        $model->inc_counter('view_count', (string)$surl['_id']);
        $model->inc_counter('web_view_count', (string)$surl['_id']);
        $model->update_set((string)$surl['_id'], array('last_time_on'=>time()));

        return $this->to_redirect($surl['url']);
    }

}

