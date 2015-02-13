<?php
/**
 * 手机设备 API 接口
 * @author purpen
 */
class Sher_Api_Action_Device extends Sher_Api_Action_Base implements Sher_Core_Action_Funnel {
  /**
	public $stash = array(
		'page'=>1,
  );
   */

	/**
	 * 入口
	 */
	public function execute() {
		
	}
	
	public function add_device(){
		$model = new Sher_Core_Model_Device();
		$dev = $this->stash['d'];
		$arr = array('d_id'=>$dev, 'user_id'=>(int)$this->stash['user_id']);
		$model->create($arr);
		
	}
	public function rm_device(){
		$model = new Sher_Core_Model_Device();
		$dev = $this->stash['d'];
		$arr = array('d_id'=>$dev, 'user_id'=>(int)$this->stash['user_id']);
		$model->remove($arr);
	}
	public function up_device(){
		$model = new Sher_Core_Model_Device();
		$dev = $this->stash['d'];
		$arr = array('d_id'=>$dev);
		$up_dev = array('$set'=>array('user_id'=>(int)$this->stash['user_id']));
		$model->update($arr,$up_dev);
	}
	public function fd_device(){
		$model = new Sher_Core_Model_Device();
		$dev = $this->stash['d'];
		$arr = array('d_id'=>$dev);
		$model->find($arr);
		var_dump($model);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
