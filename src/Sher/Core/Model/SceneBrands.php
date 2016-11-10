<?php
/**
 * 品牌 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneBrands extends Sher_Core_Model_Base {

    protected $collection = "scene_brands";

    ## 常量
    # 类型
    const KIND_FIU = 1; // 所有
    const KIND_STORE = 2;   // 商城展示
	
    protected $schema = array(
		# 标题
		'title' => '',
        # 创建人
        'user_id' => 0,
        # 描述
        'des' => '',
        # 亮点
        'feature' => '',
		# 头像
		'cover_id' => '',
        # Banner
        'banner_id' => '',
        # 产品封面图
        'product_cover_id' => '',
        # 类型
        'kind' => self::KIND_FIU,
        # 是否是自营品牌
        'self_run' => 0,
        # 标签
        'tags' => array(),
        # 首字母索引
        'mark' => '',
        # 来源: 1.官方；2.用户
        'from_to' => 1,
        # 使用次数
        'used_count' => 0,
        # 产品数量
        'item_count' => 0,
		# 推荐（编辑推荐、推荐至首页）
		'stick' => 0,
        # 推荐时间
        'stick_on' => 0,
        # 是否启用
		'status' => 1,
		## 计数器
		
		# 浏览数
    	'view_count' => 0,
		# 收藏数
        'favorite_count' => 0, 
		# 喜欢数
        'love_count' => 0,
		# 回应数 
    	'comment_count' => 0,

    # 真实浏览数
      'true_view_count' => 0,

    # web 浏览数
      'web_view_count' => 0,
    # wap 浏览数 
      'wap_view_count' => 0,
    # app 浏览数
      'app_view_count' => 0,
    );
	
	protected $required_fields = array('title');
	protected $int_fields = array('status', 'user_id', 'used_count', 'item_count', 'kind', 'self_run', 'from_to', 'stick', 'stick_on', 'view_count', 'favorite_count', 'love_count', 'comment_count', 'true_view_count', 'web_view_count', 'wap_view_count', 'app_view_count');
	protected $float_fields = array();
	protected $counter_fields = array('used_count', 'item_count', 'view_count', 'favorite_count', 'love_count', 'comment_count', 'true_view_count', 'web_view_count', 'wap_view_count', 'app_view_count');
	protected $retrieve_fields = array();
    
	protected $joins = array(

	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        // 获取封面图
		if(isset($row['cover_id'])){
			$row['cover'] = $this->cover($row);
		}
        // 获取Banner图
		if(isset($row['banner_id'])){
			$row['banner'] = $this->banner($row);
		}
        // 获取Banner图
		if(isset($row['product_cover_id'])){
			$row['product_cover'] = $this->product_cover($row);
		}

        $row['tags_s'] = '';
        if(isset($row['tags']) && !empty($row['tags'])){
		    $row['tags_s'] = !empty($row['tags']) ? implode(',', $row['tags']) : '';
        }
	}
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    parent::before_save($data);

	    if (!empty($data['title']) && empty($data['mark'])) {
	        $data['mark'] = Sher_Core_Helper_Pinyin::str2py($data['title']);
	    }

	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['tags'])));
	    }

	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){
        parent::after_save();
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
			$brand = $this->find_by_id((int)$id);
			if(!isset($brand[$field_name]) || $brand[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
	}
	
	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => $parent_id));
			}
		}
	}
	
	/**
	 * 获取封面图
	 */
	protected function cover(&$row){
		// 已设置封面图
		if(!empty($row['cover_id'])){
			$asset = new Sher_Core_Model_Asset();
			return $asset->extend_load($row['cover_id']);
		}
		// 未设置封面图，获取第一个
		$asset = new Sher_Core_Model_Asset();
		$query = array(
			'parent_id'  => $row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_SCENE_BRANDS
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
	}

	/**
	 * 获取Banner图
	 */
	protected function banner(&$row){
		// 已设置封面图
		if(!empty($row['banner_id'])){
			$asset = new Sher_Core_Model_Asset();
			return $asset->extend_load($row['banner_id']);
		}
		// 未设置封面图，获取第一个
		$asset = new Sher_Core_Model_Asset();
		$query = array(
			'parent_id'  => $row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_SCENE_BRANDS_BANNER
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
	}

	/**
	 * 获取产品封面图
	 */
	protected function product_cover(&$row){
		// 已设置封面图
		if(!empty($row['product_cover_id'])){
			$asset = new Sher_Core_Model_Asset();
			return $asset->extend_load($row['product_cover_id']);
		}
		// 未设置产品封面图，获取第一个
		$asset = new Sher_Core_Model_Asset();
		$query = array(
			'parent_id'  => $row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_SCENE_BRANDS_PRODUCT
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
        // 删除索引
        Sher_Core_Util_XunSearch::del_ids('scene_brand_'.(string)$id);
		
		return true;
	}

	/**
	 * 检测标题是否重复
	 */
	public function check_title($title, $type=1) {
		if(empty($title)){
			return false;
		}
		$row = $this->first(array('title' => (string)trim($title)));
		if(!empty($row)){
			return false;
		}
		return true;
	}

    /**
     * 标记为推荐
     */
    public function mark_as_stick($id) {
        return $this->update_set((string)$id, array('stick' => 1, 'stick_on'=>time()));
    }
	
    /**
     * 取消推荐
     */
	public function mark_cancel_stick($id) {
		return $this->update_set((string)$id, array('stick' => 0));
	}


}
