<?php
/**
 * 标签分类管理
 * @author tianshuai
 */
class Sher_Admin_Action_StyleTag extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
	);
	
	public function _init() {
		$this->set_target_css_state('page_style_tag');
    }
    
	public function execute(){
		return $this->get_list();
	}
	
	/**
    * 列表
    * @return string
  */
  public function get_list(){
    $this->set_target_css_state('all');
    return $this->to_html_page('admin/style_tag/list.html');
  }
	
	/**
	 * 编辑信息
	 */
	public function edit(){
		$model = new Sher_Core_Model_StyleTag();
		$mode = 'create';
		
		if(!empty($this->stash['id'])) {
			$this->stash['style_tag'] = $model->extend_load((int)$this->stash['id']);
			$mode = 'edit';
		}else{
			$this->stash['style_tag'] = array();
		}
		
		$this->stash['user_id'] = $this->visitor->id;
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STYLE_TAG;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STYLE_TAG;
		
		$this->stash['mode'] = $mode;
        
		return $this->to_html_page('admin/style_tag/edit.html');
	}
    
	/**
	 * 保存信息
	 */
	public function save(){		
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_note('标题不能为空！', true);
		}
		$id = isset($this->stash['_id']) ? (int)$this->stash['_id'] : 0;
        
		$model = new Sher_Core_Model_StyleTag();
        
        $data = array();
        $data['mark'] = $this->stash['mark'];
        $data['title'] = $this->stash['title'];
        $data['summary']  = $this->stash['summary'];
        $data['content']  = $this->stash['content'];
        $data['kind']  = (int)$this->stash['kind'];
        $data['content']  = $this->stash['content'];
        $data['domain']  = (int)$this->stash['domain'];
        $data['state']  = (int)$this->stash['state'];
        $data['stick']  = (int)$this->stash['stick'];
        $data['sort']  = (int)$this->stash['sort'];
        $data['cover_id'] = $this->stash['cover_id'];
        
		try{
			if(empty($id)){
				$mode = 'create';
                $data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
                
                $style_tag = $model->get_data();
                $id = $style_tag['_id'];
			}else{
				$mode = 'edit';
				$this->stash['_id'] = $data['_id'] = $id;
                
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($this->stash['asset']) && !empty($this->stash['asset'])){
				$model->update_batch_assets($this->stash['asset'], $id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('保存失败:'.$e->getMessage(), true);
		}catch(Doggy_Model_ValidateException $e){
		    return $this->ajax_note('验证数据失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/style_tag';
		
		return $this->ajax_notification('保存成功.', false, $redirect_url);
	}
    
	/**
	 * 删除
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
		$model = new Sher_Core_Model_StyleTag();
        // todo: 检查是否存在作品
        
		$model->remove($id);
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}

}

