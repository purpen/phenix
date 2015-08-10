<?php
/**
 * 合作资源(品牌、设计公司、生成商、材料供应商等)
 * @author purpen
 */
class Sher_App_Action_Cooperate extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	public $stash = array(
		'page' => 1,
    'd'    => 0,
    'rid'  => 0,
    'cid'  => 0,
    'page_title_suffix' => '孵化服务-太火鸟-中国最火爆的智能硬件孵化平台',
    'page_keywords_suffix' => '太火鸟,智能硬件,智能硬件孵化平台,孵化资源,设计公司,技术开发,合作院校,创意设计,硬件研发,硬件推广',
    'page_description_suffix' => '中国最火爆的智能硬件孵化平台-太火鸟聚集了上百家智能硬件相关资源，包括硬件设计公司、技术开发公司、合作院校等，可以为您提供从创意设计-研发-推广一条龙服务。',

	);
	
	protected $exclude_method_list = array('execute', 'index');
	
	public function _init() {
		$this->set_target_css_state('page_incubator');
        $this->set_target_css_state('page_cooperate');
    }
    
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->index();
	}
	
	/**
	 * 资源首页
	 */
	public function index(){
        $show_all = 'showno';
        $district = $this->stash['d'];
        $rid = $this->stash['rid'];
        $cid = $this->stash['cid'];
        
        if($cid || $district){
            $show_all = 'showall';
        }
        
        // 不限类型
        if(empty($cid) && empty($rid)){
            $pager_url = sprintf(Doggy_Config::$vars['app.url.cooperate'].'?d=%d&page=#p#', $district);
        }
        
        // 仅限类型
        if(empty($cid) && !empty($rid)){
            $pager_url = sprintf(Doggy_Config::$vars['app.url.cooperate'].'?rid=%d&d=%d&page=#p#', $rid, $district);
        }
        
        // 某类型下类别
        if(isset($cid) && empty($rid)){
            $category = new Sher_Core_Model_Category();
            $row = $category->load((int)$cid);
            if(empty($row)){
                $rid = $row['pid'];
            }
            $pager_url = sprintf(Doggy_Config::$vars['app.url.cooperate'].'?cid=%d&d=%d&page=#p#', $cid, $district);
        }
        
        // 获取地域城市
        $areas = new Sher_Core_Model_Areas();
        $cities = $areas->find_cities();
        $this->stash['cities'] = $cities;
        
        $this->stash['cid'] = $cid;
        $this->stash['rid'] = $rid;
        $this->stash['district'] = $district;
        
        $this->stash['show_all'] = $show_all;
        
        $this->stash['pager_url'] = $pager_url;
        
		return $this->to_html_page('page/cooperate/index.html');
	}
    
    /**
     * 详情查看
     */
    public function view(){
        $id = $this->stash['id'];
        $editable = false;
        
        $model = new Sher_Core_Model_Cooperation();
        $cooperate = $model->extend_load((int)$id);
        
        if($this->visitor->can_admin()){
            $editable = true;
        }
        
        $this->stash['cooperate'] = $cooperate;

        //添加网站meta标签
        $this->stash['page_title_suffix'] = sprintf("%s-太火鸟智能硬件平台合作方", $cooperate['name']);
        if(!empty($try['tags'])){
          $this->stash['page_keywords_suffix'] = "太火鸟,智能硬件,智能硬件孵化平台,孵化资源,设计公司,技术开发,合作院校,创意设计,硬件研发,硬件推广";   
        }
        $this->stash['page_description_suffix'] = sprintf("%s-中国最火爆的智能硬件孵化平台-太火鸟合作方，为您提供商品定义、投资融资、硬件品控、软件体验、推广营销、渠道建设等每个环节全程全力支持。", $cooperate['name']);
        
        $this->stash['editable'] = $editable;
        
        $this->stash['last_char'] = substr((string)$id, -1);
        
        $this->validate_ship($id);
        
        return $this->to_html_page('page/cooperate/view.html');
    }
	
	/**
	 * 提交申请
	 */
	public function apply(){
		$this->stash['mode'] = 'create';
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_COOPERATE;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_COOPERATE;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
        
        $this->_editor_params();
		
		return $this->to_html_page('page/cooperate/apply.html');
	}
    
    /**
     * 编辑信息
     */
    public function edit(){
        $id = $this->stash['id'];
        
		$this->stash['mode'] = 'edit';
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_COOPERATE;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_COOPERATE;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		$this->_editor_params();
        
        $model = new Sher_Core_Model_Cooperation();
        $cooperate = $model->extend_load((int)$id);
        
        $this->stash['cooperate'] = $cooperate;
        
        return $this->to_html_page('page/cooperate/apply.html');
    }
	
	/**
	 * 保存信息
	 */
	public function save(){
		// 验证数据
		if(empty($this->stash['name'])){
			return $this->ajax_json('名称不能为空！', true);
		}
        $mode = 'create';
        $data = $this->stash;
        
		$id = (int)$this->stash['_id'];
        
		// 检测编辑器图片数
		$file_count = isset($this->stash['file_count'])?(int)$this->stash['file_count']:0;
		
		// 检查是否有附件
		if(!isset($this->stash['asset'])){
			$data['asset'] = array();
		}
        
        // 获取logo
        $qkey = $this->stash['qkey'];
        if(!empty($qkey)){
            $data['logo'] = array(
                'big' => $qkey,
                'medium' => $qkey,
                'small' => $qkey,
                'mini' => $qkey
            );
        }
        
        // 标记是否孵化实验室
        if(isset($this->stash['mark_labs'])){
            $data['marks']['labs'] = $this->stash['mark_labs']; 
        }else{
            $data['marks']['labs'] = false;
        }
		
		try{
			$model = new Sher_Core_Model_Cooperation();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				
				$cooperation = $model->get_data();
				
				$id = (int)$cooperation['_id'];
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			$asset = new Sher_Core_Model_Asset();
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$asset->update_batch_assets($data['asset'], (int)$id);
			}
			
			// 保存成功后，更新编辑器图片
			Doggy_Log_Helper::debug("Upload file count[$file_count].");
			if($file_count && !empty($this->stash['file_id'])){
				$asset->update_editor_asset($this->stash['file_id'], (int)$id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("创意保存失败：".$e->getMessage());
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
        
		$redirect_url = Sher_Core_Helper_Url::cooperate_home_url($id);
        
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
    
    /**
     * 获取子类别
     */
    public function ajax_fetch_category(){
        return $this->to_taconite_page('ajax/fetch_category.html');
    }
    
	/**
	 * 关注或收藏 资源
	 * 
	 * @return string
	 */
	public function ajax_follow(){
		$user_id = (int)$this->visitor->id;
		$follow_id = (int)$this->stash['id'];
		
		if(empty($follow_id) || empty($user_id)){
			return $this->ajax_note('请求失败,缺少必要参数', true);
		}
        
		$model = new Sher_Core_Model_Favorite();
		if(!$model->has_exist_follow($user_id, $follow_id)){
			$data['user_id']   = $user_id;
			$data['target_id'] = $follow_id;
            $data['event'] = Sher_Core_Model_Favorite::EVENT_FOLLOW;
            $data['type']  = Sher_Core_Model_Favorite::TYPE_COOPERATE;
			
            $ok = $model->apply_and_save($data);
            $this->stash['domode'] = 'create';
		}
		
		return $this->to_taconite_page('ajax/follow_cooperate_ok.html');
	}
	
	/**
	 * 取消关注或收藏 资源
	 * 
	 * @return string
	 */
	public function ajax_cancel_follow(){
		$user_id = (int)$this->visitor->id;
        $follow_id = (int)$this->stash['id'];
        
        if(empty($follow_id) || empty($user_id)){
            return $this->ajax_note('请求失败,缺少必要参数',true);
        }
		
        $model = new Sher_Core_Model_Favorite();
        // 取消关注
        if($model->has_exist_follow($user_id, $follow_id)){
	        $query['user_id'] = $user_id;
	        $query['target_id'] = $follow_id;
            $query['type']  = Sher_Core_Model_Favorite::TYPE_COOPERATE;
            
	        $ok = $model->remove($query);
            
            if($ok){
    			// 更新关注数
                $model->mock_after_remove($this->visitor->id, $follow_id, 6, Sher_Core_Model_Favorite::EVENT_FOLLOW);
            }
            
            $this->stash['domode'] = 'cancel';
        }
        
		return $this->to_taconite_page('ajax/follow_cooperate_ok.html');
	}
    
    /**
     * 验证关系
     */
    protected function validate_ship($id){
		// 验证关注关系
		$model = new Sher_Core_Model_Favorite();
		$is_ship = $model->has_exist_follow($this->visitor->id, $id);
		$this->stash['is_ship'] = $is_ship;
    }
    
	/**
	 * 编辑器参数
	 */
	protected function _editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$new_pic_id = new MongoId();
		$this->stash['editor_pid'] = (string)$new_pic_id;

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_COOPERATE;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_COOPERATE_EDITOR;
	}

	
}
