<?php
/**
 * 推荐图片管理
 * @author purpen
 */
class Sher_Core_Model_Advertise extends Sher_Core_Model_Base  {

    protected $collection = "advertise";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;
    
	const STATE_DRAFT = 1;
	const STATE_PUBLISHED = 2;
	
	# 类型
	const TYPE_URL  = 1;
	const TYPE_ID   = 2;
	const TYPE_WORD = 3;

  # 来源
  const KIND_SITE = 1;
  const KIND_WAP = 2;
  const KIND_APP = 3;
	
	# 显示方式
	const MODE_IMAGE = 1;
	const MODE_TXTANDIMA = 2;
	
    protected $schema = array(
        'space_id' => 0,
		
		'cate_title' => '',
		'title' => '',
		'sub_title' => '',
        'web_url' => '',
        'b_color' => 0,
		'summary' => '',
		
		# 背景色
		'bgcolor' => '',
		# 按钮标题
		'btn_title' => '',
		# 文字对齐方式
		'text_align' => 'left',
		
		# 类型
		'type' => self::TYPE_URL,
		# 图片或文本显示方式
    'mode' => self::MODE_TXTANDIMA,
    # 来源地
    'kind' => 1,
		
		# 附件图片
		'cover_id' => '',
		
		# 浏览数量
		'view_count' => 0,
		# 点击数量
		'click_count' => 0,
		
		# 排序
		'ordby' => 0,
		
		# 是否发布、草稿
		'state' => self::STATE_DRAFT,
    );
	
    protected $required_fields = array('title', 'web_url');
	
    protected $int_fields = array('space_id', 'view_count', 'click_count', 'ordby', 'state', 'mode', 'b_color', 'kind');
    
	
	protected $joins = array(
	    'space'  => array('space_id' => 'Sher_Core_Model_Space'),
		'cover'  => array('cover_id' => 'Sher_Core_Model_Asset'),
	);
	
    protected function extra_extend_model_row(&$row) {
    	$row['view_url'] = Sher_Core_Helper_Url::ad_view_url($row['_id']);
		$row['mm_view_url'] = sprintf(Doggy_Config::$vars['app.url.wap'].'/tracker?kid=%d', $row['_id']);
		if(!isset($row['bgcolor'])){
			$row['bgcolor'] = '#000000';
		}
		if(!isset($row['text_align'])){
			$row['text_align'] = 'left';
		}
		if(!isset($row['btn_title'])){
			$row['btn_title'] = '了解详情';
		}
		if(!isset($row['mode'])){
			$row['mode'] = self::MODE_TXTANDIMA;
		}

        switch($row['type']){
            case 1:
                $row['type_label'] = '链接';
                break;
            case 2:
                $row['type_label'] = '商品';
                break;
            case 3:
                $row['type_label'] = '关键词';
                break;
            case 4:
                $row['type_label'] = '专题';
                break;
            case 5:
                $row['type_label'] = '--';
                break;
            case 6:
                $row['type_label'] = '评测';
                break;
            case 8:
                $row['type_label'] = '情境(Fiu)';
                break;
            case 11:
                $row['type_label'] = '专题(Fiu)';
                break;
            case 12:
                $row['type_label'] = '地盘(Fiu)';
                break;
            default:
                $row['type_label'] = '';
        }
    }
	
	/**
	 * 添加自定义ID
	 */
    protected function before_insert(&$data) {
        $data['_id'] = $this->gen_adv_id();
		
		parent::before_insert($data);
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
	 * 生成id编号, 9位数字符
	 */
	protected function gen_adv_id($prefix='1'){
		$name = Doggy_Config::$vars['app.serialno.name'];
		
		$kid = $prefix;
		$val = $this->next_seq_id($name);
		
		$len = strlen((string)$val);
		if ($len <= 3) {
			$kid .= date('md');
			$kid .= sprintf("%03d", $val);
		}else{
			$kid .= substr(date('md'), 0, 8 - $len);
			$kid .= $val; 
		}
		
		return (int)$kid;
	}
	
}

