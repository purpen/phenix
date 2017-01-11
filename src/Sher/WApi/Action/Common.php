<?php
/**
 * WAPI 共公接口
 * @author tianshuai
 */
class Sher_WApi_Action_Common extends Sher_WApi_Action_Base {
	
	protected $filter_auth_methods = array('execute');

	/**
	 * 入口
	 */
	public function execute(){
	}

    /**
     * 获取京东收货地址
     */
    public function fetch_city(){
        // ID
        $oid = isset($this->stash['oid']) ? (int)$this->stash['oid'] : 0;
        // 父ID
        $pid = isset($this->stash['pid']) ? (int)$this->stash['pid'] : 0;
        // 层级
        $layer = isset($this->stash['layer']) ? (int)$this->stash['layer'] : 1;

        $china_city_model = new Sher_Core_Model_ChinaCity();

        $query = array();
        $options = array('page'=>1,'size'=>1000,'sort'=>array('sort'=>-1));
        if($oid){
            $query['oid'] = $oid;
        }
        if($pid){
            $query['pid'] = $pid;
        }
        if($layer){
            $query['layer'] = $layer;
        }
        $query['status'] = 1;

        $rows = $china_city_model->find($query, $options);
        for($i=0;$i<count($rows);$i++){
            $rows[$i]['_id'] = (string)$rows[$i]['_id'];
        }
        $result['rows'] = $rows;
        //print_r($result);
        return $this->wapi_json('success!', 0, $result);
    }
	

}

