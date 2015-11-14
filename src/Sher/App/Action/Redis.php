<?php
/**
 * Redis缓存操作
 */
class Sher_App_Action_Redis extends Sher_App_Action_Base {
	public $stash = array(
    'key' => null,
	);
	protected $exclude_method_list = array('incr');

	/**
	 * 入口
	 */
	public function execute(){
	}

 	/**
    * 自增
  */
  public function incr(){
    if(empty($this->stash['key'])){
      return false;
    }
    $key = $this->stash['key'];
    $redis = new Sher_Core_Cache_Redis();
    $redis->incr($key);
  }
	
}

