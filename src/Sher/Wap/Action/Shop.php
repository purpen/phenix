<?php
/**
 * Wap Shop
 * @author purpen
 */
class Sher_Wap_Action_Shop extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
		'cid'  => 0,
		'sku' => 0,
		'id' => 0,
		'rrid' => 0,
		'n'=>1, // 数量
		's' => 1, // 型号
		'payaway' => '', // 支付机构
    'sort' => 0,
    'type' => 0,
    'category_id' => 0,

	);
	
	// 一个月时间
	protected $month =  2592000;
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','index','shop','presale','view','check_snatch_expire','ajax_guess_product','n_view', 'ajax_load_list','serve','promo','hatched_list', 'get_list', 'list', 'category', 'brand', 'stick');
	
	/**
	 * 商城入口
	 */
	public function execute(){
		return $this->index();
	}

  /**
   * 商店首页
   */
  public function index(){
    $this->set_target_css_state('page_choice');

      //微信分享
      $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
      $timestamp = $this->stash['timestamp'] = time();
      $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
      $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
      $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
      $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
      $this->stash['wxSha1'] = sha1($wxOri);
    return $this->to_html_page('wap/shop/home.html');
  }

    /**
     * 列表页
     */
    public function get_list(){
        $this->set_target_css_state('page_find');
        return $this->to_html_page('wap/shop/list.html');
    }

    /**
     * 产品分类
     */
    public function category(){
        $this->set_target_css_state('page_find');
        return $this->to_html_page('wap/shop/category.html');
    }

    /**
     * 个人中心
     */
    public function owner(){
        $this->set_target_css_state('page_owner');
        return $this->to_html_page('wap/shop/owner.html');
    }

    /**
     * 品牌
     */
    public function brand(){
        $this->set_target_css_state('page_find');
        return $this->to_html_page('wap/shop/brand.html');   
    }

    /**
     * 推荐
     */
    public function stick(){
        $this->set_target_css_state('page_find');
        return $this->to_html_page('wap/shop/stick_list.html');   
    }

  /**
   * 孵化列表
   */
  public function hatched_list(){
    return $this->to_html_page('wap/shop/incub.html'); 
  }
	
	/**
	 * 预售列表
	 */
	public function presale(){
		$this->stash['process_presaled'] = 1;
    $this->stash['is_shop'] = 0;
		
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap.shop').'presale/p#p#';
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('wap/shop.html');
	}
	
	/**
	 * 商店列表
	 */
	public function shop(){
		$cid = isset($this->stash['cid']) ? $this->stash['cid'] : 0;
    //$sort = isset($this->stash['sort']) ? $this->stash['sort'] : 9;
    $presale = isset($this->stash['presale'])?(int)$this->stash['presale']:0;
		if($cid){
			// 获取某类别列表
			$category = new Sher_Core_Model_Category();
			$current = $category->extend_load((int)$cid);
			if(empty($current)){
				return $this->show_message_page('请选择某个分类');
			}

      //添加网站meta标签
      $this->stash['page_title_suffix'] = Sher_Core_Helper_View::meta_category_obj($current, 1);
      $this->stash['page_keywords_suffix'] = Sher_Core_Helper_View::meta_category_obj($current, 2);   
      $this->stash['page_description_suffix'] = Sher_Core_Helper_View::meta_category_obj($current, 3);
			$this->stash['current'] = $current;
		}

    if(empty($presale)){
      $this->stash['is_shop'] = 1;
      $this->stash['presaled'] = 0;
    }else{
      $this->stash['is_shop'] = 0;
      $this->stash['presaled'] = 1;   
    }
		
		//$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap.shop', 'c'.$cid).'p#p#';
		$pager_url =  Doggy_Config::$vars['app.url.wap'].'/shop?'.'cid='.$cid.'&page=#p#';
		$this->stash['pager_url'] = $pager_url;

		return $this->to_html_page('wap/shop.html');
	}
	
	/**
	 * 太火鸟商城购物攻略
	 */
	public function serve(){
		$this->stash['page_title_suffix'] = '太火鸟商城购物攻略';
		return $this->to_html_page('wap/shop/serve.html');
	}
	
	/**
	 * 太火鸟商城购物攻略
	 */
	public function promo(){
		//$this->stash['page_title_suffix'] = '太火鸟商城购物攻略';
		return $this->to_html_page('wap/shop/promo.html');
	}
	
	/**
	 * 商品详情
	 */
	public function view(){
        $id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.wap']. "/shop";
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}

        $referral_code = isset($this->stash['referral_code']) ? $this->stash['referral_code'] : null;

        // 推广码记录cookie
        if(!empty($referral_code)){
            @setcookie('referral_code', $referral_code, time()+(3600*24*30), '/');
            $_COOKIE['referral_code'] = $referral_code;       
        }

        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;

        // 记录某个商品推广统计，统计注册量浏览量
        if(isset($this->stash['from']) && $this->stash['from']==2 && $id==1042791409){
          // 存cookie
          @setcookie('from_origin', '4', time()+3600*24, '/');
          $_COOKIE['from_origin'] = '4';
          @setcookie('from_target_id', (string)$id, time()+3600*24, '/');
          $_COOKIE['from_target_id'] = (string)$id;

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
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的产品不存在或已被删除！', $redirect_url);
		}
		
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);

		$model->inc_counter('true_view_count', 1, $id);
		$model->inc_counter('wap_view_count', 1, $id);
		
		// 未发布上线的产品，仅允许本人及管理员查看
		if(!$product['published'] && !($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('访问的产品等待发布中！', $redirect_url);
		}

    //添加网站meta标签
    $this->stash['page_title_suffix'] = sprintf("%s-太火鸟智品库", $product['title']);
    if(!empty($product['tags_s'])){
      $this->stash['page_keywords_suffix'] = $product['tags_s'];   
    }
    $this->stash['page_description_suffix'] = sprintf("太火鸟Taihuoniao智能硬件商店提供（%s）正品行货，全国正规智能产品购买平台，包括（%s）图片、参数、硬件测评、相关产品、使用技巧等信息，购买（%s）就去太火鸟，放心又轻松。", $product['short_title'], $product['short_title'], $product['short_title']);

    //判断是否为秒杀产品 
    $snatch_time = 0;
    if($product['snatched']){
      $is_snatch = true;
      if(!$product['snatched_start']){
        $snatch_time = $product['snatched_time'] - time();
      }
    }else{
      $is_snatch = false;
    }
    $this->stash['is_snatch'] = $is_snatch;
    $this->stash['snatch_time'] = $snatch_time;
		
		// 验证是否还有库存
		$product['can_saled'] = $model->can_saled($product);
		
		// 获取skus及inventory
		$inventory = new Sher_Core_Model_Inventory();
    //积分兑换商品与销售商品共有sku
    if($product['stage']==Sher_Core_Model_Product::STAGE_EXCHANGE){
      $sku_stage = Sher_Core_Model_Product::STAGE_SHOP;
    }else{
      $sku_stage = $product['stage'];
    }
		$skus = $inventory->find(array(
			'product_id' => $id,
			'stage' => $sku_stage,
		));

        if(!empty($skus)){
            for($k=0;$k<count($skus);$k++){
                $skus[$k]['cover_url'] = '';
                if(isset($skus[$k]['cover_id']) && !empty($skus[$k]['cover_id'])){
                    $sku_cover = $inventory->cover($skus[$k]);
                    if($sku_cover){
                        $skus[$k]['cover_url'] = $sku_cover['thumbnails']['apc']['view_url'];
                    }
                }
            }
        }

		$this->stash['skus'] = $skus;

		$this->stash['skus_count'] = count($skus);

    // 使用手册链接
    if(isset($product['guide_id']) && !empty($product['guide_id'])){
      $product['guide_url'] = sprintf(Doggy_Config::$vars['app.url.wap.social.show'], $product['guide_id'], 0);
    }
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::sale_view_url($id,'#p#');
		
		$this->stash['product'] = $product;
		$this->stash['id'] = $id;

    //微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];  
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);

    if($product['stage']==9){
      $tpl = 'wap/shop/show.html';
    }else{
      $tpl = 'wap/view.html';
    }
		
		return $this->to_html_page($tpl);
	}
	
	/**
	 * 商品详情
	 */
	public function n_view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.wap'];
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的产品不存在或已被删除！', $redirect_url);
		}
		
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		// 未发布上线的产品，仅允许本人及管理员查看
		if(!$product['published'] && !($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('访问的产品等待发布中！', $redirect_url);
		}

    //添加网站meta标签
    $this->stash['page_title_suffix'] = sprintf("%s-Fiu店", $product['title']);
    if(!empty($product['tags_s'])){
      $this->stash['page_keywords_suffix'] = $product['tags_s'];   
    }
    $this->stash['page_description_suffix'] = sprintf("太火鸟Taihuoniao智能硬件商店提供（%s）正品行货，全国正规智能产品购买平台，包括（%s）图片、参数、硬件测评、相关产品、使用技巧等信息，购买（%s）就去太火鸟，放心又轻松。", $product['short_title'], $product['short_title'], $product['short_title']);

		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::sale_view_url($id,'#p#');
		$this->stash['product'] = $product;
		$this->stash['id'] = $id;
		
		return $this->to_html_page('wap/n_view.html');
	}
	
	/**
	 * 完整购物车页面
	 */
	public function cart() {

		$user_id = $this->visitor->id;

        $redirect_url = sprintf("%s/shop", Doggy_Config::$vars['app.url.wap']);
        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;

        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        if(empty($cart) || empty($cart['items'])){
          $this->stash['products'] = array();
          $this->stash['total_money'] = 0;
          $this->stash['items_count'] = 0;
          return $this->to_html_page('wap/shop/cart.html');
        }

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();

        $total_price = 0.0;
        $item_arr = array();
        // 记录错误数据索引
        $error_index_arr = array();
        foreach($cart['items'] as $k=>$v){
          // 初始参数
          $target_id = (int)$v['target_id'];
          $type = (int)$v['type'];
          $n = (int)$v['n'];
          $vop_id = isset($v['vop_id']) ? $v['vop_id'] : null;

          $data = array();
          $data['target_id'] = $target_id;
          $data['type'] = $type;
          $data['n'] = $n;
          $data['sku_mode'] = null;
          $data['sku_name'] = null;
          $data['price'] = 0;
          $data['vop_id'] = $vop_id;

          if($type==2){
            $inventory = $inventory_model->load($target_id);
            if(empty($inventory)){
              array_push($error_index_arr, $k);
              continue;
            }
            $product_id = $inventory['product_id'];
            $data['sku_mode'] = $inventory['mode'];
            $data['sku_name'] = $inventory['mode'];
            $data['price'] = $inventory['price'];
            $data['total_price'] = $data['price']*$n;
            
          }else{
            $product_id = $target_id;
          }

          $data['product_id'] = $product_id;

          $product = $product_model->extend_load($product_id);
          if(empty($product)){
            array_push($error_index_arr, $k);
            continue;     
          }

          $data['title'] = $product['title'];
          $data['cover'] = $product['cover']['thumbnails']['mini']['view_url'];
          $data['wap_view_url'] = $product['wap_view_url'];

          if(empty($data['price'])){
            $data['price'] = (float)$product['sale_price'];
            $data['total_price'] = $product['sale_price']*$n;
          }
          $total_price += $data['total_price'];
          array_push($item_arr, $data);

        }//endfor

        // 移除不存在的商品ID
        if(!empty($error_index_arr)){
          foreach($error_index_arr as $k=>$v){
            unset($cart['items'][$v]);
          }
          $cart_model->update_set($cart['_id'], array('items'=>$cart['items'], 'item_count'=>count($cart['items'])));
        }

		$this->stash['basket_products'] = $item_arr;
		$this->stash['products'] = $item_arr;
		
		$this->stash['total_money'] = $total_price;
		$this->stash['items_count'] = count($item_arr);
		if ($item_arr > 0){
			$this->set_target_css_state('basket');
		}
		
		return $this->to_html_page('wap/shop/cart.html');
	}

	/**
	 * 验证限量抢购
	 */
	protected function validate_snatch($product_id){
		// 设置已抢购标识
		$cache_key = sprintf('snatch_%d_%d', $product_id, $this->visitor->id);
		Doggy_Log_Helper::warn('Validate wap_snatch log key: '.$cache_key);
		
		$redis = new Sher_Core_Cache_Redis();
		$buyed = $redis->get($cache_key);
		if($buyed){
			return false;
		}
		return true;
	}

	/**
	 * 如果是抢购商品，验证是否预约过
	 */
	protected function validate_appoint($product_id){
		// 设置已预约标识
    $cache_key = sprintf('mask_%d_%d', $product_id, $this->visitor->id);
		Doggy_Log_Helper::warn('Validate wap_appoint log key: '.$cache_key);
		
		$redis = new Sher_Core_Cache_Redis();
		$buyed = $redis->get($cache_key);
		if(!$buyed){
			return false;
		}
		return true;
	}
	
	/**
	 * 设置抢购商品不能重复,限时5小时
	 */
	protected function check_have_snatch($product_id, $ttl=18000){
    $cache_key = sprintf('snatch_%d_%d', $product_id, $this->visitor->id);
    Doggy_Log_Helper::warn('Validate snatch log key: '.$cache_key);
    // 设置缓存
    $redis = new Sher_Core_Cache_Redis();
    $redis->set($cache_key, 1, $ttl);
	}
	
	/**
	 * 立即购买
	 */
	public function nowbuy(){
		$sku = $this->stash['sku'];
        $redirect_url = sprintf("%s/shop", Doggy_Config::$vars['app.url.wap']);
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		$quantity = (int)$this->stash['n'];
        $options = array();
        $options['is_vop'] = 0;

        //初始变量
        //是否是抢购商品
        $is_snatched = false;
        //是否积分兑换
        $is_exchanged = false;

        // 开普勒
        $vop_id = null;
        $number = '';
        // 推广码
        $referral_code = isset($_COOKIE['referral_code']) ? $_COOKIE['referral_code'] : null;
		
		// 验证数据
		if (empty($sku) || empty($quantity)){
			return $this->show_message_page('操作异常，请重试！');
		}
		
		$user_id = $this->visitor->id;
		
		// 验证库存数量
		$inventory = new Sher_Core_Model_Inventory();
		$enoughed = $inventory->verify_enough_quantity($sku, $quantity);
		if(!$enoughed){
			return $this->show_message_page('挑选的产品已售完！', true);
		}
		$item = $inventory->load((int)$sku);
		
        if(!empty($item)){
            $product_id = $item['product_id'];
            $vop_id = isset($item['vop_id']) ? $item['vop_id'] : null;
            $number = $item['number'];
        }else{
            $product_id = (int)$sku;
        }
		
		// 获取产品信息
		$product = new Sher_Core_Model_Product();
		$product_data = $product->extend_load((int)$product_id);
		if(empty($product_data)){
			return $this->show_message_page('挑选的产品不存在或被删除，请核对！', true);
		}
    $this->stash['item_stage'] = 'shop';

    //如果是抢购Start
    if($product_data['snatched']){
      $is_snatched = true;

      //是否在抢购列表里
      if(!$this->snatch_product_ids($product_data['_id'])){
        return $this->show_message_page('抢购商品不在列表之内！', true);               
      }

      // 验证是否预约过抢购商品
      if(!$this->validate_appoint($product_data['_id'])){
        //return $this->show_message_page('抱歉，您还没有预约，不能参加本次抢购！');
      }
      // 验证抢购商品是否重复
      if(!$this->validate_snatch($product_data['_id'])){
        return $this->show_message_page('抱歉，不要重复抢哦！');
      }

      //验证抢购库存
      if(empty($product_data['snatched_count'])){
        return $this->show_message_page('商品已抢完！');    
      }

      //验证抢购时间
      if(!$product_data['snatched_start']){
        return $this->show_message_page('抢购还未开始！');     
      }
    
    }
    //如果是抢购End

    //如果是积分兑换Start
    if(isset($product_data['exchanged']) && !empty($product_data['exchanged'])){
      //验证兑换是否开启
      if(!$product_data['exchanged']){
        return $this->show_message_page('积分兑换未开启！');     
      }
      //验证兑换金额最高限额
      if(!$product_data['max_bird_coin']){
        return $this->show_message_page('积分最高限额未设置！');     
      }
      //验证兑换库存
      if(empty($product_data['exchange_count'])){
        return $this->show_message_page('兑换商品库存不足！');    
      }
      //验证当前用户鸟币是否足够
      // 用户实时积分
      $point_model = new Sher_Core_Model_UserPointBalance();
      $current_point = $point_model->load($this->visitor->id);
      if(!$current_point){
        $current_bird_coin = 0;
        //return $this->show_message_page('鸟币数量不足！');     
      }else{
        $current_bird_coin = isset($current_point['balance']['money'])?(int)$current_point['balance']['money']:0;
        //if($current_bird_coin < $product_data['max_bird_coin']){
          //return $this->show_message_page('您的鸟币数量不足！');      
        //}     
      }
      $this->stash['max_bird_coin'] = $product_data['max_bird_coin'];
      $this->stash['min_bird_coin'] = $product_data['min_bird_coin'];
      $this->stash['current_bird_coin'] = $current_bird_coin;


      $is_exchanged = true;
      $this->stash['item_stage'] = 'exchange';
    }
    //End

    // 试用产品，不可购买
    if($product_data['is_try']){
      return $this->show_message_page('试用产品，不可购买！', true);
    }

		// 销售价格/如果是抢购,取抢购价
    if($is_snatched){
      $price = $product_data['snatched_price'];
      //抢购数量只能为1
      $quantity = 1;
    }elseif($is_exchanged){
      //积分兑换数量只能为1
      $quantity = 1;
      $price = !empty($item) ? $item['price'] : $product_data['sale_price'];
    }else{
      $price = !empty($item) ? $item['price'] : $product_data['sale_price'];
    }
		
		$items = array(
			array(
				'sku'  => $sku,
				'product_id' => $product_id,
				'quantity' => $quantity,
				'price' => $price,
				'sale_price' => $price,
				'title' => $product_data['title'],
				'cover' => $product_data['cover']['thumbnails']['mini']['view_url'],
				'view_url' => $product_data['view_url'],
				'subtotal' => $price*$quantity,
                'is_snatched' => $is_snatched?1:0,
                'is_exchanged' => $is_exchanged?1:0,
                'vop_id' => $vop_id,
                'number' => (string)$number,
			),
		);
		$total_money = $price*$quantity;
		$items_count = 1;

        if($vop_id){
            $options['is_vop'] = 1;
        }
        if($referral_code){
            $options['referral_code'] = $referral_code;
        }
		
		$order_info = $this->create_temp_order($items, $total_money, $items_count, $options);
		if (empty($order_info)){
			return $this->show_message_page('系统出了小差，请稍后重试！', true);
		}
		
        // 重新计算邮费
        $freight = Sher_Core_Helper_Order::freight_stat($order_info['rid'], $order_info['dict']['addbook_id'], array('items'=>$order_info['dict']['items'], 'is_vop'=>$order_info['is_vop'], 'total_money'=>$order_info['dict']['total_money']));
        $order_info['dict']['freight'] = $freight;
		
		// 优惠活动费用
		$coin_money = 0.0;
		
		$pay_money = $total_money + $freight - $coin_money;
		
		$this->stash['order_info'] = $order_info;
		$this->stash['data'] = $order_info['dict'];
		$this->stash['pay_money'] = $pay_money;
		
		$this->set_extra_params();
		
		return $this->to_html_page('wap/checkout.html');
	}
	
	/**
	 * 重新选择收货地址后结算信息
	 */
	public function address_checkout(){
		$rrid = $this->stash['rrid'];
		$addrid = $this->stash['addrid'];
        $redirect_url = sprintf("%s/shop", Doggy_Config::$vars['app.url.wap']);
		$this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		// 获取临时订单信息
		$model = new Sher_Core_Model_OrderTemp();
		$order_info = $model->first(array('rid'=>$rrid));
		
		$total_money = 0;
		
		$items = $order_info['dict']['items'];
		for($i=0; $i<count($items); $i++){
			$total_money += $items[$i]['price']*$items[$i]['quantity'];
		}

    $this->stash['item_stage'] = 'shop'; 

    $is_exchanged = false;
    $is_snatched = false;

    // 验证是否为积分兑换或抢购
    if(count($items)==1){
      if(isset($items[0]['is_snatched']) && $items[0]['is_snatched']==1){
        $is_snatched = true;
        $this->stash['item_stage'] = 'snatched';  
      }elseif(isset($items[0]['is_exchanged']) && $items[0]['is_exchanged']==1){
        $is_exchanged = true;
        $this->stash['item_stage'] = 'exchange';     
      }
    }

    if($is_exchanged){

      // 获取产品信息
      $product = new Sher_Core_Model_Product();
      $product_data = $product->extend_load((int)$items[0]['product_id']);
      if(empty($product_data)){
        return $this->show_message_page('挑选的产品不存在或被删除，请核对！', true);
      }

      //验证当前用户鸟币是否足够
      // 用户实时积分
      $point_model = new Sher_Core_Model_UserPointBalance();
      $current_point = $point_model->load($this->visitor->id);
      if(!$current_point){
        $current_bird_coin = 0;
        //return $this->show_message_page('鸟币数量不足！');     
      }else{
        $current_bird_coin = isset($current_point['balance']['money'])?(int)$current_point['balance']['money']:0;
        //if($current_bird_coin < $product_data['max_bird_coin']){
          //return $this->show_message_page('您的鸟币数量不足！');      
        //}     
      }
      $this->stash['max_bird_coin'] = $product_data['max_bird_coin'];
      $this->stash['min_bird_coin'] = $product_data['min_bird_coin'];
      $this->stash['current_bird_coin'] = $current_bird_coin;
    
    }
		
        // 重新计算邮费
        $freight = Sher_Core_Helper_Order::freight_stat($order_info['rid'], $addrid, array('items'=>$order_info['dict']['items'], 'is_vop'=>$order_info['is_vop'], 'total_money'=>$order_info['dict']['total_money']));
        $order_info['dict']['freight'] = $freight;
		
		// 优惠活动费用
		$coin_money = 0.0;
    
    // 红包金额
    $card_money = 0.0;

    //礼品券金额
    $gift_money = 0.0;

    //鸟币金额
    $bird_coin_money = 0.0;
		
		$pay_money = $total_money + $freight - $coin_money - $card_money - $gift_money - $bird_coin_money;
		
		$this->stash['order_info'] = $order_info;
		$this->stash['data'] = $order_info['dict'];
		$this->stash['pay_money'] = $pay_money;
		
		if(!empty($addrid)){
			$addbooks = new Sher_Core_Model_DeliveryAddress();
			$default_addbook = $addbooks->extend_load($addrid);
			$this->stash['default_addbook'] = $default_addbook;
		}
		
		$this->set_extra_params();
		
		return $this->to_html_page('wap/checkout.html');
	}

	/**
	 * 购物车下单
	 */
	public function checkout(){
		$user_id = $this->visitor->id;

        $target_ids = isset($this->stash['target_ids']) ? $this->stash['target_ids'] : null;
        if(empty($target_ids)){
            return $this->show_message_page('清选择要购买的商品！', true);
        }
        $redirect_url = sprintf("%s/shop", Doggy_Config::$vars['app.url.wap']);
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;

        $target_arr = explode(',', $target_ids);
        for($i=0;$i<count($target_arr);$i++){
            $target_arr[$i] = (int)$target_arr[$i];
        }

        // 推广码
        $referral_code = isset($_COOKIE['referral_code']) ? $_COOKIE['referral_code'] : null;

        $options = array();
        $options['is_cart'] = 1;
        $options['is_vop'] = 0;
        $options['referral_code'] = $referral_code;
		
		//验证购物车，无购物不可以去结算
        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        if(empty($cart)){
            return $this->show_message_page('操作不当，请查看购物帮助！', true);
        }

		//验证购物车，无购物不可以去结算
        $result = array();
        $items = array();
        $total_money = 0;
        $total_count = 0;

        // 记录错误数据索引
        $error_index_arr = array();

        // 统计商品来源数量
        $vop_count = 0;
        $self_count = 0;

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();
        foreach($cart['items'] as $key=>$val){
            $item = array();

          // 初始参数
          $val = (array)$val;
          $target_id = (int)$val['target_id'];

            // 是否是用户选的中产品
            if(!in_array($target_id, $target_arr)){
                continue;
            }

          $type = (int)$val['type'];
          $n = isset($val['n']) ? (int)$val['n'] : 1;
          if(empty($n)){
            $n = 1;
          }

          $sku_mode = null;
          $price = 0.0;
          $vop_id = null;
          $number = '';

          // 验证是商品还是sku
          if($type==2){
            $inventory = $inventory_model->load($target_id);
            if(empty($inventory)){
              return $this->show_message_page(sprintf("编号为%d的商品不存在！", $target_id), true);
            }
            if($inventory['quantity']<$n){
              return $this->show_message_page(sprintf("%s 库存不足，请重新下单！", $inventory['mode']), true);
            }

            $product_id = $inventory['product_id'];
            $sku_mode = $inventory['mode'];
            $price = (float)$inventory['price'];
            $total_price = $price*$n;
            $sku_id = $target_id;
            $vop_id = isset($inventory['vop_id']) ? $inventory['vop_id'] : null;
            $number = $inventory['number'];
            
          }elseif($type==1){
            $sku_id = $target_id;
            $product_id = $target_id;
          }else{
            return $this->show_message_page('购物车参数不正确！', true);
          }

          $product = $product_model->extend_load($product_id);
          if(empty($product)){
            return $this->show_message_page(sprintf("编号为%d的商品不存在！", $target_id), true);
          }
          if($product['stage'] != 9){
            return $this->show_message_page(sprintf("商品:%s 不可销售！", $product['title']), true);
          }
          if($product['inventory'] < $n){
            return $this->show_message_page(sprintf("商品:%s 库存不足！", $product['title']), true);
          }

          if(empty($price)){
            $price = (float)$product['sale_price'];
            $total_price = $price*$n;
          }

          $item = array(
            'target_id' => $target_id,
            'type' => $type,
            'sku' => $sku_id,
            'product_id'  => $product_id,
            'quantity'  => $n,
            'price' => $price,
            'sku_mode' => $sku_mode,
            'sale_price' => $price,
            'title' => $product['title'],
            'cover'  => $product['cover']['thumbnails']['mini']['view_url'],
            'view_url'  => $product['view_url'],
            'subtotal'  => $total_price,
            'vop_id' => $vop_id,
            'number' => (string)$number,
          );
          $total_money += $total_price;
          $total_count += 1;

          if(!empty($item)){
              if($vop_id){
                $vop_count += 1;
              }else{
                $self_count += 1;
              }
            array_push($items, $item);
          }
        } // endfor

        //如果购物车为空，返回
        if(empty($total_money) || empty($items)){
            return $this->show_message_page('购物车异常！', true);
        }

        if(!empty($vop_count) && !empty($self_count)){
            return $this->show_message_page('不能和京东配货产品同时下单！', true);       
        }

        $items_count = count($items);

        if(!empty($vop_count)){
            $options['is_vop'] = 1;
        }
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		try{
			// 预生成临时订单
			$model = new Sher_Core_Model_OrderTemp();

      $order_info = $this->create_temp_order($items, $total_money, $items_count, $options);
      if (empty($order_info)){
        return $this->show_message_page('系统出了小差，请稍后重试！', true);
      }

        // 重新计算邮费
        $freight = Sher_Core_Helper_Order::freight_stat($order_info['rid'], $order_info['dict']['addbook_id'], array('items'=>$order_info['dict']['items'], 'is_vop'=>$order_info['is_vop'], 'total_money'=>$order_info['dict']['total_money']));
        $order_info['dict']['freight'] = $freight;

      $this->stash['order_info'] = $order_info;
      $this->stash['data'] = $order_info['dict'];
			
			$pay_money = $total_money + $freight - $coin_money - $card_money - $gift_money - $bird_coin_money;
			$this->stash['pay_money'] = $pay_money;
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create temp order failed: ".$e->getMessage());
		}
		
		$this->stash['provinces'] = $provinces;
		
		$this->set_extra_params();
		
		return $this->to_html_page('wap/checkout.html');
	}
	
	/**
	 * 确认订单并提交
	 */
	public function confirm(){
		$rrid = $this->stash['rrid'];
		if(empty($rrid)){
			// 没有临时订单编号，为非法操作
			return $this->ajax_json('操作不当，请查看购物帮助！', true);
		}
        $addbook_id = isset($this->stash['addbook_id']) ? $this->stash['addbook_id'] : null;
		if(empty($addbook_id)){
			return $this->ajax_json('请选择收货地址！', true);
		}

        // 抢购商品
        $is_snatched = false;
            
        //验证地址
        $add_book_model = new Sher_Core_Model_DeliveryAddress();
        $add_book = $add_book_model->find_by_id($this->stash['addbook_id']);
        if(empty($add_book)){
          return $this->ajax_json('地址不存在！', true);
        }

		$bonus = isset($this->stash['bonus']) ? $this->stash['bonus'] : '';
		$bonus_code = $this->stash['bonus_code'];
		$transfer_time = $this->stash['transfer_time'];
		
		Doggy_Log_Helper::debug("Submit Mobile Order [$rrid]！");
		
		// 订单用户
		$user_id = $this->visitor->id;
		
		// 加载临时订单
		$model = new Sher_Core_Model_OrderTemp();
		$result = $model->first(array('rid'=>$rrid));
		if(empty($result)){
			return 	$this->ajax_json('订单预处理失败，请重试！', true);
		}

    $is_cart = isset($result['is_cart']) ? $result['is_cart'] : 0;
    $is_presaled = isset($result['is_presaled']) ? $result['is_presaled'] : 0;
    $is_vop = isset($result['is_vop']) ? $result['is_vop'] : 0;
		
		// 订单临时信息
		$order_info = $result['dict'];
        $order_info['referral_code'] = $result['referral_code'];
        // 验证开普勒
        for($i=0;$i<count($order_info['items']);$i++){
            $vop_id = isset($order_info['items'][$i]['vop_id']) ? $order_info['items'][$i]['vop_id'] : null;
            $sku_title = $order_info['items'][$i]['title'];
            if(empty($vop_id)) continue;

            // 是否可售
            $vop_result = Sher_Core_Util_Vop::sku_check_one($vop_id);
            if(!$vop_result['success']){
 			    return $this->ajax_json($vop_result['message'], true);
            }

            // 是否是区域限制
            $vop_options = array();
            $vop_options['title'] = $sku_title;
            $vop_options['province'] = isset($add_book['province_id']) ? $add_book['province_id'] : 0;
            $vop_options['city'] = isset($add_book['city_id']) ? $add_book['city_id'] : 0;
            $vop_options['county'] = isset($add_book['county_id']) ? $add_book['county_id'] : 0;
            $vop_options['town'] = isset($add_book['town_id']) ? $add_book['town_id'] : 0;
            $vop_result = Sher_Core_Util_Vop::sku_check_area($vop_id, $vop_options);
            if(!$vop_result['success']){
 			    return $this->ajax_json($vop_result['message'], true);
            }
        }
		
		// 获取订单编号
		$order_info['rid'] = $result['rid'];
		
		// 获取购物金额
		$total_money = $order_info['total_money'];

		
		// 需要开具发票，验证开票信息
		if(isset($this->stash['invoice_type'])){
			$order_info['invoice_type'] = $this->stash['invoice_type'];
			if ($order_info['invoice_type'] == 1){
				$order_info['invoice_title'] = $this->stash['invoice_title'];
				$order_info['invoice_caty'] = $this->stash['invoice_caty'];
			}
		}

    //备注
    if(isset($this->stash['summary'])){
      $order_info['summary'] = $this->stash['summary'];
    }
		
		// 预售订单
		$order_info['is_presaled'] = $is_presaled;
		
        // 重新计算邮费
        $freight = Sher_Core_Helper_Order::freight_stat($order_info['rid'], $this->stash['addbook_id'], array('items'=>$order_info['items'], 'is_vop'=>$is_vop, 'total_money'=>$order_info['total_money']));
        $order_info['freight'] = $freight;
		
		// 优惠活动金额
		$coin_money = $order_info['coin_money'];
		
		// 红包金额
		$card_money = $order_info['card_money'];
		
		// 礼品卡金额
		$gift_money = $order_info['gift_money'];

		// 鸟币金额
    $bird_coin_money = $order_info['bird_coin_money'];

    // 鸟币数量
    $bird_coin_count = $order_info['bird_coin_count'];

    //红包和礼品卡不能同时 使用
    if(!empty($card_money) && !empty($gift_money)){
			return 	$this->ajax_json('红包和礼品卡不能同时使用！', true);
    }
		
		try{
			$orders = new Sher_Core_Model_Orders();
			
			$order_info['user_id'] = (int)$user_id;

            $order_info['is_vop'] = $is_vop;
			
			$order_info['addbook_id'] = $this->stash['addbook_id'];
			
			// 来源手机Wap订单
			$order_info['from_site'] = Sher_Core_Util_Constant::FROM_WAP;
			
			// 更新送货时间
			if(!empty($transfer_time)){
				$order_info['transfer_time'] = $transfer_time;
			}
			
			// 订单备注
			if(isset($this->stash['summary'])){
				$order_info['summary'] = $this->stash['summary'];
			}
			
			// 商品金额
			$order_info['total_money'] = $total_money;
			
			// 应付金额
			$pay_money = $total_money + $freight - $coin_money - $card_money - $gift_money - $bird_coin_money;
			
			// 支付金额不能为负数
			if($pay_money < 0){
				$pay_money = 0.0;
			}
			$order_info['pay_money'] = $pay_money;
			
			// 设置订单状态
			$order_info['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;

      $is_snatched = false;
      //抢购产品状态，跳过付款状态
      if( is_array($order_info['items']) && count($order_info['items'])==1 && isset($order_info['items'][0]['is_snatched']) && $order_info['items'][0]['is_snatched']==1){

          // 获取产品信息
          $product = new Sher_Core_Model_Product();
          $product_data = $product->load((int)$order_info['items'][0]['product_id']);
          if(empty($product_data)){
            return $this->ajax_json('抢购产品不存在！', true);
          }

          //是否在抢购列表里
          if(!$this->snatch_product_ids($product_data['_id'])){
            return $this->ajax_json('抢购产品不在列表之内！', true);               
          }

          //是否是抢购商品
          if($product_data['snatched'] != 1){
             return $this->ajax_json('非抢购产品！', true);
          }

          //是否有库存
          if($product_data['snatched_count']==0 || $product_data['inventory']==0){
            return $this->ajax_json('没有库存！', true);              
          }

          //在抢购时间内
          if(empty($product_data['snatched_time']) || (int)$product_data['snatched_time'] > time()){
            return $this->ajax_json('抢购还没有开始！', true);
          }

          // 验证是否预约过抢购商品
          if(!$this->validate_appoint($product_data['_id'])){
            //return $this->ajax_json('抱歉，您还没有预约，不能参加本次抢购！', true);
          }
          // 验证抢购商品是否重复
          if(!$this->validate_snatch($product_data['_id'])){
            return $this->ajax_json('抱歉，不要重复抢哦！', true);
          }

          $is_snatched = true;
          $snatch_product_id = $product_data['_id'];
          // 如果抢购价为0,设置订单状态为备货
          if((float)$pay_money==0){
            $order_info['status'] = Sher_Core_Util_Constant::ORDER_READY_GOODS;
            $order_info['is_payed'] = 1;              
          }
        
      }

      //抢购商品状态
      if($is_snatched){
        $order_info['kind'] = 2;
      }

      //验证积分兑换
      if( is_array($order_info['items']) && count($order_info['items'])==1 && isset($order_info['items'][0]['is_exchanged']) && $order_info['items'][0]['is_exchanged']==1){
      
        $product_id = $order_info['items'][0]['product_id'];
        //再次验证用户积分并冻结用户相应的积分数量
        $check_bird = Sher_Core_Util_Shopping::check_and_freeze_bird_coin($order_info['bird_coin_count'], $order_info['user_id'], $product_id);
        if(!$check_bird['stat']){
 				  return 	$this->ajax_json($check_bird['msg'], true);       
        }
        
      }


            $order_info['jd_order_id'] = null;
            // 创建开普勒订单
            if(!empty($order_info['is_vop'])){
                $vop_result = Sher_Core_Util_Vop::create_order($order_info['rid'], array('data'=>$order_info));
                if(!$vop_result['success']){
				    return 	$this->ajax_json($vop_result['message'], true);
                }
                $order_info['jd_order_id'] = $vop_result['data']['jdOrderId'];
            }

			$ok = $orders->apply_and_save($order_info);
			// 订单保存成功
			if (!$ok) {
				return $this->ajax_json('订单生成失败，请重试！', true);
			}
			
			$data = $orders->get_data();
			
			$rid = $data['rid'];
			
			Doggy_Log_Helper::debug("Save Mobile Order [ $rid ] is OK!");
			
			// 购物车购物方式
			if ($is_cart) {
				// 清空购物车
        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        if(!empty($cart) && !empty($cart['items'])){
          foreach($order_info['items'] as $key=>$val){
            $o_type = (int)$val['type'];
            if($o_type==1){
              $o_target_id = (int)$val['product_id'];
            }elseif($o_type==2){
              $o_target_id = (int)$val['sku'];
            }

            // 批量删除
            foreach($cart['items'] as $k=>$v){
              if($v['target_id']==$o_target_id){
                unset($cart['items'][$k]);
              }
            }
          }// endfor
          $cart_ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items']))); 
        }

			}
			
			// 删除临时订单数据
			$model->remove($rrid);
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("confirm order failed: ".$e->getMessage());
			return $this->ajax_json('订单处理异常，请重试！', true);
    }catch(Exception $e){
			Doggy_Log_Helper::warn("confirm order failed.: ".$e->getMessage());
			return $this->ajax_json('订单处理异常，请重新下单！', true);
    }
		
		// 限量抢购活动设置缓存
    if($is_snatched){
      $this->check_have_snatch($snatch_product_id);
    }
		
    //如果是抢购并且为0元抢，无需支付，跳到我的订单页
    if($is_snatched && (float)$pay_money==0){
      $next_url = Doggy_Config::$vars['app.url.wap'].'/my/order_view?rid='.$rid;
    }else{
      $next_url = Doggy_Config::$vars['app.url.wap'].'/shop/success?rid='.$rid;
    }
		
		return $this->ajax_json('下订单成功！', false, $next_url);
	}
	
	/**
	 * 下单成功，选择支付方式，开始支付
	 */
	public function success(){
        $redirect_url = sprintf("%s/shop", Doggy_Config::$vars['app.url.wap']);
        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！');
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);

        if($order_info['status']==Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
            // 成功提交订单后，发送提醒邮件<异步进程处理>
            
            $this->stash['card_payed'] = false;
            // 验证是否需要跳转支付		
            if ($order_info['pay_money'] == 0.0 && ($order_info['total_money'] + $order_info['freight'] <= $order_info['card_money'] + $order_info['coin_money'] + $order_info['gift_money'] + $order_info['bird_coin_money'])){
                $trade_prefix = 'Coin';
                if($order_info['gift_money'] > 0){
                    $trade_prefix = 'Gift';
                }
                if($order_info['card_money'] > 0){
                    $trade_prefix = 'Card';
                }
                if($order_info['bird_coin_money'] > 0){
                    $trade_prefix = 'Card';
                }
                // 自动处理支付
                $trade_no = $trade_prefix.rand();
                $model->update_order_payment_info((string)$order_info['_id'], $trade_no, Sher_Core_Util_Constant::ORDER_READY_GOODS, 1, array('user_id'=>$order_info['user_id']));
                $this->stash['card_payed'] = true;
            }       
        }else{
            $this->stash['card_payed'] = true;       
        }
		
		$this->stash['order'] = $order_info;
		$this->stash['is_weixin'] = Sher_Core_Helper_Util::is_weixin();
		
		return $this->to_html_page('wap/success.html');
	}
	
	/**
	 * 处理支付
	 */
	public function payed(){
		$rid = $this->stash['rid'];
		$payaway = $this->stash['payaway'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！');
		}
		if (empty($payaway)){
			$next_url = Doggy_Config::$vars['app.url.wap'].'/shop/success?rid='.$rid;
			return $this->show_message_page('请至少选择一种支付方式！', $next_url, 2000);
		}
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 挑选支付机构
		Doggy_Log_Helper::warn('Pay mobile away:'.$payaway);
		
		$pay_url = '';
		switch($payaway){
			case 'alipay':
				$pay_url = Doggy_Config::$vars['app.url.wap'].'/pay/alipay?rid='.$rid;
				break;
			case 'quickpay':
				$pay_url = Doggy_Config::$vars['app.url.wap'].'/pay/quickpay?rid='.$rid;
				break;
			case 'wxpay':
				$pay_url = Doggy_Config::$vars['app.url.domain'].'/wxpay/payment?rid='.$rid;
				break;
			case 'jdpay':
				$pay_url = Doggy_Config::$vars['app.url.domain'].'/app/wap/jdpay/payment?rid='.$rid;
				break;
			default:
				return $this->show_message_page('请至少选择一种支付方式！', $next_url, 2000);
		}
		
		return $this->to_redirect($pay_url);
	}
	
	/**
	 * 使用红包
	 */
	public function ajax_bonus(){
		$rid = $this->stash['rid'];
		$code = $this->stash['code'];
		if(empty($rid) || empty($code)){
			return $this->ajax_json('订单编号或红包为空！', true);
		}
		
		try{
			$data = array();
            $model = new Sher_Core_Model_OrderTemp();
            $result = $model->first(array('rid'=>$rid));
            if (empty($result)){
                return $this->ajax_json('订单操作失败，请重试！', true);
            }

            $bonus_result = Sher_Core_Util_Shopping::check_bonus($rid, $code, $this->visitor->id, $result);
            if(!empty($bonus_result['code'])){
 			    return $this->ajax_json($bonus_result['msg'], true);             
            }

            $card_money = $bonus_result['coin_money'];

			// 更新临时订单
			$ok = $model->use_bonus($rid, $code, $card_money);
			if($ok){
				$dict = $result['dict'];
				$pay_money = $dict['total_money'] + $dict['freight'] - $dict['coin_money'] - $dict['card_money'] - $dict['gift_money'] - $dict['bird_coin_money'];
				
				// 支付金额不能为负数
				if($pay_money < 0){
					$pay_money = 0.0;
				}
				$data['discount_money'] = ($dict['coin_money'] +  $dict['card_money'] + $dict['gift_money'] + $dict['bird_coin_money'])*-1;
				$data['pay_money'] = $pay_money;
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Bonus order failed: ".$e->getMessage());
			return $this->ajax_json($e->getMessage(), true);
		}
		
		return $this->ajax_json('红包成功使用', false, null, $data);
	}
	
	/**
	 * 使用礼品码
	 */
	public function ajax_gift(){
		$rid = $this->stash['rid'];
		$code = $this->stash['code'];
		if(empty($rid) || empty($code)){
			return $this->ajax_json('订单编号或礼品码为空！', true);
		}
		
		try{
			// 验证订单信息
			$model = new Sher_Core_Model_OrderTemp();
			$order_info = $model->find_by_rid($rid);
			if(empty($order_info)){
				return $this->ajax_json('订单不存在！', true);
			}
			$items = $order_info['dict']['items'];
			if(count($items) != 1){
				return $this->ajax_json('礼品码仅限单一产品！', true);
			}
			
			// 验证礼品码
			$gift_money = Sher_Core_Util_Shopping::get_gift_money($code, $items[0]['product_id']);
			
			$data = array();
			// 更新临时订单
			$ok = $model->use_gift($rid, $code, $gift_money);
			if($ok){
				$result = $model->first(array('rid'=>$rid));
				if (empty($result)){
					return $this->ajax_json('订单操作失败，请重试！', true);
				}
				$dict = $result['dict'];
				$pay_money = $dict['total_money'] + $dict['freight'] - $dict['coin_money'] - $dict['card_money'] - $dict['gift_money'] - $dict['bird_coin_money'];
				
				// 支付金额不能为负数
				if($pay_money < 0){
					$pay_money = 0.0;
				}
				$data['discount_money'] = ($dict['coin_money'] +  $dict['card_money'] + $dict['gift_money'] + $dict['bird_coin_money'])*-1;
				$data['pay_money'] = $pay_money;
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Gift order failed: ".$e->getMessage());
			return $this->ajax_json($e->getMessage(), true);
		}
		
		return $this->ajax_json('礼品码成功使用', false, null, $data);
	}
	
	
	/**
	 * 生产临时订单
	 */
	protected function create_temp_order($items=array(),$total_money,$items_count,$options=array()){
		$data = array();
		$data['items'] = $items;
		$data['total_money'] = $total_money;
		$data['items_count'] = $items_count;
        $data['addbook_id'] = '';
	
		// 检测是否已设置默认地址
		$addbook = $this->get_default_addbook($this->visitor->id);
		if (!empty($addbook)){
			$data['addbook_id'] = (string)$addbook['_id'];
			$this->stash['default_addbook'] = $addbook;
		}
		
		// 获取快递费用
		$freight = Sher_Core_Util_Shopping::getFees();
		
		// 优惠活动费用
		$coin_money = 0.0;
		
		// 红包金额
		$card_money = 0.0;
		
		// 礼品码金额
		$gift_money = 0.0;

    // 鸟币金额
    $bird_coin_money = 0.0;

    // 鸟币数量
    $bird_coin_count = 0;
		
		// 设置订单默认值
		$default_data = array(
	        'payment_method' => 'a',
	        'transfer' => 'a',
	        'transfer_time' => 'a',
	        'summary' => '',
	        'invoice_type' => 0,
			'freight' => $freight,
			'card_money' => $card_money,
			'coin_money' => $coin_money,
      'gift_money' => $gift_money,
      'bird_coin_money' => $bird_coin_money,
      'bird_coin_count' => $bird_coin_count,
	        'invoice_caty' => 1,
	        'invoice_content' => 'd',
	    );
		
		$new_data = array();
		$new_data['dict'] = array_merge($default_data, $data);

    if(isset($options['is_cart'])){
      $new_data['is_cart'] = $options['is_cart'];
    }
    if(isset($options['is_presaled'])){
      $new_data['is_presaled'] = $options['is_presaled'];
    }
    if(isset($options['kind'])){
      $new_data['kind'] = $options['kind'];
    }
		$new_data['is_vop'] = isset($options['is_vop']) ? $options['is_vop'] : 0;
        $new_data['referral_code'] = isset($options['referral_code']) ? $options['referral_code'] : null;
		$new_data['user_id'] = $this->visitor->id;
		$new_data['expired'] = time() + Sher_Core_Util_Constant::EXPIRE_TIME;
		
		try{
			$order_info = array();
			// 预生成临时订单
			$model = new Sher_Core_Model_OrderTemp();
			$ok = $model->apply_and_save($new_data);
			if ($ok) {
				$order_info = $model->get_data();
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create temp order failed: ".$e->getMessage());
			return false;
		}
		
		return $order_info;
	}
	
	/**
	 * 确认收货地址
	 */
	public function address(){
		$rrid = $this->stash['rrid'];
		$addrid = $this->stash['addrid'];
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
		
		return $this->to_html_page('wap/address.html');
	}
	
	/**
	 * 确认收货地址
	 */
	public function submit_address(){
		$rrid = $this->stash['rrid'];
		$id = $this->stash['id'];
		
		$back_url = sprintf(Doggy_Config::$vars['app.url.wap'].'/shop/checkout?rrid=%s&addrid=%s', $rrid, $id);
		
		return $this->to_redirect($back_url);
	}
	
    /**
     * 修改配送地址
     */
	public function ajax_address(){
		$rrid = $this->stash['rrid'];
		$id = $this->stash['_id'];
		
		
		$model = new Sher_Core_Model_AddBooks();
		
		$data = array();
		$mode = 'create';
		
		$data['name'] = $this->stash['name'];
		$data['phone'] = $this->stash['phone'];
		$data['province'] = $this->stash['province'];
		$data['city']  = $this->stash['city'];
		$data['address'] = $this->stash['address'];
		$data['zip']  = $this->stash['zip'];
		$data['is_default'] = $this->stash['is_default'];
		
		try{
			// 检测是否有默认地址
			$ids = array();
			if ($data['is_default'] == 1) {
				$result = $model->find(array(
					'user_id' => (int)$this->visitor->id,
					'is_default' => 1,
				));
				for($i=0;$i<count($result);$i++){
					$ids[] = (string)$result[$i]['_id'];
				}
				Doggy_Log_Helper::debug('原默认地址:'.json_encode($ids));
			}
			
			if(empty($id)){
				$data['user_id'] = $this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				 
				$data = $model->get_data();
				$id = (string)$data['_id'];
			}else{
				$mode = 'edit';
				
				$data['_id'] = $id;
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('新地址保存失败,请重新提交', true);
			}
			
			// 更新默认地址
			if (!empty($ids)){
				$updated_default_ids = array();
				for($i=0;$i<count($ids);$i++){
					if ($ids[$i] != $id){
						Doggy_Log_Helper::debug('原默认地址:'.$ids[$i]);
						$model->update_set($ids[$i], array('is_default' => 0));
						$updated_default_ids[] = $ids[$i];
					}
				}
				$this->stash['updated_default_ids'] = $updated_default_ids;
			}
			
			$this->stash['id'] = $id;
			$this->stash['address'] = $model->extend_load($id);
			$this->stash['mode'] = $mode;
			
		} catch (Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn('新地址保存失败:'.$e->getMessage());
			return $this->ajax_json('新地址保存失败:'.$e->getMessage(), true);
		}
		
		return $this->to_taconite_page('wap/address/ajax_address.html');
	}
	
	/**
	 * 获取默认地址，无默认地址，取第一个地址
	 */
	protected function get_default_addbook($user_id){
		$addbooks = new Sher_Core_Model_DeliveryAddress();
		
		$query = array(
			'user_id' => (int)$user_id,
			'is_default' => 1
		);
		$options = array(
			'sort' => array('created_on' => -1),
		);
		$result = $addbooks->first($query);
    $result = $addbooks->extended_model_row($result);
		
		return $result;
	}
	
    /**
     * 设置订单的扩展参数
     * @return void
     */
    protected function set_extra_params($province=null){
        $order = new Sher_Core_Model_Orders();
		
        //获取付款方式列表
        $payment_methods = $order->find_payment_methods();
		$this->stash['payment_methods'] = $payment_methods;
		
        //获取送货方式
        $transfer_methods = $order->find_transfer_methods();
		if(!empty($province)){
			$order->validate_express_fees($province);
			$transfer_methods['a']['freight'] = $order->getFees();
		}
		$this->stash['transfer_methods'] = $transfer_methods;
		
        //获取送货时间列表
        $transfer_times = $order->find_transfer_time();
		$this->stash['transfer_times'] = $transfer_times;
		
        //获取发票内容类型
        $invoice_category = $order->find_invoice_category();
		$this->stash['invoice_category'] = $invoice_category;
        
        unset($order);
  }

  /**
   * 抢购ID列表
   */
  protected function snatch_product_ids($product_id){
    //取块内容
    $product_ids = Sher_Core_Util_View::load_block('snatch_product_ids', 1);
    $products_arr = array();
    if($product_ids){
      $products_arr = explode(',', $product_ids);
    }
    if(in_array((int)$product_id, $products_arr)){
      return true;
    }else{
      return false;
    }
  }

  /**
   * 抢购倒计时确认
   */
  public function check_snatch_expire(){
    $id = $this->stash['product_id'];
		$model = new Sher_Core_Model_Product();
    $product = $model->load((int)$id);
    if(empty($product)){
      return $this->ajax_json('商品未找到!', true);
    }
    if($product['snatched_time'] <= time()){
      return $this->ajax_json('操作成功', false);
    }else{
      return $this->ajax_json('您的系统时间不准确,请刷新页面查看结果!', true);
    }
  }

  /**
   * 验证用户鸟币
   */
  public function ajax_check_bird_coin(){
    $user_id = $this->visitor->id;
		$rid = $this->stash['rid'];
		$bird_coin = $this->stash['bird_coin'];
		if(empty($rid) || empty($bird_coin)){
			return $this->ajax_json('订单编号或鸟币为空！', true);
		}
		
		try{
			// 验证订单信息
			$model = new Sher_Core_Model_OrderTemp();
			$order_info = $model->find_by_rid($rid);
			if(empty($order_info)){
				return $this->ajax_json('订单不存在！', true);
			}
			$items = $order_info['dict']['items'];
			if(count($items) != 1){
				return $this->ajax_json('仅限单一产品使用！', true);
			}
			
			// 验证鸟币-返回抵消金额 
			$bird_coin_money = Sher_Core_Util_Shopping::check_bird_coin($bird_coin, $user_id, $items[0]['product_id']);
			
			$data = array();
			// 更新临时订单
			$ok = $model->use_bird_coin($rid, $bird_coin, $bird_coin_money);
			if($ok){
				$result = $model->first(array('rid'=>$rid));
				if (empty($result)){
					return $this->ajax_json('订单操作失败，请重试！', true);
				}
				$dict = $result['dict'];
				$pay_money = $dict['total_money'] + $dict['freight'] - $dict['coin_money'] - $dict['card_money'] - $dict['gift_money'] - $dict['bird_coin_money'];
				
				// 支付金额不能为负数
				if($pay_money < 0){
					$pay_money = 0.0;
				}
				$data['discount_money'] = ($dict['coin_money'] + $dict['card_money'] + $dict['gift_money'] + $dict['bird_coin_money'])*-1;
				$data['pay_money'] = $pay_money;
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("use bird-coin order failed: ".$e->getMessage());
			return $this->ajax_json($e->getMessage(), true);
		}
		
		return $this->ajax_json('鸟币使用成功!', false, null, $data);
  }

	/**
	 * 获取推荐产品
	 */
	public function ajax_guess_product(){
		$sword = $this->stash['sword'];
        $current_id = $this->stash['id'];
		$size = $this->stash['size'];
		
		$result = array();
		$options = array(
			'page' => 1,
			'size' => $size,
			'sort_field' => 'latest',
      // 最新
      'sort' => 1,
      'evt' => 'tag',
      't' => 6,
      'oid' => $current_id,
      'type' => 1,
		);        
		if(!empty($sword)){
      $xun_arr = Sher_Core_Util_XunSearch::search($sword, $options);
      if($xun_arr['success'] && !empty($xun_arr['data'])){
        $product_mode = new Sher_Core_Model_Product();
        $items = array();
        foreach($xun_arr['data'] as $k=>$v){
          $product = $product_mode->extend_load((int)$v['oid']);
          if(!empty($product)){
            // 过滤用户表
            if(isset($product['user'])){
              $product['user'] = Sher_Core_Helper_FilterFields::user_list($product['user']);
            }

              // tips
              if($product['tips_label']==1){
                $product['new_tips'] = true;
              }elseif($product['tips_label']==2){
                $product['hot_tips'] = true;         
              }
            
            array_push($items, array('product'=>$product));
          }
        }
        $result['rows'] = $items;
        $result['total_rows'] = $xun_arr['total_count'];
      }else{
             $addition_criteria = array(
                'type' => 1,
                'target_id' => array('$ne' => (int)$current_id),
                'stage' => 9,
            );
            $sword = array_values(array_unique(preg_split('/[,，\s]+/u', $sword)));
			  //$result = Sher_Core_Service_Search::instance()->search(implode('',$sword), 'full', $addition_criteria, $options);     
        $result = array();
      }

		}
        
        return $this->ajax_json('', false, '', $result);
	}

  /**
   * ajax加载商品列表
   */
  public function ajax_load_list(){    
        $category_id = $this->stash['category_id'];
        $presaled = isset($this->stash['presaled'])?$this->stash['presaled']:0;
        $category_tags = isset($this->stash['category_tags']) ? $this->stash['category_tags'] : null;
        $brand_id = isset($this->stash['brand_id']) ? $this->stash['brand_id'] : null;
        $type = $this->stash['type'];

        // 是否使用缓存
        $use_cache = isset($this->stash['use_cache']) ? (int)$this->stash['use_cache'] : 0;

        
        $page = $this->stash['page'];
        $size = $this->stash['size'];
        $sort = $this->stash['sort'];
        
        $query = array();
        $options = array();
        $result = array();
        
		if ($category_id) {
			$query['category_ids'] = (int)$category_id;
		}
        // is_shop=1
        $query['stage'] = 9;

        if($brand_id){
            $query['brand_id'] = $brand_id;
        }

        if($category_tags){
          $category_tag_arr = explode(',', $category_tags);
          $query['category_tags'] = array('$in'=>$category_tag_arr);
        }
        
        // 仅发布
        $query['published'] = 1;
        $query['deleted'] = 0;
        
        if($type){
            switch((int)$type){
                case 1:
                    $query['stage'] = 15;
                    break;
                case 2:
                    $query['stage'] = 9;
                    break;
                case 3:
                    $query['stage'] = 12;
                    break;
                case 4:
                    $query['stick'] = 1;
                    break;
                case 5:
                    $query['featured'] = 1;
                    break;
                case 6:
                    $query['snatched'] = 1;
                    break;
                case 7:
                    $query['stage'] = 5;
                    break;
                case 8:
                    $query['hatched'] = 1;
                    break;
            }
        }
		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'vote';
				break;
			case 2:
				$options['sort_field'] = 'love';
				break;
			case 3:
				$options['sort_field'] = 'comment';
				break;
			case 4:
				$options['sort_field'] = 'stick:update';
				break;
			case 5:
				$options['sort_field'] = 'featured:update';
				break;
			case 6:
				$options['sort_field'] = 'comment';
				break;
			case 7:
				$options['sort_field'] = 'price';
				break;
			case 8:
				$options['sort_field'] = 'price_asc';
				break;
			case 9:
				$options['sort_field'] = 'view_count';
				break;
		}
        
        $options['page'] = $page;
        $options['size'] = $size;

        //限制输出字段
        $some_fields = array(
          '_id'=>1, 'title'=>1, 'short_title'=>1, 'snatched'=>1, 'featured'=>1, 'brand_id'=>1,
          'stage'=>1, 'stick'=>1, 'category_id'=>1, 'created_on'=>1, 'asset_count'=>1, 'vote_favor_count'=>1,
          'advantage'=>1, 'sale_price'=>1, 'cover_id'=>1, 'comment_count'=>1, 'view_count'=>1,
          'updated_on'=>1, 'favorite_count'=>1, 'love_count'=>1, 'deleted'=>1,'presale_money'=>1, 'tags'=>1,
          'vote_oppose_count'=>1, 'summary'=>1, 'voted_finish_time'=>1, 'succeed'=>1, 'presale_finish_time'=>1,
          'sale_count'=>1, 'tips_label'=>1, 'hatched_cover_url'=>1,
        );
        $options['some_fields'] = $some_fields;
        
        $r_key = sprintf("wap:shop_list:%s_%s_%s_%s_%s_%s_%s", $type, $category_id, $category_tags, $brand_id, $sort, $page, $size);
        $redis = new Sher_Core_Cache_Redis();

        // 从redis获取 
        if($use_cache){
            $result = $redis->get($r_key);
            if($result){
                $result = json_decode($result, true);
            }       
        }

        // 无缓存读数据库
        if(empty($result)){

            $service = Sher_Core_Service_Product::instance();
            $result = $service->get_product_list($query, $options);

            $max = count($result['rows']);
            for($i=0;$i<$max;$i++){
              // 过滤用户表
              if(isset($result['rows'][$i]['user'])){
                $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user']);
              }

              // tips
              if($result['rows'][$i]['tips_label']==1){
                $result['rows'][$i]['new_tips'] = true;
              }elseif($result['rows'][$i]['tips_label']==2){
                $result['rows'][$i]['hot_tips'] = true;         
              }

            } //end for

            // 写入缓存
            if(!empty($use_cache) && !empty($result)){
                $redis->set($r_key, json_encode($result), Sher_Core_Util_Constant::REDIS_CACHE_EXPIRED);
            }

            $data = array();

            $data['type'] = $type;
            $data['page'] = $page;
            $data['sort'] = $sort;
            $data['size'] = $size;
            $data['presaled'] = $presaled;
            $data['category_id'] = $category_id;
            $data['results'] = $result;

        }   // endif !cache
        
        return $this->ajax_json('', false, '', $data);
  }
	

}

