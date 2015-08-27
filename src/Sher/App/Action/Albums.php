<?php
/**
 * 专辑
 * @ author caowei@taihuoniao.com
 */
class Sher_App_Action_Albums extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
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

	protected $page_html = 'page/albums/index.html';
	
	protected $exclude_method_list = array('execute','get_list','view');
	
	public function _init() {
		$this->set_target_css_state('page_albums');
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_PRODUCT;
    }
	
	/**
	 * 专辑
	 */
	public function execute(){
		return $this->index();
	}
    
	/**
	 * 专辑首页
	 */
	public function index(){
		return $this->to_html_page('page/albums/index.html');
	}
	
	/**
	 * 上传函数
	 */
	protected function upload(){
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_ALBUMS;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_ALBUMS;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
	}
	
	/**
	 * 专辑添加页面
	 */
	public function add(){
		Doggy_Log_Helper::warn("保存失败");
		$this->stash['mode'] = 'edit';
		$this->upload();
		return $this->to_html_page('page/albums/submit.html');
	}
	
	/**
	 * 专辑编辑页面
	 */
	public function edit(){
		$this->stash['mode'] = 'edit';
		$this->upload();
		return $this->to_html_page('page/albums/submit.html');
	}
	
	/**
	 * 保存主题信息
	 */
	public function save(){
		
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_json('标题不能为空！', true);
		}
		
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['des'] = $this->stash['des'];
		$data['cover_id'] = $this->stash['cover_id'];
		
		// 检查是否有图片
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
		}else{
			$data['asset'] = array();
		}
		
		try{
			$id = (int)$this->stash['id'];
			$model = new Sher_Core_Model_Album();
			
			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = (int)$this->visitor->id;
				//var_dump($data);
				$ok = $model->apply_and_save($data);
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			// 上传成功后，更新所属的图片
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}
				
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("保存失败：".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}catch(Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
			
		}
		
		//$redirect_url = Sher_Core_Helper_Url::topic_view_url($id);
		//return $this->ajax_json('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 专辑列表
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
	 * 查看专辑详情
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
		$this->stash['page_title_suffix'] = sprintf("%s-【%s】-太火鸟智品库", $product['title'], $product['category']['title']);
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

        // 验证积分兑换
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

		//评论参数
		$comment_options = array(
		  'comment_target_id' =>  $product['_id'],
		  'comment_target_user_id' => $product['user_id'],
		  'comment_type'  =>  4,
		  'comment_pager' =>  Sher_Core_Helper_Url::sale_view_url($id, '#p#'),
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
		
		return $this->to_html_page('page/shop/view.html');
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
     * ajax加载专辑列表
     */
    public function ajax_load_list(){        
        $category_id = $this->stash['category_id'];
        $presaled = isset($this->stash['presaled'])?$this->stash['presaled']:0;
        $type = $this->stash['type'];
        
        $page = $this->stash['page'];
        $size = $this->stash['size'];
        $sort = $this->stash['sort'];
        
        $service = Sher_Core_Service_Product::instance();
        $query = array();
        $options = array();
        
		if ($category_id) {
			$query['category_id'] = (int)$category_id;
		}
        // is_shop=1
        $query['stage'] = array('$in'=>array(5, 9, 12, 15));
        
		// 预售
		if ($presaled) {
		  $query['stage'] = 5;
		}
        // 仅发布
        $query['published'] = 1;
        
        if($type){
            switch((int)$type){
                case 1:
                    $query['stage'] = 15;
                    break;
                case 2:
                    $query['stage'] = array('$in'=>array(5,9));
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
        
        $result = $service->get_product_list($query, $options);
        
        $this->stash['results'] = $result;
        
        return $this->ajax_json('', false, '', $this->stash);
    }
}
