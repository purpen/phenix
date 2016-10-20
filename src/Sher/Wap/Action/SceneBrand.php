<?php
/**
 * 品牌
 * @author tianshuai
 */
class Sher_Wap_Action_SceneBrand extends Sher_Wap_Action_Base {
	
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
    	$this->set_target_css_state('page_find');
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $redirect_url = Doggy_Config::$vars['app.url.wap'];
        if(empty($id)){
          return $this->show_message_page('访问的品牌不存在！', $redirect_url);
        }
        $user_id = $this->visitor->id;

		$model = new Sher_Core_Model_SceneBrands();
		$scene_brand = $model->extend_load($id);

        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;


		if(empty($scene_brand)) {
            return $this->show_message_page('访问的品牌不存在或已删除！', $redirect_url);
		}

		if($scene_brand['status']==0){
            return $this->show_message_page('访问的品牌已禁用！', $redirect_url);
		}

        $this->stash['scene_brand'] = $scene_brand;

  	    return $this->to_html_page('wap/scene_brand/view.html');
    }

	
}
