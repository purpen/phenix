<?php
/**
 * 达人认证
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Model_UserTalent extends Sher_Core_Model_Base  {
	
    protected $collection = "user_talent";

    ## 常量
    # 是否审核
    const VERIFIED_NO = 0;  # 未审核
    const VERIFIED_REJECT = 1;  # 拒绝
    const VERIFIED_PASS = 2;  # 通过
	
	protected $schema = array(
        'user_id' => 0,
        'info' => '',
        'contact' => '',
        // 身份认证
        'label' => null,
        'id_card_cover_id' => '',
        'business_card_cover_id' => '',
        # 已审核的
        'verified' => self::VERIFIED_NO,
        'status' => 0,
    );

    protected $required_fields = array();
  
    protected $int_fields = array();

	protected $joins = array(
        'user'  => array('user_id'  => 'Sher_Core_Model_User'),
        'id_card_cover' =>  array('id_card_cover_id' => 'Sher_Core_Model_Asset'),
        'business_card_cover' =>  array('business_card_cover_id' => 'Sher_Core_Model_Asset'),
	);

	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {

		return true;
	}

    /**
     * 保存前事件
     */
    protected function before_save(&$data) {
  
    }

    /**
     * 保存后事件
     */
    protected function after_save(){
    
    }
    
    /**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($id, $parent_id){
		if (!empty($id)){
			$model = new Sher_Core_Model_Asset();
			Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
			$model->update_set($id, array('parent_id' => $parent_id));
		}
	}
}
