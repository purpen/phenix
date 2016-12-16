<?php
/**
 * 品牌
 * @author tianshuai
 */
class Sher_App_Action_SceneBrand extends Sher_App_Action_Base {
	
	public $stash = array(

	);

	
	protected $exclude_method_list = array('execute', 'ajax_fetch_more');
	
	/**
	 * 活动
	 */
	public function execute(){

	}

    /**
     * 自动加载获取
     */
    public function ajax_fetch_more(){
        
		$page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
		$size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$kind = isset($this->stash['kind'])?(int)$this->stash['kind']:1;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$mark = isset($this->stash['mark']) ? strtolower($this->stash['mark']) : null;
		$self_run = isset($this->stash['self_run']) ? (int)$this->stash['self_run'] : 0;
		$from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 1;
        $title = isset($this->stash['title']) ? $this->stash['title'] : null;

        // 是否使用缓存
        $use_cache = isset($this->stash['use_cache']) ? (int)$this->stash['use_cache'] : 0;
        
        $query = array();
        $options = array();
        $result = array();
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
        
        $options['page'] = $page;
        $options['size'] = $size;

        // 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'stick';
				break;
		}

        //限制输出字段
		$some_fields = array(
            '_id'=>1, 'title'=>1, 'des'=>1, 'kind'=>1, 'cover_id'=>1, 'cover'=>1, 'banner_id'=>1, 'brand'=>1,
            'used_count'=>1,'stick'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1, 'mark'=>1, 'stick_on'=>1,
            'self_run'=>1, 'from_to'=>1, 'product_cover_id'=>1, 'product_cover'=>1, 'feature'=>1,
		);
        $options['some_fields'] = $some_fields;

        $r_key = sprintf("wap:scene_brand:%s_%s_%s_%s_%s_%s_%s_%s_%s", $type, $user_id, $kind, $stick, $self_run, $from_to, $sort, $page, $size);
        $redis = new Sher_Core_Cache_Redis();

        // 从redis获取 
        if($use_cache){
            $result = $redis->get($r_key);
            if($result){
                $result = json_decode($result, true);
            }       
        }
        
        // 无缓存读数据库
        if(empty($result)){
            // 开启查询
            $service = Sher_Core_Service_SceneBrands::instance();
            $result = $service->get_scene_brands_list($query, $options);

            $next_page = 'no';
            if(isset($result['next_page'])){
                if((int)$result['next_page'] > $page){
                    $next_page = (int)$result['next_page'];
                }
            }
            
            $max = count($result['rows']);

            // 重建数据结果
            $data = array();
            for($i=0;$i<$max;$i++){
                $obj = $result['rows'][$i];

                foreach($some_fields as $key=>$value){
                    $data[$i][$key] = isset($obj[$key]) ? $obj[$key] : null;
                }
                $data[$i]['_id'] = (string)$obj['_id'];
                // 头像 url
                $data[$i]['cover_url'] = $obj['cover']['thumbnails']['huge']['view_url'];
                // Banner url
                $data[$i]['banner_url'] = $obj['banner']['thumbnails']['aub']['view_url'];
                // product cover url
                $data[$i]['product_cover_url'] = $obj['product_cover']['thumbnails']['apc']['view_url'];

                $data[$i]['wap_view_url'] = sprintf("%s/scene_brand/view?id=%s", Doggy_Config::$vars['app.url.wap'], $data[$i]['_id']);

            } //end for

            $result['rows'] = $data;
            $result['nex_page'] = $next_page;

            // 写入缓存
            if(!empty($use_cache) && !empty($result)){
                $redis->set($r_key, json_encode($result), Sher_Core_Util_Constant::REDIS_CACHE_EXPIRED);
            }

        }   // endif !cache

        $result['type'] = $type;
        $result['page'] = $page;
        $result['sort'] = $sort;
        $result['size'] = $size;
        
        return $this->ajax_json('success', false, '', $result);
    }


}

