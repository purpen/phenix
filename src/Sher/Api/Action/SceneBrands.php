<?php
/**
 * 品牌管理
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_SceneBrands extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'view');

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
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		$kind = isset($this->stash['kind'])?(int)$this->stash['kind']:1;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$mark = isset($this->stash['mark']) ? strtolower($this->stash['mark']) : null;
		$self_run = isset($this->stash['self_run']) ? (int)$this->stash['self_run'] : 0;
		$from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 1;
        $title = isset($this->stash['title']) ? $this->stash['title'] : null;
		
		$some_fields = array(
			'_id'=>1, 'title'=>1, 'des'=>1, 'kind'=>1, 'cover_id'=>1, 'banner_id'=>1, 'brand'=>1, 'used_count'=>1,'stick'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1, 'mark'=>1, 'self_run'=>1, 'from_to'=>1,
		);
		
		$query   = array();
		$options = array();

        $query['kind'] = $kind;
		
		if($stick){
			if($stick == 1){
				$query['stick'] = 1;
			}
			if($stick == -1){
				$query['stick'] = 0;
			}
		}

		if($from_to){
			if($from_to == 1){
				$query['from_to'] = 1;
			}
			if($from_to == -1){
				$query['from_to'] = 0;
			}
		}

        // 首字母索引
        if(!empty($mark)){
            $query['mark'] = $mark;
        }

        // 是否自营
        if(!empty($self_run)){
            if($self_run==-1){
                $query['self_run'] = 0;
            }else{
                $query['self_run'] = 1;
            }
        }

        // 模糊查标签
        if(!empty($title)){
            $query['title'] = array('$regex'=>$title);
        }
		
		// 状态
		$query['status'] = 1;
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'stick:update';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_SceneBrands::instance();
        $result = $service->get_scene_brands_list($query, $options);
		
		// 重建数据结果
		foreach($result['rows'] as $k => $v){
            $result['rows'][$k]['_id'] = (string)$result['rows'][$k]['_id'];
			$result['rows'][$k]['cover_url'] = $result['rows'][$k]['cover']['thumbnails']['huge']['view_url'];
			$result['rows'][$k]['banner_url'] = $result['rows'][$k]['banner']['thumbnails']['aub']['view_url'];
		}
		
		// 过滤多余属性
        $filter_fields  = array('cover_id', 'banner_id', 'banner', 'cover','__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/*
	 * 品牌详情
	 */
	public function view(){
		
		$id = isset($this->stash['id']) ? $this->stash['id'] : '';
		
		if (empty($id)) {
      return $this->api_json('请求失败，缺少必要参数!', 3001);
    }
		
		$model = new Sher_Core_Model_SceneBrands();
        $result = $model->load($id);

		if (empty($result)) {
            return $this->api_json('品片不存在或已删除!', 3002);
        }

		$result  = $model->extended_model_row($result);
		
		$data = array();
		$data['_id'] = (string)$result['_id'];
		$data['title'] = $result['title'];
		$data['kind'] = $result['kind'];
		$data['item_count'] = $result['item_count'];
		$data['mark'] = $result['mark'];
		$data['des'] = $result['des'];
		$data['used_count'] = $result['used_count'];
		$data['self_run'] = isset($result['self_run']) ? $result['self_run'] : 0;
		$data['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['created_on']);
		$data['cover_url'] = $result['cover']['thumbnails']['huge']['view_url'];
		$data['banner_url'] = isset($result['banner']) ? $result['banner']['thumbnails']['aub']['view_url'] : null;

		return $this->api_json('请求成功', 0, $data);
	}

    /**
     * 添加品牌
     */
    public function submit(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $user_id = $this->current_user_id;
        $title = isset($this->stash['title']) ? trim($this->stash['title']) : null;
        if(empty($title)){
            return $this->api_json('缺少请求参数!', 3001);
        }
		$model = new Sher_Core_Model_SceneBrands();

        if(!$model->check_title($title)){
            return $this->api_json('品牌已存在!', 3002);       
        }

        $row = array();
        $row['title'] = $title;

        if(empty($id)){
            $row['user_id'] = $user_id;
            $row['from_to'] = 2;
            $ok = $model->apply_and_save($row);
        }else{
            $ok = $model->apply_and_update($row);
        }

        if(!$ok){
            return $this->api_json('保存失败!', 3003);
        }

        if(empty($id)){
            $brand = $model->get_data();
            $id = (string)$brand['_id'];       
        }

        // 更新全文索引
        Sher_Core_Helper_Search::record_update_to_dig($id, 14);

        return $this->api_json('success', 0, array('id'=>$id));
    
    }
}

