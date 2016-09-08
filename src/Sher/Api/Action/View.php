<?php
/**
 * api show
 * @author tianshuai
 */
class Sher_Api_Action_View extends Sher_App_Action_Base {
	
	protected $exclude_method_list = array('execute', 'topic_show', 'product_show', 'special_subject_show', 'scene_subject_show', 'try_show', 'fiu_point', 'about', 'fetch_vip', 'fiu_service_term');
	
	/**
	 * api show
	 */
	public function execute(){

	}

  /**
   * fiu 服务条款 
   *
   */
  public function fiu_service_term(){
    return $this->to_html_page('fiu/fiu_service_term.html');
  }

  /**
   * 关于太火鸟
   */
  public function about(){
    return $this->to_html_page('fiu/fiu_about.html');
  }

  /**
   * 用户积分
   */
  public function fiu_point(){
    $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
    $app_type = isset($this->stash['app_type']) ? (int)$this->stash['app_type'] : 1;
    $user_id = 0;
    if(empty($uuid) || empty($from_to)){
			return $this->api_json('缺少请求参数！', 3001); 
    }

    if($app_type==1){
      $pusher_model = new Sher_Core_Model_Pusher();
    }elseif($app_type==2){
      $pusher_model = new Sher_Core_Model_FiuPusher();          
    }else{
      return $this->api_json("应用来源不正确", 3002);           
    }
    $pusher = $pusher_model->first(array('uuid'=> $uuid, 'from_to'=>$from_to, 'is_login'=>1));
    if($pusher){
      $user_id = $pusher['user_id'];
    }else{
      return $this->api_json('请先登录!', 3003);
    }

    $user_model = new Sher_Core_Model_User();
    $user = $user_model->extend_load($user_id);
    // 用户实时积分
    $point_model = new Sher_Core_Model_UserPointBalance();
    $current_point = $point_model->load($user_id);

    $this->stash['user'] = $user;   
    $this->stash['current_point'] = $current_point;

  	return $this->to_html_page('fiu/point.html');
  }

  /**
   * 商城领爱奇异会员
   */
  public function fetch_vip(){
    $uuid = isset($this->stash['uuid']) ? $this->stash['uuid'] : null;
    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
    $app_type = isset($this->stash['app_type']) ? (int)$this->stash['app_type'] : 1;
    $user_id = $this->current_user_id;
    if(empty($uuid) || empty($from_to)){
			return $this->api_json('缺少请求参数！', 3001); 
    }

  	return $this->to_html_page('fiu/aqy.html');
  }
	
	/**
	 * 显示主题详情帖---手机app content
	 */
	public function topic_show(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.topic'];
		if(empty($id)){
			return $this->api_json('访问的主题不存在或已被删除！', 3001);
		}
		
		$model = new Sher_Core_Model_Topic();
		$topic = $model->load($id);
		
		if(empty($topic) || $topic['deleted']){
			return $this->api_json('访问的主题不存在或已被删除！', 3002);
		}

		//创建关联数据
		$topic = $model->extended_model_row($topic);
		
		$this->stash['topic'] = &$topic;
		
		return $this->to_html_page('page/topic/api_show.html');
	}

	/**
	 * app商品描述部分html5展示
	 */
	public function product_show(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->api_json('访问的产品不存在！', 3000);
		}
		
		$product = array();
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);

		if($product['deleted']){
			return $this->api_json('访问的产品不存在或已被删除！', 3001);
		}

		//加载model扩展数据
		$product = $model->extended_model_row($product);
    if(isset($product['content_wap']) && !empty($product['content_wap'])){
      $content = trim($product['content_wap']);
      if(!empty($content)){
		    $product['content'] = $product['content_wap'];
      }
		}

		$this->stash['product'] = &$product;
		return $this->to_html_page('page/product/api_show.html');
	}
	
	/**
	 * 专题详情页面显示
	 */
	public function special_subject_show(){
		
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->api_json('访问的产品不存在！', 3000);
		}
		
		$model = new Sher_Core_Model_SpecialSubject();
		$result = $model->extend_load((int)$id);
		
		$this->stash['content'] = $result['content'];
		return $this->to_html_page('page/special_subject/api_show.html');
	}

	/**
	 * 情境专题
	 */
	public function scene_subject_show(){
		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if(empty($id)){
			return $this->api_json('访问的专题不存在！', 3000);
		}
		
		$model = new Sher_Core_Model_SceneSubject();
		$scene_subject = $model->extend_load($id);

        $type = $scene_subject['type'];

        // 新品
        $product = null;
        if(!empty($scene_subject['product_id'])){
            $product_model = new Sher_Core_Model_Product();
            $product = $product_model->extend_load((int)$scene_subject['product_id']);
        }
        $scene_subject['product'] = $product;

        // 促销
        $product_arr = array();
        if(!empty($scene_subject['product_ids'])){
            $product_model = new Sher_Core_Model_Product();
            for($i=0;$i<count($scene_subject['product_ids']);$i++){
                $product = $product_model->extend_load($scene_subject['product_ids'][$i]);
                if(empty($product) || $product['deleted']==1 || $product['published']==0) continue;
                $row = array(
                    '_id' => $product['_id'],
                    'title' => $product['short_title'],
                    'banner_url' => $product['banner']['thumbnails']['aub']['view_url'],
                    'summary' => $product['summary'],
                    'market_price' => $product['market_price'],
                    'sale_price' => $product['sale_price'],
                );
                array_push($product_arr, $row);
            }
        }
        $scene_subject['products'] = $product_arr;
        

        $this->stash['scene_subject'] = $scene_subject;
		
		$this->stash['content'] = $scene_subject['content'];

        if($scene_subject['type']==1){
            $tpl = 'page/scene_subject/api_show.html';
        }elseif($scene_subject['type']==2){
            $tpl = 'page/scene_subject/api_show.html';
        }elseif($scene_subject['type']==3){
            $tpl = 'page/scene_subject/api_hot.html';       
        }elseif($scene_subject['type']==4){
            $tpl = 'page/scene_subject/api_new.html';
        }elseif($scene_subject['type']==5){
            $tpl = 'page/scene_subject/api_hot.html';            
        }else{
            $tpl = 'page/scene_subject/api_show.html';       
        }
		return $this->to_html_page($tpl);
	}

	/**
	 * app试用描述部分html5展示
	 */
	public function try_show(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->api_json('访问的内容不存在！', 3000);
		}
		
		$try = array();
		
		$model = new Sher_Core_Model_Try();
		$try = $model->load((int)$id);

		//加载model扩展数据
		$try = $model->extended_model_row($try);
		if(isset($try['content_wap']) && !empty($try['content_wap'])){
      $content = trim($try['content_wap']);
      if(!empty($content)){
		    $try['content'] = $try['content_wap'];
      }
		}

		$this->stash['try'] = &$try;
		return $this->to_html_page('page/try/api_show.html');
	}

}

