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
	
	protected $exclude_method_list = array('execute', 'welcome', 'home', 'coupon', 'fire', 'goccia', 'dm', 'activity', 'verify_code', 'contact', 'comeon','egg');
	
	protected $admin_method_list = array();
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->home();
		//return $this->to_html_page('page/pubbirdegg.html');
	}
	
	/**
	 * 欢迎首页
	 */
	public function welcome(){
		return $this->to_html_page('page/welcome.html');
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
		$egou = 0;
		if($this->stash['uid'] && $this->stash['hid']){
			
			$eid = $this->stash['uid'];
			$hid = $this->stash['hid'];
			
			// 判断e购用户是否已经参加过活动
			$model = new Sher_Core_Model_Egou();
			
			$date = array();
			$date['eid'] = $eid;
			$date['hid'] = $hid;
			$result = $model->first($date);
			if(empty($result)){
				// 将易购用户信息保存至cookie
				@setcookie('egou_uid', $eid, 0);
				@setcookie('egou_hid', $hid, 0);
				$egou = 1;
			}
			
			// 清除cookie值
			//setcookie('egou_uid', '', time() - 3600);
			//setcookie('egou_hid', '', time() - 3600);
		}
		$this->stash['egou_show'] = $egou;
        
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
		$ok = $verify->create(array('phone'=>$phone,'code'=>$code));
		if($ok){
			// 开始发送
			Sher_Core_Helper_Util::send_register_mms($phone, $code);
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
	 * 访问egou处理方法
	 */
	public function egou(){
		
		// 获取相关数据
		$try_status = $_COOKIE['is_try'];
		//$love_status = $_COOKIE['is_love'];
		$stuff_status = $_COOKIE['is_stuff'];
		
		// 判断用户是否登陆
		if(!$this->visitor->id){
			return $this->display_note_page('请登陆后重新再试',null);
		}
		
		// 判断用户是否完成任务
		if(!$try_status && !$stuff_status){
			return $this->display_note_page('请完成任意一项任务后重新再试');
		}
		 
		$egou_uid = $_COOKIE['egou_uid'];
		$egou_hid = $_COOKIE['egou_hid'];
		
		// 将用户信息插入数据库
		$model = new Sher_Core_Model_Egou();
		$date = array();
		$date['eid'] = $egou_uid;
		$date['hid'] = $egou_hid;
		$date['user_id'] = $this->visitor->id;
		$ok = $model->create($date);
		
		if(!$ok){
			return $this->display_note_page('用户信息插入失败,请重试!');
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
		return $this->to_redirect($url);
	}
}
?>
