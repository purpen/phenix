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
    'content' => null,
    //备注
    'remark'  => null,
    'user_id' => 0,  
		'state' => 1,
  	);

  protected $required_fields = array('mark', 'title');

  protected $int_fields = array('state', 'user_id');


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
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id' => $id));
		unset($asset);
		
		return true;
	}
	
}

