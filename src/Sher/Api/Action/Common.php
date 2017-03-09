<?php
/**
 * 公共接口
 * @author tianshuai
 */
class Sher_Api_Action_Common extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute');
	
	/**
	 * 入口
	 */
	public function execute(){
	}
	
	/**
	 * 详情
	 */
	public function delete_asset(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
        }

        if(empty($id)){
 		    return $this->api_json('缺少请求参数！', 3001);
        }
		
		$asset_model = new Sher_Core_Model_Asset();

        $ok = $asset_model->remove($id);
        if(!$ok){
  		    return $this->api_json('删除失败！', 3002);           
        }

		return $this->api_json('请求成功', 0, array('id'=>$id));
	}


	/**
	 * 设置默认封面图
     * @param id: 对象ID；asset_id:图片ID，type: 1.地盘； 2.--
	 */
	public function set_default_cover(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $asset_id = isset($this->stash['asset_id']) ? $this->stash['asset_id'] : null;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
        $user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
        }

        if(empty($id)){
 		    return $this->api_json('缺少请求参数！', 3001);
        }

        $model = null;
        switch($type){
            case 1:
                $id = (int)$id;
                $model = new Sher_Core_Model_SceneScene();
                break;
            default:
                $model = null;
        }

        if(empty($model)){
 		    return $this->api_json('类型不正确！', 3002);  
        }

        $asset_model = new Sher_Core_Model_Asset();
        $asset = $asset_model->find_by_id($asset_id);
        if(empty($asset)){
   		    return $this->api_json('图片不存在或已删除！', 3003);       
        }

        $ok = $model->update_set($id, array('cover_id'=>$asset_id));
        if(!$ok){
  		    return $this->api_json('设置封面图失败！', 3004);           
        }

		return $this->api_json('请求成功', 0, array('id'=>$id));
	}

	
}

