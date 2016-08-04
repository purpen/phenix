<?php
/**
 * 测试服务
 * @author tianshuai
 */
class Sher_Core_Jobs_Test extends Doggy_Object {
	
	/**
	 * Before perform
	 * Set up environment for the job
	 */
	public function setUp(){}
	
	/**
	 * Run job
	 */
	public function perform(){
		Doggy_Log_Helper::warn("Make test task jobs!");
		$id = $this->args['id'];
        Doggy_Log_Helper::warn('cccc');
		
	}
	
	/**
	 * After perform
	 * Remov environment for this job
	 */
	public function tearDown(){}
	
}

