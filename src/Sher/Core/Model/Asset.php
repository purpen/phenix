<?php
/**
 * 附件 Model
 * @author purpen
 */
class Sher_Core_Model_Asset extends Sher_Core_Model_Base {

    protected $collection = "asset";
	
	private $file = null;
	private $file_content = null;

    const STATE_FAIL = 0;		//处理失败
    const STATE_PENDING = 1;
    const STATE_OK = 2;

    //类型
    const KIND_IMG = 1;
    const KIND_FILE = 2;
	
	# 照片
    const TYPE_PHOTO = 1;
	
	# 普通附件，编辑器图片
	const TYPE_ASSET = 2;
	# 广告图片
	const TYPE_AD = 3;
	
    # 用户头像
    const TYPE_AVATAR = 4;
	
	# 产品图片
	const TYPE_PRODUCT = 10;
    # 产品banner图
    const TYPE_PRODUCT_BANNER = 11;

    # 产品png图
    const TYPE_PRODUCT_PNG = 12;
    # 产品pad banner图
    const TYPE_PRODUCT_PAD_BANNER = 13;
	const TYPE_EDITOR_PRODUCT = 15;
	
	const TYPE_STORE = 25; // 店铺
	
	# 评测图片/配图(小图)
	const TYPE_TRY     = 30;
    const TYPE_TRY_F   = 31;
	
	# 话题图片
	const TYPE_TOPIC   = 50;
	# 话题编辑器图片
	const TYPE_EDITOR_TOPIC = 55;
    # 话题附件
    const TYPE_FILE_TOPIC = 56;

    # 合作联系图片,1.产品合作
    const TYPE_CONTACT = 60;

    # 分类标签封面图
    const TYPE_STYLE_TAG = 61;

    # 后台区块编辑器图片
    const TYPE_EDITOR_BLOCK = 63;

    # 活动图片
    const TYPE_ACTIVE = 65;
    # 活动编辑器图
    const TYPE_EDITOR_ACTIVE = 66;
    # 活动用户上传图
    const TYPE_USER_ACTIVE = 67;
	
	# 产品灵感
	const TYPE_STUFF = 70;
	const TYPE_STUFF_EDITOR = 71;
	
	# 合作资源
	const TYPE_COOPERATE = 80;
	const TYPE_COOPERATE_EDITOR = 81;

	# 评价关联图片
	const TYPE_COMMENT_EVALUATE = 84;
    # 评论图片
    const TYPE_COMMENT = 85;

    # 实验室图片/编辑器图片
    const TYPE_DEVICE = 86;
    const TYPE_DEVICE_EDITOR = 87;
    
    # 大赛封面
    const TYPE_CONTEST = 90;

    # 媒体报道封面
    const TYPE_REPORT = 92;
	
	# 专辑封面
    const TYPE_ALBUMS = 95;
	# 图片
	const TYPE_SPECIAL_SUBJECT = 96;
	
	# 专辑封面
	const TYPE_SPECIAL_COVER = 98;

	#情境图片
	const TYPE_GPRODUCT = 97;
	const TYPE_GPRODUCT_EDITOR = 99;
	
	const TYPE_SCENE_BRANDS = 100;
	const TYPE_SCENE_SIGHT = 102;
    # 封面/地盘头像/Banner
	const TYPE_SCENE_SCENE = 101;
	const TYPE_SCENE_AVATAR = 106;
	const TYPE_SCENE_BANNER = 107;
    const TYPE_SCENE_DRAFT = 108;
	const TYPE_GPRODUCT_BANNER = 120;
	const TYPE_GPRODUCT_PNG = 121;
	
	const TYPE_ID_CARD = 125;
	const TYPE_BUSINESS_CARD = 126;
	const TYPE_USER_HEAD_PIC = 127;

    #情景品牌cover/Banner
    const TYPE_SCENE_BRANDS_PRODUCT = 128;
	const TYPE_SCENE_BRANDS_BANNER = 129;

    # 情境专题封面
    const TYPE_SCENE_SUBJECT = 130;
    const TYPE_SCENE_SUBJECT_BANNER = 131;

    # Fiu 主题
    const TYPE_THEME = 134;
    const TYPE_THEME_BANNER = 135;

    # sku封面
    const TYPE_SKU_COVER = 140;

    # 微信商务合作附件
    const TYPE_WX_COOPERATE = 145;

    protected $schema = array(
		'user_id' => '',
    	'parent_id' => '',
		
		## 原图信息
		'file_id' => '',
    	'filepath' => '',
		'filename' => '',
        'size' => 0,
        'width' => 0,
        'height' => 0,
		'mime' => null,
    	'desc'  =>  null,
		
		## 缩略图组
		/*
	    'mini'=> array(
			'filepath' => '',
			'width' => '',
			'height' => '',
			'type' => '',
		)*/
		'thumbnails' => array(
			'mini'    => array(),
			'tiny'    => array(),
			'small'   => array(),
			'medium'  => array(),
			'large'   => array(),
			'big'     => array(),
			'huge'    => array(),
			'massive' => array(),
		),

        // 类型
        'kind' => 1,
		
		'domain' => Sher_Core_Util_Constant::STROAGE_ASSET,
		'asset_type' => self::TYPE_PRODUCT,
		'state' => 1,
    );
	
	# 附件类型
	protected $thumbnails = array('mini','tiny','small','medium','large','big','huge','massive');
	
	protected $thumbnails_styles = array(
		'mini' => 's.jpg', # 160x120
		'tiny' => 'ti.jpg', # 160x120
		'small' => 'sm.jpg', # 280x210
		'medium' => 'me.jpg', # 320x240
		'large' => 'la.jpg',  # 580x435
		'big' => 'bi.jpg',  # 700x525 有水印
		'huge' => 'hu.jpg', # 900x*
		'massive' => 'ma.jpg', #
	);
	
	protected $retrieve_fields = array('filepath'=>1,'thumbnails'=>1,'asset_type'=>1,'parent_id'=>1, 'size'=>1, 'desc'=>1, 'width'=>1, 'height'=>1, 'filename'=>1, 'kind'=>1);
	
	# 响应式设计所需图
	protected $thumbnails_resp = array(
		'resp' => 'resp.jpg', # 480x360
		'hd'   => 'hd.jpg', # 1180x*
		'md'   => 'm.jpg',  # 180x180
		'hm'   => 'hm.jpg', # 420x250
		# 头像截图
		'ava'  => 'ava.jpg',  # 180x180
		# app Banner(通用)
		'aub'  => 'p750x422.jpg',  // 750x422
		# app 商品封面
		'apc' => 'p500x500.jpg', // 500x500
		# app 分类小图
		'acs' => 'p325x200.jpg', // 325x200
		# 除商品编辑器图片，带水印
		'hdw' => 'hdw.jpg', // 1180x*
        # Fiu场景缩略图
        'asc' => 'p170x300.jpg', // 170x300
	);

    protected $required_fields = array('filepath');
	
    protected $int_fields = array('user_id','size','width','height','asset_type','state');
	
    protected function extra_extend_model_row(&$row) {
        $row['id'] = (string)$row['_id'];
		if (!empty($row['filepath'])){
			$row['fileurl'] = Sher_Core_Helper_Url::asset_qiniu_view_url($row['filepath']);
		}
		
		$this->extend_asset_view_url($row);
    }
	
	/**
	 * 重建asset url
	 */
	protected function extend_asset_view_url(&$row){
		if (isset($row['thumbnails']) && is_array($row['thumbnails'])) {
			foreach($row['thumbnails'] as $key => $value){
				$row['thumbnails'][$key]['view_url'] = Sher_Core_Helper_Url::asset_qiniu_view_url($row['filepath'], $this->thumbnails_styles[$key]);
			}
			# 响应式设计所需图
			foreach($this->thumbnails_resp as $key => $value){
				$row['thumbnails'][$key]['view_url'] = Sher_Core_Helper_Url::asset_qiniu_view_url($row['filepath'], $this->thumbnails_resp[$key]);
			}
		} else {
			// 设置默认值
			$row['thumbnails'] = array(
				'mini'    => array(
					'view_url' => Doggy_Config::$vars['app.url.default_thumb_small'],
				),
				'tiny'    => array(
					'view_url' => Doggy_Config::$vars['app.url.default_thumb_small'],
				),
				'small'   => array(
					'view_url' => Doggy_Config::$vars['app.url.default_thumb_small'],
				),
				'medium'  => array(
					'view_url' => Doggy_Config::$vars['app.url.default_thumb_middle'],
				),
				'large'   => array(
					'view_url' => Doggy_Config::$vars['app.url.default_thumb_large'],
				),
				'big'     => array(
					'view_url' => Doggy_Config::$vars['app.url.default_thumb_big'],
				),
				'huge'    => array(
					'view_url' => Doggy_Config::$vars['app.url.default_thumb_huge'],
				),
				'massive' => array(
					'view_url' => Doggy_Config::$vars['app.url.default_thumb_massive'],
				),
				'main' => array(
					'view_url' => Doggy_Config::$vars['app.url.default_thumb_massive'],
				),
			);
		}
	}
	
	/**
	 * 更新附件类型
	 */
	public function update_thumbnails($thumb=array(),$type='tiny',$id=null) {
		if(in_array($type, $this->thumbnails)){
			# 过滤字段
			$new_data = array(
				'filepath' => $thumb['filepath'],
				'width' => floor($thumb['width']),
				'height' => floor($thumb['height']),
			);
			
			$this->update_set($id, array("thumbnails.${type}" => $new_data));
		}
	}
	
	/**
	 * 
	 */
    protected function before_save(&$data) {
		
    }
	
	/**
	 * 生成记录后，写文件进磁盘
	 */
	protected function after_save() {
		$file = $this->file();
		$file_content = $this->file_content();
		
	    $path = $this->filepath;
		
		Doggy_Log_Helper::debug("Path: $path, File: $file.");
		
		if(!is_null($path)){
			try{
				if (!is_null($file)) {
					// Sher_Core_Util_Asset::storeAsset(Sher_Core_Util_Constant::ASSET_DOAMIN, $path, $file);
					// 云存储方式
					Sher_Core_Util_Asset::store_asset_cloud($path, $file);
				}
				
				if (!is_null($file_content)) {
					Doggy_Log_Helper::debug("File content length: ".strlen($file_content));
					// Sher_Core_Util_Asset::storeData(Sher_Core_Util_Constant::ASSET_DOAMIN, $path, $file_content);
					Sher_Core_Util_Asset::store_data_cloud($path, $file_content);
				}
				
			}catch(Sher_Core_Util_Exception $e){
				Doggy_Log_Helper::error('Save asset file failed. ' . $e->getMessage());
				throw new Sher_Core_Model_Exception('Save asset file failed. ' . $e->getMessage());
			}
			
			// 生成其他缩略图放入任务队列
			$args = array(
				'asset_type' => $this->asset_type
			);
			
			// Sher_Core_Jobs_Queue::maker_thumb((string)$this->data['_id'], $args);
		}
    }
	
	/**
	 * 批量更新附件所属对象
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		for($i=0; $i<count($ids); $i++){
			$this->update_set($ids[$i], array('parent_id' => $parent_id));
		}
		return true;
	}
	
	/**
	 * 更新编辑器上传附件
	 */
	public function update_editor_asset($file_id, $parent_id){
		$criteria = array('file_id'=>$file_id);
		return $this->update_set($criteria, array('parent_id' => $parent_id), false, true, true);
	}
	
	/**
	 * 删除附件记录及附件文件
	 */
	public function remove_and_file($query=array()) {
		if(empty($query)){
			return false;
		}
		$rows = $this->find($query);
		foreach($rows as $row){
			$file_path = $row['filepath'];
			// 删除文件 先注掉，以防测试环境删除正式环境图片
			//Sher_Core_Util_Asset::delete_cloud_file($file_path);
			
			$this->remove($row['_id']);
		}
		return true;
	}
	
	/**
	 * 删除附件记录
	 */
	public function delete_file($id) {
        $row = $this->find_by_id($id);
        if (empty($row)) {
            return null;
        }
        $file_path = $row['filepath'];
		
		// 删除文件
		Sher_Core_Util_Asset::delete_cloud_file($file_path);
		
        return $this->remove($id);
    }
	
	/**
	 * 通过path删除附件记录
	 */
	public function delete_by_path($file_path){
		if(empty($file_path)){
			return false;
		}
		
		// 截取首字符/
		if (strpos($file_path, '/') == 0){
			$file_path = substr($file_path, 1);
		}
		
		$row = $this->first(array(
			'filepath' => $file_path,
		));
		Doggy_Log_Helper::debug("Delete Path: $file_path");
		if(empty($row)){
			return false;
		}
		// 删除文件
		$res = Sher_Core_Util_Asset::delete_cloud_file($file_path);
		if (isset($res['error'])){
			return false;
		}
		
		return $this->remove($row['_id']);
	}
	
	/**
	 * 存储临时文件路径
	 */
	public function set_file($file){
		$this->file = $file;
	}
	
	public function file(){
		return $this->file;
	}
	
	/**
	 * 存储临时文件内容
	 */
	public function set_file_content($c){
		$this->file_content = $c;
	}
	
	public function file_content(){
		return $this->file_content;
	}

  /**
   * 返回所有缩略图后缀
   */
  public function thumb_info(){
    return $this->thumbnails_styles;
  }
	
	
}
?>
