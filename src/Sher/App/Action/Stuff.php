<?php
/**
 * 产品灵感
 * @author purpen
 */
class Sher_App_Action_Stuff extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id'   => '',
		'page' => 1,
    'size' => 10,
		'step' => 0,
		'pid'  => 0,
		'cid'  => 0,
		'sort' => 0,
    'page_title_suffix' => '智品库-太火鸟智能硬件孵化平台创新产品汇集库',
    'page_keywords_suffix' => '太火鸟,智能硬件,智品库,智能手环,智能手表,健康监测,智能家居,智能首饰,智能母婴,创意产品,新奇特',
    'page_description_suffix' => '智品库是太火鸟智能硬件孵化平台产品汇集区，产品包括智能手环、健康监测、智能家居、智能首饰、智能母婴、创意产品等等，发表你的创新产品，让我们用创意和梦想，去改变平凡无奇的世界。',
	);
	
	protected $exclude_method_list = array('execute','latest', 'featured', 'sticked', 'view','hundred','ajax_fetch_more', 'tlist', 'tshow');
	
	protected $page_html = 'page/stuff/zlist.html';
	
	public function _init() {
		$this->set_target_css_state('page_social');
		$this->set_target_css_state('page_stuff');
    }

	/**
	 * 产品灵感入口
	 */
	public function execute(){
		return $this->hundred();
	}
	
	/**
	 * TOp-100 首页 2016/01/18
	 */
	public function hundred(){
		$top_category_id = Doggy_Config::$vars['app.stuff.top100_category_id'];
    $this->stash['pid'] = $top_category_id;
		return $this->to_html_page('page/stuff/index.html');
	}
	
	/**
	  *TOp-100 提交
	**/
	public function tsubmit(){
    // 活动结束
    return $this->to_redirect(sprintf("%s", Doggy_Config::$vars['app.url.stuff']));

		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$this->stash['new_file_id'] = Sher_Core_Helper_Util::generate_mongo_id();

		$top_category_id = Doggy_Config::$vars['app.stuff.top100_category_id'];
		$this->stash['pid'] = $top_category_id;
		return $this->to_html_page('page/stuff/tsubmit.html');
	}
	
	/**
	  *TOp-100 列表
	**/
	public function tlist(){
		$top_category_id = Doggy_Config::$vars['app.stuff.top100_category_id'];
    $this->stash['pid'] = $top_category_id;
		return $this->to_html_page('page/stuff/tlist.html');
	}
	
	/**
	  *TOp-100 详情页
	**/
	public function tshow(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.stuff']."/hundred";
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$stuff = $model->load($id);
		
		if(empty($stuff) || $stuff['deleted']){
			return $this->show_message_page('访问的产品不存在或被删除！', $redirect_url);
		}
		
		$stuff = $model->extended_model_row($stuff);

    //添加网站meta标签
    $this->stash['page_title_suffix'] = sprintf("【%s】-太火鸟智能硬件Top100", $stuff['title']);
    if(!empty($stuff['tags_s'])){
      $this->stash['page_keywords_suffix'] = $stuff['tags_s'];   
    }
    $this->stash['page_description_suffix'] = "智品库是太火鸟智能硬件孵化平台产品汇集区，产品包括智能手环、健康监测、智能家居、智能首饰、智能母婴、创意产品等等，发表你的创新产品，让我们用创意和梦想，去改变平凡无奇的世界。";
		
		// 增加pv++
		$inc_ran = rand(1,6);
		$model->inc_counter('view_count', $inc_ran, $id);
		
		// 当前用户是否有管理权限
		$editable = false;
		if ($this->visitor->id){
			if ($this->visitor->id == $stuff['user_id'] || $this->visitor->can_edit){
				$editable = true;
			}
		}

    // 验证用户是否点赞过
    $is_loved = false;
    if ($this->visitor->id){
      $favorite_model = new Sher_Core_Model_Favorite();
      $is_loved = $favorite_model->check_loved((int)$this->visitor->id, (string)$stuff['_id'], Sher_Core_Model_Favorite::TYPE_STUFF);
    }
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		$this->stash['stuff'] = $stuff;
		$this->stash['editable'] = $editable;
    $this->stash['is_loved'] = $is_loved;

    // 跳转楼层
    $floor = isset($this->stash['floor']) ? (int)$this->stash['floor'] : 0;
    if($floor){
        $new_page = ceil($floor/10);
        $this->stash['page'] = $new_page;
    }
		
    // 评论参数
    $comment_options = array(
      'comment_target_id' => $stuff['_id'],
      'comment_target_user_id' => $stuff['user_id'],
      'comment_type'  =>  Sher_Core_Model_Comment::TYPE_STUFF,

      'comment_pager' =>  sprintf("%s/tshow?id=%d&page=#p##comment_top", Doggy_Config::$vars['app.url.stuff'], $stuff['_id']),
      //是否显示上传图片/链接
      'comment_show_rich' => 1,
    );
    $this->_comment_param($comment_options);
		return $this->to_html_page('page/stuff/tshow.html');
	}
	
	
	/**
	 * 最新列表
	 */
	public function latest(){
		return $this->zlist('latest');
	}
	
	/**
	 * 精选列表
	 */
	public function featured(){
		$this->stash['featured'] = 1;
		return $this->zlist('featured');
	}
	
	/**
	 * 推荐列表
	 */
	public function sticked(){
		$this->stash['sticked'] = 1;
		return $this->zlist('sticked');
	}
	
	/**
	 * 产品灵感
	 */
	protected function zlist($list_tab='latest'){
		$cid = isset($this->stash['cid']) ? $this->stash['cid'] : 0;
		$top_category_id = Doggy_Config::$vars['app.topic.idea_category_id'];
		$is_top = false;
		if(!$cid || ($cid == $top_category_id)){
			$this->stash['all_stuff'] = 'active';
			$cid = $top_category_id;
			$is_top = true;

    }else{
      //添加网站meta标签
      $this->stash['page_title_suffix'] = Sher_Core_Helper_View::meta_category_id($cid, 1);
      $this->stash['page_keywords_suffix'] = Sher_Core_Helper_View::meta_category_id($cid, 2);   
      $this->stash['page_description_suffix'] = Sher_Core_Helper_View::meta_category_id($cid, 3);
    }
		$this->stash['is_top'] = $is_top;
		$this->stash['top_category_id'] = $top_category_id;
		$this->stash['cid'] = $cid;
        
        
        // 获取计数
        $dig = new Sher_Core_Model_DigList();
        $counter = $dig->load(Sher_Core_Util_Constant::STUFF_COUNTER);
        $this->stash['counter'] = $counter;
		
		// 分页链接
		$page = 'p#p#';
		$this->stash['pager_url'] = Sher_Core_Helper_Url::build_url_path('app.url.stuff', $list_tab, 'c'.$cid).$page;
		
		return $this->display_tab_page($list_tab);
	}
	
	/**
	 * 灵感详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.stuff'];
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$stuff = $model->load($id);
		
		if(empty($stuff) || $stuff['deleted']){
			return $this->show_message_page('访问的产品不存在或被删除！', $redirect_url);
		}

    // 如果是top100,跳到相应页面 
    if($stuff['from_to']==5){
      return $this->to_redirect(sprintf("%s/tshow?id=%d", Doggy_Config::$vars['app.url.stuff'], $stuff['_id']));
    }
		
		$stuff = $model->extended_model_row($stuff);

    //添加网站meta标签
    $this->stash['page_title_suffix'] = sprintf("【%s】-太火鸟创新产品汇集库", $stuff['title']);
    if(!empty($stuff['tags_s'])){
      $this->stash['page_keywords_suffix'] = $stuff['tags_s'];   
    }
    $this->stash['page_description_suffix'] = "智品库是太火鸟智能硬件孵化平台产品汇集区，产品包括智能手环、健康监测、智能家居、智能首饰、智能母婴、创意产品等等，发表你的创新产品，让我们用创意和梦想，去改变平凡无奇的世界。";
		
		// 增加pv++
		$inc_ran = rand(1,6);
		$model->inc_counter('view_count', $inc_ran, $id);
		
		// 当前用户是否有管理权限
		$editable = false;
		if ($this->visitor->id){
			if ($this->visitor->id == $stuff['user_id'] || $this->visitor->can_edit){
				$editable = true;
			}
		}
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$stuff['fid']);
		
		$this->stash['stuff'] = $stuff;
		$this->stash['parent_category'] = $parent_category;
		$this->stash['editable'] = $editable;
		
    // 评论参数
    $comment_options = array(
      'comment_target_id' => $stuff['_id'],
      'comment_target_user_id' => $stuff['user_id'],
      'comment_type'  =>  Sher_Core_Model_Comment::TYPE_STUFF,
      'comment_pager' =>  Sher_Core_Helper_Url::stuff_comment_url($id, '#p#'),
      //是否显示上传图片/链接
      'comment_show_rich' => 1,
    );
    $this->_comment_param($comment_options);

		return $this->to_html_page('page/stuff/view.html');
	}
	
	/**
	 * 提交入口
	 */
	public function submit(){
		$top_category_id = Doggy_Config::$vars['app.topic.idea_category_id'];
        $label_title = '产品';
        // 孵化资源 案例
        if(isset($this->stash['rid'])){
            $label_title = '案例';
            $top_category_id = Doggy_Config::$vars['app.stuff.okcase_category_id'];
        }
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		$parent_category['view_url'] = Doggy_Config::$vars['app.url.stuff'];
		$this->stash['parent_category'] = $parent_category;
		
		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		$this->_editor_params();
        
        $this->stash['label_title'] = $label_title;
        $this->stash['selected_category_id'] = $top_category_id;
		
		return $this->to_html_page('page/stuff/submit.html');
	}

	/**
	 * 大赛提交入口
	 */
	public function contest_submit(){
    $redirect_url = sprintf("%s/about3", Doggy_Config::$vars['app.url.contest']);
    $contest_id = isset($this->stash['contest_id'])?(int)$this->stash['contest_id']:0;
    if(empty($contest_id)){
			return $this->show_message_page('大赛ID不存在！', $redirect_url);
    }
		$top_category_id = Doggy_Config::$vars['app.stuff.contest_category_id'];

		// 获取父级分类
		$category_model = new Sher_Core_Model_Category();
		$default_category = $category_model->first(array('domain'=>4, 'pid'=>(int)$top_category_id));
		$this->stash['default_category_id'] = !empty($default_category)?$default_category['_id']:0;
		
		$this->stash['mode'] = 'create';
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		$this->_editor_params();
		
		return $this->to_html_page('page/stuff/contest_submit.html');
	}
	
	/**
	 * 编辑修改产品灵感
	 */
	public function edit(){
		if(empty($this->stash['id'])){
			return $this->show_message_page('缺少请求参数！', true);
		}
        
        $this->stash['from_to'] = 0;
        if(isset($this->stash['from'])){
            if($this->stash['from']=='birdegg'){
                $this->stash['from_to'] = 2;
            }elseif($this->stash['from']=='swhj'){
                $this->stash['from_to'] = 1;    
            }
        }
		
		$model = new Sher_Core_Model_Stuff();
		$stuff = $model->load((int)$this->stash['id']);
		
        if(empty($stuff)){
            return $this->show_message_page('编辑的产品不存在或被删除！', true);
        }
		// 仅管理员或本人具有删除权限
		if (!$this->visitor->can_edit() && !($stuff['user_id'] == $this->visitor->id)){
			return $this->show_message_page('你没有权限编辑的该主题！', true);
		}
        
		$stuff = $model->extended_model_row($stuff);

        // 如果是大赛,隐藏商品参数
        if($stuff['from_to']==1 || $stuff['from_to']==3){
            $this->stash['is_match'] = true;
        }

    // 如果是大赛，跳到大赛编辑页
    if($stuff['from_to']==7){
 			$redirect_url = Doggy_Config::$vars['app.url.contest'].'/edit3?id='.$stuff['_id'];    
      return $this->to_redirect($redirect_url);
    }

    if($stuff['from_to']==5){ // 如果是top100

      $top_category_id = Doggy_Config::$vars['app.stuff.top100_category_id'];
      $this->stash['pid'] = $top_category_id;

      $tpl = 'page/stuff/tsubmit.html';
    }else{  // 不是 top100

      // 是否为一级分类
      $is_top = false;
      $current_category = array();
      
      $category = new Sher_Core_Model_Category();
      // 获取当前分类信息
      $current_category = $category->load((int)$stuff['category_id']);
      
      // 获取父级分类
      $parent_category = $category->extend_load((int)$stuff['fid']);
      $parent_category['view_url'] = Doggy_Config::$vars['app.url.stuff'];
      $this->stash['parent_category'] = $parent_category;

      $this->stash['is_top'] = $is_top;
      $this->stash['current_category'] = $current_category;
      $this->stash['cid'] = (int)$current_category['pid'];

      $tpl = 'page/stuff/submit.html';
    }
		
		$this->stash['mode'] = 'edit';
		$this->stash['stuff'] = $stuff;
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$this->stash['new_file_id'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		$this->_editor_params();
		
		return $this->to_html_page($tpl);
	}
	
	/**
	 * 保存产品信息
	 */
	public function save(){
		
		// 禁用用户无法操作
		if(!$this->stash["visitor"]['state']){
			$redirect_url = Doggy_Config::$vars['app.domain.base'];
			return $this->to_redirect($redirect_url);
		}
		
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_json('名称不能为空！', true);
		}
      if(empty($this->stash['category_id'])){
          return $this->ajax_json('请选择一个类别！', true); 
      }
      if(empty($this->stash['cover_id'])){
          return $this->ajax_json('请至少上传一张图片并设置为封面图！', true); 
      }

      $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
      // 如果是大赛,必须选择一所大学
      if($from_to == 1){
          if(empty($this->stash['college_id']) || (int)$this->stash['college_id'] == 0){
              return $this->ajax_json('请选择所在大学！', true);   
          }
      }elseif($from_to == 6){
        if(!isset($this->stash['attr']) || empty($this->stash['attr'])){
          return $this->ajax_json('请选择参赛类型！', true); 
        }
        if(!isset($this->stash['name']) || empty($this->stash['name'])){
          return $this->ajax_json('请补全用户信息！', true); 
        }
        if(!isset($this->stash['tel']) || empty($this->stash['tel'])){
          return $this->ajax_json('请补全用户信息！', true); 
        } 
        if(!isset($this->stash['position']) || empty($this->stash['position'])){
          return $this->ajax_json('请补全用户信息！', true); 
        } 
        if(!isset($this->stash['description']) || empty($this->stash['description'])){
          return $this->ajax_json('作品描述不能为空！', true); 
        } 
      // 奇思勇动3
      }elseif($from_to == 7){
        if(!isset($this->stash['attr']) || empty($this->stash['attr'])){
          return $this->ajax_json('请选择参赛类型！', true); 
        }
        if($this->stash['attr'] == 2) {
          if(!isset($this->stash['company']) || empty($this->stash['company'])){
            return $this->ajax_json('请添写公司／团队名称！', true);                       
          }
          if(!isset($this->stash['c_name']) || empty($this->stash['c_name'])){
           return $this->ajax_json('请添写联系人姓名！', true);                       
          }
        }
        if(!isset($this->stash['name']) || empty($this->stash['name'])){
          return $this->ajax_json('请添写参赛者姓名！', true); 
        }
        if(!isset($this->stash['tel']) || empty($this->stash['tel'])){
          return $this->ajax_json('请添写联系人电话！', true); 
        } 
        if(!isset($this->stash['position']) || empty($this->stash['position'])){
          return $this->ajax_json('请添写联系人职位信息！', true); 
        } 
        if(!isset($this->stash['email']) || empty($this->stash['email'])){
          //return $this->ajax_json('请添写联系人邮箱！', true); 
        } 
        if(!isset($this->stash['address']) || empty($this->stash['address'])){
          //return $this->ajax_json('请添写联系地址！', true); 
        } 
        if(!isset($this->stash['description']) || empty($this->stash['description'])){
          return $this->ajax_json('项目描述不能为空！', true); 
        }      
      }
        
		$id = isset($this->stash['_id']) ? (int)$this->stash['_id'] : 0;
		
		$mode = 'create';
		$data = array();
		
		$data['title'] = $this->stash['title'];
		$data['description'] = $this->stash['description'];
		$data['fid'] = isset($this->stash['fid']) ? (int)$this->stash['fid'] : 0;
		$data['tags'] = isset($this->stash['tags']) ? $this->stash['tags'] : null;
		$data['category_id'] = (int)$this->stash['category_id'];
		$data['cooperate_id'] = isset($this->stash['cooperate_id'])?(int)$this->stash['cooperate_id']:0;
		$data['verified'] = isset($this->stash['verified'])?(int)$this->stash['verified']:0;
    $data['cover_id'] = $this->stash['cover_id'];
		$data['short_title'] = isset($this->stash['short_title'])?$this->stash['short_title']:'';
		//反定制定
		$data['contest_id'] = isset($this->stash['contest_id']) ? (int)$this->stash['contest_id'] : 0;

    // 所属
    if(isset($this->stash['from_to'])){
        $data['from_to'] = (int)$this->stash['from_to'];
    }else{
        $data['from_to'] = 0;
    }

    // 团体或个人
    if(isset($this->stash['attr'])){
        $data['attr'] = (int)$this->stash['attr'];
    }

    // 团队介绍-蛋年
    if(isset($this->stash['team_introduce'])){
        $data['team_introduce'] = $this->stash['team_introduce'];
    }

    // 品牌
    if(isset($this->stash['brand'])){
        $data['brand'] = $this->stash['brand'];
    }
    // 设计师
    if(isset($this->stash['designer'])){
        $data['designer'] = $this->stash['designer'];
    }
    // 所属国家
    if(isset($this->stash['country'])){
        $data['country'] = $this->stash['country'];
    }
    // 上市时间
    if(isset($this->stash['market_time'])){
        $data['market_time'] = $this->stash['market_time'];
    }
    // 指导价格
    if(isset($this->stash['official_price'])){
        $data['official_price'] = $this->stash['official_price'];
    }
    // 产品阶段
    if(isset($this->stash['processed'])){
        $data['processed'] = (int)$this->stash['processed'];
    }
    // 购买地址
    if(isset($this->stash['buy_url'])){
        $data['buy_url'] = $this->stash['buy_url'];
    }

    // 所在省份
    if(isset($this->stash['province_id'])){
        $data['province_id'] = (int)$this->stash['province_id'];
    }
    // 所在大学
    if(isset($this->stash['college_id'])){
        $data['college_id'] = (int)$this->stash['college_id'];
    }

    // 如果是关联投票产品
    if(isset($this->stash['fever_id'])){
        $data['fever_id'] = (int)$this->stash['fever_id'];
    }

    // 联系姓名(参赛者)
    if(isset($this->stash['name'])){
        $data['name'] = $this->stash['name'];
    }
    // 联系姓名
    if(isset($this->stash['c_name'])){
        $data['c_name'] = $this->stash['c_name'];
    }

    // 联系方式 
    if(isset($this->stash['tel'])){
        $data['tel'] = $this->stash['tel'];
    }
    // 联系地址
    if(isset($this->stash['address'])){
        $data['address'] = $this->stash['address'];
    }
    // 联系邮箱
    if(isset($this->stash['email'])){
        $data['email'] = $this->stash['email'];
    }

    // 职业
    if(isset($this->stash['position'])){
        $data['position'] = $this->stash['position'];
    }

    // 公司名称 
    if(isset($this->stash['company'])){
        $data['company'] = $this->stash['company'];
    }

    // 作品链接
    if(isset($this->stash['link'])){
        $data['link'] = $this->stash['link'];
    }

    // 如果是top100
    $honor = array();
    if(isset($this->stash['crowdfunding_money'])){
      $honor['crowdfunding_money'] = $this->stash['crowdfunding_money'];
    }
    if(isset($this->stash['sale_money'])){
      $honor['sale_money'] = $this->stash['sale_money'];
    }
    if(isset($this->stash['prize'])){
      $honor['prize'] = $this->stash['prize'];
    }
    if($honor){
      $data['honor'] = $honor;
    }
		
		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
		}else{
			$data['asset'] = array();
		}
		
		try{
			$model = new Sher_Core_Model_Stuff();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				
				$stuff = $model->get_data();
				$id = (int)$stuff['_id'];
				
				// 更新用户灵感数量
				$this->visitor->inc_counter('stuff_count', $data['user_id']);
			}else{
				$mode = 'edit';
          $data['_id'] = (int)$id;
            // 如果是大赛,用户更改了省份或大学,需要重新统计排行
            if($data['from_to']==1){
                $old_stuff = $model->find_by_id((int)$id);
            }
			$ok = $model->apply_and_update($data);

        if($ok){
          //如果是大赛,用户更改了省份或大学,需要重新统计排行
          if($data['from_to']==1){
            if(!empty($old_stuff)){
              $old_province_id = isset($old_stuff['province_id'])?$old_stuff['province_id']:0;
              $old_college_id = isset($old_stuff['college_id'])?$old_stuff['college_id']:0;

              //如果有变更,更新排行统计
              $num_mode = new Sher_Core_Model_SumRecord();
              if(isset($data['province_id']) && $data['province_id'] != $old_province_id){
                $num_mode->down_record($old_province_id, 'match2_count', 1);
                if($old_stuff['love_count']){
                  $num_mode->multi_down_record($old_province_id, 'match2_love_count', $old_stuff['love_count'], 1);
                }
                $num_mode->add_record($data['province_id'], 'match2_count', 1);
                $num_mode->multi_add_record($data['province_id'], 'match2_love_count', $old_stuff['love_count'], 1);
              }

              if(isset($data['college_id']) && $data['college_id'] != $old_college_id){
                $num_mode->down_record($old_college_id, 'match2_count', 2);
                if($old_stuff['love_count']){
                  $num_mode->multi_down_record($old_college_id, 'match2_love_count', $old_stuff['love_count'], 2);
                }
                $num_mode->add_record($data['college_id'], 'match2_count', 2); 
                $num_mode->multi_add_record($data['college_id'], 'match2_love_count', $old_stuff['love_count'], 2);
              }

            } // endif from_to

          }// endif ok
          
        }
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			$asset = new Sher_Core_Model_Asset();
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$asset->update_batch_assets($data['asset'], (int)$id);
			}
			
			// 保存成功后，更新编辑器图片
			if(!empty($this->stash['file_id'])){
				$asset->update_editor_asset($this->stash['file_id'], (int)$id);
			}

      // 更新全文索引
      Sher_Core_Helper_Search::record_update_to_dig((int)$id, 2); 
      //更新百度推送
      if($mode=='create'){
        //Sher_Core_Helper_Search::record_update_to_dig((int)$id, 11); 
      }
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("创意保存失败：".$e->getMessage());
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		
        if($data['from_to'] == 1){  // 十万火计2
            $redirect_url = Doggy_Config::$vars['app.url.contest'].'/view2/'.$id.'.html';
        }elseif($data['from_to'] == 2){ // 蛋年
            $redirect_url = Doggy_Config::$vars['app.url.birdegg'].'/'.$id.'.html';
        }elseif($data['from_to'] == 3){ // 奇思甬动
            $redirect_url = Doggy_Config::$vars['app.url.contest'].'/qsyd_view/'.$id.'.html';
        }elseif($data['from_to'] == 4){ // 反向定制
            $redirect_url = Sher_Core_Helper_Url::stuff_view_url($id); 
        }elseif($data['from_to'] == 5){ // top100专题
            $redirect_url = sprintf("%s/tshow?id=%d", Doggy_Config::$vars['app.url.stuff'], $id);
        }elseif($data['from_to'] == 6){ // 奇思甬动2
            $redirect_url = sprintf("%s/qsyd_view2?id=%d", Doggy_Config::$vars['app.url.contest'], $id);
        }elseif($data['from_to'] == 7){ // 奇思甬动2
            $redirect_url = sprintf("%s/qsyd_view3?id=%d", Doggy_Config::$vars['app.url.contest'], $id);
        }else{
   		    $redirect_url = Sher_Core_Helper_Url::stuff_view_url($id);       
        }
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 推荐
	 */
	public function ajax_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Stuff();
			$model->mark_as_stick((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 取消推荐
	 */
	public function ajax_cancel_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Stuff();
			$model->mark_cancel_stick((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 精选
	 */
	public function ajax_featured(){
		if(empty($this->stash['id'])){
			return $this->ajax_notification('产品不存在！', true);
		}
		
		try{
			if (!$this->visitor->can_edit()){
				return $this->ajax_json('抱歉，你没有权限进行此操作！', true);
			}
			
			$id = $this->stash['id'];
			
			$model = new Sher_Core_Model_Stuff();
			$ok = $model->mark_as_featured((int)$id);
			
			if ($ok) {
				// 添加到精选
				$diglist = new Sher_Core_Model_DigList();
                $diglist->inc_stuff_counter('items.feature_count');
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 取消置顶
	 */
	public function ajax_cancel_featured(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
			if (!$this->visitor->can_edit()){
				return $this->ajax_json('抱歉，你没有权限进行此操作！', true);
			}
			
			$model = new Sher_Core_Model_Stuff();
			$ok = $model->mark_cancel_featured((int)$id);
			if ($ok) {
				$diglist = new Sher_Core_Model_DigList();
				$diglist->dec_stuff_counter('items.feature_count');
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 删除产品灵感
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Stuff();
			$stuff = $model->load((int)$id);
			
			// 仅管理员或本人具有删除权限
			if (!$this->visitor->can_edit() && !($stuff['user_id'] == $this->visitor->id)){
				return $this->ajax_notification('抱歉，你没有权限进行此操作！', true);
			}
			
			$model->remove((int)$id);
			
			// 删除关联对象
			$model->mock_after_remove($id, $stuff);
			
            # 更新计数器
            $diglist = new Sher_Core_Model_DigList();
			// 从精选列表中减去
			if ($stuff['featured']){
				$diglist->dec_stuff_counter('items.feature_count');
			}
            // 从总数中减去
			$diglist->dec_stuff_counter('items.total_count');
            
			// 更新所属分类
			$category = new Sher_Core_Model_Category();
			
			$category->dec_counter('total_count', $stuff['category_id']);
			$category->dec_counter('total_count', $stuff['fid']);
			
			// 更新用户主题数量
			$this->visitor->dec_counter('stuff_count', $stuff['user_id']);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		// 删除成功后返回URL
		$this->stash['redirect_url'] = Doggy_Config::$vars['app.url.stuff'];
		$this->stash['ids'] = array($id);
		
		return $this->to_taconite_page('ajax/delete.html');
	}

	/**
	 * ajax删除产品灵感
	 */
	public function ajax_del(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('产品不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Stuff();
			$stuff = $model->load((int)$id);
			
			// 仅管理员或本人具有删除权限
			if (!$this->visitor->can_edit() && !($stuff['user_id'] == $this->visitor->id)){
				return $this->ajax_notification('抱歉，你没有权限进行此操作！', true);
			}
			
			$model->remove((int)$id);
			
			// 删除关联对象
			$model->mock_after_remove($id, $stuff);
			
			// 从精选列表中删除
			if ($stuff['featured']){
				$diglist = new Sher_Core_Model_DigList();
				$diglist->remove_item(Sher_Core_Util_Constant::FEATURED_STUFF, (int)$id, Sher_Core_Util_Constant::TYPE_STUFF);
			}
			
			// 更新所属分类
			$category = new Sher_Core_Model_Category();
			
			$category->dec_counter('total_count', $stuff['category_id']);
			$category->dec_counter('total_count', $stuff['fid']);
			
			// 更新用户主题数量
			$this->visitor->dec_counter('stuff_count', $stuff['user_id']);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/del_ok.html');
	}

  /**
   * 用户参赛前完善个人信息
   */
  public function ajax_user_profile(){
    $result = array();

    if(!isset($this->stash['target_id'])){
			return $this->ajax_note('请求失败,缺少必要参数', true);
    }

    if(empty($this->stash['realname']) || empty($this->stash['phone']) || empty($this->stash['address']) || empty($this->stash['job'])){
      return $this->ajax_note('请求失败,缺少用户必要参数', true); 
    }

    $user_data = array();
    $user_data['profile']['realname'] = $this->stash['realname'];
    $user_data['profile']['phone'] = $this->stash['phone'];
    $user_data['profile']['address'] = $this->stash['address'];
    $user_data['profile']['job'] = $this->stash['job'];

    try {
      //更新基本信息
      $user_ok = $this->visitor->save($user_data);
      if(!$user_ok){
        return $this->ajax_note("更新用户信息失败", true);  
      }
      $redirect_url = sprintf("%s/contest_submit?contest_id=%d", Doggy_Config::$vars['app.url.stuff'], $this->stash['target_id']);
      return $this->ajax_json('保存成功.', false, $redirect_url);
    } catch (Sher_Core_Model_Exception $e) {
      Doggy_Log_Helper::error('Failed to contest user profile:'.$e->getMessage());
      return $this->ajax_note("更新失败:".$e->getMessage(), true);
    }
  
  }

  /**
   * 自动加载获取
   */
  public function ajax_fetch_more(){
    $category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
    // type=6;验证用户是否已点赞
		$type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
    $page = (int)$this->stash['page'];
    $size = (int)$this->stash['size'];
    $is_top = isset($this->stash['is_top']) ? (int)$this->stash['is_top'] : 0;
    $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
    $stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
    $featured = isset($this->stash['featured']) ? (int)$this->stash['featured'] : 0;
    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
    $verified = isset($this->stash['verified']) ? (int)$this->stash['verified'] : 0;
    $show_top = isset($this->stash['show_top']) ? (int)$this->stash['show_top'] : 0;
    $is_prize = isset($this->stash['is_prize']) ? (int)$this->stash['is_prize'] : 0;
        
    $query = array();
    $options = array();
        
		// 限制分类
		if($category_id){
			if ($is_top) {
				$query['fid'] = $category_id;
			} else {
				$query['category_id'] = $category_id;
			}
		}

		// 限制用户
    if ($user_id) {
      if(is_array($user_id)){
        $query['user_id'] = array('$in'=>$user_id);
      }else{
        $query['user_id'] = $user_id;
      }
    }

		// 推荐
		if($stick){
      if($stick==-1){
			  $query['stick'] = 0;
      }else{
			  $query['stick'] = 1;
      }
		}

		// 精选
		if($featured){
      if($featured==-1){
			  $query['featured'] = 0;
      }else{
			  $query['featured'] = 1;
      }
		}

    // 来源
    if($from_to){
      $query['from_to'] = $from_to;
    }
        
    // 已审核的
    if($verified){
      if($verified==-1){
        $query['verified'] = 0;
      }else{
        $query['verified'] = 1;   
      }
    }
        
		// 类别
    if($type){
    
    }

    // 是否中奖
    if($is_prize){
        $query['is_prize'] = $is_prize;
    }
        
    $options['page'] = $page;
    $options['size'] = $size;

		// 设置排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'hotest';
				break;
			case 2:
				$options['sort_field'] = 'comment';
				break;
			case 3:
				$options['sort_field'] = 'favorite';
				break;
			case 4:
				$options['sort_field'] = 'love';
				break;
			case 5:
				$options['sort_field'] = 'update';
				break;
      case 6:
        $options['sort_field'] = 'view';
      case 7:
        $options['sort_field'] = 'stick:update';
		}
        
    // 限制输出字段
    $some_fields = array(
      '_id'=>1, 'title'=>1, 'short_title'=>1, 'user_id'=>1, 't_color'=>1, 'top'=>1,
      'fine'=>1, 'stick'=>1, 'category_id'=>1, 'created_on'=>1, 'asset_count'=>1,
      'last_user'=>1, 'last_reply_time'=>1, 'cover_id'=>1, 'comment_count'=>1, 'view_count'=>1,
      'updated_on'=>1, 'favorite_count'=>1, 'love_count'=>1, 'deleted'=>1,'published'=>1, 'tags'=>1,
      'description'=>1, 'attrbute'=>1,
    );
    //$options['some_fields'] = $some_fields;

    $service = Sher_Core_Service_Stuff::instance();
        
    $resultlist = $service->get_stuff_list($query,$options);
    $next_page = 'no';
    if(isset($resultlist['next_page'])){
        if((int)$resultlist['next_page'] > $page){
            $next_page = (int)$resultlist['next_page'];
        }
    }

    // 验证用户是否点赞过
    if ($type==6 && $this->visitor->id){
      $favorite_model = new Sher_Core_Model_Favorite();
    }
        
    $max = count($resultlist['rows']);
    for($i=0;$i<$max;$i++){
        $symbol = isset($resultlist['rows'][$i]['user']['symbol']) ? $resultlist['rows'][$i]['user']['symbol'] : 0;
        if(!empty($symbol)){
          $s_key = sprintf("symbol_%d", $symbol);
          $resultlist['rows'][$i]['user'][$s_key] = true;
        }

        $is_loved = false;
        // 验证用户是否点赞过
        if ($type==6 && $this->visitor->id){
          $is_loved = $favorite_model->check_loved((int)$this->visitor->id, (string)$resultlist['rows'][$i]['_id'], Sher_Core_Model_Favorite::TYPE_STUFF);
        }
        $resultlist['rows'][$i]['is_loved'] = $is_loved;

        // 过滤用户表
        if(isset($resultlist['rows'][$i]['user'])){
          $resultlist['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($resultlist['rows'][$i]['user'], array('symbol_1', 'symbol_2'));
        }

        // 加索引
        $resultlist['rows'][$i]['index'] = $i+1;

        $resultlist['rows'][$i]['top_index'] = '';
        if(!empty($show_top) && $page==1){
          if($i==0){
            $resultlist['rows'][$i]['top_index'] = 'top1';
          }elseif($i==1){
            $resultlist['rows'][$i]['top_index'] = 'top2';         
          }elseif($i==2){
            $resultlist['rows'][$i]['top_index'] = 'top3';
          }
        }

    } //end for

    $data = array();
    $data['nex_page'] = $next_page;
    $data['results'] = $resultlist;
    $data['show_top3'] = !empty($show_top) ? true : false;
    
    return $this->ajax_json('', false, '', $data);
  }

	
	/**
	 * 删除某个附件
	 */
	public function delete_asset(){
		$id = $this->stash['id'];
		$asset_id = $this->stash['asset_id'];
		if (empty($id) || empty($asset_id)){
			return $this->ajax_note('附件不存在！', true);
		}
		$model = new Sher_Core_Model_Stuff();
		$model->delete_asset($id, $asset_id);
		
		return $this->to_taconite_page('ajax/delete_asset.html');
	}
	
	/**
	 * 编辑器参数
	 */
	protected function _editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$new_pic_id = new MongoId();
		$this->stash['editor_pid'] = (string)$new_pic_id;

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF_EDITOR;
	}

  /**
   * 评论参数
   */
  protected function _comment_param($options){
    $this->stash['comment_target_id'] = $options['comment_target_id'];
    $this->stash['comment_target_user_id'] = $options['comment_target_user_id'];
    $this->stash['comment_type'] = $options['comment_type'];

		// 评论的链接URL
		$this->stash['pager_url'] = $options['comment_pager'];

        // 是否显示图文并茂
        $this->stash['comment_show_rich'] = $options['comment_show_rich'];
		// 评论图片上传参数
		$this->stash['comment_token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['comment_domain'] = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$this->stash['comment_asset_type'] = Sher_Core_Model_Asset::TYPE_COMMENT;
		$this->stash['comment_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
    }
    
}
