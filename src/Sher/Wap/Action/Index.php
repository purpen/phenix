<?php
/**
 * Wap首页
 * @author purpen
 */
class Sher_Wap_Action_Index extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
	);
	
	// 一个月时间
	protected $month =  2592000;
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','home','twelve','comeon','games','clients','fiu','scan_qr','fiu_download','surl');
	
	/**
	 * 商城入口
	 */
	public function execute(){
		return $this->home();
	}
	
	/**
	 * 首页
	 */
	public function home(){
		$this->stash['page_title_suffix'] = '太火鸟';

			//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/index.html');
	}
	
	/**
	 * 404 page
	 */
	public function not_found(){
		return $this->to_html_page('page/404.html');
	}
	
	/**
	 * 双12活动
	 */
	public function twelve(){
		return $this->to_html_page('wap/publish.html');
	}

	/**
	 * fiu
	 */
	public function fiu(){
		$this->stash['page_title_suffix'] = 'Fiu浮游™';

        // 记录浏览数
        $num_mode = new Sher_Core_Model_SumRecord();
        $num_mode->add_record('25', 'view_count', 4, 4); 

		//微信分享
        $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
        $timestamp = $this->stash['timestamp'] = time();
        $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
        $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
        $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
        $this->stash['wxSha1'] = sha1($wxOri);
		return $this->to_html_page('wap/fiu/index.html');
	}
	
	/**
	 * 游戏
	 */
	public function games(){
    //直接跳转游戏页面
    if($this->visitor->id){
      $this->stash['user_id'] = $this->visitor->id;
      $this->stash['nickname'] = $this->visitor->nickname;
    }else{
      $this->stash['user_id'] = 0;
      $this->stash['nickname'] = '';
    }
		return $this->to_html_page('../web/playegg/index.html');
		//return $this->to_html_page('wap/games.html');
	}
	
	/**
	 * 客户端
	 */
	public function clients(){
		return $this->to_html_page('wap/clients.html');
	}
	
	/**
	 * 就现在能量线
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
		
		return $this->to_html_page('wap/noodles.html');
	}

    /**
     * 扫码跳转/记录推广码
     */
    public function qr(){
        $type = isset($this->stash['infoType']) ? (int)$this->stash['infoType'] : 0;
        $id = isset($this->stash['infoId']) ? $this->stash['infoId'] : 0;
        $referral_code = isset($this->stash['referral_code']) ? $this->stash['referral_code'] : null;

        // 推广码记录cookie
        if(!empty($referral_code)){
            @setcookie('referral_code', $referral_code, time()+(3600*24*30), '/');
            $_COOKIE['referral_code'] = $referral_code;       
        }

        switch($type){
            case 1:
                $redirect_url = sprintf(Doggy_Config::$vars['app.url.wap.shop.view'], $id);
                break;
            default:
                $redirect_url = Doggy_Config::$vars['app.url.wap']."/shop";

        }

        return $this->to_redirect($redirect_url);
    }

    /**
     * fiu 下载
     */
    public function fiu_download(){
        $url = "http://frstatic.qiniudn.com/download/app-release_1.8.2.apk";
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
        $model->inc_counter('wap_view_count', (string)$surl['_id']);
        $model->update_set((string)$surl['_id'], array('last_time_on'=>time()));

        return $this->to_redirect($surl['url']);
    }

}
