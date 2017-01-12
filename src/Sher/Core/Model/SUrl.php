<?php
/**
 * 短网址
 * @author tianshuai
 */
class Sher_Core_Model_SUrl extends Sher_Core_Model_Base  {
	protected $collection = "s_url";
	
	protected $schema = array(
        # 短网址code
        'mark' => null,
        # 原网址
        'url' => null,
        # 类型: 1.通用；
        'type' => 1,
        # 点击数
        'view_count' => 0,
  	);

  protected $required_fields = array('mark', 'url');

  protected $int_fields = array('type', 'view_count');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

	}


	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}
	
}

