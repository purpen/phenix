<?php
/**
 * 体验店列表
 * @author purpen
 */
class Sher_Core_Model_Estore extends Sher_Core_Model_Base {
    
    protected $collection = "estore";
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;
    
    // 店铺城市列表
    public static $city = array(
        '0001' => array(
            'name' => '北京', // 39.8707070000,116.3666050000
            'lng' => 116.3666050000, // 经度
            'lat' => 39.8707070000, // 纬度
        ),
        '0002' => array(
            'name' => '上海', // 31.2363050000,121.4802370000
            'lng' => 121.4802370000, // 经度
            'lat' => 31.2363050000, // 纬度
        ),
        '0003' => array(
            'name' => '广州', // 23.1353080000,113.2707930000
            'lng' => 113.2707930000, // 经度
            'lat' => 23.1353080000, // 纬度
        ),
        '0004' => array(
            'name' => '深圳', // 22.5485150000,114.0661120000
            'lng' => 114.0661120000, // 经度
            'lat' => 22.5485150000, // 纬度
        ),
        '0005' => array(
            'name' => '杭州', // 30.2800590000,120.1616930000
            'lng' => 120.1616930000, // 经度
            'lat' => 30.2800590000, // 纬度
        ),
        '0006' => array(
            'name' => '成都', // 30.5762790000,104.0712160000
            'lng' => 104.0712160000, // 经度
            'lat' => 30.5762790000, // 纬度
        ),
        '0007' => array(
            'name' => '西安', // 34.3474360000,108.9463060000
            'lng' => 108.9463060000, // 经度
            'lat' => 34.3474360000, // 纬度
        ),
        '0008' => array(
            'name' => '武汉', // 30.5984280000,114.3118310000
            'lng' => 114.3118310000, // 经度
            'lat' => 30.5984280000, // 纬度
        ),
    );
    
    // 审核状态
    const APPROVED_NO   = 1;
    const APPROVED_OK   = 2;
    
    protected $schema = array(
        '_id'            => null,
        'name'           => '',
		# 优势/亮点
		'advantage'      => '',
        'summary'        => '',
        'phone'          => '',
        'worktime'       => '',
        # 合作方式，经销店、深度合作体验店
        'type'           => 0,
        # 合作产品数量
        'product_count'  => 0,
        
        'user_id'        => 0,
        
        'cover_id'       => 0,
        # 图片数组
		'asset' => array(),
        
        # 地址位置
        'location'       => array(
            'type' => 'Point',
            # 经度,纬度
            'coordinates' => array(),
        ),
        'address'        => '',
        
        # 计数
        'view_count'     => 0,
        
        # 推荐
        'sticked'        => 0,
        
        # 是否删除
        'deleted'        => 0,
        # 是否审核
        'approved'       => self::APPROVED_NO,
        
        # 随机数
        'random'         => 0,
    );
    
    protected $required_fields = array('user_id','name');
    protected $int_fields = array('user_id','deleted','approved');
    
    protected $joins = array(
        'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
    );
    
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {        
		
        // 去除 html/php标签
		$row['strip_summary'] = strip_tags(htmlspecialchars_decode($row['summary']));
        
        // 转化坐标
        if ($row['location']['coordinates']) {
            $row['location']['point'] = array('lng' => $row['location']['coordinates'][0], 'lat' => $row['location']['coordinates'][1]);
        }
	}
    
    /**
     * 重建封面图
     */
    public function rebuild_cover($cover) {
        $images = array();
        if (!empty($cover) && !empty($cover['thumbnails'])) {
            $images['mini']   = $cover['thumbnails']['mini']['view_url'];
            $images['medium'] = $cover['thumbnails']['medium']['view_url'];
            $images['large']  = $cover['thumbnails']['large']['view_url'];
        }
        return $images;
    }
    
	// 添加自定义ID
    protected function before_insert(&$data) {
        $data['_id'] = $this->gen_custom_id();
		Doggy_Log_Helper::warn("Create new estore ".$data['_id']);
		
		parent::before_insert($data);
    }
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
		// 新建数据,补全默认值
		if ($this->is_saved()){
			// 添加随机数
			$data['random'] = Sher_Core_Helper_Util::gen_random();
		}
		
	    parent::before_save($data);
	}
    
	/**
	 * 生成自定义ID, ID六位数字符
	 */
	protected function gen_custom_id($prefix='1'){
		$name = Doggy_Config::$vars['app.serialno.name'];
		$val = $this->next_seq_id($name);
        
		$sid  = $prefix;
        $sid .= sprintf("%05d", $val);
		Doggy_Log_Helper::debug("Gen to id [$sid]");
		
		return (int)$sid;
	}
    
    /**
     * 通过审核
     */
	public function mark_as_approved($id){
		return $this->update_set($id, array('approved' => self::APPROVED_OK));
	}
	
    /**
     * 取消审核
     */
	public function mark_cancel_approved($id){
		return $this->update_set($id, array('approved' => self::APPROVED_NO));
	}
    
    /**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id' => $id));
		unset($asset);
		return true;
	}
    
}