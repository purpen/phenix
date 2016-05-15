<?php
/**
 * 品牌管理
 * @author caowei＠taihuoniao.com
 */
class Sher_Api_Action_SceneBrands extends Sher_Api_Action_Base {
	
	public $stash = array(
		'id'   => '',
        'page' => 1,
        'size' => 10,
	);
	
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
		$size = isset($this->stash['size'])?(int)$this->stash['size']:1000;
		
		$some_fields = array(
			'_id'=>1, 'title'=>1, 'des'=>1, 'cover_id'=>1, 'used_count'=>1,'stick'=>1, 'status'=>1, 'created_on'=>1, 'updated_on'=>1,
		);
		
		$query   = array();
		$options = array();
		
		// 请求参数
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		
		if($stick){
			if($stick == 1){
				$query['stick'] = 1;
			}
			if($stick == -1){
				$query['stick'] = 0;
			}
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
				$options['sort_field'] = 'stick';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		
		// 开启查询
        $service = Sher_Core_Service_SceneBrands::instance();
        $result = $service->get_scene_brands_list($query, $options);
		
		if($sort == 2){
			$total_count = abs($result['rows'][0]['used_count'] - $result['rows'][count($result['rows'])-1]['used_count']);
			$every_count = round($total_count/3);
		}
		
		// 重建数据结果
		foreach($result['rows'] as $k => $v){
			
			// 返回品牌大小类型
			$result['rows'][$k]['brands_size_type'] = 1;
			if(isset($every_count) && $every_count){
				if($v['used_count']>=0 && $v['used_count'] < $every_count){
					$result['rows'][$k]['brands_size_type'] = 1;
				}
				if($v['used_count']>=$every_count && $v['used_count'] < 2*$every_count){
					$result['rows'][$k]['brands_size_type'] = 2;
				}
				if($v['used_count']>=2*$every_count && $v['used_count'] < 3*$every_count){
					$result['rows'][$k]['brands_size_type'] = 3;
				}
			}
			
			$result['rows'][$k]['cover_url'] = $result['rows'][$k]['cover']['thumbnails']['huge']['view_url'];
		}
		
		// 过滤多余属性
        $filter_fields  = array('cover_id','cover','__extend__');
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);
		
		//var_dump($result['rows']);die;
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
		$scene_product_model = new Sher_Core_Model_SceneProduct();
		$result  = $model->extend_load($id);
		
		if (!$result) {
            return $this->api_json('请求内容为空!', 3002);
        }
		
		$data = array();
		$data['_id'] = (string)$result['_id'];
		$data['title'] = $result['title'];
		$data['des'] = $result['des'];
		$data['used_count'] = $result['used_count'];
		$data['created_at'] = Sher_Core_Helper_Util::relative_datetime($result['created_on']);
		$data['cover_url'] = $result['cover']['thumbnails']['huge']['view_url'];

    // 从商品取一张封面
    $product = $scene_product_model->first(array('brand_id'=>$id));
    $asset_id = null;
    if($product){
      if(isset($product['banner_id']) && !empty($product['banner_id'])){
        $asset_id = $product['banner_id'];
      }else{
        if(!empty($product['banner_asset_ids'])){
          $asset_id = $product['banner_asset_ids'][0];
        }
      }
    }
    if($asset_id){
      $asset_model = new Sher_Core_Model_Asset();
      $asset = $asset_model->extend_load($asset_id);
      if($asset){
        $data['banner_url'] = $asset['thumbnails']['aub']['view_url'];
      }else{
        $data['banner_url'] = null;     
      }
    }else{
      $data['banner_url'] = null;  
    }

		return $this->api_json('请求成功', 0, $data);
	}
}

