<?php
/**
 * WAPI 共公接口
 * @author tianshuai
 */
class Sher_WApi_Action_Common extends Sher_WApi_Action_Base {
	
	protected $filter_auth_methods = array('execute', 'slide');

	/**
	 * 入口
	 */
	public function execute(){
	}

    /**
     * 获取京东收货地址
     */
    public function fetch_city(){
        // ID
        $oid = isset($this->stash['oid']) ? (int)$this->stash['oid'] : 0;
        // 父ID
        $pid = isset($this->stash['pid']) ? (int)$this->stash['pid'] : 0;
        // 层级
        $layer = isset($this->stash['layer']) ? (int)$this->stash['layer'] : 1;

        $china_city_model = new Sher_Core_Model_ChinaCity();

        $query = array();
        $options = array('page'=>1,'size'=>1000,'sort'=>array('sort'=>-1));
        if($oid){
            $query['oid'] = $oid;
        }
        if($pid){
            $query['pid'] = $pid;
        }
        if($layer){
            $query['layer'] = $layer;
        }
        $query['status'] = 1;

        $rows = $china_city_model->find($query, $options);
        for($i=0;$i<count($rows);$i++){
            $rows[$i]['_id'] = (string)$rows[$i]['_id'];
        }
        $result['rows'] = $rows;
        //print_r($result);
        return $this->wapi_json('success!', 0, $result);
    }

	/**
	 * 广告位轮换图
	 */
	public function slide(){
		$result = array();
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
		$use_cache = isset($this->stash['use_cache']) ? (int)$this->stash['use_cache'] : 1;
		
		// 请求参数
		$space_id = isset($this->stash['space_id']) ? $this->stash['space_id'] : 0;
		$name = isset($this->stash['name']) ? $this->stash['name'] : '';
        $category_name = isset($this->stash['category_name']) ? $this->stash['category_name'] : '';
		if(empty($name) && empty($space_id)){
			return $this->wapi_json('请求参数不足', 3001);
		}

        // 从redis获取 
        if($use_cache){
            $r_key = sprintf("api:slide:%s_%s_%s", $name, $page, $size);
            $redis = new Sher_Core_Cache_Redis();
            $cache = $redis->get($r_key);
            if($cache){
                return $this->wapi_json('请求成功', 0, json_decode($cache, true));
            }       
        }

		// 获取某位置的推荐内容
        if(!empty($name) && empty($space_id)){
			$model = new Sher_Core_Model_Space();
          if(!empty($category_name)){
            $c_name = sprintf("%s_%s", $name, $category_name);
                  $row = $model->first(array('name' => $c_name));
            if(!empty($row)){
              $space_id = (int)$row['_id'];
            }else{
              $row = $model->first(array('name' => $name));
              if(!empty($row)){
                $space_id = (int)$row['_id'];
              }else{
                return $this->wapi_json('请求参数不足', 3002);
              }
            }
          }else{
            $row = $model->first(array('name' => $name));
            if(!empty($row)){
              $space_id = (int)$row['_id'];
            }else{
              return $this->wapi_json('请求参数不足', 3003);
            }
          }

		}

        // 
		$query   = array();
		$options = array();
		
		// 查询条件
		if ($space_id) {
			$query['space_id'] = (int)$space_id;
		}
		
		$query['state'] = Sher_Core_Model_Advertise::STATE_PUBLISHED;
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = 'ordby';
		
        $service = Sher_Core_Service_Advertise::instance();
        $result = $service->get_ad_list($query,$options);
	

        //显示的字段
        $options['some_fields'] = array(
          '_id'=> 1, 'title'=>1, 'space_id'=>1, 'sub_title'=>1, 'web_url'=>1, 'summary'=>1, 'cover_id'=>1, 'type'=>1, 'ordby'=>1, 'kind'=>1,
          'created_on'=>1, 'state'=>1
        );

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$value){
				$data[$i][$key] = $result['rows'][$i][$key];
        }

			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['fileurl'];
		}

		$result['rows'] = $data;

		// 获取单条记录 ????
		if($size == 1 && !empty($result['rows'])){
			//$result = $result['rows'][0];
		}

        if($use_cache){
            $redis->set($r_key, json_encode($result), 300);
        }
		
		return $this->wapi_json('请求成功', 0, $result);
	}
	

}

