<?php
/**
 * 推荐图片管理
 * @author purpen
 */
class Sher_Core_Model_Advertise extends Sher_Core_Model_Base  {

    protected $collection = "advertise";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;
    
	const STATE_DRAFT = 1;
	const STATE_PUBLISHED = 2;
	
	# 类型
	const TYPE_URL  = 1;
	const TYPE_ID   = 2;
	const TYPE_WORD = 3;
	
    protected $schema = array(
        'space_id' => 0,
		
		'title' => '',
		'sub_title' => '',
        'web_url' => '',
		'summary' => '',
		
		# 类型
		'type' => self::TYPE_URL,
		
		# 附件图片
		'cover_id' => '',
		
		# 浏览数量
		'view_count' => 0,
		# 点击数量
		'click_count' => 0,
		
		# 排序
		'ordby' => 0,
		
		# 是否发布、草稿
		'state' => self::STATE_DRAFT,
    );
	
    protected $required_fields = array('title', 'web_url');
	
    protected $int_fields = array('space_id', 'view_count', 'click_count', 'ordby', 'state');
    
	
	protected $joins = array(
	    'space'  => array('space_id'  => 'Sher_Core_Model_Space'),
		'cover'  => array('cover_id' => 'Sher_Core_Model_Asset'),
	);
	
    protected function extra_extend_model_row(&$row) {
    	$row['view_url'] = Sher_Core_Helper_Url::ad_view_url($row['_id']);
		$row['mm_view_url'] = sprintf(Doggy_Config::$vars['app.url.wap'].'/tracker?kid=%d', $row['_id']);
    }
	
	/**
	 * 添加自定义ID
	 */
    protected function before_insert(&$data) {
        $data['_id'] = $this->gen_adv_id();
		
		parent::before_insert($data);
    }
	
	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => (int)$parent_id));
			}
		}
	}
	
	/**
	 * 更新发布上线
	 */
	public function mark_as_publish($id, $published=2) {
		return $this->update_set($id, array('state' => $published));
	}
	
	/**
	 * 生成id编号, 9位数字符
	 */
	protected function gen_adv_id($prefix='1'){
		$name = Doggy_Config::$vars['app.serialno.name'];
		
		$kid = $prefix;
		$val = $this->next_seq_id($name);
		
		$len = strlen((string)$val);
		if ($len <= 3) {
			$kid .= date('md');
			$kid .= sprintf("%03d", $val);
		}else{
			$kid .= substr(date('md'), 0, 8 - $len);
			$kid .= $val; 
		}
		
		return (int)$kid;
	}
	
}
?>