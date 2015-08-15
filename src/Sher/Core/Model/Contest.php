<?php
/**
 * 征集大赛
 * @author purpen
 */
class Sher_Core_Model_Contest extends Sher_Core_Model_Base  {

    protected $collection = "contest";
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 状态
	const STATE_DEFAULT   = 1;
	const STATE_PUBLISHED = 2;
	
    protected $schema = array(
        'short_title' => '',
    	'title'       => '',
        # 标识
        'short_name'  => '',
        # 简介
        'summary'     => '',
        'content'     => '',
        # 外链
        'link'        => '',
        
        # 开始/截止日期
        'start_date'  => '',
        'finish_date' => '',
        # 状态: 1,进行中, 2,审核中,  5.结束
        'step_stat' => 1,
        
        'cover_id'    => '',
        
        # 作品数量
        'stuff_count' => 0,
        'view_count'  => 0,
        # 创建用户
        'user_id'     => 0,
        
        'state'       => self::STATE_DEFAULT,
        'published_on'=> 0,
    );
	
    protected $joins = array(
        'cover'  => array('cover_id' => 'Sher_Core_Model_Asset'),
    );
	
    protected $required_fields = array('user_id', 'title', 'short_title');
    protected $int_fields = array('user_id', 'stuff_count', 'view_count', 'state', 'step_stat');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
        $row['view_url'] = Sher_Core_Helper_Url::contest_view_url($row['_id']);;
		// 验证是否指定封面图
		if(empty($row['cover_id'])){
			$this->mock_cover($row);
    }
        // 状态
        $row['step_label'] = '';
        if($row['step_stat']){
          switch($row['step_stat']){
            case 1:
              $row['step_label'] = '进行中';
              break;
            case 2:
              $row['step_label'] = '审核中';
              break;
            case 5:
              $row['step_label'] = '结束';
              break;
          }
        }
    }
    
	/**
	 * 获取第一个附件作为封面图
	 */
	protected function mock_cover(&$row){
		$asset = new Sher_Core_Model_Asset();
		$cover = $asset->first(array(
			'parent_id'  => (int)$row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_CONTEST,
		));
		
		$row['cover_id'] = (string)$cover['_id'];
		$row['cover'] = $asset->extended_model_row($cover);
	}
	
	/**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => (int)$parent_id));
			}
		}
	}
    
	/**
	 * 更新发布上线
	 */
	public function mark_as_publish($id, $published=2) {
		return $this->update_set($id, array('state' => $published));
	}
    
	/**
	 * 投票成功后，更新对象票数
	 */
	protected function after_save() {
        
	}

}
