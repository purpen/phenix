<?php
/**
 * 合作资源（设计品牌、院校、生成供应商）
 * @author purpen
 */
class Sher_Core_Model_Cooperation extends Sher_Core_Model_Base {
	
    protected $collection = 'cooperation';
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 状态标识
	const STATE_BLOCKED  = -1;
    const STATE_DISABLED = 0;
    const STATE_PENDING = 1;
    const STATE_OK = 2;
	
	# 资源类型
	const TYPE_BRAND   = 1;
	const TYPE_DESIGN  = 2;
	const TYPE_SCHOOL  = 3;
	const TYPE_AGENCY  = 4;
	const TYPE_DEVELOP = 5;
	
    protected $schema = array(
		'user_id'    => 0,
		
 		'name'       => '',
		'fullname'   => '',
		'summary'    => '',
		'keywords'   => array(),
		
		# logo设置
		'logo_id'       => '',
		'banner_id'     => '',
		
		'email'      => '',
		'phone'      => '',
		# 所在城市
		'province'   => '',
		'city'       => '',
		'address'    => '',
		
		# 联系人
		'people'   	 => '',
		'mobile'     => '',
		'wechat'     => '',
		
		# 网站
		'site_url'   => '',
		
		'view_count' => 0,
		
		# 等级
		'rank'       => 0,
		# 类型
		'type'       => self::TYPE_BRAND,
 		# 是否推荐
		'stick'      => 0,
		
		'state'      => self::STATE_DISABLED,
    );
	
    protected $required_fields = array('name','summary');
    protected $int_fields = array('user_id','rank','type','stick','state');
	
	protected $joins = array();
	
    //~ some event handles
    /**
	 * 保存之前,处理关键词中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['keywords']) && !is_array($data['keywords'])) {
	        $data['keywords'] = array_filter(array_values(array_unique(preg_split('/[,，\s]+/u', $data['keywords']))));
	    }
	    $data['updated_on'] = time();
	    parent::before_save($data);
	}
	
	/**
	 * 保存之后事件
	 */
  	protected function after_save() {
  	  	parent::after_save();
  	}

    protected function extra_extend_model_row(&$row) {
        $id = $row['id'] = $row['_id'];
		if(isset($row['summary'])){
			// 转码
			$row['summary'] = htmlspecialchars_decode($row['summary']);
		
			// 去除 html/php标签
			$row['strip_summary'] = strip_tags($row['summary']);
		}
    }
	
}
?>
