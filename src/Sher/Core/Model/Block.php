<?php
/**
 * 区块
 * @author purpen
 */
class Sher_Core_Model_Block extends Sher_Core_Model_Base  {
	protected $collection = "block";
	
	protected $schema = array(
    'mark' => null,
    'title' => null,
    'code' => null,
    'content' => null,
    //备注
    'remark'  => null,
    'user_id' => 0,
    'kind' => 0,
    'pack' => null,
		'state' => 1,
  	);

  protected $required_fields = array('mark', 'title');

  protected $int_fields = array('state', 'user_id', 'kind');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		// HTML 实体转换为字符
		if (isset($row['content'])){
			$row['content'] = htmlspecialchars_decode($row['content']);
		}
		// 去除 html/php标签
    if(isset($row['remark'])){
		  $row['strip_remark'] = strip_tags(htmlspecialchars_decode($row['remark']));
    }
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}
	
}

