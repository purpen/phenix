<?php
/**
 * 供应商
 * @author tianshuai
 */
class Sher_Core_Model_Supplier extends Sher_Core_Model_Base {
    
    protected $collection = "supplier";
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
    
    protected $schema = array(
        'title'           => '',
        'short_title'   => '',
        'summary'        => '',
        'type'           => 1,
        # 产品数量
        'product_count'  => 0,
        'user_id'        => 0,
        # 计数
        'view_count'     => 0,
        
        # 推荐
        'stick'        => 0,
        'stick_on'      => 0,
        # 是否删除
        'deleted'        => 0,
        # 是否审核
        'status'       => 1,

    );
    
    protected $required_fields = array('user_id','title');
    protected $int_fields = array('user_id','deleted','status','stick','product_count','view_count','type');
    
    protected $joins = array(

    );
    
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {        

	}
    
	// 添加自定义ID
    protected function before_insert(&$data) {
		
		parent::before_insert($data);
    }
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
		
	    parent::before_save($data);
	}

    
    /**
     * 通过审核
     */
	public function mark_as_stick($id){
		return $this->update_set((int)$id, array('stick' =>1, 'stick_on'=>time()));
	}
	
    /**
     * 取消审核
     */
	public function mark_cancel_stick($id){
		return $this->update_set((int)$id, array('stick' => 0));
	}
    
    /**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		return true;
	}
    
}
