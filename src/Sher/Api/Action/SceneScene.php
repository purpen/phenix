<?php
/**
 * 情景管理
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_SceneScene extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist','save');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 情景列表
	 */
	public function getlist(){
		
		return $this->api_json('请求成功', 0, $data);
	}
	
	/**
	 * 提交情景
	 */
	public function save(){
		
		$user_id = $this->current_user_id;
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;
		
		$data = array();
		$data['title'] = $this->stash['title'];
		$data['des'] = $this->stash['des'];
		$data['tags'] = $this->stash['tags'];
		$data['address'] = $this->stash['address'];
		$data['location']['coordinates']['lat'] = $this->stash['lat'];
		$data['location']['coordinates']['lng'] = $this->stash['lng'];
		//$data['asset'] = isset($this->stash['asset'])?$this->stash['asset']:array();
		
		if(empty($data['title']) || empty($data['des'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		if(empty($data['address']) || empty($data['address'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		if(empty($data['tags']) || empty($data['tags'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		if(empty($data['location']['coordinates']['lat']) || empty($data['location']['coordinates']['lat'])){
			return $this->api_json('请求参数不能为空', 3000);
		}
		
		$data['tags'] = explode(',',$data['tags']);
		foreach($data['tags'] as $k => $v){
			$data['tags'][$k] = (int)$v;
		}
		//var_dump($data);die;
		try{
			$model = new Sher_Core_Model_SceneScene();
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
				return $this->api_json('保存失败,请重新提交', 4002);
			}
			
			// 上传成功后，更新所属的附件
			/*
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}*/			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("api情景保存失败：".$e->getMessage());
			return $this->api_json('情景保存失败:'.$e->getMessage(), 4001);
		}
		
		return $this->api_json('提交成功', 0, array('id'=>$id));
	}
}

