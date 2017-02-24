<?php
/**
 * Wap-地盘
 * @author tianshuai
 */
class Sher_Wap_Action_Storage extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
	);

	protected $exclude_method_list = array('execute', 'plan','custom');

	
	/**
	 * 商城入口
	 */
	public function execute(){
		//return $this->home();
	}

    /**
     * 招募计划
     */
    public function plan(){
        return $this->to_html_page('wap/storage/plan.html');
    }

    /**
     * 轻定制
     */
    public function custom(){
        return $this->to_html_page('wap/storage/custom.html');
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

        // 亮点
        $brights = array();
        if($scene['bright_spot']){
            for($i=0;$i<count($scene['bright_spot']);$i++){
                $arr = explode(':!', $scene['bright_spot'][$i]);
                if(count($arr)<2) continue;
                if($arr[0] == '[text]'){
                    array_push($brights, array('type'=>1, 'content'=>$arr[1]));
                }elseif($arr[0] == '[img]'){
                    array_push($brights, array('type'=>2, 'content'=>$arr[1]));
                }else{
                    continue;
                }
            }
        }
        $scene['brights'] = $brights;
        $scene['bright_count'] = count($brights);

		$this->stash['page_title_suffix'] = $scene['title'];

		//微信分享
        $wx_share = Sher_Core_Helper_Util::wechat_share_param();
        $this->stash['wx_share'] = $wx_share;

        $this->stash['scene'] = $scene;
		return $this->to_html_page('wap/storage/view.html');
	}

}
