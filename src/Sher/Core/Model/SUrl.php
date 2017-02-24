<?php
/**
 * 短网址
 * @author tianshuai
 */
class Sher_Core_Model_SUrl extends Sher_Core_Model_Base  {
	protected $collection = "s_url";
	
	protected $schema = array(
        # 短网址code
        'code' => null,
        # 原网址
        'url' => null,
        # 类型: 1.自定义；2.链接推广；3.--
        'type' => 1,
        # 点击数
        'view_count' => 0,
        # web 浏览数
        'web_view_count' => 0,
        # wap 浏览数 
        'wap_view_count' => 0,
        # app 浏览数
        'app_view_count' => 0,
        'user_id' => 0,
        'status' => 1,
        # 上一次访问时间
        'last_time_on' => 0,
        # 最后一次更新时间
        'last_update_on' => 0,
        # 来源: 1.PC; 2.Wap; 3.APP; 4.--
        'from_to' => 1,
  	);

    protected $required_fields = array('url');
    protected $int_fields = array('type', 'user_id', 'status', 'last_time_on', 'last_update_on', 'from_to');
	protected $counter_fields = array('view_count', 'web_view_count', 'wap_view_count', 'app_view_count');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        // 类型
        switch($row['type']){
            case 1:
                $row['type_label'] = '自定义';
                break;
            case 2:
                $row['type_label'] = '链接推广';
                break;
            default:
                $row['type_label'] = '--';
        }

        // 来源
        $from_to = isset($row['from_to']) ? $row['from_to'] : 1;
        switch($from_to){
            case 1:
                $row['from_label'] = 'PC';
                break;
            case 2:
                $row['from_label'] = 'WAP';
                break;
            case 3:
                $row['from_label'] = 'APP';
                break;
            default:
                $row['from_label'] = '--';
        }

	}

	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {

        //如果是新的记录
        if($this->insert_mode) {
            $data['last_update_on'] = time();
        }

        // 自动生成推广码
        $data['code'] = Sher_Core_Util_View::url_short($data['url']);
		
	    parent::before_save($data);
	}


	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		
		return true;
	}

	/**
	 * 更新计数
	 */
    public function inc_counter($field_name, $id=null, $inc=1) {
        if (is_null($id)) {
            $id = $this->id;
        }
        
        if (empty($id) || !in_array($field_name, $this->counter_fields)) {
            return false;
        }
        
        $id = DoggyX_Mongo_Db::id($id);
        return $this->inc($id, $field_name, $inc);
    }
	
	/**
	 * 更新计数
	 */
    public function dec_counter($field_name, $id=null, $force=false) {
        if (is_null($id)) {
            $id = $this->id;
        }
        if (empty($id) || !in_array($field_name, $this->counter_fields)) {
            return;
        }
        $id = DoggyX_Mongo_Db::id($id);
        return $this->dec($id, $field_name);
    }

    /**
     * 通过code查找
     */
    public function find_by_code($code){
        if(empty($code)) return false;
        $row = $this->first(array('code'=>$code));
        if(empty($row)) return false;
        return $row;
    }
	
}

