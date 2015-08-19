<?php
/**
 * 孵化器
 * @author purpen
 */
class Sher_App_Action_Incubator extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
        'page_title_suffix' => '孵化-太火鸟-中国最火爆的智能硬件孵化平台',
        'page_keywords_suffix' => '太火鸟,智能硬件,智能硬件孵化平台,孵化资源,设计公司,技术开发,合作院校,创意设计,硬件研发,硬件推广',
        'page_description_suffix' => '中国最火爆的智能硬件孵化平台-太火鸟聚集了上百家智能硬件相关资源，包括硬件设计公司、技术开发公司、合作院校等，可以为您提供从创意设计-研发-推广一条龙服务。',
	);
	
	protected $exclude_method_list = array('execute','index','view');
	
	public function _init() {
		$this->set_target_css_state('page_incubator');
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
        return $this->to_html_page('page/incubator/index.html');	
	}
    
    /**
     * 服务介绍
     */
    public function service(){
        return $this->to_html_page('page/incubator/service.html');
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
		
	    return $this->to_html_page('page/cooperate/cooperate_submit.html');
  	}

    /**
     * 详情查看
     */
    public function view(){
      $id = $this->stash['id'];
      
      $model = new Sher_Core_Model_Contact();
      $incubator = $model->extend_load($id);
      
      $this->stash['incubator'] = $incubator;
      
      return $this->to_html_page('page/incubator/view.html');
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
			return $this->ajax_json('邮箱不能为空！', true);
		}
		
		$id = (int)$this->stash['_id'];
		
		//保存信息
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['category_id'] = $this->stash['category_id'];
		$data['content'] = $this->stash['content'];
		$data['name'] = $this->stash['name'];
		$data['tel'] = $this->stash['tel'];
		$data['email'] = $this->stash['email'];

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
      $incubator = $model->get_data();
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('产品合作保存失败:'.$e->getMessage(), true);
		}

    	$this->stash['is_error'] = false;
    	$this->stash['note'] = '保存成功!';
		$this->stash['redirect_url'] = Sher_Core_Helper_Url::incubator_view_url($incubator['_id']);
		
		return $this->to_taconite_page('ajax/note.html');
    }

	/**
	 * ajax删除产品合作
	 */
	public function ajax_del(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Contact();
			$cooperation = $model->load((string)$id);
			
			// 仅管理员或本人具有删除权限
			if (!$this->visitor->can_admin() && !($cooperation['user_id'] == $this->visitor->id)){
				return $this->ajax_notification('抱歉，你没有权限进行此操作！', true);
			}
			
			$model->remove((string)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/del_ok.html');
	}
	
}
?>
