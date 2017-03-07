<?php
/**
 * 情境
 * @author tianshuai
 */
class Sher_Wap_Action_Sight extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'id'   => '',
		'page' => 1,
        'size' => 8,

	);
	
	protected $exclude_method_list = array('execute', 'getlist', 'view');

	/**
	 * 情境专题入口
	 */
	public function execute(){
		return $this->getlist();
	}

    /**
      *详情
    */
    public function view(){
        $this->set_target_css_state('page_choice');
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        $redirect_url = sprintf("%s/shop", Doggy_Config::$vars['app.url.wap']);
        if(empty($id)){
          return $this->show_message_page('访问的情境不存在！', $redirect_url);
        }
        $user_id = $this->visitor->id;

		$model = new Sher_Core_Model_SceneSight();
		$sight = $model->extend_load($id);

		if(empty($sight)) {
            return $this->show_message_page('访问的情境不存在！', $redirect_url);
		}
		if($sight['deleted']==1){
            return $this->show_message_page('访问的情境已删除！', $redirect_url);
		}

		if($sight['is_check']==0){
            return $this->show_message_page('访问的情境未发布！', $redirect_url);
		}

        $this->stash['sight'] = $sight;


        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;

  	    return $this->to_html_page('wap/sight/view.html');
    }


	
}
