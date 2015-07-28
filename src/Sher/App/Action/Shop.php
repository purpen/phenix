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
    'page_title_suffix' => '太火鸟商店-智能硬件购物第一品牌',
    'page_keywords_suffix' => '太火鸟,太火鸟商店,太火鸟智能硬件商店,智能硬件,智能硬件商店,数码电子,智能家居,智能可穿戴设备,智能出行,智能家电,智能清洁,游戏影音,娱乐生活',
    'page_description_suffix' => '太火鸟商店是中国智能硬件购物第一品牌商店。在线销售智能家居，智能可穿戴设备，智能出行设备，智能家电，智能清洁设备，游戏影音设备，娱乐生活设备等上千种智能产品，全面、权威，为您提供完美的智能硬件购物体验。',
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/shop/index.html';
	
	protected $exclude_method_list = array('execute','get_list','view','ajax_fetch_comment','check_snatch_expire','pmall','ajax_guess_product', 'product_list', 'edit_evaluate', 'ajax_load_list');
	
	public function _init() {
		$this->set_target_css_state('page_shop');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_PRODUCT;
    }
	
	/**
	 * 社区
	 */
	public function execute(){
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
    $this->stash['page_title_suffix'] = sprintf("%s-【%s】-太火鸟商店", $product['title'], $product['category']['title']);
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

    //判断类型
    if($product['stage']==Sher_Core_Model_Product::STAGE_SHOP){
      $item_stage = $this->stash['item_stage'] = 'shop';
    }elseif($product['stage']==Sher_Core_Model_Product::STAGE_EXCHANGE){
      $item_stage = $this->stash['item_stage'] = 'exchange';
    }elseif($product['stage']==Sher_Core_Model_Product::STAGE_IDEA){
      $item_stage = $this->stash['item_stage'] = 'idea';
    }else{
  	  return $this->show_message_page('产品类型错误！', $redirect_url);  
    }

    //验证积分兑换
    if($item_stage=='exchange'){
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
    $this->stash['page_title_suffix'] = sprintf("%s-【%s】-太火鸟商店", $product['title'], $product['category']['title']);
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

    //判断类型
    if($product['stage']==Sher_Core_Model_Product::STAGE_SHOP){
      $item_stage = $this->stash['item_stage'] = 'shop';
    }elseif($product['stage']==Sher_Core_Model_Product::STAGE_EXCHANGE){
      $item_stage = $this->stash['item_stage'] = 'exchange';
    }elseif($product['stage']==Sher_Core_Model_Product::STAGE_IDEA){
      $item_stage = $this->stash['item_stage'] = 'idea';
    }else{
  	  return $this->show_message_page('产品类型错误！', $redirect_url);  
    }

    //验证积分兑换
    if($item_stage=='exchange'){
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
      'evt' => 'tag',
      't' => 1,
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
			  $result = Sher_Core_Service_Search::instance()->search(implode('',$sword), 'full', $addition_criteria, $options);     
      }

		}
		
		$this->stash['result'] = $result;
		
		return $this->to_taconite_page('ajax/guess_products.html');
	}
	
	/**
	 * ajax获取评论
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
			$model = new Sher_Core_Model_Contact();
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
  
  }
	
}
