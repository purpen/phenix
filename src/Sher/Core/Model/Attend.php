<?php
/**
 * 活动报名/试用申请拉票表
 * @author purpen
 */
class Sher_Core_Model_Attend extends Sher_Core_Model_Base  {

  protected $collection = "attend";

  protected $required_fields = array('user_id', 'target_id');
  protected $int_fields = array('user_id', 'event', 'category_id', 'stick');
	
	# Event: 活动报名
  const EVENT_ACTIVE = 1;
  # 试用申请 拉票人数
	const EVENT_APPLY = 2;
  # 试用预热想要
  const EVENT_TRY_WANT = 3;

  # 专题/送红包
  const EVENT_SUBJECT = 5;
  # app商城风格显示
  const EVENT_APP_STORE_INDEX = 6;
	
  protected $schema = array(
    'user_id' => null,
    # 如果是专题：1. 云马C1PK; 2. 试用抽奖; 3. 兑吧抽奖送红包 4. 火眼项目入住 5.奶爸妈PK 6.兑吧3 7.文利 8.Fiu新用户送红包 9.--
    'target_id' => null,
    # 父分类
    'pid' => 0,
    # 分类
    'category_id' => 0,
    # 活动报名人数，抽奖次数 
    'ticket' => 1,
    'event'  => self::EVENT_ACTIVE,
    # 子ID, 用于专题PK论战 1.正方;2.反方；所属试用ＩＤ; 用于商城app首页类型展示：1.商品；2.专题；3.--；4.--
    'cid' => 0,
    'state' => 1,
    'stick' => 0,
    # 活动报名信息
    'info' => array(),
  );

  protected $joins = array(
    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
  );

	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {

        $row['cid_label'] = '';
        if($row['event']==self::EVENT_APP_STORE_INDEX){
            switch($row['cid']){
                case 1:
                    $row['cid_label'] = '商品';
                    break;
                case 2:
                    $row['cid_label'] = '专题';
                    break;
                case 3:
                    $row['cid_label'] = '--';
                    break;
                default:
                    $row['cid_label'] = '--';
            }
        }

    }

	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
		
		// 获取父级类及类组
		if (isset($data['category_id'])){
			$category = new Sher_Core_Model_Category();
			$result = $category->find_by_id((int)$data['category_id']);
			if (empty($result)){
				//throw new Sher_Core_Model_Exception('所选分类出错！');
			}
			$data['pid'] = $result['pid'];
		}
		
	    parent::before_save($data);
	}
	
	/**
	 * 报名成功后，更新对象数量
	 */
	protected function after_save() {
        //如果是新的记录
        if($this->insert_mode) {

          if ($this->data['event'] == self::EVENT_ACTIVE){
            $active = new Sher_Core_Model_Active();
            $active->inc_counter('signup_count', 1, (int)$this->data['target_id']);
            unset($active);
          }
          if ($this->data['event'] == self::EVENT_APPLY){
            $apply = new Sher_Core_Model_Apply();
            $apply->inc_counter('vote_count', 1, $this->data['target_id']);
            unset($apply);
          }
          if ($this->data['event'] == self::EVENT_TRY_WANT){
            $try = new Sher_Core_Model_Try();
            $try->increase_counter('want_count', 1, (int)$this->data['target_id']);
            unset($try);
          }

            $model = new Sher_Core_Model_Category();
            if (!empty($this->data['category_id'])) {
                $model->inc_counter('total_count', 1, $this->data['category_id']);
            }
            
        }

		parent::after_save();
	}
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id,$ticket) {
    //活动报名人数减1
		//$active = new Sher_Core_Model_Active();
		//$active->dec_counter('signup_count', (int)$id);
		
		//unset($active);
	}

	
  /**
   * 检测是否报名
   */
  public function check_signup($user_id, $target_id, $event=1){
    $int_target_ids = array(self::EVENT_ACTIVE, self::EVENT_TRY_WANT, self::EVENT_SUBJECT);
    if(in_array((int)$event, $int_target_ids)){
      $query['target_id'] = (int) $target_id;    
    }else{
      $query['target_id'] = $target_id;    
    }

    $query['user_id'] = (int) $user_id;
    $query['event'] = (int) $event;
    $result = $this->count($query);

    return $result>0?true:false;
  }

    /**
     * 标记为推荐
     */
    public function mark_as_stick($id) {
        return $this->update_set($id, array('stick' => 1));
    }
	
    /**
     * 取消推荐
     */
	public function mark_cancel_stick($id) {
		return $this->update_set($id, array('stick' => 0));
	}
	
}

