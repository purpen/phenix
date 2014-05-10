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
	
	# 照片
    const TYPE_PHOTO = 1;
	# 普通附件，编辑器图片
	const TYPE_ASSET = 2;
	
    # 用户头像
    const TYPE_AVATAR = 4;
	
	# 产品图片
	const TYPE_PRODUCT = 10;
	
	# 主体图片
	const TYPE_TOPIC   = 50;

    protected $schema = array(
		'user_id' => '',
    	'parent_id' => '',
		
		## 原图信息
    	'filepath' => '',
		'filename' => '',
        'size' => 0,
        'width' => 0,
        'height' => 0,
		'mime' => null,
		
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
		
		'asset_type' => self::TYPE_PRODUCT,
		'state' => 1,
    );
	
	# 附件类型
	protected $thumbnails = array('mini','tiny','small','medium','large','big','huge','massive');

    protected $required_fields = array('filepath');
	
    protected $int_fields = array('user_id', 'parent_id','size','width','height','asset_type','state');
	
    protected function extra_extend_model_row(&$row) {
		$this->extend_asset_view_url($row);
    }
	
	/**
	 * 重建asset url
	 */
	protected function extend_asset_view_url(&$row){
		if (isset($row['thumbnails']) && is_array($row['thumbnails'])) {
			foreach($row['thumbnails'] as $key => $value){
				if (isset($row['thumbnails'][$key]['filepath'])){
					$row['thumbnails'][$key]['view_url'] = Sher_Core_Helper_Url::asset_view_url($row['thumbnails'][$key]['filepath']);
				}
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
					Sher_Core_Util_Asset::storeAsset(Sher_Core_Util_Constant::ASSET_DOAMIN, $path, $file);
				}
				
				if (!is_null($file_content)) {
					Sher_Core_Util_Asset::storeData(Sher_Core_Util_Constant::ASSET_DOAMIN, $path, $file_content);
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
	 * 删除附件记录及附件文件
	 */
	public function remove_and_file($query=array()) {
		if(empty($query)){
			return false;
		}
		$rows = $this->find($query);
		foreach($rows as $row){
			$file_path = $row['filepath'];
			Sher_Core_Util_Asset::deleteAsset(Sher_Core_Util_Constant::ASSET_DOAMIN, $file_path);
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
		Sher_Core_Util_Asset::deleteAsset(Sher_Core_Util_Constant::ASSET_DOAMIN, $file_path);
		
        return $this->remove($id);
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
	
	
}
?>