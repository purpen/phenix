<?php
/**
 * API 接口
 * @author tianshuai
 */
class Sher_Api_Action_Search extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist', 'outside_search');

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

    $db = 'phenix';

    // 全文搜索/标签搜索

    $options = array(
      'page' => $page,
      'size' => $size,
      'evt'  => $evt,
      'sort' => $s,
      'asc'  => $asc,
      't'    => $t,
    );
    
    $result = Sher_Core_Util_XunSearch::search($q, $options, $db);
    if($result['success']){
      //$user_model = new Sher_Core_Model_User();
      $asset_model = new Sher_Core_Model_Asset();
      $product_model = new Sher_Core_Model_Product();
      foreach($result['data'] as $k=>$v){

        $kind = $result['data'][$k]['kind'];
        $cid = (int)$result['data'][$k]['cid'];
        $oid = (int)$result['data'][$k]['oid'];
        $result['data'][$k]['_id'] = $oid;

        // 产品
        if($kind=='Product'){
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
        // 话题
        }elseif($kind=='Topic'){
        
        }else{
        
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

        //封面图
        if($v['cover_id']){
          $asset_obj = $asset_model->extend_load($v['cover_id']);
          if($asset_obj){
            $result['data'][$k]['cover_url'] = $asset_obj['thumbnails']['apc']['view_url'];;
          }
        }

        // 获取对象属性
        $result['data'][$k]['kind_name'] = Sher_Core_Helper_Search::kind_name($v['kind'], $v['cid']);

        // 获取asset_type
        $result['data'][$k]['asset_type'] = Sher_Core_Helper_Search::gen_asset_type($v['kind']);

      }

    }else{
      return $this->api_json('请求失败!', 3002);
    }
    $result['rows'] = $result['data'];
    unset($result['data']);
		return $this->api_json('请求成功', 0, $result);
	}


  /**
   * 站外搜索，包括淘宝、天猫
   * @author tianshuai
   * @param q:搜索内容；evt: 1.淘宝天猫、2.京东; sort: 排序;
   */

  public function outside_search(){
    $result = array();
 		$q = isset($this->stash['q']) ? $this->stash['q'] : null;
    $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 1;
    $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
    $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
    $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;

    if(empty($q)){
      return $this->api_json('缺少请求参数!', 3001);
    }

    if($evt==1){
    
    }elseif($evt==2){
    
    }else{
      return $this->api_json('搜索类型不正确!', 3002);    
    }


  }

  /**
   * 请求淘宝、天猫搜索
   * @author tianshuai
   * @param q:搜索内容；
   */

  private function tb_search($q, $options=array()){
    $result = array();
    $result['success'] = false;
    $sort = isset($options['sort']) ? (int)$options['sort'] : 0;
    $page = isset($options['page']) ? (int)$options['page'] : 1;
    $size = isset($options['size']) ? (int)$options['size'] : 8; 

    if(empty($q)){
      $result['msg'] = '搜索关键字不能为空!';
      return $result;     
    }


  }

  /**
   * 请求淘宝、天猫搜索
   * @author tianshuai
   * @param q:搜索内容；
   */

  private function jd_search($q, $options=array()){
    $result = array();
    $result['success'] = false;
    $sort = isset($options['sort']) ? (int)$options['sort'] : 0;
    $page = isset($options['page']) ? (int)$options['page'] : 1;
    $size = isset($options['size']) ? (int)$options['size'] : 8; 

    if(empty($q)){
      $result['msg'] = '搜索关键字不能为空!';
      return $result;     
    }


  }

	
}

