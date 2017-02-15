<?php
/**
 * 情境专题管理
 * @author tianshuai
 */
class Sher_AppAdmin_Action_SceneSubject extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'kind' => '',
        'type' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_app_scene_subject');
		// 判断左栏类型
		$this->stash['show_type'] = "sight";
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {

        $type = isset($this->stash['type']) ? $this->stash['type'] : 0;

        switch($type){
          case 1:
            $this->set_target_css_state('article');
            break;
          case 2:
            $this->set_target_css_state('active');
            break;
          case 3:
            $this->set_target_css_state('hot');
            break;
          case 4:
            $this->set_target_css_state('new');
            break;
          case 5:
            $this->set_target_css_state('goods');
            break;
          case 6:
            $this->set_target_css_state('sight');
            break;
          default:
            $this->set_target_css_state('all');
        }

		$this->stash['pager_url'] = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/scene_subject/get_list?type=%d&page=#p#', $type);
		return $this->to_html_page('app_admin/scene_subject/list.html');
	}
	
	/**
	 * 添加页面
	 */
	public function submit(){

		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;

        // 记录上一步来源地址
        $this->stash['return_url'] = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : Doggy_Config::$vars['app.url.app_admin']."/scene_subject";
		if(empty($id)){
            $this->stash['mode'] = 'create';
        }else{
            $this->stash['mode'] = 'edit';
            $model = new Sher_Core_Model_SceneSubject();
            $scene_subject = $model->extend_load($id);
            if(empty($scene_subject)){
			    return $this->show_message_page('专题不存在！', $redirect_url);
            }
            $this->stash['scene_subject'] = $scene_subject;
        }

		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_SCENE_SUBJECT;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SUBJECT;

		$this->stash['banner_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['banner_asset_type'] = Sher_Core_Model_Asset::TYPE_SCENE_SUBJECT_BANNER;
		
		return $this->to_html_page('app_admin/scene_subject/submit.html');
	}
	
	/**
	 * 保存方法
	 */
	public function save(){
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$scene_subject_html = $this->stash['scene_subject_html'];
		$scene_subject_title = $this->stash['title'];
		$scene_subject_short_title = $this->stash['short_title'];
		$scene_subject_tag = $this->stash['tags'];
		$cover_id = $this->stash['cover_id'];
		$banner_id = isset($this->stash['banner_id']) ? $this->stash['banner_id'] : null;
		$kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
		$evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
        $tags = isset($this->stash['tags']) ? $this->stash['tags'] : '';
        $sight_ids = isset($this->stash['sight_ids']) ? $this->stash['sight_ids'] : null;
        $prize_sight_ids = isset($this->stash['prize_sight_ids']) ? $this->stash['prize_sight_ids'] : null;
        $product_ids = isset($this->stash['product_ids']) ? $this->stash['product_ids'] : null;
        $product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;
        $view_count = isset($this->stash['view_count']) ? (int)$this->stash['view_count'] : 0;
        $share_count = isset($this->stash['share_count']) ? (int)$this->stash['share_count'] : 0;
        $mode = isset($this->stash['mode']) ? (int)$this->stash['mode'] : 1;
        $extra_tag = $this->stash['extra_tag'];

        $begin_time = isset($this->stash['begin_time']) ? $this->stash['begin_time'] : null;
        $end_time = isset($this->stash['end_time']) ? $this->stash['end_time'] : null;
		
		// 验证内容
		if(!$scene_subject_html){
			return $this->ajax_json('内容不能为空！', true);
		}
		
		// 验证标题
		if(!$scene_subject_title){
			return $this->ajax_json('标题不能为空！', true);
		}
		
		// 验证标签
		if(!$scene_subject_tag){
			return $this->ajax_json('标签不能为空！', true);
		}
		
		$date = array(
			'title' => $scene_subject_title,
            'short_title' => $scene_subject_short_title,
			'tags' => $tags,
			'content' => $scene_subject_html,
			'cover_id' => $cover_id,
			'banner_id' => $banner_id,
			'kind' => $kind,
            'evt' => $evt,
            'type' => $type,
            'summary' => $this->stash['summary'],
            'product_ids' => $product_ids,
            'begin_time' => $begin_time,
            'end_time' => $end_time,
            'product_id' => $product_id,
            'view_count' => $view_count,
            'share_count' => $share_count,
            'prize_sight_ids' => $prize_sight_ids,
            'sight_ids' => $sight_ids,
            'mode' => $mode,
            'extra_tag' => $extra_tag,
		);
		
		try{

			$model = new Sher_Core_Model_SceneSubject();

			if(empty($id)){
				// add
                $date['user_id'] = $this->visitor->id;
				$ok = $model->apply_and_save($date);
				$data_id = $model->get_data();
				$id = $data_id['_id'];
			} else {
				// edit
				$date['_id'] = $id;
				$ok = $model->apply_and_update($date);
			}

			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($this->stash['asset']) && !empty($this->stash['asset'])){
				$model->update_batch_assets($this->stash['asset'], $id);
			}
			// 上传成功后，更新所属的附件(Banner)
			if(isset($this->stash['banner_asset']) && !empty($this->stash['banner_asset'])){
				$model->update_batch_assets($this->stash['banner_asset'], $id);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.app_admin'].'/scene_subject';
		return $this->ajax_json('保存成功', false, $redirect_url);
	}
	
	/**
	* 删除专题
	*/
	public function deleted(){
	   
		$id = isset($this->stash['id']) ? $this->stash['id'] : 0;
		
		if(empty($id)){
		   return $this->ajax_notification('专题信息不存在！', true);
		}
	   
		try{
			 $model = new Sher_Core_Model_SceneSubject();
			 $ok = $model->remove((int)$id);
			 
			 if(!$ok){
				 return $this->ajax_json('保存失败,请重新提交', true);
			 }
			 
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		$this->stash['ids'] = $ids;
		return $this->to_taconite_page('ajax/delete.html');
	}
	
	/**
	* 发布／取消
	*/
	public function ajax_publish(){
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		
		if(empty($id)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_SceneSubject();
        if(!empty($evt)){
		    $ok = $model->mark_as_publish($id);
        }else{
		    $ok = $model->mark_cancel_publish($id);
        }
		
		if(!$ok){
			return $this->ajax_notification('操作失败!', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_subject/publish_ok.html');
	}
	
	/**
	* 推荐／取消
	*/
	public function ajax_stick(){
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		
		if(empty($id)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_SceneSubject();
        if(!empty($evt)){
		    $ok = $model->mark_as_stick($id);
        }else{
		    $ok = $model->mark_cancel_stick($id);
        }
		
		if(!$ok){
			return $this->ajax_notification('操作失败!', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_subject/stick_ok.html');
	}

	/**
	 * 精选
	 */
	public function ajax_fine() {
    $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
    $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneSubject();
            if(!empty($evt)){
                $ok = $model->mark_as_fine($id);
            }else{
                $ok = $model->mark_cancel_fine($id);
            }
            
            if(!$ok){
                return $this->ajax_notification('操作失败!', true);
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_subject/fine_ok.html');
	}



}

