<?php
/**
 * app专题
 * @author tianshuai
 */
class Sher_Core_Model_Block extends Sher_Core_Model_Base  {
  protected $collection = "special_subject";

  ##常量
  #类型
  KIND_CUSTOM = 1;
  KIND_APPOINT = 2;
	
	protected $schema = array(
    'title' => null,
    'cover_id' => null,
    'banner_id' => null,
    'user_id' => null,
    # 分类ID
    'category_id' => null,
    # 内容
    'content' => null,
    # 简述
    'summary' => null,
    'tags' => array(),
    //备注
    'remark'  => null,
    'user_id' => 0,
    'kind' => self::KIND_CUSTOM,
    'pack' => null,
    'stick' => 0,
    'state' => 1,
    'view_count' => 0,
    'comment_count' => 0,
    'love_count' => 0,
    'favorite_count' => 0,
  );

  protected $required_fields = array('user_id', 'title', 'category_id');

  protected $int_fields = array('state', 'user_id', 'kind', 'stick', 'view_count', 'comment_count', 'love_count', 'favorite_count');

	protected $counter_fields = array('view_count', 'comment_count', 'love_count', 'favorite_count');

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

		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}
	
}

