<?php
/**
 * 品牌 Model
 * @ author caowei@taihuoniao.com
 */
class Sher_Core_Model_SceneBrands extends Sher_Core_Model_Base {

    protected $collection = "scene_brands";

    ## 常量
    # 类型
    const KIND_FIU = 1; // ＦＩＵ
    const KIND_STORE = 2;   // 商城
	
    protected $schema = array(
		# 标题
		'title' => '',
        # 描述
        'des' => '',
		# 封面
		'cover_id' => '',
    # Banner
    'banner_id' => '',
    # 类型
    'kind' => self::KIND_FIU,
        # 点击次数
        'used_count' => 0,
        # 产品数量
        'item_count' => 0,
		# 推荐（编辑推荐、推荐至首页）
		'stick' => 0,
        # 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title','des','cover_id');
	protected $int_fields = array('status', 'used_count', 'item_count', 'kind');
	protected $float_fields = array();
	protected $counter_fields = array('used_count', 'item_count');
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

        // 类型
        $kind = isset($row['kind']) ? $row['kind'] : 0;
        switch($kind){
            case 1:
                $row['kind_label'] = 'Fiu';
                break;
            case 2:
                $row['kind_label'] = 'Store';
                break;
            default:
                $row['kind_label'] = '--';
        }
	}
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    parent::before_save($data);
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
			$albums = $this->find_by_id((int)$id);
			if(!isset($albums[$field_name]) || $albums[$field_name] <= 0){
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
}
