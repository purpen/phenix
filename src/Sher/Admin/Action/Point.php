<?php
/**
 * 积分管理
 * @author night
 */
class Sher_Admin_Action_Point extends Sher_Admin_Action_Base {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);

	/**
	 * 默认
	 */
	public function execute() {
		return $this->record_list();
	}
	
	/**
	 * 用户积分记录列表
	 */
	public function record_list() {
        $this->set_target_css_state('all');
        $this->set_target_css_state('page_point_list');

		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/point/record_list?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/point/record_list.html');
	}

    public function settings_type(){
        $this->set_target_css_state('setting_type');
        $this->set_target_css_state('page_point_settings');
        $model = new Sher_Core_Model_PointType();
        $rows = $model->find();
        $this->stash['records'] = $rows;
        return $this->to_html_page('admin/point/settings_type.html');
    }
    public function settings_event(){
        $this->set_target_css_state('setting_event');
        $this->set_target_css_state('page_point_settings');
        $model = new Sher_Core_Model_PointEvent();
        $rows = $model->find();
        $this->stash['events'] = $rows;
        return $this->to_html_page('admin/point/settings_event.html');
    }

    public function settings_add_event() {
        $this->set_target_css_state('setting_event');
        $this->set_target_css_state('page_point_settings');
        if (empty($this->stash['id'])) {
            $mode = 'create';
        }
        else {
            $mode = 'edit';
            $record = new Sher_Core_Model_PointEvent();
            $id = $this->stash['id'];
            $record = $record->find_by_id($id);
            $this->stash['record'] = $record;
        }
        $this->stash['mode'] = $mode;
        $point = new Sher_Core_Model_PointType();
        $this->stash['points'] = $point->find();
        return $this->to_html_page('admin/point/edit_event.html');
    }

    public function ajax_save_point_event() {
        $this->set_target_css_state('setting_event');
        $this->set_target_css_state('page_point_settings');
        $model = new Sher_Core_Model_PointEvent();
        $data = $this->stash;
        try{
            if(empty($data['_id'])){
                $ok = $model->apply_and_save($data);
            }else{
                $ok = $model->apply_and_update($data);
            }
            if(!$ok){
                return $this->ajax_note('积分事件保存失败,请重新提交', true);
            }
        }catch(Sher_Core_Model_Exception $e){
            return $this->ajax_note('积分事件保存失败:'.$e->getMessage(), true);
        }
        $redirect_url = Doggy_Config::$vars['app.url.admin'].'/point/settings_event';
        return $this->ajax_notification('事件保存成功.', false, $redirect_url);

    }

    public function add_point_type() {
        $this->set_target_css_state('setting_event');
        $this->set_target_css_state('page_point_settings');
        if (empty($this->stash['id'])) {
            $mode = 'create';
        }
        else {
            $mode = 'edit';
            $point = new Sher_Core_Model_PointType();
            $id = $this->stash['id'];
            $point = $point->find_by_id($id);
            $this->stash['point'] = $point;
        }
        $this->stash['mode'] = $mode;
        return $this->to_html_page('admin/point/edit_point.html');
    }

    public function ajax_save_point_type(){
        $this->set_target_css_state('setting_event');
        $this->set_target_css_state('page_point_settings');
        $redirect_url = Doggy_Config::$vars['app.url.admin'].'/point/settings_type';
        $this->set_target_css_state('setting_event');
        $this->set_target_css_state('page_point_settings');
        $model = new Sher_Core_Model_PointType();
        try{
            $data = $this->stash;
            if(empty($data['_id'])){
                $ok = $model->apply_and_save($data);
            }else{
                $ok = $model->apply_and_update($data);
            }
            if(!$ok){
                return $this->ajax_note('积分类型保存失败,请重新提交', true);
            }
        }catch(Sher_Core_Model_Exception $e){
            return $this->ajax_note('积分类型保存失败:'.$e->getMessage(), true);
        }

        return $this->ajax_notification('积分类型保存成功.', false, $redirect_url);
    }

}
