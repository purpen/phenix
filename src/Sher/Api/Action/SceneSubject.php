<?php
/**
 * API 接口
 * 情境专题
 * @author tianshuai
 */
class Sher_Api_Action_SceneSubject extends Sher_Api_Action_Base {

	protected $filter_user_method_list = array('execute', 'getlist', 'view');
	
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
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		
		$some_fields = array(
			'_id'=>1, 'title'=>1, 'category_id'=>1, 'publish'=>1,
			'cover_id'=>1, 'tags'=>1, 'summary'=>1, 'user_id'=>1, 'kind'=>1, 'stick'=>1,
			'status'=>1, 'view_count'=>1, 'comment_count'=>1, 'love_count'=>1, 'favorite_count'=>1,
		);
		
		// 请求参数
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
			
		$query   = array();
		$options = array();
		

		$query['publish'] = 1;

		// 查询条件
		if($user_id){
			$query['user_id'] = $user_id;
		}
		
		if($stick){
			if($stick==-1){
				$query['stick'] = 0;
			}else{
				$query['stick'] = 1;
			}
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'stick:latest';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		// 开启查询
		$service = Sher_Core_Service_SceneSubject::instance();
		$result = $service->get_scene_subject_list($query, $options);
		
		// 重建数据结果
        $data = array();
		for($i=0;$i<count($result['rows']);$i++){
            foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : null;
			}
			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['aub']['view_url'];
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 详情
	 */
	public function view(){
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('访问的专题不存在！', 3000);
		}
        $user_id = $this->current_user_id;
		
		$model = new Sher_Core_Model_SceneSubject();
		$scene_subject = $model->load($id);

		if(empty($scene_subject)) {
				return $this->api_json('访问的专题不存在！', 3001);
		}

		if($scene_subject['publish']==0){
			return $this->api_json('访问的专题未发布！', 3002);
		}

		if($scene_subject['status']==0){
			return $this->api_json('访问的专题已禁用！', 3003);
		}

        $some_fields = array(
          '_id', 'title', 'short_title', 'tags', 'tags_s', 'kind',
          'cover_id', 'category_id', 'summary', 'status', 'publish', 'user_id',
          'stick', 'love_count', 'favorite_count', 'view_count', 'comment_count',
        );

		$scene_subject = $model->extended_model_row($scene_subject);
		$scene_subject['content'] = null;

        // 重建数据结果
        $data = array();
        for($i=0;$i<count($some_fields);$i++){
          $key = $some_fields[$i];
          $data[$key] = isset($scene_subject[$key]) ? $scene_subject[$key] : null;
        }
        // 封面图url
        $data['cover_url'] = $scene_subject['cover']['thumbnails']['aub']['view_url'];

        //验证是否收藏或喜欢
        $data['is_love'] = 0;
        if(!empty($user_id)){
            $fav = new Sher_Core_Model_Favorite();
            $data['is_love'] = $fav->check_loved($user_id, $scene_subject['_id'], 13) ? 1 : 0;       
        }

        $data['content_view_url'] = sprintf('%s/view/scene_subject_show?id=%d', Doggy_Config::$vars['app.url.api'], $scene_subject['_id']);

        // 分享内容
        $data['share_view_url'] = sprintf("%s/scene_subject/view?id=%d", Doggy_Config::$vars['app.url.wap'], $data['_id']);
        $data['share_desc'] = Doggy_Dt_Filters_String::truncate(strip_tags($data['summary']), 80);
		
		// 增加pv++
		$model->inc_counter('view_count', 1, $id);

		return $this->api_json('请求成功', 0, $data);
	}
	
}

