<?php
/**
 * 收藏/喜欢
 * @author purpen
 */
class Sher_App_Action_Favorite extends Sher_App_Action_Base {
	public $stash = array(
		
	);
	
	protected $exclude_method_list = array('execute','ajax_load_list');

	/**
	 * 默认入口
	 */
	public function execute() {
		
	}
	
	/**
	 * 初始化互动操作
	 */
	public function ajax_done(){
		$id = $this->stash['id'];
		$type = $this->stash['type'];
		$event = $this->stash['event'];
		if (empty($id) || empty($type) || empty($event)){
			$this->ajax_json('缺少请求参数', true);
		}
		
		$user_id = $this->visitor->id;
		$data = array();
		
		$model = new Sher_Core_Model_Favorite();
		// 验证是否收藏
		if ($event == Sher_Core_Model_Favorite::EVENT_FAVORITE){
			$data['favorited'] = $model->check_favorite($user_id, $id, $type);
		} else if ($event == Sher_Core_Model_Favorite::EVENT_LOVE){
			$data['loved'] = $model->check_loved($user_id, $id, $type);
		}
		
		return $this->ajax_json('操作成功', false, null, $data);
	}
	
	/**
	 * 收藏/关注
	 */
	public function ajax_favorite(){
		$id = $this->stash['id'];
		$type = $this->stash['type'];
		
		if(empty($id) || empty($type)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		$new_fav = false;
		try{
			$model = new Sher_Core_Model_Favorite();
			$fav_info = array(
				'type' => $type,
			);
			if (!$model->check_favorite($this->visitor->id, $id, $type)) {
				$ok = $model->add_favorite($this->visitor->id, $id, $fav_info);
			}
      if($ok){
        $new_fav = true;
      }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试:'.$e->getMessage(), true);
		}
        
		// 获取计数
		$favorite_count = $this->remath_count($id, $type);

    if(isset($this->visitor->avatar['mini'])){
        $avatar = Sher_Core_Helper_Url::avatar_cloud_view_url($this->visitor->avatar['mini'], 'avn.jpg');
    }else{
        $avatar = Doggy_Config::$vars['app.url.packaged'].'/images/avatar_default_mini.jpg';
    }

    $data = array(
        'favorite_count' => $favorite_count,
        'new_fav'     => $new_fav,
        'avatar'     => $avatar,
        'nickname'   => $this->visitor->nickname,
        'city'       => $this->visitor->city,
        'job'        => $this->visitor->profile['job'],
        'user_id'    => $this->visitor->id,
    );
		
		return $this->ajax_json('操作成功',false,'', $data);
	}
	
	/**
	 * 取消收藏
	 */
	public function ajax_cancel_favorite(){
		$id = $this->stash['id'];
		$type = $this->stash['type'];
		if(empty($id) || empty($type)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$ok = $model->remove_favorite($this->visitor->id, $id, $type);

		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		// 获取计数
		$favorite_count = $this->remath_count($id, $type);
		
		return $this->ajax_json('操作成功',false,'',array('favorite_count'=>$favorite_count));
	}
	
	/**
	 * 计算总数
	 */
	protected function remath_count($id, $type, $filed='favorite_count'){
		$count = 0;
		switch($type){
			case Sher_Core_Model_Favorite::TYPE_TOPIC:
				$model = new Sher_Core_Model_Topic();
				$result = $model->load((int)$id);
				break;
			case Sher_Core_Model_Favorite::TYPE_PRODUCT:
				$model = new Sher_Core_Model_Product();
				$result = $model->load((int)$id);
				break;
			case Sher_Core_Model_Favorite::TYPE_STUFF:
				$model = new Sher_Core_Model_Stuff();
				$result = $model->load((int)$id);
				break;
			case Sher_Core_Model_Favorite::TYPE_COOPERATE:
				$model = new Sher_Core_Model_Cooperation();
				$result = $model->load((int)$id);
				break;
			case Sher_Core_Model_Favorite::TYPE_ALBUMS:
				$model = new Sher_Core_Model_Albums();
				$result = $model->load((int)$id);
				break;
			case Sher_Core_Model_Favorite::TYPE_COMMENT:
				$model = new Sher_Core_Model_Comment();
				$result = $model->load((string)$id);
				break;
			case Sher_Core_Model_Favorite::TYPE_APP_SUBJECT:
				$model = new Sher_Core_Model_SpecialSubject();
				$result = $model->load((int)$id);
				break;
			case Sher_Core_Model_Favorite::TYPE_APP_SCENE_SUBJECT:
				$model = new Sher_Core_Model_SceneSubject();
				$result = $model->load((int)$id);
				break;
		}
		if(!empty($result)){
 		  $count = $result[$filed];     
		}
		return $count;
	}
    
	/**
	 * 点赞
	 */
	public function ajax_laud(){
		$id = $this->stash['id'];
		$type = $this->stash['type'];
		if(empty($id) || empty($type)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$fav_info = array(
				'type' => $type,
			);
            
            $newadd = false;
			if(!$model->check_loved($this->visitor->id, $id, $type)){
				$ok = $model->add_love($this->visitor->id, $id, $fav_info);
                $newadd = true;
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试:'.$e->getMessage(), true);
		}
        
		// 获取计数
		$love_count = $this->remath_count($id, $type, 'love_count');
        if(isset($this->visitor->avatar['mini'])){
            $avatar = Sher_Core_Helper_Url::avatar_cloud_view_url($this->visitor->avatar['mini'], 'avn.jpg');
        }else{
            $avatar = Doggy_Config::$vars['app.url.packaged'].'/images/avatar_default_mini.jpg';
        }
        
        $data = array(
            'love_count' => $love_count,
            'newadd'     => $newadd,
            'avatar'     => $avatar,
            'nickname'   => $this->visitor->nickname,
            'city'       => $this->visitor->city,
            'job'        => isset($this->visitor->profile['job']) ? $this->visitor->profile['job'] : '',
            'user_id'    => $this->visitor->id,
        );
        
		return $this->ajax_json('操作成功', false, '', $data);
	}
	
	/**
	 * 取消点赞
	 */
	public function ajax_cancel_laud(){
		$id = $this->stash['id'];
		$type = $this->stash['type'];
		if(empty($id) || empty($type)){
			return $this->ajax_json('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Favorite();
			$ok = $model->cancel_love($this->visitor->id, $id, $type);

		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		// 获取计数
		$love_count = $this->remath_count($id, $type, 'love_count');
		
		return $this->ajax_json('操作成功', false, '', array('love_count' => $love_count,'user_id' => $this->visitor->id));
	}

  /**
   * 自动加载获取
   */
    public function ajax_load_list(){
      $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 2;
      $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
      $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
      $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
      $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 0;
      $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
      $target_id = isset($this->stash['target_id']) ? $this->stash['target_id'] : null;
      
      $service = Sher_Core_Service_Favorite::instance();
        
      $query = array();
        
		  if($target_id){
        if((int)$type == 3){
          $query['target_id'] = (string)$target_id;     
        }else{
  			  $query['target_id'] = (int)$target_id;    
        }
		  }

      if($type){
        $query['type'] = $type;
      }
      if($event){
        $query['event'] = $event;
      }
      if($user_id){
        $query['user_id'] = $user_id;
      }
        
      $options['page'] = $page;
      $options['size'] = $size;

      $options['sort_field'] = 'time';
        
      // 排序
      switch ((int)$sort) {
        case 0:
          $options['sort_field'] = 'time';
          break;
      }

      // 限制输出字段
      $some_fields = array();
      $options['some_fields'] = $some_fields;
      
      $resultlist = $service->get_like_list($query,$options);
      $next_page = 'no';
      if(isset($resultlist['next_page'])){
          if((int)$resultlist['next_page'] > $page){
              $next_page = (int)$resultlist['next_page'];
          }
      }

        $product_model = new Sher_Core_Model_Product();
        
        $max = count($resultlist['rows']);
        for($i=0;$i<$max;$i++){
            $obj = $resultlist['rows'][$i];
            $symbol = isset($resultlist['rows'][$i]['user']['symbol']) ? $resultlist['rows'][$i]['user']['symbol'] : 0;
            if(!empty($symbol)){
              $s_key = sprintf("symbol_%d", $symbol);
              $resultlist['rows'][$i]['user'][$s_key] = true;
            }

            // 过滤用户表
            if(isset($resultlist['rows'][$i]['user'])){
              $resultlist['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($resultlist['rows'][$i]['user'], array('symbol_1', 'symbol_2'));
            }

            switch($obj['type']){
                // 产品
            case 1:
                $product = array();
                if(isset($obj['product'])){
                    $product['_id'] = $obj['product']['_id'];
                    $product['title'] = $obj['product']['title'];
                    $product['short_title'] = $obj['product']['short_title'];
                    $product['wap_view_url'] = $obj['product']['wap_view_url'];
                    $product['cover'] = $obj['product']['cover'];
                    $product['sale_price'] = $obj['product']['sale_price'];
                    $product['is_product'] = $obj['product']['stage']==9 ? true : false;

                  // tips
                  if($obj['product']['tips_label']==1){
                    $product['new_tips'] = true;
                  }elseif($obj['product']['tips_label']==2){
                    $product['hot_tips'] = true;
                  }

                }
                $resultlist['rows'][$i]['product'] = $product;
                break;
                // 专题
            case 13:
                $scene_subject = array();
                if(isset($obj['scene_subject'])){
                    $scene_subject['_id'] = $obj['scene_subject']['_id'];
                    $scene_subject['title'] = $obj['scene_subject']['title'];
                    $scene_subject['short_title'] = $obj['scene_subject']['short_title'];
                    $scene_subject['cover_url'] = $obj['scene_subject']['cover']['thumbnails']['aub']['view_url'];
                    $scene_subject['banner_url'] = $obj['scene_subject']['banner']['thumbnails']['aub']['view_url'];
                    $scene_subject['begin_time_at'] = date('m/d', $obj['scene_subject']['begin_time']);
                    $scene_subject['end_time_at'] = date('m/d', $obj['scene_subject']['end_time']);
                    $scene_subject['wap_view_url'] = sprintf("%s/scene_subject/view?id=%d", Doggy_Config::$vars['app.url.wap'], $obj['scene_subject']['_id']);

                    // 产品
                    $product_arr = array();
                    if(!empty($obj['scene_subject']['product_ids'])){
                        for($j=0;$j<count($obj['scene_subject']['product_ids']);$j++){
                            $product = $product_model->extend_load((int)$obj['scene_subject']['product_ids'][$j]);
                            if(empty($product) || $product['deleted']==1 || $product['published']==0) continue;
                            $row = array(
                                '_id' => $product['_id'],
                                'title' => $product['short_title'],
                                'cover_url' => $product['cover']['thumbnails']['apc']['view_url'],
                                'banner_url' => $product['banner']['thumbnails']['aub']['view_url'],
                                'summary' => $product['summary'],
                                'sale_price' => $product['sale_price'],
                                'wap_view_url' => $product['wap_view_url'],
                            );
                            array_push($product_arr, $row);
                        }
                    }
                    $scene_subject['products'] = $product_arr;

                }
                $resultlist['rows'][$i]['scene_subject'] = $scene_subject;
                break; 

                // 品牌
            case 14:
                $scene_brand = array();
                if(isset($obj['scene_brand'])){
                    $scene_brand['_id'] = (string)$obj['scene_brand']['_id'];
                    $scene_brand['title'] = $obj['scene_brand']['title'];
                    $scene_brand['feature'] = $obj['scene_brand']['feature'];
			        // 头像 url
                    $scene_brand['cover_url'] = $obj['scene_brand']['cover']['thumbnails']['huge']['view_url'];
                    $scene_brand['banner_url'] = $obj['scene_brand']['banner']['thumbnails']['aub']['view_url'];
                    $scene_brand['product_cover_url'] = $obj['scene_brand']['product_cover']['thumbnails']['apc']['view_url'];
                    $scene_brand['wap_view_url'] = sprintf("%s/scene_brand/view?id=%s", Doggy_Config::$vars['app.url.wap'], $obj['scene_brand']['_id']);
                
                }
                $resultlist['rows'][$i]['scene_brand'] = $scene_brand;
                break; 

            }



        } //end for

        $resultlist['nex_page'] = $next_page;
        
        return $this->ajax_json('', false, '', $resultlist);
    }
	
}
