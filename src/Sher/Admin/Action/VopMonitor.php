<?php
/**
 * 开普勒产品监控管理
 * @author tianshuai
 */
class Sher_Admin_Action_VopMonitor extends Sher_Admin_Action_Base {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'sku_id' => '',
        'product_id' => '',
        'jd_sku_id' => '',
	);
	
	public function execute(){
		return $this->get_list();
	}
	
	/**
     * 列表
     * @return string
     */
    public function get_list() {
		
    	$this->set_target_css_state('page_product');
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/vop_monitor?sku_id=%d&product_id=%d&jd_sku_id=%d&page=#p#';
		$this->stash['vop_monitor'] = 1;
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['sku_id'], $this->stash['product_id'], $this->stash['jd_sku_id']);
		
		// 判断左栏类型
		$this->stash['show_type'] = "product";

        $query = array();

        $page = (int)$this->stash['page'];
        $size = (int)$this->stash['size'];

        if($this->stash['sku_id']){
            $query['sku_id'] = (int)$this->stash['sku_id'];
        }
        if($this->stash['product_id']){
            $query['product_id'] = (int)$this->stash['product_id'];
        }
        if($this->stash['jd_sku_id']){
            $query['jd_sku_id'] = (int)$this->stash['jd_sku_id'];
        }

        $options = array('page'=>$page, 'size'=>$size, 'sort'=>array('modify_on'=>-1));

        $vop_monitor_model = new Sher_Core_Model_VopMonitor();
        $product_model = new Sher_Core_Model_Product();

        $rows = $vop_monitor_model->find($query, $options);
        $count = count($rows);

        for($i=0;$i<$count;$i++){
            $product = $product_model->extend_load((int)$rows[$i]['product_id']);
            if($product){
                $rows[$i]['product'] = $product;
            }
        }

        $total_count = $this->stash['total_count'] = $vop_monitor_model->count($query);
        $this->stash['total_page'] = ceil($total_count/$size);
        $this->stash['rows'] = $rows;
		
        return $this->to_html_page('admin/vop_monitor/list.html');
    }

    /**
     * 同步价格
     */
    public function ajax_sync_price(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;

        if(empty($id)){
            return $this->ajax_json('缺少请求参数!', true);
        }

        $vop_monitor_model = new Sher_Core_Model_VopMonitor();
        $inventory_model = new Sher_Core_Model_Inventory();
        $product_model = new Sher_Core_Model_Product();

        $vop = $vop_monitor_model->load($id);
        if(empty($vop)){
            return $this->ajax_json('内容不存在!', true);
        }

        if(empty($vop['new_price'])){
            return $this->ajax_json('价格为空!', true);     
        }

        $ok = $inventory_model->update_set((int)$vop['sku_id'], array('price'=>$vop['new_price']));
        if(!$ok){
            return $this->ajax_json('更新sku价格失败!', true);
        }

        if($type==2){
            $ok = $product_model->update_set((int)$vop['product_id'], array('sale_price'=>$vop['new_price'], 'market_price'=>$vop['new_price']));
            if(!$ok){
                return $this->ajax_json('更新产品价格失败!', true);          
            }
        }

        $vop_monitor_model->update_set($id, array('price'=>$vop['new_price']));

        return $this->ajax_json('success', false, null, array('id'=>$id));

    }

    /**
     * 商品上下架操作
     */
    public function ajax_product_publish(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;

        if(empty($id)){
            return $this->ajax_json('缺少请求参数!', true);
        }

        $vop_monitor_model = new Sher_Core_Model_VopMonitor();
        $inventory_model = new Sher_Core_Model_Inventory();
        $product_model = new Sher_Core_Model_Product();

        $vop = $vop_monitor_model->load($id);
        if(empty($vop)){
            return $this->ajax_json('内容不存在!', true);
        }

        $product = $product_model->load($vop['product_id']);
        if(empty($product)){
            return $this->ajax_json('商品不存在!', true);       
        }
        $stat = $product['published'];

        if($type==0 && $stat != 0){
            $product_model->mark_as_published($product['_id'], 0);
        }elseif($type==1 && $stat != 1){
            $product_model->mark_as_published($product['_id']);       
        }

        $ok = $vop_monitor_model->update_set($id, array('stat'=>$type));
        if(!$ok){
            return $this->ajax_json('更新失败!', true);       
        }
        return $this->ajax_json('success', false, null, array('id'=>$id));
    }

    /**
     * 删除操作
     */
    public function deleted(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : 0;

        if(empty($id)){
            return $this->ajax_json('缺少请求参数!', true);
        }

        $vop_monitor_model = new Sher_Core_Model_VopMonitor();
        $ok = $vop_monitor_model->remove($id);
        if(!$ok){
            return $this->ajax_json('删除失败!', true);      
        }
        $vop_monitor_model->mock_after_remove($id);
        return $this->ajax_json('success', false, null, array('id'=>$id));
    }

}

