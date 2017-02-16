<?php
/**
 * 情境管理
 * @author caowei@taihuoniao.com
 */
class Sher_AppAdmin_Action_SceneSight extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'state' => '',
        'deleted' => 0,
        's_title' => '',

        'month' => null,
        'week' => null,
        'day' => null,
	);
	
	public function _init() {
		
		$this->stash['show_type'] = "sight";
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {

		$this->set_target_css_state('page_app_scene_sight');
        
		$user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        if($this->stash['deleted']==1){
   		    $this->set_target_css_state('deleted');        
        }else{

		    switch($type){
            case 1:
                $this->set_target_css_state('fine');
                break;
            case 2:
                $this->set_target_css_state('check');
                break;
            default:
                $this->set_target_css_state('all');
            }       
        }
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/scene_sight?type=%d&deleted=%d&user_id=%d&s_title=%s&page=#p#', $type, $this->stash['deleted'], $user_id, $this->stash['s_title']);
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('app_admin/scene_sight/list.html');
	}

    /**
	 * 更新
	 */
	public function submit(){

		$this->set_target_css_state('page_app_scene_sight');
        // 记录上一步来源地址
        $this->stash['return_url'] = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : Doggy_Config::$vars['app.url.app_admin']."/scene_sight";
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : '';
		if(!$id){
			return $this->ajax_json('缺少请求参数！', true);
		}
		$mode = 'edit';

		$fid = Doggy_Config::$vars['app.scene_sight.category_id'];
        $this->stash['fid'] = $fid;
		
		$model = new Sher_Core_Model_SceneSight();
		$sight = $model->load($id);
		$sight = $model->extended_model_row($sight);
		if($sight){
			$sight['tags'] = implode(',',$sight['tags']);
		}
		$this->stash['sight'] = $sight;
		$this->stash['mode'] = $mode;

		// 查询标签信息
		$scene_tags_model = new Sher_Core_Model_SceneTags();
		$root = $scene_tags_model->find_root_key(1);
		$scene_tags = $scene_tags_model->find(array('parent_id'=>(int)$root['_id']));
		$this->stash['scene_tags'] = $scene_tags;

		$this->stash['app_baidu_map_ak'] = Doggy_Config::$vars['app.baidu.map_ak'];
		
		return $this->to_html_page('app_admin/scene_sight/submit.html');
	}

	/**
	 * 推荐
	 */
	public function ajax_stick() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneSight();
            if($evt==0){
                $model->mark_cancel_stick($id);
            }else{
                $model->mark_as_stick($id);
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_sight/stick_ok.html');
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
			$model = new Sher_Core_Model_SceneSight();
            if($evt==0){
                $model->mark_cancel_fine($id);
            }else{
                $model->mark_as_fine($id);
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_sight/fine_ok.html');
	}
	
	/**
	 * 审核
	 */
	public function ajax_check() {
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$evt = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		if(empty($id)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneSight();
			$model->update_set($id, array('is_check'=>(int)$evt));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('app_admin/scene_sight/check_ok.html');
	}

	/**
	 * 删除
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
		$model = new Sher_Core_Model_SceneSight();
		$result = $model->load((int)$id);
		
		if($result && $model->mark_remove($id)){
            $model->mock_after_remove($id, $result);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html');
	}

	/**
	 * 提交情境
	 */
	public function save(){
		
		$user_id = $this->visitor->id;
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		$category_ids = isset($this->stash['category_ids']) ? $this->stash['category_ids'] : '';
		
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['des'] = $this->stash['des'];
		$data['tags'] = $this->stash['tags'];
		$data['scene_id'] = (int)$this->stash['scene_id'];
        $data['category_ids'] = $category_ids;
		$data['city'] = isset($this->stash['city']) ? $this->stash['city'] : '';
		$data['address'] = $this->stash['address'];
		$data['location'] = array(
            'type' => 'Point',
            'coordinates' => array(doubleval($this->stash['lng']), doubleval($this->stash['lat'])),
        );
        $data['subject_ids'] = isset($this->stash['subject_ids']) ? $this->stash['subject_ids'] : null;
		
		if(empty($data['title'])){
			return $this->ajax_json('请求参数不能为空', true);
		}
		
		if(empty($data['tags']) || empty($data['tags'])){
			return $this->ajax_json('请求参数不能为空', true);
		}
		
		try{
			$model = new Sher_Core_Model_SceneSight();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = $user_id;
				
				$ok = $model->apply_and_save($data);
				$scene = $model->get_data();
				
				$id = $scene['_id'];
			}else{
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}

          // 更新全文索引
          Sher_Core_Helper_Search::record_update_to_dig((int)$id, 5);
	
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("api情境保存失败：".$e->getMessage());
			return $this->ajax_json('情境保存失败:'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('提交成功', false, null);
	}


  /**
   * 情境统计
   */
  public function sight_stat(){
		// 判断左栏类型
		$this->stash['show_type'] = "sight";
    $this->set_target_css_state('page_app_sight_stat');
    $this->set_target_css_state('all_list');

		$pager_url = sprintf(Doggy_Config::$vars['app.url.app_admin'].'/scene_sight/sight_stat?month=%s&week=%s&day=%s&page=#p#', $this->stash['month'], $this->stash['week'], $this->stash['day']);
		$this->stash['pager_url'] = $pager_url;

    return $this->to_html_page('app_admin/scene_sight/sight_stat.html');

  }

  /**
   * 情境统计删除操作
   */
  public function sight_stat_delete(){
    $id = isset($this->stash['id']) ? $this->stash['id'] : null;
		if(empty($id)){
			return $this->ajax_note('请求参数为空', true);
		}
    $user_id = $this->visitor->id;
    if(!Sher_Core_Helper_Util::is_high_admin($user_id)){
 			return $this->ajax_note('没有执行权限!', true);     
    }
		$model = new Sher_Core_Model_SightStat();
		if($model->remove($id)){
            $model->mock_after_remove($id);
		}
		
		$this->stash['id'] = $id;
		return $this->to_taconite_page('app_admin/del_ok.html'); 
  }

	
}
