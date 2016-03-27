<?php
/**
 * 情景商品
 * @author tianshuai
 */
class Sher_Core_Model_SceneProduct extends Sher_Core_Model_Base {

  protected $collection = "scene_product";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;

  ## 属性
  const ATTR_THN = 1; // 官网
  const ATTR_TB = 2;  // 淘宝
  const ATTR_TM = 3;  // 天猫
  const ATTR_JD = 4;  // 京东
	
  protected $schema = array(
    # 原文ID(淘宝、天猫、京东产品ID)
    'oid' => null,
    'user_id' => null,
		# 类别支持多选
		'category_id' => 0,
		# 分类父级
		'fid' => 0,
    # 来源; 1.官网；2.用户创建
    'kind' => 1,
		
		# 所属产品(对应官网产品ID)暂时不用
		'product_id' => 0,
    # 品牌ID
    'brand_id' => null,
		
	    'title' => '',
		'short_title' => '',
        'description' => '',
        'summary' => '',
    	'tags' => array(),
      # 分类标签
      'category_tags' => array(),
		
 		'cover_id' => '',
		'asset_ids' => array(),
    'asset_count' => 0,
    'banner_asset_ids' => array(),
    'png_asset_ids' => array(),
    # 封面图，从淘宝或京东抓取
    'cover_url' => null,

    # 销售价格
    'sale_price'  => 0,
    # 市场价格
    'market_price' => 0,
		
		## 计数器
		# 浏览数
    	'view_count' => 0,
		# 收藏数
        'favorite_count' => 0, 
		# 喜欢数
        'love_count' => 0,
		# 回应数 
    	'comment_count' => 0,
    # 去购买数
      'buy_count' => 0,

		# 推荐（编辑推荐、推荐至首页）
		'stick' => 0,
		# 精华标识
		'fine'  => 0,
		
    	'deleted' => 0,
		# 是否发布
    	'published' => 0,
    # 属性
      'attrbute' => self::ATTR_THN,
      'link' => null,
      'state' => 1,

  );
	
	protected $required_fields = array('user_id', 'title');
	protected $int_fields = array('user_id','category_id','try_id','fid','deleted','published','product_id');
	protected $float_fields = array('sale_price','market_price');
	
	protected $counter_fields = array('view_count', 'favorite_count', 'love_count', 'comment_count', 'buy_count');
	
	protected $joins = array(
	  'user'      =>  array('user_id'     => 'Sher_Core_Model_User'),
		'category'  =>  array('category_id' => 'Sher_Core_Model_Category'),
		//'brand'  =>  array('brand_id' => 'Sher_Core_Model_SceneBrands'),
	);
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
    if (isset($data['tags']) && !is_array($data['tags'])) {
        $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['tags'])));
    }
    if (isset($data['category_tags']) && !is_array($data['category_tags'])) {
        $data['category_tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['category_tags'])));
    }
		// 获取父级类及类组
		if (isset($data['category_id']) && !empty($data['category_id'])){
			$category = new Sher_Core_Model_Category();
			$result = $category->find_by_id((int)$data['category_id']);
			if (empty($result)){
				throw new Sher_Core_Model_Exception('所选分类出错！');
			}
			$data['fid'] = $result['pid'];
		}
		
	  parent::before_save($data);
	}
	
	/**
	 * 保存之后，更新相关count
	 */
    protected function after_save() {

      // 如果是新的记录
      if($this->insert_mode) {
        $category_id = $this->data['category_id'];
        $fid = $this->data['fid'];
  
        $category_model = new Sher_Core_Model_Category();
        if (!empty($category_id)) {
            $category_model->inc_counter('total_count', 1, $category_id);
        }
        if (!empty($fid)) {
            $category_model->inc_counter('total_count', 1, $fid);
        }

      }

    }
	
	/**
	 * 扩展Model数据
	 */
	protected function extra_extend_model_row(&$row) {
		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';

    if(isset($row['category_tags']) && !empty($row['category_tags'])){
 		  $row['category_tags_s'] = implode(',',$row['category_tags']);   
    }

		if(!isset($row['short_title']) || empty($row['short_title'])){
			$row['short_title'] = $row['title'];
		}
        
		if(isset($row['description'])){
			// 转码
			$row['description'] = htmlspecialchars_decode($row['description']);
		
			// 去除 html/php标签
			$row['strip_description'] = Doggy_Dt_Filters_String::truncate(strip_tags($row['description']), 140);
		}
		// 获取封面图
		$row['cover'] = $this->cover($row);
        
    $row['created_at'] = Doggy_Dt_Filters_DateTime::relative_datetime($row['created_on']);

    // 来自
    switch($row['attrbute']){
      case 1:
        $row['attrbute_str'] = '官网';
        break;
      case 2:
        $row['attrbute_str'] = '淘宝';
        break;
      case 3:
        $row['attrbute_str'] = '天猫';
        break;
      case 4:
        $row['attrbute_str'] = '京东';
        break;
      default:
        $row['attrbute_str'] = '--';
    }

	}
	
	/**
	 * 获取封面图
	 */
	protected function cover(&$row){
		// 已设置封面图
		if(!empty($row['cover_id'])){
			$asset_model = new Sher_Core_Model_Asset();
			return $asset_model->extend_load($row['cover_id']);
		}
		// 未设置封面图，获取第一个
		$asset = new Sher_Core_Model_Asset();
		$query = array(
			'parent_id'  => (int)$row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_GPRODUCT,
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
	}
	
    /**
     * 标记主题为编辑推荐
     */
    public function mark_as_stick($id, $value=1) {
        $ok = $this->update_set($id, array('stick' => $value));
        if($ok){
            $data = $this->load($id);
        }
        return $ok;
    }
	
    /**
     * 取消主题编辑推荐
     */
	public function mark_cancel_stick($id){
		    $ok = $this->update_set($id, array('stick' => 0));
        if($ok){
            $data = $this->load($id);
        }
	}
	
    /**
     * 标记主题 精华
     */
	public function mark_as_fine($id){
		$ok = $this->update_set($id, array('fine' => 1));
        if($ok){
            $data = $this->load($id);
        }
        return $ok;
	}
	
    /**
     * 标记主题 取消精华
     */
	public function mark_cancel_fine($id){
		$ok = $this->update_set($id, array('fine' => 0));
        if($ok){
            $data = $this->load($id);
        }
        return $ok;
	}

	
	/**
	 * 更新类别回复数
	 */
	public function update_category_reply_count($id){
		$row = $this->find_by_id((int)$id);
		if (!empty($row)) {
			$category = new Sher_Core_Model_Category();
			$category->inc_counter('reply_count', 1, $row['category_id']);
			$category->inc_counter('reply_count', 1, $row['fid']);
			unset($category);
		}
	}


	/**
	 * 增加计数
	 */
	public function inc_counter($field_name, $inc=1, $id=null){
		if(is_null($id)){
			$id = $this->id;
		}
		if(empty($id) || !in_array($field_name, $this->counter_fields)){
			return false;
		}
		
		return $this->inc($id, $field_name, $inc);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($field_name,$id=null,$force=false,$count=1){
	    if(is_null($id)){
	        $id = $this->id;
	    }
	    if(empty($id)){
	        return false;
	    }
		if(!$force){
			$product = $this->find_by_id((int)$id);
			if(!isset($product[$field_name]) || $product[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id' => $id, 'asset_type'=>array('$in'=>array(97,99,120,121))));
		unset($asset);
		
		// 删除Comment
		$comment = new Sher_Core_Model_Comment();
		$comment->remove(array('target_id' => $id, 'type'=>Sher_Core_Model_Comment::TYPE_GPRODUCT));
		unset($comment);
		
		return true;
	}
	
	/**
	 * 更新编辑器上传附件
	 */
	public function update_editor_asset($id, $file_id){
		$criteria = array('file_id'=>$file_id);
		
		$asset = new Sher_Core_Model_Asset();
		$ok = $asset->update_set($criteria, array('parent_id' => (int)$id), false, true, true);
		
		// 重新附件数量计算
		$asset_count = $asset->count(array(
			'parent_id' => (int)$id,
			'asset_type' => Sher_Core_Model_Asset::TYPE_GPRODUCT_EDITOR,
		));
		
		Doggy_Log_Helper::debug("Query asset count[$asset_count].");
		
		unset($asset);
	}

	
}

