<?php
/**
 * 孵化器
 * @author tianshuai
 */
class Sher_Wap_Action_Incubator extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
    'page_title_suffix' => '孵化资源-太火鸟-中国最火爆的智能硬件孵化平台',
    'page_keywords_suffix' => '太火鸟,智能硬件,智能硬件孵化平台,孵化资源,设计公司,技术开发,合作院校,创意设计,硬件研发,硬件推广',
    'page_description_suffix' => '中国最火爆的智能硬件孵化平台-太火鸟聚集了上百家智能硬件相关资源，包括硬件设计公司、技术开发公司、合作院校等，可以为您提供从创意设计-研发-推广一条龙服务。',
		
	);
	
	protected $exclude_method_list = array('execute','index','resource');
	
	public function _init() {
		//$this->set_target_css_state('page_incubator');
    }
	
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->index();
	}
	
	/**
	 * 首页
	 */
	public function index(){
		return $this->to_html_page('wap/incubator/index.html');
	}

	/**
	 * 孵化资源首页
	 */
	public function resource(){
		return $this->to_html_page('wap/incubator/show.html');
	}
	
  	/**
   	 * 产品合作表单提交
     */
    public function cooperate(){
	  	$row = array();
	    $this->stash['mode'] = 'create';

	    $callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
	    $this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
	    $this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;

	    $this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
	    $this->stash['pid'] = new MongoId();
    
	    $this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_CONTACT;

		$this->stash['contact'] = $row;
		
	    return $this->to_html_page('wap/incubator/cooperate_submit.html');
  	}
	
    /**
     * 产品合作保存
     */
    public function save_cooperate(){
		// 验证数据
		if(empty($this->stash['category_id'])){
			return $this->ajax_json('请选择一个分类！', true);
		}
		if(empty($this->stash['title'])){
			return $this->ajax_json('产品名称不能为空！', true);
		}
		if(empty($this->stash['content'])){
			return $this->ajax_json('产品详情不能为空！', true);
		}
		if(empty($this->stash['name'])){
			return $this->ajax_json('联系人不能为空！', true);
		}
		if(empty($this->stash['tel'])){
			return $this->ajax_json('联系电话不能为空！', true);
		}
		if(empty($this->stash['email'])){
			//return $this->ajax_json('邮箱不能为空！', true);
		}
		
		$id = (int)$this->stash['_id'];

    //是否来自京东众筹
    if((int)$this->stash['category_id']==4){
      $from_jd = true;
    }else{
      $from_jd = false;
    }
		
		//保存信息
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['category_id'] = $this->stash['category_id'];
		$data['content'] = $this->stash['content'];
		$data['name'] = $this->stash['name'];
		$data['tel'] = $this->stash['tel'];
		$data['email'] = isset($this->stash['email'])?$this->stash['email']:'';
    $data['position'] = isset($this->stash['position'])?$this->stash['position']:'';
    $data['company'] = isset($this->stash['company'])?$this->stash['company']:'';
    $data['brand'] = isset($this->stash['brand'])?$this->stash['brand']:'';

		$data['cover_id'] = $this->stash['cover_id'];
		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
			$data['asset_count'] = count($data['asset']);
		}else{
			$data['asset'] = array();
			$data['asset_count'] = 0;
		}
		
		try{
			$model = new Sher_Core_Model_Contact();
			
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
					
				$ok = $model->apply_and_save($data);
        //短信提醒张婷--18810228896
        if($ok){
          // 开始发送
          $msg = "有一条孵化项目合作的提交 “".$data['title']."”, 请及时到官网后台查看! 【太火鸟】";
          Sher_Core_Helper_Util::send_defined_mms(18810228896, $msg);
        }
				
			}else{
				$data['_id'] = $id;
				
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}

    $this->stash['is_error'] = false;
    if($from_jd){
      $this->stash['show_note_time'] = 3000;
    	$this->stash['note'] = '申请已提交,我们将尽快与您联系.';
      $redirect_url = Doggy_Config::$vars['app.url.wap.promo'].'/jd';
    }else{
    	$this->stash['note'] = '保存成功!';
      $redirect_url = Doggy_Config::$vars['app.url'].'/incubator';
    }
		$this->stash['redirect_url'] = $redirect_url;
		
		return $this->to_taconite_page('ajax/note.html');
    }

  /**
   * 用户申请孵化合作
   */
  public function ajax_attend(){

    $mode = new Sher_Core_Model_Cooperation();

    if(empty($this->stash['people']) || empty($this->stash['mobile']) || empty($this->stash['wechat']) || empty($this->stash['position']) || empty($this->stash['name'])){
      return $this->ajax_json('请求失败,缺少用户必要参数', true); 
    }

    $data = array();
    $data['user_id'] = (int)$this->visitor->id;
    $data['people'] = $this->stash['people'];
    $data['address'] = isset($this->stash['address']) ? $this->stash['address'] : '';
    $data['email'] = $this->stash['email'];
    $data['mobile'] = $this->stash['mobile'];
    $data['wechat'] = $this->stash['wechat'];
    $data['name'] = $this->stash['name'];
    $data['position'] = $this->stash['position'];
    try{
      $ok = $mode->apply_and_save($data);
      if($ok){
		    return $this->ajax_json('申请成功,需要登录电脑版补全信息!', false);
      }else{
  			return $this->ajax_json('申请失败!', true);   
      }  
    }catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save cooperation failed: ".$e->getMessage());
 			return $this->ajax_json('申请失败.!', true); 
    }
  
  }
	
}

