<?php
/**
 * Edm管理
 * @author purpen
 */
class Sher_Core_Model_Edm extends Sher_Core_Model_Base {    
    protected $collection = "edm";
	
	# 状态
	const STATE_DRAFT = 0;
	const STATE_WAITING = 1;
	const STATE_SENDING = 2;
	const STATE_FINISHED = 3;
	const STATE_FAILED = 4;
	
    protected $schema = array(		
		'title' => '',
		'tags' => array(),
		'summary' => '',
		# 邮件内容
		'mailbody' => '',
		
		'weekly_number' => 0,
		'weekly_total' => 0,
		
		'state' => self::STATE_DRAFT,
    );
	
    protected $int_fields = array('weekly_number', 'weekly_total', 'state');

	protected $required_fields = array('title', 'summary', 'mailbody');
	
	protected function extra_extend_model_row(&$row) {		
		$row['state_label'] = $this->get_status_label($row['state']);
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
	 * 设置发送状态
	 */
	public function mark_set_send($id){
		return $this->update_set($id, array('state'=>self::STATE_SENDING));
	}
	
}
?>