<?php
/**
 * 产品试用
 * @author purpen
 */
class Sher_Core_Model_Try extends Sher_Core_Model_Base  {

    protected $collection = "trial";
	
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	## 参与方式
	const JOIN_FREE_AWAY = 1;
	const JOIN_PAY_AWAY  = 2;
	
	## 状态
	const STATE_DRAFT = 0;
	const STATE_PUBLISH = 1;

	## 申请限制
	const APPLY_TERM_NO = 0;  // 不限制
	const APPLY_TERM_LEVEL = 1; // 等级限制
	const APPLY_TERM_MONEY = 2; //  鸟币限制

	## 类型
	const KIND_FREE = 1;  // 零元免费
	const KIND_SUPER = 2; // 超级试用
	const KIND_FAST = 3;  // 闪电试用
	
    protected $schema = array(
		'title' => '',
        'short_title' => '',
		# 简述，活动亮点
		'description' => '',
		# 内容，详细说明
		'content' => '',
        # 第几期
        'season' => 0,
        # 状态: 0.预热, 1,申请中, 2,审核中, 3.提交反馈, 5.结束
        'step_stat' => 0,
        
        # 标签
        'tags' => array(),
		
		# 封面图
		'cover_id' => '',
		# banner图
		'banner_id' => '',
		
		# 浏览数
		'view_count' => 0,
		'love_count' => 0,
		'comment_count' => 0,
		
		# 活动发起人
		'user_id' => 0,
 		# 关联的产品
    	'product_id' => 0,
		# 购买链接
		'buy_url' => null,
		
		# 试用数量
		'try_count'  => 0,
		# 申请人数
		'apply_count' => 0,
		# 评测报告数量
		'report_count' => 0,
        # 虚拟申请人数
        'invented_apply_count' => 0,
		# 申请通过的人员
		'pass_users' => array(),

		# 预热想要数量
		'want_count' => 0,
	
		# 预热开启条件(想要人数)
		'open_limit' => 0,
		
		# 参与方式
		'join_away' => self::JOIN_FREE_AWAY,
		# 开始时间
		'start_time' => 0,
		# 结束时间
		'end_time' => 0,
        # 名单公布时间
        'publish_time' => 0,
        
        # 品牌介绍 
        'brand_introduce' => null,

        # 图片集 brand_avatar/ios/android
        'imgs' => array(
            'brand_avatar' => null,
            'qr_ios' => null,
            'qr_android' => null,
        ),

		# 申请限制开关
        'apply_term' => self::APPLY_TERM_NO,
		# 限制条件，比如等级和鸟币
        'term_count' => 0,

		# 申请类型
		'kind' => self::KIND_FREE,
		
		# 设置推荐
		'sticked' => 0,
		# 草稿、发布
		'state' => self::STATE_DRAFT,
    );
	
    protected $joins = array(
    	'product' => array('product_id' => 'Sher_Core_Model_Product'),
		'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
		'banner' => array('banner_id' => 'Sher_Core_Model_Asset'),
		'user' => array('user_id' => 'Sher_Core_Model_User'),
    );
	
    protected $required_fields = array('title', 'user_id');
	
    protected $int_fields = array('user_id', 'product_id', 'sticked', 'join_away', 'try_count', 'apply_count', 'pass_count', 'season', 'step_stat', 'invented_apply_count', 'apply_term', 'want_count', 'report_count', 'kind', 'open_limit');
	
	protected $counter_fields = array('view_count', 'love_count', 'comment_count', 'apply_count', 'invented_apply_count', 'want_count', 'report_count');
	/**
	 * 扩展关联数据
	 */
  protected function extra_extend_model_row(&$row) {
    	$row['view_url'] = sprintf(Doggy_Config::$vars['app.url.try.view'], $row['_id']);
    	$row['wap_view_url'] = sprintf(Doggy_Config::$vars['app.url.wap.try.view'], $row['_id']);
        $row['comment_view_url'] = sprintf(Doggy_Config::$vars['app.url.try'].'/view/%d/%d', $row['_id'], 1);
        
		if(!isset($row['short_title']) || empty($row['short_title'])){
			$row['short_title'] = $row['title'];
		}
		
		# 审核通过人数
		$row['pass_count'] = count($row['pass_users']);
		
		if(isset($row['content'])){
			// 转码
			$row['content'] = htmlspecialchars_decode($row['content']);
		
			// 去除 html/php标签
			$row['strip_content'] = strip_tags($row['content']);
		}
		
		# 验证是否结束
		if (isset($row['end_time'])){
			// end_time 是0时，应该按24时截止，+1day
			if(strtotime($row['end_time'])+24*60*60 < time()){
				$row['is_end'] = true;
			}else{
				$row['is_end'] = false;
			}
		}

    // 灰型说明
    if(isset($row['kind'])){
      switch($row['kind']){
        case 1:
          $row['kind_label'] = '0元免费';
          break;
        case 2:
          $row['kind_label'] = '超级试用';
          break;
        case 3:
          $row['kind_label'] = '闪电试用';
          break;
        default:
          $row['kind_label'] = '--';
      }
    }

    if(isset($row['step_stat'])){
      switch((int)$row['step_stat']){
        case 0:
          $row['step_label'] = '预热';
          break;
        case 1:
          $row['step_label'] = '申请中';
          break;
        case 2:
          $row['step_label'] = '审核中';
          break;
        case 3:
          $row['step_label'] = '报告回收中';
          break;
        case 5:
          $row['step_label'] = '结束';
          break;
        default:
          $row['step_label'] = '--';
      }
    }
		
  }

	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
    if (isset($data['tags']) && !is_array($data['tags'])) {
        $data['tags'] = array_values(array_unique(preg_split('/[,，\s]+/u',$data['tags'])));
    }
		
	  parent::before_save($data);
	}
	
	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => $parent_id));
			}
		}
	}
	
	/**
	 * 更新通过的用户
	 */
	public function update_pass_users($id, $user_id, $is_add=true){
		if ($is_add) {
			$new_data = array(
				'$addToSet' => array('pass_users' => (int)$user_id),
			);
		} else {
			$new_data = array(
				'$pull' => array('pass_users' => (int)$user_id),
			);
		}
		
		return $this->update($id, $new_data);
	}
	
	/**
	 * 增加计数
	 */
	public function increase_counter($field_name, $inc=1, $id=null){
		if(is_null($id)){
			$id = $this->id;
		}
		if(empty($id) || !in_array($field_name, $this->counter_fields)){
			return false;
		}
		
		return $this->inc($id, $field_name, $inc);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($count_name,$id=null,$force=false){
	    if(is_null($id)){
	        $id = $this->id;
	    }
	    if(empty($id)){
	        return false;
	    }
		if(!$force){
			$stuff = $this->find_by_id($id);
			if(!isset($stuff[$count_name]) || $stuff[$count_name] <= 0){
				return true;
			}
		}
		return $this->dec($id, $count_name);
	}
	
	/**
	 * 设置封面图
	 */
	public function mark_set_cover($id, $cover_id){
		return $this->update_set($id, array('cover_id'=>$cover_id));
	}
	
	/**
	 * 设置Banner
	 */
	public function mark_set_banner($id, $banner_id){
		return $this->update_set($id, array('banner_id'=>$banner_id));
	}
	
    /**
     * 标记为推荐
     */
    public function mark_as_stick($id) {
        return $this->update_set($id, array('sticked' => 1));
    }
	
    /**
     * 取消推荐
     */
	public function mark_cancel_stick($id) {
		return $this->update_set($id, array('sticked' => 0));
	}
	
	/**
	 * 更新发布上线
	 */
	public function mark_as_publish($id, $published=1) {
		return $this->update_set($id, array('state' => $published));
	}
	
}
