<?php
/**
 * API 接口
 * @author tianshuai
 */
class Sher_Api_Action_Search extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'expanded');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}


	/**
	 * 商品搜索
	 */
	public function getlist(){
		$q = isset($this->stash['q']) ? $this->stash['q'] : null;
        $evt = isset($this->stash['evt']) ? $this->stash['evt'] : 'content';
        $t = isset($this->stash['t']) ? (int)$this->stash['t'] : 3;
        $s = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 1;
        $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
        $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
        $asc = isset($this->stash['asc']) ? 1 : 0;
        $cid = isset($this->stash['cid']) ? (int)$this->stash['cid'] : 0;

		if(empty($q)){
			return $this->api_json('请输入关键词！', 3000);
		}

    $current_user_id = $this->current_user_id;

    // 全文搜索/标签搜索

    $options = array(
      'page' => $page,
      'size' => $size,
      'evt'  => $evt,
      'sort' => $s,
      'asc'  => $asc,
      'cid' => $cid,
      't'    => $t,
    );
    
    $result = Sher_Core_Util_XunSearch::search($q, $options);
    if($result['success']){
      $user_model = new Sher_Core_Model_User();
      $asset_model = new Sher_Core_Model_Asset();
      $product_model = new Sher_Core_Model_Product();
      $topic_model = new Sher_Core_Model_Topic();
      //$scene_model = new Sher_Core_Model_SceneScene();
      $scene_sight_model = new Sher_Core_Model_SceneSight();
      //$scene_product_model = new Sher_Core_Model_SceneProduct();
      $scene_context_model = new Sher_Core_Model_SceneContext();
      $scene_subject_model = new Sher_Core_Model_SceneSubject();
      $scene_brand_model = new Sher_Core_Model_SceneBrands();
        $follow_model = new Sher_Core_Model_Follow();

      $asset_service = Sher_Core_Service_Asset::instance();

      foreach($result['data'] as $k=>$v){

        //封面图
        if($v['cover_id']){
          $asset_obj = $asset_model->extend_load($v['cover_id']);
        }

        $kind = $result['data'][$k]['kind'];
        $cid = $result['data'][$k]['cid'];
        $oid = $result['data'][$k]['oid'];
        $result['data'][$k]['_id'] = $oid;

        // 产品
        if($kind=='Product'){ // 产品
            $obj = $product_model->extend_load((int)$oid);
            // 商品不需要显示详情
            $result['data'][$k]['content'] = null;
            if($obj){
              $result['data'][$k]['title'] = $obj['short_title'];
              $result['data'][$k]['market_price'] = $obj['market_price'];
              $result['data'][$k]['sale_price'] = $obj['sale_price'];
              $result['data'][$k]['stage'] = $obj['stage'];
              $result['data'][$k]['tips_label'] = 0;
            }else{
              $result['data'][$k]['market_price'] = 0;
              $result['data'][$k]['sale_price'] = 0; 
            }

          // 图片尺寸
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['apc']['view_url'];
          }
        }elseif($kind=='Topic'){  // 话题
          // 图片尺寸
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['aub']['view_url'];
          }
        
        }elseif($kind=='Scene'){  // 地盘
            /**
          $obj = $scene_model->load((int)$oid);
          $result['data'][$k]['address'] = '';
          if($obj){
            $result['data'][$k]['address'] = $obj['address'];
          }
          
          // 图片尺寸
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['huge']['view_url'];
          }

             */
        }elseif($kind=='Sight'){  // 情境
          $obj = $scene_sight_model->extend_load((int)$oid);
          if($obj){
            $result['data'][$k]['view_count'] = $obj['view_count'];
            $result['data'][$k]['love_count'] = $obj['love_count'];
            $result['data'][$k]['address'] = $obj['address'];
            $result['data'][$k]['scene_id'] = $obj['scene_id'];
            $result['data'][$k]['scene_title'] = isset($obj['scene']['title']) ? $obj['scene']['title'] : null;
            $result['data'][$k]['created_at'] = Sher_Core_Helper_Util::relative_datetime($obj['created_on']);
            if(isset($obj['user']) && !empty($obj['user'])){
              $user_info = array(
                'user_id' => $obj['user']['_id'],
                'nickname' => $obj['user']['nickname'],
                'avatar_url' => $obj['user']['medium_avatar_url'],
                'summary' => $obj['user']['summary'],
                'is_expert' => isset($obj['user']['identify']['is_expert']) ? (int)$obj['user']['identify']['is_expert'] : 0,
                'label' => isset($obj['user']['profile']['label']) ? $obj['user']['profile']['label'] : '',
                'expert_label' => isset($obj['user']['profile']['expert_label']) ? $obj['user']['profile']['expert_label'] : '',
                'expert_info' => isset($obj['user']['profile']['expert_info']) ? $obj['user']['profile']['expert_info'] : '',
              );
            }else{
              $user_info = array();
            }
            $result['data'][$k]['user_info'] = $user_info;
          
          }
          // 图片尺寸
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['huge']['view_url'];
          }
        
        }elseif($kind=='SProduct'){ // 情景产品
            /**
          // 图片尺寸
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['apc']['view_url'];
          }
          $scene_product = $scene_product_model->load((int)$oid);
          if(empty($scene_product)){
            continue;
          }

          // 产品来源
          $result['data'][$k]['attrbute'] = $scene_product['attrbute'];

          $result['data'][$k]['market_price'] = $scene_product['market_price'];
          $result['data'][$k]['sale_price'] = $scene_product['sale_price'];

          //返回Banner图片数据
          $assets = array();
          $asset_query = array('parent_id'=>(int)$oid, 'asset_type'=>120);
          $asset_options['page'] = 1;
          $asset_options['size'] = 8;
          $asset_result = $asset_service->get_asset_list($asset_query, $asset_options);

          $scene_product['banner_id'] = isset($scene_product['banner_id']) ? $scene_product['banner_id'] : null;
          $banner_asset_obj = false;
          if(!empty($asset_result['rows'])){
            foreach($asset_result['rows'] as $key=>$value){
              if($scene_product['banner_id']==(string)$value['_id']){
                $banner_asset_obj = $value;
              }else{
                array_push($assets, $value['thumbnails']['aub']['view_url']);
              }
            }
            // 如果存在封面图，追加到第一个
            if($banner_asset_obj){
              array_unshift($assets, $banner_asset_obj['thumbnails']['aub']['view_url']);
            }
          }
          $result['data'][$k]['banners'] = $assets;

             */
        }elseif($kind=='SContext'){ // 场景分享语境
          $scene_context = $scene_context_model->load($oid);
          if(!empty($scene_context)){
            $result['data'][$k]['des'] = $scene_context['des'];
          }
        }elseif($kind=='SSubject'){ // 情境主题

            $scene_subject = $scene_subject_model->load((int)$oid);       
            if(!empty($scene_subject)){
                $result['data'][$k]['title'] = $scene_subject['title'];
                $result['data'][$k]['short_title'] = $scene_subject['short_title'];
                $result['data'][$k]['short_title'] = $scene_subject['short_title'];
                $result['data'][$k]['attend_count'] = $scene_subject['attend_count'];
                $result['data'][$k]['view_count'] = $scene_subject['view_count'];
                $result['data'][$k]['type'] = $scene_subject['type'];
                $result['data'][$k]['begin_time_at'] = !empty($scene_subject['begin_time']) ? date('Y-m-d', $scene_subject['begin_time']) : '';
                $result['data'][$k]['end_time_at'] = !empty($scene_subject['end_time']) ? date('Y-m-d', $scene_subject['end_time']) : '';
            } 

          // 图片尺寸
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['aub']['view_url'];
          }
        
        }elseif($kind=='SBrand'){   // 情境品牌
            $scene_brand = $scene_brand_model->load($oid);       
            if(!empty($scene_brand)){
                $result['data'][$k]['title'] = $scene_brand['title'];
            }
          
          // 图片尺寸
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['aub']['view_url'];
          }
        }elseif($kind=='User'){     // 用户
            $user = $user_model->extend_load((int)$oid);       
            if(!empty($user)){
                $result['data'][$k]['nickname'] = $user['nickname'];
                $result['data'][$k]['summary'] = $user['summary']==null ? '' : $user['summary'];
                $result['data'][$k]['avatar_url'] = $user['medium_avatar_url'];
                $result['data'][$k]['is_export'] = isset($user['identify']['is_expert']) ? (int)$user['identify']['is_expert'] : 0;
                $result['data'][$k]['label'] = isset($user['profile']['label']) ? $user['profile']['label'] : '';
                $result['data'][$k]['expert_label'] = isset($user['profile']['expert_label']) ? $user['profile']['expert_label'] : '';
                $result['data'][$k]['expert_info'] = isset($user['profile']['expert_info']) ? $user['profile']['expert_info'] : '';
                $result['data'][$k]['is_follow'] = 0;
                if($current_user_id){
                    if($follow_model->has_exist_ship($current_user_id, $user['_id'])){
                        $result['data'][$k]['is_follow'] = 1;
					}
                }
            }
        }

        // 获取用户信息
        /**
        if($v['user_id']){
          $user = $user_model->find_by_id((int)$v['user_id']);
          $result['data'][$k]['nickname'] = $user['nickname'];
          $result['data'][$k]['home_url'] = Sher_Core_Helper_Url::user_home_url($user['_id']);
        }
        */

        //描述内容过滤
        $result['data'][$k]['content'] = strip_tags($v['high_content'], '<em>');

        // 获取对象属性
        $result['data'][$k]['kind_name'] = Sher_Core_Helper_Search::kind_name($kind, $cid);

        // 获取asset_type
        $result['data'][$k]['asset_type'] = Sher_Core_Helper_Search::gen_asset_type($kind);

      } // endfor

    }else{
      return $this->api_json('请求失败!', 3002);
    }
    $result['current_page'] = $page;
    $result['rows'] = $result['data'];
    unset($result['data']);
		return $this->api_json('请求成功', 0, $result);
	}

    /**
     * 搜索建议
     */
    public function expanded(){
        $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
        $q = isset($this->stash['q']) ? $this->stash['q'] : null;
        if(empty($q)) {
            return $this->api_json('缺少请求参数!', 3001);          
        }

        $result = Sher_Core_Util_XunSearch::expanded($q, $size);

        if($result['success']){
		    return $this->api_json('请求成功', 0, $result);
        }else{
            return $this->api_json($result['msg'], 3002);       
        }

    }
}

