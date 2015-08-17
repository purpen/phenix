<?php
/**
 * 举报/投诉
 * @author tianshuai
 */
class Sher_Core_Model_ReportTip extends Sher_Core_Model_Base  {
	protected $collection = "report";

  //投票
  const T_TYPE_VOTE = 1;
  const T_TYPE_STUFF = 2;

  //类型: 1,举报
  const KIND = 1;
	
  protected $schema = array(
    // 举报内容关联ID
    'target_id' => null,
    // 举报类型
    'target_type' => self::T_TYPE_VOTE,
    // 被举报用户ID
    'target_user_id' => 0,
    'title' => null,
    // 描述
    'content' => null,
    //备注
    'remark'  => null,
    // 举报人ID
    'user_id' => 0,
    // 类型:1,举报;
    'kind'  => 1,
    //举报原因:1,侵权
    'evt' => 1,
    // 是否处理
		'status' => 0,
  	);

  protected $required_fields = array('target_id', 'user_id');

  protected $int_fields = array('status', 'user_id', 'kind', 'evt', 'target_type', 'target_user_id');

  protected $joins = array(
    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
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
    if(isset($row['remark'])){
		  $row['strip_remark'] = strip_tags(htmlspecialchars_decode($row['remark']));
    }
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {

	}

  /**
   * 状态操作
   */
  public function mark_as_stat($id, $status=1){
    $data = $this->extend_load((string)$id);

    if(empty($data)){
      return array('status'=>0, 'msg'=>'内容不存在');
    }
    if($data['status']==(int)$status){
      return array('status'=>0, 'msg'=>'重复的操作');  
    }
    $ok = $this->update_set((string)$id, array('status' => $status));
    if($ok){
      return array('status'=>1, 'msg'=>'操作成功');  
    }else{
      return array('status'=>0, 'msg'=>'操作失败');   
    }
  }
	
}

