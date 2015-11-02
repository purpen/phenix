<?php
/**
 * API模块基类
 * @author purpen
 */
class Sher_Api_Action_Base extends Sher_Core_Action_Base implements DoggyX_Action_Initialize {
	
	/**
	 * 客户端key
	 */
	public $client_id;
	/**
	 * 唯一设备码
	 */
	public $uuid;
	
	/**
	 * 渠道ID
	 */
	public $channel;
	/**
	 * 签名
	 */
	public $sign;

  	/**
   	 * 用户ID
     */
    public $current_user_id;

    /**
     * 不验证用户ＩＤ的action
     */
    public $no_check_user_ids = array('login', 'register', 'verify_code', 'find_pwd');

    /**
     * 当前方法名
     */
    public $current_method_name = '';
	
	/**
	 * 参与签名的key
	 */
	public $resparams = array('client_id','uuid','channel','time');
	
    /**
     * 忽略不需要传的当前用户ID(current_user_id)
     */
    protected $ignore_check_method_list = array();

	/**
	 * 初始化验证
	 */
	public function _init() {
        $this->client_id = isset($this->stash['client_id'])?$this->stash['client_id']:0;
		$this->uuid = isset($this->stash['uuid'])?$this->stash['uuid']:0;
		$this->channel = isset($this->stash['channel'])?$this->stash['channel']:'';
		$this->sign = isset($this->stash['sign'])?$this->stash['sign']:'';
        $this->current_user_id = isset($this->stash['current_user_id'])?(int)$this->stash['current_user_id']:0;
        // 当前方法名
        $this->current_method_name = substr($_SERVER['PHP_SELF'], strrpos($_SERVER['PHP_SELF'], '/') + 1);
    }
    
    /**
	 * 检察用户ID参数
	 */
    public function check_current_user(){
        $method = substr($_SERVER['PHP_SELF'], strrpos($_SERVER['PHP_SELF'], '/') + 1);
        if(in_array($method, $this->ignore_check_method_list)){
            if($this->current_user_id == 0){
                return $this->api_json('a用户ID不存在！', 3000);
            }
        } 
    }
	
	/**
	 * 重建结果集
	 */
	public function rebuild_result($result, $some_fields){}
	
}
?>
