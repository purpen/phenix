<?php
/**
 * 新品试用
 * @author purpen
 */
class Sher_Wap_Action_Try extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
    'page_title_suffix' => '新品试用-太火鸟智能硬件孵化平台',
    'page_keywords_suffix' => '智能硬件社区,孵化需求,活动动态,品牌专区,产品评测,太火鸟,智能硬件,智能硬件孵化,孵化社区,创意众筹,硬件营销,硬件推广',
    'page_description_suffix' => '【免费】申请智能硬件产品试用，发表产品评测，尽在太火鸟智能硬件孵化平台。',
	);
	
	// 一个月时间
	protected $month =  2592000;
	
	protected $page_tab = 'page_index';
	protected $page_html = 'page/index.html';
	
	protected $exclude_method_list = array('execute','getlist','view','test');
	
	
	/**
	  *拉票
	 */
	public function test(){
		return $this->to_html_page('wap/try_success.html');
	}
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 首页
	 */
	public function getlist(){
		return $this->to_html_page('wap/trylist.html');
	}
	
	/**
	 * 查看评测
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		$tpl = 'wap/try_show.html';
		
		$redirect_url = Doggy_Config::$vars['app.url.wap.try'];
		if(empty($id)){
			return $this->show_message_page('访问的公测产品不存在！', $redirect_url);
		}
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Try();
		$try = &$model->extend_load($id);
		
		if(empty($try)){
			return $this->show_message_page('访问的公测产品不存在或已被删除！', $redirect_url);
		}

    //添加网站meta标签
    $this->stash['page_title_suffix'] = sprintf("%s-新品试用-太火鸟智能硬件孵化平台", $try['title']);
    if(!empty($try['tags'])){
      $this->stash['page_keywords_suffix'] = sprintf("太火鸟,智能硬件,智能硬件孵化平台,新品试用,%s,产品评测", $try['tags'][0]);   
    }
    $this->stash['page_description_suffix'] = sprintf("【免费】申请%s试用，发表产品评测，更多智能硬件使用，就在太火鸟智能硬件孵化平台。", $try['short_title']);
		
		// 增加pv++
		$model->increase_counter('view_count', 1, $id);
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		$this->stash['try'] = &$try;
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::topic_view_url($id, '#p#');
		
		// 获取省市列表
		$areas = new Sher_Core_Model_Areas();
		$provinces = $areas->fetch_provinces();
		
		$this->stash['provinces'] = $provinces;
		
		// 评测报告分类
		$this->stash['report_category_id'] = Doggy_Config::$vars['app.try.report_category_id'];

    //评论参数
    $comment_options = array(
      'comment_target_id' =>  $try['_id'],
      'comment_target_user_id' =>  $try['user_id'],
      'comment_type'  =>  3,
      //是否显示上传图片/链接
      'comment_show_rich' => 1,
    );
    $this->_comment_param($comment_options);
		
		return $this->to_html_page($tpl);
	}
	
	/**
	 * 提交申请
	 */
	public function ajax_apply(){
    $this->stash['stat'] = 0;
    $this->stash['msg'] = null;
		if (!isset($this->stash['target_id'])){
      $this->stash['msg'] = '缺少请求参数';
			return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');
		}
		
		$target_id = $this->stash['target_id'];
		$user_id = $this->visitor->id;
		
		try{
			// 验证是否结束
			$try = new Sher_Core_Model_Try();
			$row = $try->extend_load((int)$target_id);
			if($row['is_end']){
        $this->stash['msg'] = '抱歉，活动已结束，等待下次再来！';
			  return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');
			}
			
			// 检测是否已提交过申请
			$model = new Sher_Core_Model_Apply();
			if(!$model->check_reapply($user_id,$target_id)){
        $this->stash['msg'] = '你已提交过申请，无需重复提交！';
			  return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');
			}
			
			if(empty($this->stash['_id'])){
				if(isset($this->stash['id'])){
					unset($this->stash['id']);
				}
				$this->stash['user_id'] = $user_id;
				
				$ok = $model->apply_and_save($this->stash);
			}
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create apply failed: ".$e->getMessage());
      $this->stash['msg'] = '提交失败，请重试！';
      return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');
		}
		$this->stash['stat'] = 1;
    $this->stash['msg'] = '申请提交成功，等待审核.';
    return $this->to_taconite_page('ajax/wap_apply_try_show_error.html');
	}

  /**
   * 评论参数
   */
  protected function _comment_param($options){
    $this->stash['comment_target_id'] = $options['comment_target_id'];
    $this->stash['comment_target_user_id'] = $options['comment_target_user_id'];
    $this->stash['comment_type'] = $options['comment_type'];
		// 评论的链接URL
		$this->stash['pager_url'] = isset($options['comment_pager'])?$options['comment_pager']:0;

    //是否显示图文并茂
    $this->stash['comment_show_rich'] = isset($options['comment_show_rich'])?$options['comment_show_rich']:0;
		// 评论图片上传参数
		$this->stash['comment_token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['comment_domain'] = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$this->stash['comment_asset_type'] = Sher_Core_Model_Asset::TYPE_COMMENT;
		$this->stash['comment_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
  }

}

