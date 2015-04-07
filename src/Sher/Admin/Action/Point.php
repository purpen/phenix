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
		return $this->point_record_list();
	}
	
	/**
	 * 用户积分记录列表
	 */
	public function point_record_list() {
        $this->set_target_css_state('point');
        $this->set_target_css_state('page_point_list');
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/point/record_list?page=#p#');
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('admin/point/point_record_list.html');
	}

    /**
     * 用户事件记录列表
     */
    public function event_record_list() {
        $this->set_target_css_state('event');
        $this->set_target_css_state('page_point_list');
        $pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/point/event_record_list?page=#p#');
        $this->stash['pager_url'] = $pager_url;
        return $this->to_html_page('admin/point/event_record_list.html');
    }

    /**
     * 积分配置/积分类型列表页面
     * @return string
     */
    public function settings_type(){
        $this->set_target_css_state('setting_type');
        $this->set_target_css_state('page_point_settings');
        $model = new Sher_Core_Model_PointType();
        $rows = $model->find();
        $this->stash['records'] = $rows;
        return $this->to_html_page('admin/point/settings_type.html');
    }

    /**
     * 积分配置/积分事件列表页面
     * @return string
     */
    public function settings_event(){
        $this->set_target_css_state('setting_event');
        $this->set_target_css_state('page_point_settings');
        $model = new Sher_Core_Model_PointEvent();
        $rows = $model->find();
        $this->stash['events'] = $rows;
        return $this->to_html_page('admin/point/settings_event.html');
    }

    /**
     * 添加修改积分事件
     *
     * @return string
     */
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

    /**
     * 保存积分事件
     */
    public function ajax_save_point_event() {
        $this->set_target_css_state('setting_event');
        $this->set_target_css_state('page_point_settings');
        $model = new Sher_Core_Model_PointEvent();
        $data = $this->stash;
        try{
            $ok = $model->apply_and_save($data);
            if(!$ok){
                return $this->ajax_note('积分事件保存失败,请重新提交', true);
            }
        }catch(Sher_Core_Model_Exception $e){
            return $this->ajax_note('积分事件保存失败:'.$e->getMessage(), true);
        }
        $redirect_url = Doggy_Config::$vars['app.url.admin'].'/point/settings_event';
        return $this->ajax_notification('事件保存成功.', false, $redirect_url);

    }

    /**
     * 添加、修改积分类型页面
     * @return string
     */
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

    /**
     * Ajax保存积分类型记录
     */
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

    /**
     * 会员成长等级页面
     * @return string
     */
    public function user_ranks(){
        $this->set_target_css_state('page_point_ranks');
        $model = new Sher_Core_Model_UserRankDefine();
        $records = $model->find();
        $this->stash['ranks'] = $records;
        return $this->to_html_page('admin/point/user_ranks.html');
    }


    /**
     * 添加会员等级
     * @return string
     */
    public function add_user_rank(){
        $this->set_target_css_state('page_point_ranks');
        if (empty($this->stash['id'])) {
            $mode = 'create';
        }
        else {
            $mode = 'edit';
            $rank = new Sher_Core_Model_UserRankDefine();
            $id = $this->stash['id'];
            $rank = $rank->find_by_id((int)$id);
            $this->stash['record'] = $rank;
        }
        $point = new Sher_Core_Model_PointType();
        $this->stash['points'] = $point->find();
        $this->stash['mode'] = $mode;
        return $this->to_html_page('admin/point/edit_user_rank.html');
    }
    public function ajax_save_user_rank() {
        $this->set_target_css_state('setting_event');
        $this->set_target_css_state('page_point_settings');
        $model = new Sher_Core_Model_UserRankDefine();
        try{
            $data = $this->stash;
            if(empty($data['id'])){
                $ok = $model->apply_and_save($data);
            }else{
                $ok = $model->apply_and_update($data);
            }
            if(!$ok){
                return $this->ajax_note('会员等级保存失败,请重新提交', true);
            }
        }catch(Sher_Core_Model_Exception $e){
            return $this->ajax_note('会员等级保存失败:'.$e->getMessage(), true);
        }
        $redirect_url = Doggy_Config::$vars['app.url.admin'].'/point/user_ranks';
        return $this->ajax_notification('会员等级保存陈工.', false, $redirect_url);
    }

}
