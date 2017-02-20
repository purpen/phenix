<?php
/**
 * Wap-地盘
 * @author tianshuai
 */
class Sher_Wap_Action_Storage extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
	);

	
	/**
	 * 商城入口
	 */
	public function execute(){
		//return $this->home();
	}
	
	/**
	 * 首页
	 */
	public function view(){

        $id = ($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$redirect_url = Doggy_Config::$vars['app.url.wap']. "/shop";
        if(empty($id)){
			return $this->show_message_page('缺少请求参数！', $redirect_url);
        }

        $scene_model = new Sher_Core_Model_SceneScene();
        $scene = $scene_model->extend_load($id);

        if(empty($scene)){
			return $this->show_message_page('地盘不存在！', $redirect_url);
        }

		if ($scene['deleted']==1) {
            return $this->show_message_page('地盘不存在或已删除!', $redirect_url);
        }

		if ($scene['is_check']==0) {
            return $this->show_message_page('地盘未通过审核!', $redirect_url);
        }

		$this->stash['page_title_suffix'] = $scene['title'];

			//微信分享
	    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
	    $timestamp = $this->stash['timestamp'] = time();
	    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
	    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
	    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
	    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
	    $this->stash['wxSha1'] = sha1($wxOri);


		return $this->to_html_page('wap/storage/view.html');
	}

}
