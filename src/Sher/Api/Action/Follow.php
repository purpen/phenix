<?php
/**
 * API 接口
 * 关注用户
 * @author caowei@taihuoniao.com
 */
class Sher_Api_Action_Follow extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute','get_list','ajax_follow','ajax_cancel_follow');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->get_list();
	}

	/**
	 * 通用列表
	 */
	public function get_list(){
		
		// user_id=10
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:100;
		
		$some_fields = array(
			'_id'=>1, 'user_id'=>1, 'follow_id'=>1, 'group_id'=>1, 'is_read'=>1, 'type'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 请求参数
		$user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 10;
        $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$follow_type = isset($this->stash['follow_type']) ? (int)$this->stash['follow_type'] : 0;
        $find_type = isset($this->stash['find_type']) ? (int)$this->stash['find_type'] : 1;
		
		if($find_type){
            if($find_type == 1){
                $query['user_id'] = $user_id; // 自己关注的人
            }else if($find_type == 2){
                $query['follow_id'] = $user_id; // 自己的粉丝
            }
		}
        
        if($follow_type){
			$query['type'] = $follow_type;
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		//var_dump($query);die;
		
		// 开启查询
        $service = Sher_Core_Service_Follow::instance();
        $result = $service->get_follow_list($query, $options);
		
		//var_dump($result);die;
		// 重建数据结果
		foreach($result['rows'] as $k => $v){
            $follow = array();
            if($find_type == 1){
                // 自己关注的人
                if(isset($result['rows'][$k]['follow']['_id'])){
                    $follow['user_id'] = isset($result['rows'][$k]['follow']['_id']) ? $result['rows'][$k]['follow']['_id'] : 0;
                    $follow['account'] = isset($result['rows'][$k]['follow']['account']) ? $result['rows'][$k]['follow']['account'] : '';
                    $follow['nickname'] = isset($result['rows'][$k]['follow']['nickname']) ? $result['rows'][$k]['follow']['nickname'] : '';
                    $follow['avatar_url'] = isset($result['rows'][$k]['follow']['big_avatar_url']) ? $result['rows'][$k]['follow']['big_avatar_url'] : '';
                    $follow['summary'] = isset($result['rows'][$k]['follow']['summary']) ? $result['rows'][$k]['follow']['summary'] : '';
                    $follow['follow_ext']['rank_point'] = $result['rows'][$k]['follow_ext']['rank_point'];
                    $follow['follow_ext']['user_rank'] = $result['rows'][$k]['follow_ext']['user_rank']['title'];
                }
            }else if($find_type == 2){
                // 自己的粉丝
                if(isset($result['rows'][$k]['fans']['_id'])){
                    $follow['user_id'] = isset($result['rows'][$k]['fans']['_id']) ? $result['rows'][$k]['fans']['_id'] : 0;
                    $follow['account'] = isset($result['rows'][$k]['fans']['account']) ? $result['rows'][$k]['fans']['account'] : '';
                    $follow['nickname'] = isset($result['rows'][$k]['fans']['nickname']) ? $result['rows'][$k]['fans']['nickname'] : '';
                    $follow['avatar_url'] = isset($result['rows'][$k]['fans']['big_avatar_url']) ? $result['rows'][$k]['fans']['big_avatar_url'] : '';
                    $follow['summary'] = isset($result['rows'][$k]['fans']['summary']) ? $result['rows'][$k]['fans']['summary'] : '';
                    $follow['fans_ext']['rank_point'] = $result['rows'][$k]['fans_ext']['rank_point'];
                    $follow['fans_ext']['user_rank'] = $result['rows'][$k]['fans_ext']['user_rank']['title'];
                }
            }
            $result['rows'][$k]['follows'] = $follow;
		}
		
		// 过滤多余属性
        $filter_fields  = array('fans','follow','fans_ext','follow_ext','__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		//var_dump($result['rows']);die;
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 关注
	 */
	public function ajax_follow(){
		
		$user_id = $this->current_user_id;
		$user_id = 10;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
		
		$follow_id= isset($this->stash['follow_id']) ? (int)$this->stash['follow_id'] : 0;
        
		if(empty($follow_id)){
			return $this->api_json('缺少请求参数！', 3001);
		}
        
        if($follow_id == $user_id){
			return $this->api_json('请求失败,自己无法关注自己', 3002);
		}
        
        // 验证是否超过最大关注数
        $user_model = new Sher_Core_Model_User();
		if($user_model->follow_count >= Sher_Core_Model_Follow::MAX_FOLLOW){
			return $this->api_json("请求失败,关注人数不能超过".Sher_Core_Model_Follow::MAX_FOLLOW."个", 3003);
		}
		
		try{
			$model = new Sher_Core_Model_Follow();
			// 添加关注
            $is_both = false;
            if(!$model->has_exist_ship($user_id,$follow_id)){
                $data['user_id'] = (int)$user_id;
                $data['follow_id'] = (int)$follow_id;
                
                // 验证关注者是否关注了自己
                if($model->has_exist_ship($follow_id,$user_id)){
                    $data['type'] = Sher_Core_Model_Follow::BOTH_TYPE;
                    $is_both = true;
                }
                
                $model->create($data);
                
                // 更新关注数、粉丝数
                $user_model->inc_counter('fans_count', $follow_id);
                $user_model->inc_counter('follow_count', $user_id);
                
                // 更新粉丝相互关注状态
                if($is_both){
                    $some_data['type'] = Sher_Core_Model_Follow::BOTH_TYPE;
                    $update['user_id'] = (int)$follow_id;
                    $update['follow_id'] = (int)$user_id;
                    
                    $ship->update_set($update,$some_data);
                }
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3003);
		}
		
		return $this->api_json('操作成功', 0, array('follow_id'=>$follow_id));
	}
	
	/**
	 * 取消关注
	 */
	public function ajax_cancel_follow(){
		
		$user_id = $this->current_user_id;
		
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
		}
        
        $follow_id= isset($this->stash['follow_id']) ? (int)$this->stash['follow_id'] : 0;
        
		if(empty($follow_id)){
			return $this->api_json('缺少请求参数！', 3001);
		}
		
		try{
			
			$model = new Sher_Core_Model_Follow();
			if($model->has_exist_ship($user_id,$follow_id)){
                $query['user_id'] = (int)$user_id;
                $query['follow_id'] = (int)$follow_id;
    
                $model->remove($query);
                
                // 更新关注数、粉丝数
                $user_model = new Sher_Core_Model_User();
                $user_model->dec_counter('fans_count', $follow_id);
                $user_model->dec_counter('follow_count', $user_id);
    
                // 更新粉丝相互关注状态
                $some_data['type'] = Sher_Core_Model_Follow::ONE_TYPE;
                $update['user_id'] = (int)$follow_id;
                $update['follow_id'] = (int)$user_id;
                
                $model->update_set($update,$some_data);
            }
		}catch(Sher_Core_Model_Exception $e){
			return $this->api_json('操作失败:'.$e->getMessage(), 3003);
		}
		
		return $this->api_json('操作成功', 0, array('follow_id'=>$follow_id));
	}
}
