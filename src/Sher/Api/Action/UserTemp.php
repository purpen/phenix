<?php
/**
 * 产品、品牌临时库
 * @author tianshuai
 */
class Sher_Api_Action_UserTemp extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'view', 'getlist');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}

  /**
   * 添加
   */
  public function add(){
    $user_id = $this->current_user_id;

    $title = isset($this->stash['title']) ? $this->stash['title'] : null;
    // 类型：1.产品；2.品牌
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
    $brand_id = isset($this->stash['brand_id']) ? $this->stash['brand_id'] : '';
    $brand_name = isset($this->stash['brand_name']) ? $this->stash['brand_name'] : '';

    $rows = array(
      'user_id' => $user_id,
      'title' => $title,
      'type' => $type,
      'brand_id' => $brand_id,
      'brand_name' => $brand_name,
    );

    $user_temp_model = new Sher_Core_Model_UserTemp();
    $ok = $user_temp_model->apply_and_save($rows);
    if($ok){
      $id = $user_temp_model->id;
    	return $this->api_json('创建失败!', 0, array('id'=>$id)); 
    }else{
   	  return $this->api_json('创建失败!', 3003);
    }

  }

    /**
     * 详情
     */
    public function view(){
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        if(empty($id)){
   	        return $this->api_json('缺少请求参数!', 3001);
        }
        $user_temp_model = new Sher_Core_Model_UserTemp();
        $user_temp = $user_temp_model->extend_load($id);
        if(empty($user_temp)){
     	    return $this->api_json('数据不存在!', 3002);      
        }
        return $this->api_json('success', 0, $user_temp);
    }


  /**
   * 删除产品
   */
  public function deleted(){
    $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
    if(empty($id)){
      return $this->api_json('缺少请求参数!', 3000);  
    }
    $user_id = $this->current_user_id;

		try{
			$user_temp_model = new Sher_Core_Model_UserTemp();
			
      $user_temp = $user_temp_model->load($id);
      if(empty($user_temp)){
        return $this->api_json('删除的内容不存在！', 3001);       
      }
      if($user_temp['user_id'] != $user_id){
        return $this->api_json('没有权限！', 3002);        
      }
      
      $user_temp_model->remove($id);
      $user_temp_model->mock_after_remove($id, $user_temp);

		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败,请重新再试', 3004);
		}
		return $this->api_json('删除成功！', 0, array('id'=>$id));
  }


}

