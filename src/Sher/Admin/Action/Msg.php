<?php
/**
 * 短信管理
 * @author tianshuai
 */
class Sher_Admin_Action_Msg extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_msg');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->send_msg();
	}
	
	/**
	 * 创建/更新
	 */
	public function send_msg(){
		$this->set_target_css_state('all');
		return $this->to_html_page('admin/msg/submit.html');
	}

  /**
   * 发送执行
   */
  public function do_send_msg (){
    $phones = $this->stash['phones'];
    $content = $this->stash['content'];
    if(empty($phones) || empty($content)){
      return $this->ajax_json('缺少请求参数!', true);
    }

    if(strlen($content)>500){
      return $this->ajax_json('内容已超出500字符!', true);   
    }

    $phones_format= preg_replace("/(\n)|(\s)|(\t)|(;)|(；)|(，)|( )/" ,',' ,$phones); 
    $phone_arr = explode(",", $phones_format);
    if(empty($phone_arr)){
      return $this->ajax_json('手机号为空!', true);   
    }

    $max = count($phone_arr);
    if($max>50){
      return $this->ajax_json('手机号不超过50个!', true);      
    }
    $success_total = 0;
    for($i=0;$i<$max;$i++){
      $phone = trim($phone_arr[$i]);
      $is_mobile = Sher_Core_Helper_Util::is_mobile($phone);
      if($is_mobile){
        Sher_Core_Helper_Util::send_defined_mms($phone, $content);
        $success_total++;
      }
    }
    return $this->ajax_json("已成功发送 $success_total 条短信!", false, 0);
  }

}

