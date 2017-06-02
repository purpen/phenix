<?php
/**
 * 灵感管理
 * @author tianshuai
 */
class Sher_Admin_Action_Stuff extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
    'sort' => 0,
    'from_to' => 0,
    'load_college' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_stuff');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		return $this->get_list();
	}
	
	/**
	 * 列表--全部
	 */
	public function get_list() {
		
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		
    $this->set_target_css_state('all_list');
		$page = (int)$this->stash['page'];

		$this->stash['category_id'] = 0;
		$this->stash['is_top'] = true;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/stuff?sort=%d&page=#p#', $this->stash['sort']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/stuff/list.html');
	}

	/**
	 * 列表--蛋年
	 */
	public function egg_list() {
		
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		
    $this->set_target_css_state('egg_list');
		$page = (int)$this->stash['page'];
		
    $category_id = Doggy_Config::$vars['app.birdegg.category_id'];
    $this->stash['category_id'] = $category_id;
    $this->stash['is_top'] = true;
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/stuff/egg_list?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/stuff/list.html');
	}

	/**
	 * 列表--十万火计2
	 */
	public function swhj2_list() {
		
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		
    $this->set_target_css_state('swhj2_list');
		$page = (int)$this->stash['page'];

    $category_id = Doggy_Config::$vars['app.contest.dream2_category_id'];
    $this->stash['category_id'] = $category_id;
    $this->stash['is_top'] = true;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/stuff/swhj2_list?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
    $this->stash['load_college'] = 1;
		
		return $this->to_html_page('admin/stuff/list.html');
	}

	/**
	 * 列表--反向定制
	 */
	public function redesign_list() {
		
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		
    $this->set_target_css_state('redesign_list');
		$page = (int)$this->stash['page'];

    $this->stash['from_to'] = 4;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/stuff/redesign_list?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/stuff/list.html');
	}

	/**
	 * 列表--Top100
	 */
	public function top100_list() {
		
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		
    $this->set_target_css_state('top100_list');
		$page = (int)$this->stash['page'];

    $this->stash['from_to'] = 5;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/stuff/top100_list?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/stuff/list.html');
	}

	/**
	 * 列表--奇思甬动2
	 */
	public function qsyd2_list() {
		
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		
    $this->set_target_css_state('qsyd2_list');
		$page = (int)$this->stash['page'];

    $this->stash['from_to'] = 6;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/stuff/qsyd2_list?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/stuff/list.html');
	}

	/**
	 * 列表--奇思甬动3
	 */
	public function qsyd3_list() {
		
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		
    $this->set_target_css_state('qsyd3_list');
		$page = (int)$this->stash['page'];

    $this->stash['from_to'] = 7;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/stuff/qsyd3_list?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/stuff/list.html');
	}

	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;
		$mode = 'create';

        $top_category_id = Doggy_Config::$vars['app.contest.qsyd2_category_id'];
		$this->stash['fid'] = $top_category_id;
		
		$model = new Sher_Core_Model_Stuff();

		if(!empty($id)){
			$mode = 'edit';
			$stuff = $model->find_by_id($id);
      $stuff = $model->extended_model_row($stuff);
			$this->stash['stuff'] = $stuff;
		}
    $this->stash['mode'] = $mode;

        // 记录上一步来源地址
        $this->stash['return_url'] = $_SERVER['HTTP_REFERER'];
		
		return $this->to_html_page('admin/stuff/submit.html');
	}

	/**
	 * 保存
	 */
	public function save() {

        // 要求只能某个账户操作
        if($this->visitor->id != 10){
            //return $this->ajax_notification('权限不够！', true);   
        }

        $redirect_url = isset($this->stash['return_url']) ? htmlspecialchars_decode($this->stash['return_url']) : null;
		
		$model = new Sher_Core_Model_Stuff();
		try{
      $data = array();
			if(empty($this->stash['_id'])){
				$mode = 'create';
				//$ok = $model->apply_and_save($this->stash);
      }else{

		$mode = 'edit';
        $data['_id'] = (int)$this->stash['_id'];

        //$add_love_count = (int)$this->stash['add_love_count'];
        //$data['love_count'] = (int)$this->stash['love_count'] + abs($add_love_count);
        //$data['view_count'] = (int)$this->stash['view_count'];
        $data['category_id'] = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
				$ok = $model->apply_and_update($data);
			}
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}		
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('保存失败:'.$e->getMessage(), true);
		}
		
        if(!$redirect_url){
		    $redirect_url = Doggy_Config::$vars['app.url.admin'].'/stuff';
        }
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('灵感不存在！', true);
		}

    // 要求只能某个账户操作
    if(!Sher_Core_Helper_Util::is_high_admin($this->visitor->id)){
      return $this->ajax_notification('没有执行权限!', true);     
    }
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Stuff();
			
			foreach($ids as $id){
				$stuff = $model->load((int)$id);
				
        if (!empty($stuff)){
		      $model->remove((int)$id);
			    $model->mock_after_remove($id, $stuff);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

  /**
   * 搜索
   */
  public function search(){
    $this->stash['is_search'] = true;
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/stuff/search?s=%d&q=%s&sort=%d&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s'], $this->stash['q'], $this->stash['sort']);
    return $this->to_html_page('admin/stuff/list.html');
  
  }

	/**
	 * 通过审核/撤销通过
	 */
	public function ajax_verified(){
		$ids = $this->stash['id'];
    $evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));
		
		foreach($ids as $id){
			$result = $model->mark_as_verified((int)$id, $evt);
		}
		
		$this->stash['note'] = '操作成功！';
		
		return $this->to_taconite_page('ajax/published_ok.html');
	}

  /**
   * 推荐／取消
   */
  public function ajax_stick(){
 		$ids = $this->stash['id'];
    $evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));
		
		foreach($ids as $id){
			$result = $model->mark_as_stick((int)$id, $evt);
		}
		
		$this->stash['note'] = '操作成功！';
		
		return $this->to_taconite_page('ajax/published_ok.html');
  
  }

  /**
   * 点赞名单
   */
  public function get_love_list(){
    
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/stuff/get_love_list?target_id=%d&page=#p#', $this->stash['target_id']);
		$this->stash['pager_url'] = $pager_url;
    return $this->to_html_page('admin/stuff/love_list.html');
  }

}

