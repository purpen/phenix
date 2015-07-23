<?php
/**
 * captcha验证码
 */
class Sher_App_Action_Captcha extends Sher_App_Action_Base {

	protected $exclude_method_list = array('execute', 'view', 'check', 'qr_code');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->view();
	}

 	/**
   	 * captcha show
     */
    public function view(){
    	session_start();
    	$captcha = new Sher_Core_Util_Captcha();
    	return $captcha->create(4, array('width'=>20, 'height'=>30));
  	}

  	/**
     * 检察验证码
     */
    public function check(){
    	session_start();
    	$code = $this->stash['code'];
    	$type = isset($this->stash['type'])? (int)$this->stash['type'] : 1;
    	$captcha = new Sher_Core_Util_Captcha();
    	$is_true =  $captcha->check($code, $type);
    	if($is_true){
      	  	return $this->to_raw('1');
    	}else{
			return $this->to_raw('0');
    	}
  	}

/**
 * qr code
 */
  public function qr_code(){
    Header("Content-type: image/PNG");
    $url = $this->stash['url'];
    $qr_model = new Sher_Core_Util_QR($url);
    $qr = $qr_model->image(4);
    return $qr;
  }

}

