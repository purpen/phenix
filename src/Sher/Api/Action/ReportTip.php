<?php
/**
 * 举报管理
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_ReportTip extends Sher_Api_Action_Base {
	
	public $stash = array(
		'id'   => '',
        'page' => 1,
        'size' => 10,
	);
	
	protected $filter_user_method_list = array('execute', 'getlist', 'save');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 列表
	 */
	public function getlist(){
		
	}
	
	/**
	 * 提交
	 */
	public function save(){
		
		// target_id=25&target_user_id=125&type=3&evt=1&title=test&content=test
		$type_arr = array(1,2,3,4,5);
		
		$user_id = $this->current_user_id;
		$user_id = 10;
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
        $title = isset($this->stash['title']) ? $this->stash['title'] : '';
        $content = isset($this->stash['content']) ? $this->stash['content'] : '';
        
        if(!$target_id){
            return $this->api_json('关联id不能为空', 4002);
        }
        
        if(!$type){
            return $this->api_json('类型不能为空', 4002);
        }
		
		if(!in_array($type,$type_arr)){
            return $this->api_json('类型不合法', 4002);
        }
        
        if(!$evt){
            return $this->api_json('举报原因不能为空', 4002);
        }
        
        if(!$title){
            //return $this->api_json('举报标题不能为空', 4002);
        }
        
        if(!$content){
            //return $this->api_json('举报内容不能为空', 4002);
        }
		
		switch($type){
			case 3:
				$model = new Sher_Core_Model_SceneScene();
				$result = $model->first((int)$target_id);
				$target_user_id = $result['user_id'];
				break;
			case 4:
				$model = new Sher_Core_Model_SceneSight();
				$result = $model->first((int)$target_id);
				$target_user_id = $result['user_id'];
				break;
			case 5:
				$target_user_id = $target_id;
				break;
		}
        
        $data = array();
		$data['target_id'] = $target_id;
		$data['target_user_id'] = isset($target_user_id) ? $target_user_id : 0;
        $data['target_type'] = $type;
        $data['title'] = $title;
        $data['content'] = $content;
        $data['evt'] = $evt;
		
		//var_dump($data);die;
		try{
			$model = new Sher_Core_Model_ReportTip();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = $user_id;
				$ok = $model->apply_and_save($data);
				$res = $model->get_data();
				$id = $res['_id'];
			}else{
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->api_json('保存失败,请重新提交', 4002);
			}
			
					
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('保存失败:'.$e->getMessage(), 4001);
		}
		
		return $this->api_json('提交成功', 0, array('current_user_id'=>$user_id));
	}
	
}