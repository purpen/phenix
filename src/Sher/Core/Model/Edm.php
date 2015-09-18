<?php
/**
 * Edm/通知群发管理
 * @author purpen
 */
class Sher_Core_Model_Edm extends Sher_Core_Model_Base {    
    protected $collection = "edm";
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 状态
	const STATE_DRAFT = 0;
	const STATE_WAITING = 1;
	const STATE_SENDING = 2;
	const STATE_FINISHED = 3;
	const STATE_FAILED = 4;

  # 类型
  const KIND_EDM = 1;
  const KIND_NOTICE = 2;
	
    protected $schema = array(		
		'title' => '',
		'tags' => array(),
		'summary' => '',
		# 邮件内容
		'mailbody' => '',
    # 用户ID
    'user_id' => 0,
		
		'weekly_number' => 0,
		'weekly_total' => 0,
		
		# 测试用户地址
		'test_user' => '',

    # 类型：1.edm; 2.私信
    'kind' => self::KIND_EDM,
		
		'state' => self::STATE_DRAFT,
    );
	
    protected $int_fields = array('weekly_number', 'weekly_total', 'state', 'kind');

	protected $required_fields = array('title', 'summary', 'mailbody');
	
	protected function extra_extend_model_row(&$row) {		
		$row['state_label'] = $this->get_status_label($row['state']);
		if(isset($row['mailbody'])){
			// 转码
			$row['mailbody'] = htmlspecialchars_decode($row['mailbody']);
		}
	}
	
	/**
	 * 状态标签
	 */
	protected function get_status_label($status){
		switch($status){
			case self::STATE_DRAFT:
				$status_label = '草稿箱';
				break;
			case self::STATE_WAITING:
				$status_label = '等待发送';
				break;
			case self::STATE_SENDING:
				$status_label = '正在发送';
				break;
			case self::STATE_FAILED:
				$status_label = '发送失败';
				break;
			case self::STATE_FINISHED:
				$status_label = '发送完成';
				break;
		}
		return $status_label;
	}
	
	/**
	 * 设置等待发送
	 */
	public function mark_set_wait($id){
		return $this->update_set($id, array('state'=>self::STATE_WAITING));
	}
	
	/**
	 * 设置发送状态
	 */
	public function mark_set_send($id){
		return $this->update_set($id, array('state'=>self::STATE_SENDING));
	}
	
	/**
	 * 设置完成状态
	 */
	public function mark_set_finish($id){
		return $this->update_set($id, array('state'=>self::STATE_FINISHED));
	}
	
}

