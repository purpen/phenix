<?php
/**
 * 商店
 * @author purpen
 */
class Sher_App_Action_Shop extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'sku' => '',
		'type' => 0,
		'category_id' => 0,
		'sort' => 0,
		'topic_id' => '',
		'page' => 1,
		'size' => 3,
		'sword' => '',
		'page_title_suffix' => '太火鸟智品库-智能硬件产品购买、评测、资讯信息库',
		'page_keywords_suffix' => '太火鸟,太火鸟智品库,智能硬件,产品评测,产品资讯',
		'page_description_suffix' => '太火鸟智品库有海量智能硬件评测和资讯信息，并提供智能出行设备、智能手表、智能手环、智能家居、运动健康、智能情趣、智能母婴等上百种智能硬件产品的在线销售',
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/shop/index.html';
	
	protected $exclude_method_list = array('execute','get_list','view','ajax_fetch_comment','check_snatch_expire','pmall','ajax_guess_product', 'product_list', 'edit_evaluate', 'ajax_load_list', 'ajax_load_albums_list');
	
	public function _init() {
		$this->set_target_css_state('page_shop');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_PRODUCT;
    }
	
	/**
	 * 社区
	 */
	public function execute(){

        // 来自花瓣送红包统计
        $from = isset($this->stash['from']) ? $this->stash['from'] : null;
        if(!empty($from) && $from=='hb'){
          // 存cookie
          @setcookie('from_origin', '5', time()+3600*24, '/');
          $_COOKIE['from_origin'] = '5';
          @setcookie('from_target_id', '1', time()+3600*24, '/');
          $_COOKIE['from_target_id'] = '1';

          // 统计点击数量
          $dig_model = new Sher_Core_Model_DigList();
          $dig_key = Sher_Core_Util_Constant::DIG_THIRD_DB_STAT;

          $dig = $dig_model->load($dig_key);
          if(empty($dig) || !isset($dig['items']["view_05"])){
            $dig_model->update_set($dig_key, array("items.view_05"=>1), true);     
          }else{
            // 增加浏览量
            $dig_model->inc($dig_key, "items.view_05", 1);
          }

        }

		return $this->index();
	}
	
	/**
	 * 商店首页
	 */
	public function test(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.shop'];
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的产品不存在或已被删除！', $redirect_url);
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
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);
		
		// 非销售状态的产品，跳转至对应的链接
		if(!in_array($product['stage'], array(Sher_Core_Model_Product::STAGE_SHOP, Sher_Core_Model_Product::STAGE_EXCHANGE, Sher_Core_Model_Product::STAGE_IDEA))){
			return $this->to_redirect($product['view_url']);
		}

        // 判断类型
        if($product['stage'] == Sher_Core_Model_Product::STAGE_SHOP){
            $item_stage = $this->stash['item_stage'] = 'shop';
        }elseif($product['stage'] == Sher_Core_Model_Product::STAGE_EXCHANGE){
            $item_stage = $this->stash['item_stage'] = 'exchange';
        }elseif($product['stage'] == Sher_Core_Model_Product::STAGE_IDEA){
            $item_stage = $this->stash['item_stage'] = 'idea';
        }else{
  	        return $this->show_message_page('产品类型错误！', $redirect_url);  
        }

        // 验证积分兑换
        if($item_stage == 'exchange'){
            if(empty($product['exchanged']) || empty($product['max_bird_coin'])){
    	        return $this->show_message_page('产品积分异常错误！', $redirect_url);        
            }
        }
		
		// 未发布上线的产品，仅允许本人及管理员查看
		if(!$product['published'] && !($this->visitor->can_admin() || $product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('访问的产品等待发布中！', $redirect_url);
		}

        // 判断是否为秒杀产品 
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
		$this->stash['skus'] = $skus;
		$this->stash['skus_count'] = count($skus);
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::sale_view_url($id,'#p#');
		
		$this->stash['product'] = $product;
		$this->stash['id'] = $id;
		
		// 验证关注关系
		$ship = new Sher_Core_Model_Follow();
		$is_ship = $ship->has_exist_ship($this->visitor->id, $product['designer_id']);
		$this->stash['is_ship'] = $is_ship;
        // 私信用户
        $this->stash['user'] = $product['designer'];
		return $this->to_html_page('page/shop/view.html');
	}
	/**
	 * 商店首页
	 */
	public function index(){
		return $this->to_html_page('page/shop/home.html');
	}
	
	/**
	 * 商店列表
	 */
	public function get_list() {
		$category_id = (int)$this->stash['category_id'];
		$type = (int)$this->stash['type'];
		$sort = (int)$this->stash['sort'];
		$page = (int)$this->stash['page'];
		$current_category = array();
		
	    $presale = isset($this->stash['presale'])?(int)$this->stash['presale']:0;
	    $this->stash['all_active'] = false;
	    $this->stash['presale_active'] = false;
	    if(empty($presale)){
			$this->stash['is_shop'] = 1;
			$this->stash['presaled'] = 0;
			if($category_id == 0){
				$this->stash['all_active'] = true;
				$current_category = array('name' => 'all');
			}
			$pager_url = Sher_Core_Helper_Url::shop_list_url($category_id, $type, $sort,'#p#');
			$list_prefix = Doggy_Config::$vars['app.url.shop'];
	    }else{
			$this->stash['is_shop'] = 0;
			$this->stash['presaled'] = 1;
			if($category_id == 0){
				$this->stash['presale_active'] = true;
			}
			$pager_url = Sher_Core_Helper_Url::sale_list_url($category_id, $type, $sort,'#p#');
			$list_prefix = Doggy_Config::$vars['app.url.sale'];
            
			$this->set_target_css_state('saled');
			$current_category = array('name'=>'presale');
	    }
		// 排序方式
		switch($sort){
			case 1:
				$sort_text = 'latest';
				break; 
			case 2:
				$sort_text = 'hot';
				break;
			case 3:
				$sort_text = empty($presale) ? 'price' : 'money';
				break;
			case 4:
				$sort_text = empty($presale) ? 'sales' : 'presales';
				break;
			default:
				$sort_text = 'stick:latest';
				break;
		}
		$this->stash['sort_text'] = $sort_text;
		
		$this->stash['pager_url'] = $pager_url;
		$this->stash['list_prefix'] = $list_prefix;
		
		// 获取当前类别
		if($category_id){
			$category = new Sher_Core_Model_Category();
			$current_category = $category->extend_load((int)$category_id);
			//添加网站meta标签
			$this->stash['page_title_suffix'] = Sher_Core_Helper_View::meta_category_obj($current_category, 1);
			$this->stash['page_keywords_suffix'] = Sher_Core_Helper_View::meta_category_obj($current_category, 2);   
			$this->stash['page_description_suffix'] = Sher_Core_Helper_View::meta_category_obj($current_category, 3);
		}

		$this->stash['current_category'] = $current_category;
		
		return $this->to_html_page('page/shop/index.html');
	}
    
    /**
     * 积分商城
     */
    public function pmall(){
        $current_point = array();
        if($this->visitor->id){
            // 用户实时积分
            $point_model = new Sher_Core_Model_UserPointBalance();
            $current_point = $point_model->load($this->visitor->id);
        }
        
        $this->stash['current_point'] = $current_point;
        
        return $this->to_html_page('page/shop/pmall.html');
    }
	
	/**
	 * 查看产品详情
	 */
	public function view() {
		//return $this->test();
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.shop'];
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
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);
        if (!empty($product)) {
            $product = $model->extended_model_row($product);
        }
		
		if(empty($product) || $product['deleted']){
			return $this->show_message_page('访问的产品不存在或已被删除！', $redirect_url);
		}

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
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);

		$model->inc_counter('true_view_count', 1, $id);
		$model->inc_counter('web_view_count', 1, $id);
		
		// 非销售状态的产品，跳转至对应的链接
		if(!in_array($product['stage'], array(Sher_Core_Model_Product::STAGE_SHOP, Sher_Core_Model_Product::STAGE_EXCHANGE, Sher_Core_Model_Product::STAGE_IDEA))){
			return $this->to_redirect($product['view_url']);
		}

		// 当前用户是否有管理权限
        $editable = false;
		if ($this->visitor->id){
			if ($this->visitor->id == $product['user_id'] || $this->visitor->can_edit){
				$editable = true;
			}
		}
        $this->stash['editable'] = $editable;

        // 判断类型
        if($product['stage'] == Sher_Core_Model_Product::STAGE_SHOP){
            $item_stage = $this->stash['item_stage'] = 'shop';
        }elseif($product['stage'] == Sher_Core_Model_Product::STAGE_EXCHANGE){
            $item_stage = $this->stash['item_stage'] = 'exchange';
        }elseif($product['stage'] == Sher_Core_Model_Product::STAGE_IDEA){
            $item_stage = $this->stash['item_stage'] = 'idea';
        }else{
  	        return $this->show_message_page('产品类型错误！', $redirect_url);  
        }

        // 显示购买及兑换按钮
        if(in_array($product['stage'], array(9, 12))){
          $this->stash['show_pay_btn'] = true;
        }else{
          $this->stash['show_pay_btn'] = false;       
        }

        // 验证积分兑换
        if($item_stage=='exchange'){
            if(empty($product['exchanged']) || empty($product['max_bird_coin'])){
    	        return $this->show_message_page('产品积分异常错误！', $redirect_url);        
            }
        }

        // 判断是否为秒杀产品 
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
		$this->stash['skus'] = $skus;
		$this->stash['skus_count'] = count($skus);

		//评论参数
		$comment_options = array(
		  'comment_target_id' =>  $product['_id'],
		  'comment_target_user_id' => $product['user_id'],
		  'comment_type'  =>  4,
		  'comment_pager' =>  Sher_Core_Helper_Url::shop_view_url($id, '#p#'),
		  //是否显示上传图片/链接
		  'comment_show_rich' => 1,
		);
		$this->_comment_param($comment_options);
		
		$this->stash['product'] = $product;
		$this->stash['id'] = $id;
		
		// 验证关注关系
		$ship = new Sher_Core_Model_Follow();
		$is_ship = $ship->has_exist_ship($this->visitor->id, $product['designer_id']);
		$this->stash['is_ship'] = $is_ship;
        // 私信用户
        $this->stash['user'] = $product['designer'];
		
		// 获取专辑分类
		$albums = new Sher_Core_Model_Albums();
		$albums = $albums->find();
		$this->stash['albums_url'] = Doggy_Config::$vars['app.url.album.shop'].'?did=';
		$this->stash['albums'] = $albums;
		
		return $this->to_html_page('page/shop/view.html');
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
			't' => 7,
			'oid' => $current_id,
			'type' => 1,
		);        
		if(!empty($sword)){
            $xun_arr = Sher_Core_Util_XunSearch::search($sword, $options);
            if($xun_arr['success'] && !empty($xun_arr['data'])){
                $product_mode = new Sher_Core_Model_Product();
                $items = array();
                foreach($xun_arr['data'] as $k=>$v){
                    // 过滤当前对象
                    if((int)$current_id == (int)$v['oid']){
                      continue;
                    }
                    $product = $product_mode->extend_load((int)$v['oid']);
                    if(!empty($product)){
                        // 过滤用户表
                        if(isset($product['user'])){
                          $product['user'] = Sher_Core_Helper_FilterFields::user_list($product['user']);
                        }
                        if(isset($product['designer'])){
                          $product['designer'] = Sher_Core_Helper_FilterFields::user_list($product['designer']);
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
                );
                $sword = array_values(array_unique(preg_split('/[,，\s]+/u', $sword)));
                //$result = Sher_Core_Service_Search::instance()->search(implode('',$sword), 'full', $addition_criteria, $options);     
                $result = array();
            }

        }

    if(empty($result)){
      return false;
    }

    $data = array();
    $data['state'] = 1;
    $data['results'] = $result;
        
        return $this->ajax_json('', false, '', $data);
	}
	
	/**
	 * ajax获取产品用户评价
	 */
	public function ajax_fetch_comment(){
        $current_user_id = $this->visitor->id?(int)$this->visitor->id:0;
		$this->stash['page'] = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$this->stash['per_page'] = isset($this->stash['per_page'])?(int)$this->stash['per_page']:8;
		$this->stash['total_page'] = isset($this->stash['total_page'])?(int)$this->stash['total_page']:1;
        $this->stash['current_user_id'] = $current_user_id;
        
		return $this->to_taconite_page('ajax/fetch_shop_comment.html');
	}


  	/**
   	 * 产品合作入口
   	 */
  	public function cooperate(){
        $this->set_target_css_state('page_cooperate');
   		return $this->to_html_page('page/shop/cooperate.html');
  	}

	/**
	 * 删除某个附件
	 */
	public function delete_asset(){
		$id = $this->stash['id'];
		$asset_id = $this->stash['asset_id'];
		if (empty($asset_id)){
			return $this->ajax_note('附件不存在！', true);
		}
		
		if (!empty($id)){
			$model = new Sher_Core_Model_Product();
			$model->delete_asset($id, $asset_id);
		}else{
			// 仅仅删除附件
			$asset = new Sher_Core_Model_Asset();
			$asset->delete_file($id);
		}
		
		return $this->to_taconite_page('ajax/delete_asset.html');
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
   * 商品列表,给兼职编辑
   */
  public function product_list(){
    if(!$this->visitor->can_edit){
 		  return $this->ajax_note('没有权限!', true);	   
    }
    $stage = isset($this->stash['stage'])?(int)$this->stash['stage']:0;
    $this->set_target_css_state('page_product');
		$pager_url = Doggy_Config::$vars['app.url.shop'].'/product_list?stage=%d&page=#p#';
		switch($stage){
		case 12:
				$this->stash['process_exchange'] = 1;
				break;
			case 9:
				$this->stash['process_saled'] = 1;
				break;
			case 5:
				$this->stash['process_presaled'] = 1;
				break;
			case 1:
				$this->stash['process_voted'] = 1;
				break;
		}
		$this->stash['pager_url'] = sprintf($pager_url, $stage);
    $this->stash['is_search'] = false;
  
		return $this->to_html_page('page/shop/product_list.html');
  }

  /**
   * 加评论,给兼职编辑
   */
  public function edit_evaluate(){
    if(!$this->visitor->can_edit){
 		  return $this->ajax_note('没有权限!', true);	   
    }

		$id = (int)$this->stash['id'];
		
		$model = new Sher_Core_Model_Product();
		if(!empty($id)){
			$product = $model->load($id);
	        if (!empty($product)) {
	            $product = $model->extended_model_row($product);
	        }
			$this->stash['product'] = $product;
		}
  
		return $this->to_html_page('page/shop/evaluate.html');
  }

	/**
	 * 编辑快捷评价
	 */
	public function edit_ajax_evaluate(){

        if(!$this->visitor->can_edit){
     		  return $this->ajax_json('没有权限!', true);	   
        }
		$row = array();
		$row['user_id'] = $this->stash['user_id'];
		$row['star'] = $this->stash['star'];
		$row['target_id'] = $this->stash['target_id'];
		$row['content'] = $this->stash['content'];
		$row['type'] = (int)$this->stash['type'];
		
		// 验证数据
		if(empty($row['target_id']) || empty($row['content']) || empty($row['star'])){
			return $this->ajax_json('获取数据错误,请重新提交', true);
		}
		
		$model = new Sher_Core_Model_Comment();
		$ok = $model->apply_and_save($row);
		if($ok){
			$comment_id = $model->id;
			$this->stash['comment'] = &$model->extend_load($comment_id);
		}
		
        return $this->ajax_json('操作成功!', false);
	}

  /**
   * 产品搜索
   */
  public function edit_search(){
    if(!$this->visitor->can_edit){
 		  return $this->ajax_note('没有权限!', true);	   
    }
    $this->set_target_css_state('page_product');
    $this->stash['is_search'] = true;
		
		$pager_url = Doggy_Config::$vars['app.url.shop'].'/edit_search?stage=%d&s=%d&q=%s&page=#p#';
		switch($this->stash['stage']){
			case 9:
				$this->stash['process_saled'] = 1;
				break;
			case 5:
				$this->stash['process_presaled'] = 1;
				break;
			case 1:
				$this->stash['process_voted'] = 1;
				break;
		}
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['stage'], $this->stash['s'], $this->stash['q']);
    return $this->to_html_page('page/shop/product_list.html');
  
  }

    /**
     * ajax加载商品列表
     */
    public function ajax_load_list(){        
        $category_id = $this->stash['category_id'];
        $category_tags = isset($this->stash['category_tags']) ? $this->stash['category_tags'] : null;
        $type = $this->stash['type'];
        
        $page = $this->stash['page'];
        $size = $this->stash['size'];
        $sort = $this->stash['sort'];
        
        $service = Sher_Core_Service_Product::instance();
        $query = array();
        $options = array();
        
		if ($category_id) {
			$query['category_ids'] = (int)$category_id;
		}
        // is_shop=1
        $query['stage'] = array('$in'=>array(5, 9, 12, 15));

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
		}
        
        $options['page'] = $page;
        $options['size'] = $size;

        //限制输出字段
        $some_fields = array(
          '_id'=>1, 'title'=>1, 'short_title'=>1, 'snatched'=>1, 'featured'=>1,
          'stage'=>1, 'stick'=>1, 'category_id'=>1, 'created_on'=>1, 'asset_count'=>1, 'vote_favor_count'=>1,
          'advantage'=>1, 'sale_price'=>1, 'cover_id'=>1, 'comment_count'=>1, 'view_count'=>1,
          'updated_on'=>1, 'favorite_count'=>1, 'love_count'=>1, 'deleted'=>1,'presale_money'=>1, 'tags'=>1,
          'vote_oppose_count'=>1, 'summary'=>1, 'voted_finish_time'=>1, 'succeed'=>1, 'presale_finish_time'=>1,
          'sale_count'=>1, 'tips_label'=>1,
        );
        $options['some_fields'] = $some_fields;
        
        $result = $service->get_product_list($query, $options);

        $max = count($result['rows']);
        for($i=0;$i<$max;$i++){
          // 过滤用户表
          if(isset($result['rows'][$i]['user'])){
            $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user']);
          }
          if(isset($result['rows'][$i]['designer'])){
            $result['rows'][$i]['designer'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['designer']);
          }

          // tips
          if($result['rows'][$i]['tips_label']==1){
            $result['rows'][$i]['new_tips'] = true;
          }elseif($result['rows'][$i]['tips_label']==2){
            $result['rows'][$i]['hot_tips'] = true;         
          }
        } // endfor

        $data = array();
        $data['results'] = $result;
        
        return $this->ajax_json('', false, '', $data);
    }

	/**
	 * 编辑产品灵感
	 */
	public function idea_edit(){
		if(empty($this->stash['id'])){
			return $this->show_message_page('缺少请求参数！', true);
		}
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$this->stash['id']);
		
        if(empty($product)){
            return $this->show_message_page('编辑的产品不存在或被删除！', true);
        }
		// 仅管理员或本人具有删除权限
		if (!$this->visitor->can_edit() && !($product['user_id'] == $this->visitor->id)){
			return $this->show_message_page('你没有权限编辑的该主题！', true);
		}
        
		$product = $model->extended_model_row($product);
		
		$this->stash['mode'] = 'edit';
		$this->stash['product'] = $product;
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		$this->_editor_params();
		
		return $this->to_html_page('page/shop/idea_submit.html');
	}

	/**
	 * 提交入口
	 */
	public function idea_submit(){

		$this->stash['mode'] = 'create';
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_PRODUCT;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		$this->_editor_params();
		
		return $this->to_html_page('page/shop/idea_submit.html');
	}

	/**
	 * 保存产品信息
	 */
	public function idea_save(){
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_json('标题不能为空！', true);
		}
        if(empty($this->stash['category_id'])){
            return $this->ajax_json('请选择一个类别！', true); 
        }
        if(empty($this->stash['cover_id'])){
            return $this->ajax_json('请至少上传一张图片并设置为封面图！', true); 
        }
        
		$id = (int)$this->stash['_id'];
		
		$mode = 'create';
		$data = array();
		
		$data['title'] = $this->stash['title'];
		$data['content'] = $this->stash['content'];
		$data['advantage'] = isset($this->stash['advantage'])?$this->stash['advantage']:null;
		$data['tags'] = $this->stash['tags'];
		$data['category_id'] = (int)$this->stash['category_id'];
		$data['cooperate_id'] = isset($this->stash['cooperate_id'])?(int)$this->stash['cooperate_id']:0;
        $data['cover_id'] = isset($this->stash['cover_id'])?$this->stash['cover_id']:null;
        $data['short_title'] = isset($this->stash['short_title'])?$this->stash['short_title']:'';

        // 添加视频
        $data['video'] = array();
        if(isset($this->stash['video'])){
            foreach($this->stash['video'] as $v){
                if(!empty($v)){
                    array_push($data['video'], $v);
                }
            }
        }
    
        // 团队介绍
        $team_introduce = isset($this->stash['team_introduce'])?$this->stash['team_introduce']:null;
        // 品牌名称
        $brand = isset($this->stash['brand'])?$this->stash['brand']:null;
        // 设计师
		$designer = isset($this->stash['designer'])?$this->stash['designer']:null;
        // 所属国家
		$country = isset($this->stash['country'])?$this->stash['country']:null;
        // 上市时间
		$market_time = isset($this->stash['market_time'])?$this->stash['market_time']:null;
        // 指导价格
		$official_price = isset($this->stash['official_price'])?$this->stash['official_price']:null;
        // 购买地址
		$buy_url = isset($this->stash['buy_url'])?$this->stash['buy_url']:null;
        // 产品阶段
		$processed = isset($this->stash['processed'])?$this->stash['processed']:0;

        $product_info = array(
            'team_introduce' => $team_introduce,
            'brand' => $brand,
            'designer' => $designer,
            'country' => $country,
            'market_time' => $market_time,
            'official_price' => $official_price,
            'buy_url' => $buy_url,
            'processed' => $processed,
        );
        
        $data['product_info'] = $product_info;

        // 关联产品
        $data['fever_id'] = isset($this->stash['fever_id'])?(int)$this->stash['fever_id']:0;
        $data['published'] = isset($this->stash['published'])?(int)$this->stash['published']:0;
		
		// 检测编辑器图片数
		$file_count = isset($this->stash['file_count'])?(int)$this->stash['file_count']:0;
        
		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
		}else{
			$data['asset'] = array();
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
                // 产品类型
                $data['stage'] = 15;
                
				$ok = $model->apply_and_save($data);
				
				$product = $model->get_data();
				$id = (int)$product['_id'];
				
				// 更新用户灵感数量
				$this->visitor->inc_counter('product_count', $data['user_id']);
			}else{
				$mode = 'edit';

		        $data['_id'] = $id;
                $ok = $model->apply_and_update($data);
			}
            
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			$asset = new Sher_Core_Model_Asset();
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$asset->update_batch_assets($data['asset'], (int)$id);
			}
			
			// 保存成功后，更新编辑器图片
			if(!empty($this->stash['file_id'])){
			  Doggy_Log_Helper::debug("Upload file count[$file_count].");
				$asset->update_editor_asset($this->stash['file_id'], (int)$id);
			}

            // 更新全文索引
            Sher_Core_Helper_Search::record_update_to_dig((int)$id, 3); 
            // 更新百度推送
            if($mode == 'create'){
                Sher_Core_Helper_Search::record_update_to_dig((int)$id, 12); 
            }
            
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("创意保存失败：".$e->getMessage());
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}

   	    $redirect_url = Sher_Core_Helper_Url::shop_view_url($id);       

		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 推荐
	 */
	public function ajax_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
            // 验证是否具有权限
            if(!$this->visitor->can_edit() && !$this->visitor->is_customer()){
                return $this->ajax_json('抱歉，你没有权限操作此项！', true);
            }
			$model = new Sher_Core_Model_Product();
			$model->mark_as_stick((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 取消推荐
	 */
	public function ajax_cancel_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
            // 验证是否具有权限
            if(!$this->visitor->can_edit() && !$this->visitor->is_customer()){
                return $this->ajax_json('抱歉，你没有权限操作此项！', true);
            }
            
			$model = new Sher_Core_Model_Product();
			$model->mark_cancel_stick((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
    
	/**
	 * 精选
	 */
	public function mark_as_featured(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
            // 验证是否具有权限
            if(!$this->visitor->can_edit() && !$this->visitor->is_customer()){
                return $this->ajax_json('抱歉，你没有权限操作此项！', true);
            }
			$model = new Sher_Core_Model_Product();
			$ok = $model->mark_as_featured((int)$id);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
    
	/**
	 * 取消精华
	 */
	public function mark_cancel_featured(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
            // 验证是否具有权限
            if(!$this->visitor->can_edit() && !$this->visitor->is_customer()){
                return $this->ajax_json('抱歉，你没有权限操作此项！', true);
            }
			$model = new Sher_Core_Model_Product();
			$model->mark_cancel_featured((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}

	/**
	 * 删除商品灵感
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Product();
			$product = $model->load((int)$id);
			
			// 仅编辑权限或本人具有删除权限
			if ($this->visitor->can_edit() || $product['user_id'] == $this->visitor->id){
				$model->remove((int)$id);
				
				// 删除关联对象
				$model->mock_after_remove($id);
				
				// 更新所属分类: 主题数、回复数
				$category = new Sher_Core_Model_Category();
				
				$category->dec_counter('total_count', $product['category_id']);
				$category->dec_counter('total_count', $product['fid']);
				
				// 更新用户主题数量
				$this->visitor->dec_counter('product_count', $product['user_id']);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		// 删除成功后返回URL
		$this->stash['redirect_url'] = Doggy_Config::$vars['app.url.shop'];
		$this->stash['ids'] = array($id);
		
		return $this->to_taconite_page('ajax/delete.html');
	}

	/**
	 * 编辑器参数
	 */
	protected function _editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$new_pic_id = new MongoId();
		$this->stash['editor_pid'] = (string)$new_pic_id;

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_PRODUCT;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_PRODUCT;
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
     * ajax加载商品所在专辑列表
     */
    public function ajax_load_albums_list(){
      $pid = isset($this->stash['pid']) ? (int)$this->stash['pid'] : 0;
      $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 5;
      $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
      $load_type = isset($this->stash['load_type']) ? (int)$this->stash['load_type'] : 1;
        
        $service = Sher_Core_Service_Albumshop::instance();
        $query = array();
        $options = array();

        if($pid){
			    $query['pid'] = $pid;
        }

        switch($sort){
          case 0:
          $options['sort_field'] = 'latest';
          break;
          case 1:
          $options['sort_field'] = 'latest';
          break;
        }
        
        $options['page'] = 1;
        $options['size'] = $size;
        
        $result = $service->get_Albumshop_list($query, $options);
		// 过滤用户
        $max = count($result['rows']);
        $album_model = new Sher_Core_Model_Albums();
        for($i=0;$i<$max;$i++){
			if(isset($result['rows'][$i]['user'])){
			  $result['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($result['rows'][$i]['user']);
        $album = $album_model->load($result['rows'][$i]['dadid']);
        $result['rows'][$i]['album'] = $album;
        unset($result['rows'][$i]['product']);
			}
        }
		
		$data = array();
        $data['has_one'] = $max>0 ? true : false;
        $data['album_url'] = Doggy_Config::$vars['app.url.album.shop'];
        $data['result'] = $result;
        return $this->ajax_json('', false, '', $data);
    }
	
}
