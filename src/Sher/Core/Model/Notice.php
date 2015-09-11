<?php
/**
 * 通知
 * @author tianshuai
 */
class Sher_Core_Model_Notice extends Sher_Core_Model_Base  {
	protected $collection = "notice";

  ##类型
  const KIND_NOTICE = 1;
  const KIND_OTHER = 2;

  ##状态
  const STATE_NO = 0;
  const STATE_BEGIN = 1;
  const STATE_ING = 1;
  const STATE_FINISH = 3;
  const STATE_FAIL = 4;

	protected $schema = array(
    'title' => null,
    'content' => null,
    //备注
    'remark'  => null,
    'user_id' => 0,
    's_user_id' => 0,
    // 发布
    'published' => 0,
    'kind' => self::KIND_NOTICE,
		'state' => self::STATE_NO,
  	);

  protected $required_fields = array('title');

  protected $int_fields = array('state', 'user_id', 'kind', 's_user_id', 'published');


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

