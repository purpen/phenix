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

	/**
	 * 上传图片
     * @param id: 对象ID；tmp: 流文件；type: 1.地盘封面； 2.地盘亮点(草稿)
	 */
	public function upload_asset(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $tmp = isset($this->stash['tmp']) ? $this->stash['tmp'] : null;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
        $user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
        }

        if(empty($id) || empty($tmp)){
 		    return $this->api_json('缺少请求参数！', 3001);
        }

        // 上传封面
        $file = base64_decode(str_replace(' ', '+', $tmp));
        $image_info = Sher_Core_Util_Image::image_info_binary($file);
        if($image_info['stat']==0){
            return $this->api_json($image_info['msg'], 3002);
        }
        if (!in_array(strtolower($image_info['format']),array('jpg','png','jpeg'))) {
            return $this->api_json('图片格式不正确！', 3003);
        }

        $domain = null;
         $asset_type = 0;
        switch($type){
            case 1:
                $domain = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
                $asset_type = Sher_Core_Model_Asset::TYPE_SCENE_SCENE;
                $id = (int)$id;
                break;
            case 2:
                $domain = Sher_Core_Util_Constant::STROAGE_SCENE_SCENE;
                $asset_type = Sher_Core_Model_Asset::TYPE_SCENE_DRAFT;
                $id = (int)$id;
                break;
            default: 
                $domain = null;
                $asset_type = 0;
        }

        if(empty($domain) || empty($asset_type)){
            return $this->api_json('type类型不正确！', 3004);
        }

        $params = array();
        $asset_id = null;
        $new_file_id = new MongoId();
        $params['domain'] = $domain;
        $params['asset_type'] = $asset_type;
        $params['filename'] = $new_file_id.'.jpg';
        $params['parent_id'] = $id;
        $params['user_id'] = $user_id;
        $params['image_info'] = $image_info;
        $result = Sher_Core_Util_Image::api_image($file, $params);
        
        if($result['stat']){
            $asset_id = $result['asset']['id'];
            $filepath = Sher_Core_Helper_Url::asset_qiniu_view_url($result['asset']['filepath'], 'hu.jpg');
        }else{
            return $this->api_json('上传失败！', 3005);            
        } 

		return $this->api_json('请求成功', 0, array('id'=>$id, 'asset_id'=>$asset_id, 'filepath'=>array('huge'=>$filepath)));
	}

	
}

