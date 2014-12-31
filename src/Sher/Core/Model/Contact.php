<?php
/**
 * 联系表Model
 * @author purpen
 */
class Sher_Core_Model_Contact extends Sher_Core_Model_Base {

  protected $collection = "contact";
	
	#类型kind
    const KIND_PRODUCT    = 1;
	
    protected $schema = array(
		'user_id'     => 0,
		#类型
		'kind' => self::KIND_PRODUCT,
		# 联系人
	  'name'   => null,
		#电话
		'tel' => null,
    #邮箱
    'email' =>  null,

    #分类
    'category_id' => 0,

    #标题
    'title' => null,
    #介绍
		'content' => null,
		#标签
    'tags'    => array(),
		
		# 封面图
 		'cover_id' => '',
		'asset' => array(),
		# 附件图片数
		'asset_count' => 0,
		
		# 状态:0,未处理，1.已处理
		'state' => 0,

    #备注
    'summary' => null,

    );
	
	protected $required_fields = array('user_id','title','name');
	
	protected $int_fields = array('user_id','kind','category_id','state','asset_count');
	
	protected $joins = array(
	    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
	    'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		// HTML 实体转换为字符
		if (isset($row['content'])){
			$row['content'] = htmlspecialchars_decode($row['content']);
		}
		// 去除 html/php标签
		$row['strip_summary'] = strip_tags(htmlspecialchars_decode($row['summary']));
		
	}
	
	// 添加自定义ID
  protected function before_insert(&$data) {

    parent::before_insert($data);
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
	 * 删除某附件
	 */
	public function delete_asset($id, $asset_id){
		// 从附件数组中删除
		$criteria = $this->_build_query($id);
		self::$_db->pull($this->collection, $criteria, 'asset', $asset_id);
		
		$this->dec_counter('asset_count', $id);
		
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->delete_file($asset_id);
		unset($asset);
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id' => $id));
		unset($asset);
		
		return true;
	}
	
}
?>
