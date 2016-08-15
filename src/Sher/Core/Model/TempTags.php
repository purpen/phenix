<?php
/**
 * 临时标签
 * @author tianshuai
 */
class Sher_Core_Model_TempTags extends Sher_Core_Model_Base  {

    protected $collection = "temp_tag";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
    protected $schema = array(
        'name' => null,
        'index' => null,
        'topic_count'=>0,
        'product_count'=>0,
        'search_count'=>0,
        'total_count' => 0,
		'subscribe_count' => 0,
        'context_count' => 0,
        'scene_count' => 0,
        'sight_count' => 0,
        'scene_product_count' => 0,
        'search_on' => null,
        # 类型：1.普通；2.分类；3.其它；4.--；
        'kind' => 1,
        # 父ID
        'fid' => 0,
        # 层级
        'layer' => 0,
        'status' => 1,
        'stick' => 0,
    );
	
    protected $required_fields = array('name');
    protected $int_fields = array('topic_count','product_count','total_count','search_count','stick','kind','status','fid','layer','context_count','scene_product_count','scene_product_count','subscribe_count');
	protected $counter_fields = array('total_count','topic_count','scene_count','sight_count','context_count','product_count','scene_product_count','search_count','subscribe_count');
	
    
    protected function extra_extend_model_row(&$row) {

        if(isset($row['kind'])){
            switch($row['kind']){
                case 1:
                    $row['kind_label'] = '情景';
                    break;
                case 2:
                    $row['kind_label'] = '商品';
                    break;
                case 3:
                    $row['kind_label'] = '分类';
                    break;
                case 4:
                    $row['kind_label'] = '默认';
                    break;
                default:
                    $row['kind_label'] = '--';
            }
        }
    }
    
	/**
	 * 添加索引键
	 */
    protected function before_insert(&$data) {
		
		parent::before_insert($data);
    }

	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (!empty($data['name'])) {
	        $data['index'] = Sher_Core_Helper_Pinyin::str2py($data['name']);
	    }

        $layer = 0;
        if(!empty($data['fid'])){
            $tags_model = new Sher_Core_Model_TempTags();
            $f_tag = $tags_model->load((int)$data['fid']);
            if($f_tag){
                $layer = (int)$f_tag['layer'] + 1;
            }
        }
        $data['layer'] = $layer;
	    parent::before_save($data);
	}
	
	/**
	 * 验证关键词信息
	 */
    protected function validate() {
		// 新建记录
		if($this->insert_mode){
			if (!$this->_check_name()){
				throw new Sher_Core_Model_Exception('关键词已存在，请更换！');
			}
		}
		
        return true;
    }
	
	/**
	 * 检测关键词是否唯一
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
	
    public function get_hot_tags() {
    	$options['sort'] = array('topic_count'=>-1);
    	$options['page'] = 1;
        $options['size'] = 300;
		
    	$result = $this->find(array(),$options);
    	srand();
    	$rand_array = array('s','d');
    	for($i=0;$i<count($result);$i++) {
	    	$this->extra_extend_model_row($result[$i]);
	    	$result[$i]['css_size'] = "tag_".rand(1,4);
	    	$itor = rand(0,1);
	    	$result[$i]['css_line'] = "bdr_".$rand_array[$itor];
    	}
    	return $result;
    }
    
    /**
     * 返回按照索引表的标签列表
     *
     * @param string $tags_per_index
     * @param string $tags_sort
     * @return void
     */
    public function get_tag_lookup_list($tags_per_index=40,$tags_sort = array('topic_count' => -1)) {
        $lookup_indexes = array('a','b','c','d','e','f','g','h','i','j',
            'k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
        $result = array();
        for ($i=0; $i < count($lookup_indexes); $i++) {
            $k = $lookup_indexes[$i];
            $result[$i]['index'] = $k;
            $result[$i]['up_index'] = strtoupper($k);
            $result[$i]['tags'] = $this->find(array('index' => $k),array('page' => 1,'size' => $tags_per_index,'sort' => $tags_sort));
        }
        return $result;
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
	 * 更新
	 */
	public function dec_counter($field_name, $inc=1, $id=null){
		if(is_null($id)){
			$id = $this->id;
		}
		if(empty($id) || !in_array($field_name, $this->counter_fields)){
			return false;
		}
		return $this->dec($id,$field_name, $inc);
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

    /**
     * 统计数量
     */
    public function record_count($tags, $evt){
        $tag_ids = array();
        $temp_tags = array();
        foreach($tags as $v){
            $has_one = $this->first(array('name'=>$v));
            if($has_one){
                array_push($tag_ids, $has_one['_id']);
            }else{
                array_push($temp_tags, $v);
            }
        } // end foreach

        switch($evt){
            case 1:
                $fields_count = 'context_count';
                break;
            case 2:
                $fields_count = 'scene_count';
                break;
            case 3:
                $fields_count = 'sight_count';
                break;
            default:
                $fields_count = '';
        }   // end switch

        if(!empty($tag_ids)){
            foreach($tag_ids as $v){
                $this->increase_counter($fields_count, 1, $v);
                $this->increase_counter('total_count', 1, $v);
            }
        }

        if(!empty($temp_tags)){
            foreach($temp_tags as $t){
                $rows = array(
                    'name' => $t,
                    'total_count' => 1,
                );
                if(!empty($fields_count)){
                    $rows["$fields_count"] = 1;
                }
                $this->create($rows);         
            }
            
        }     

    }
	
}

