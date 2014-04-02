<?php
/**
 * 产品Model
 * @author purpen
 */
class Sher_Core_Model_Product extends Sher_Core_Model_Base {

    protected $collection = "product";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 产品周期stage
    const STAGE_VOTE     = 1;
    const STAGE_PRESALE  = 2;
    const STAGE_SHOP     = 3;

    protected $schema = array(
		'sku'     => null,
	    'title'   => '',
		# 简述
        'summary' => '',
		# 详情内容
		'content' => '',
    	'tags'    => array(),
		
		## 价格
		
		# 成本价
		'cost_price'   => 0,
		# 市场价
		'market_price' => 0,
		# 销售价
		'sale_price'   => 0,
		# 预售价
		'hot_price'    => 0,
		
		# 商品型号信息
		'attributes' => array(
			'width'  => 0,
			'height' => 0,
			'weight' => 0,
			'color'  => 0,
		),
		
		# 其他扩展信息
		'meta' => array(
			# 商品单位
			'unit' => null,
		),
		
		# 封面图
 		'cover_id' => '',
		'asset' => array(),
		# 附件图片数
		'asset_count' => 0,
		
		# 类别支持多选
		'category_id' => 0,
		
		# 上传者
	    'user_id' => null,
		
		# 时间点
		'timer' => array(
			'voted_start_time'    => null,
			'voted_finish_time'   => null,
			'presale_start_time'  => null,
			'presale_finish_time' => null,
		),
		
		## 计数器
		
		# 浏览数
    	'view_count'=>0,
		# 收藏数
        'favorite_count' => 0, 
		# 喜欢数
        'love_count' => 0,
		# 回应数 
    	'comment_count' => 0,
		# 赞成数
		'vote_favor_count' => 0,
		# 反对数
		'vote_oppose_count' => 0,
		# 销售数
		'sale_count' => 0,
		
		# 产品周期 (投票、预售、销售)
		'stage' => self::STAGE_VOTE,
		
		# 状态
		'state' => 0,
		
    	# 删除标识
    	'deleted' => 0,
		# 是否发布
    	'published' => 1,
		
		# 随机数
		'random' => 0,
    );

	protected $required_fields = array('user_id','title');
	protected $int_fields = array('user_id','category_id','state','published','deleted');
	
	protected $counter_fields = array('asset_count', 'view_count', 'favorite_count', 'love_count', 'comment_count','vote_favor_count','vote_oppose_count');
	
	protected $joins = array(
	    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
	    'cover' => array('cover_id' => 'Sher_Core_Model_Asset')
	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		$row['view_url'] = $this->gen_view_url($row);
		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';
		$row['vote_count'] = $row['vote_favor_count'] + $row['vote_oppose_count'];
		
	}
	
	/**
	 * 获取产品不同阶段的URL
	 */
	protected function gen_view_url($row){
		$stage = isset($row['stage']) ? $row['stage'] : 0;
		switch($stage) {
			case self::STAGE_VOTE:
				$view_url = Sher_Core_Helper_Url::vote_view_url($row['_id']);
				break;
			case self::STAGE_PRESALE:
				$view_url = Sher_Core_Helper_Url::sale_view_url($row['_id']);
				break;
			case self::STAGE_SHOP:
				$view_url = Sher_Core_Helper_Url::shop_view_url($row['_id']);
				break;
			default:
				$view_url = Sher_Core_Helper_Url::vote_view_url($row['_id']);
				break;
		}
		
		return $view_url;
	}
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，\s]+/u',$data['tags'])));
	    }
		// 新建数据,补全默认值
		if ($this->is_saved()){
			$data['sku'] = $this->gen_product_sku();
			
			// 添加随机数
			$data['random'] = Sher_Core_Helper_Util::gen_random();
		}
		
	    parent::before_save($data);
	}
	
	/**
	 * 设置封面图
	 */
	public function mark_set_cover($id, $cover_id){
		return $this->update_set($id, array('cover_id'=>$cover_id));
	}
	
	
	
	/**
	 * 生成产品的SKU
	 */
	protected function gen_product_sku($prefix='91'){
		$sku  = $prefix;
		$sku .= substr(time(), 4);
		$sku .= $this->rand_number_str(2);
		
		return (int)$sku;
	}
	
	/**
	 * 产生一个特定长度的数字字符串
	 */
	protected function rand_number_str($len=2, $chars='0123456789'){
        $string = '';
        for($i=0; $i<$len; $i++){
            $pos = rand(0, strlen($chars)-1);
            $string .= $chars{$pos};
        }
        return $string;
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
	public function dec_counter($field_name,$id=null,$force=false){
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
		
		return $this->dec($id, $field_name);
	}
	
	
}
?>