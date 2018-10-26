<?php
/**
 * 铟立方未来商店服务号接口
 * @author tianshuai 
 */
class Sher_WApi_Action_D3inService extends Sher_WApi_Action_Base implements DoggyX_Action_Initialize {

	protected $filter_auth_methods = array('execute');
		
	/**
	 * 初始化参数
	 */
	public function _init() {

	}
		
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->payment();
	}

  /**
   * Fiu 
   */
  public function payment(){
    Doggy_Log_Helper::warn("获取参数信息: ".json_encode($this->stash));
    $signature = $this->stash['signature'];
    $echostr = $this->stash['echostr'];
    $timestamp = $this->stash['timestamp'];
    $nonce = $this->stash['nonce'];

    //$check_result = Sher_Core_Util_WxPub::getSHA1();


    echo "test";
    return "ok";
  }

}

