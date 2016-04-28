<?php
/**
 * API 接口
 * @author tianshuai
 */
class Sher_Api_Action_Search extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist');

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
    $t = isset($this->stash['t']) ? (int)$this->stash['t'] : 7;
    $s = isset($this->stash['s']) ? (int)$this->stash['s'] : 1;
    $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
    $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;
    $asc = isset($this->stash['asc']) ? 1 : 0;

		if(empty($q)){
			return $this->api_json('请输入关键词！', 3000);
		}

    $user_id = $this->current_user_id;

    // 全文搜索/标签搜索

    $options = array(
      'page' => $page,
      'size' => $size,
      'evt'  => $evt,
      'sort' => $s,
      'asc'  => $asc,
      't'    => $t,
    );
    
    $result = Sher_Core_Util_XunSearch::search($q, $options);
    if($result['success']){
      //$user_model = new Sher_Core_Model_User();
      $asset_model = new Sher_Core_Model_Asset();
      $product_model = new Sher_Core_Model_Product();
      $topic_model = new Sher_Core_Model_Topic();
      $scene_model = new Sher_Core_Model_SceneScene();
      $scene_sight_model = new Sher_Core_Model_SceneSight();
      $scene_product_model = new Sher_Core_Model_SceneProduct();
      $scene_context_model = new Sher_Core_Model_SceneContext();

      foreach($result['data'] as $k=>$v){

        //封面图
        if($v['cover_id']){
          $asset_obj = $asset_model->extend_load($v['cover_id']);
        }

        $kind = $result['data'][$k]['kind'];
        $cid = (int)$result['data'][$k]['cid'];
        $oid = (int)$result['data'][$k]['oid'];
        $result['data'][$k]['_id'] = $oid;

        // 产品
        if($kind=='Product'){ // 产品
          if($cid==9){
            $obj = $product_model->find_by_id($oid);
            // 商品不需要显示详情
            $result['data'][$k]['content'] = null;
            if($obj){
              $result['data'][$k]['market_price'] = $obj['market_price'];
              $result['data'][$k]['sale_price'] = $obj['sale_price'];
            }else{
              $result['data'][$k]['market_price'] = 0;
              $result['data'][$k]['sale_price'] = 0;           
            }
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
        
        }elseif($kind=='Scene'){  // 情景
          $obj = $scene_model->load($oid);
          
          // 图片尺寸
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['huge']['view_url'];
          }
        
        }elseif($kind=='Sight'){  // 场景
          // 图片尺寸
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['huge']['view_url'];
          }
        
        }elseif($kind=='SProduct'){ // 情景产品

          // 图片尺寸
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['apc']['view_url'];
          }
        }elseif($kind=='SContext'){ // 场景分享语境
        
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
    $result['rows'] = $result['data'];
    unset($result['data']);
		return $this->api_json('请求成功', 0, $result);
	}


	
}

