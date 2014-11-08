<?php
/**
 * API模块基类
 * @author purpen
 */
class Sher_Api_Action_Base extends Sher_Core_Action_Authorize implements DoggyX_Action_Initialize {
	
	/**
	 * 客户端key
	 */
	public $client_id;
	/**
	 * 唯一设备码
	 */
	public $uuid;
	/**
	 * 签名
	 */
	public $sign;
	
	/**
	 * 参与签名的key
	 */
	public $resparams = array('client_id','uuid','time');
	
	/**
	 * 初始化验证
	 */
	public function _init() {
        $this->client_id = isset($this->stash['client_id'])?$this->stash['client_id']:0;
		$this->uuid = isset($this->stash['uuid'])?$this->stash['uuid']:0;
		$this->sign = isset($this->stash['sign'])?$this->stash['sign']:'';
    }
	
}
?>