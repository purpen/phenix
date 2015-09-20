<?php
/**
 * 推荐位置管理
 * @author purpen
 */
class Sher_Core_Model_Space extends Sher_Core_Model_Base  {

    protected $collection = "space";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
    
	const TYPE_IMAGE = 1;
	const TYPE_TEXT  = 2;
  const TYPE_INFO  = 3;

  const KIND_SITE = 1;
  const KIND_WAP = 2;
  const KIND_APP = 3;

	// 用户身份
	protected $kinds = array(
		array(
			'id' => self::KIND_SITE,
			'name' => '电脑版'
		),
		array(
			'id' => self::KIND_WAP,
			'name' => '手机版'
		),
		array(
			'id' => self::KIND_APP,
			'name' => 'APP版'
		),

	);
	
    protected $schema = array(
        'name' => '',
		'title' => '',
        'type' => self::TYPE_INFO,
        'kind' => self::KIND_SITE,
        # 图片尺寸
        'width' => 0,
        'height' => 0,
    );
	
    protected $required_fields = array('name','title');
    protected $int_fields = array('type', 'kind', 'width', 'height');
    
    protected function extra_extend_model_row(&$row) {

      $row['kind_name'] = '';
      if(isset($row['kind'])){
        $row['kind_name'] = $this->find_kinds($row['kind']);
      }
    	
    }
    
	/**
	 * 验证位置标识信息
	 */
    protected function validate() {
		// 新建记录
		if($this->insert_mode){
			if (!$this->_check_name()){
				throw new Sher_Core_Model_Exception('位置标识已存在，请更换！');
			}
		}
		
        return true;
    }
	
	/**
	 * 检测位置标识是否唯一
	 */
	protected function _check_name() {
		$name = $this->data['name'];
		if(empty($name)){
			return false;
		}
		$row = $this->first(array('name' => $name));
		if(!empty($row)){
			return false;
		}
		
		return true;
	}

	/**
	 * 获取kind类型选项
	 */
	public function find_kinds($id=0){
		if($id){
			for($i=0;$i<count($this->kinds);$i++){
				if ($this->kinds[$i]['id'] == $id){
					return $this->kinds[$i];
				}
			}
			return '--';
		}
		return $this->kinds;
	}
	
}

