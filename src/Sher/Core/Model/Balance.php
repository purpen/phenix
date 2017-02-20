<?php
/**
 * 拥金表
 * @author tianshuai
 */
class Sher_Core_Model_Balance extends Sher_Core_Model_Base  {
	protected $collection = "balance";

    ## 类型
    const TYPE_PERSON = 1;
    const TYPE_COMPANY = 2;

    ## 进度
    const STAGE_ING = 1; // 进行中
    const STAGE_REFUND = 2; // 退款
    const STAGE_FINISH = 5; // 已完成
	
	protected $schema = array(
        'user_id' => 0,
        # 联盟账户
        'alliance_id' => null,
        # 订单ID、
        'target_id' => '',
        'order_rid' => null,
        'sub_order_id' => null,
        'product_id' => 0,
        'sku_id' => 0,
        # 所属地盘ID
        'storage_id' => 0,
        # 产品单价
        'sku_price' => 0,
        # 数量
        'quantity' => 1,
        # 拥金比例
        'commision_percent' => 0,
        # 账户加成
        'addition' => 0,
        # 拥金单价 商品单价*商品分成*账户加成
        'unit_price' => 0,
        # 拥金总额 单价*数量
        'total_price' => 0,
        # 推广码
        'code' => null,
        # 备注
        'summary'  => null,
        # 类型
        'type' => self::TYPE_PERSON,
        # 类型: 1.推广分佣；2.地盘分成；3.--
        'kind' => 1,
        # 推广类型: 1.订单；2.用户注册；3.--；
        'sub_kind' => 1,
        # 所属订单商品状态: 1.未完成(进行中); 2.退款；5.完成(可以结算)
        'stage' => self::STAGE_ING,
        # 状态: 0.未结算；1.已结算；2.关闭；
		'status' => 0,
        # 结算时间
        'balance_on' => 0,
        # 订单来源
        'from_site' => 1,

  	);

    protected $required_fields = array('user_id', 'alliance_id');

    protected $int_fields = array('status', 'user_id', 'kind', 'type', 'product_id', 'sku_id', 'quantity', 'stage', 'balance_on');
	protected $float_fields = array('commision_percent', 'unit_price', 'total_price', 'addition', 'sku_price');
	protected $counter_fields = array();


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {

        // 类型
        switch($row['type']){
            case 1:
                $row['type_label'] = '个人';
                break;
            case 2:
                $row['type_label'] = '公司';
                break;
            default:
                $row['type_label'] = '--';
        }

        // 来源
        switch($row['kind']){
            case 1:
                $row['kind_label'] = '推广佣金';
                break;
            case 2:
                $row['kind_label'] = '地盘分成';
                break;
            default:
                $row['kind_label'] = '--';
        }

        // 进度(只用作地盘分成)
        switch($row['stage']){
            case 1:
                $row['stage_label'] = '已支付';
                break;
            case 2:
                $row['stage_label'] = '用户退款';
                break;
            case 5:
                $row['stage_label'] = '可结算';
                break;
            default:
                $row['stage_label'] = '--';
        }

        // 进度
        switch($row['status']){
            case 0:
                $row['status_label'] = '未结算';
                break;
            case 1:
                $row['status_label'] = '已结算';
                break;
            case 2:
                $row['status_label'] = '关闭';
                break;
            default:
                $row['status_label'] = '--';
        }
        if($row['stage'] == 2){
            $row['status_label'] = '已关闭';
        }

        // 分成百分比转化
        $row['commision_percent_p'] = isset($row['commision_percent']) ? $row['commision_percent']*100 : 0;

	}


	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {

	    parent::before_save($data);
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){
        // 如果是新的记录
        if($this->insert_mode){
            $alliance_id = $this->data['alliance_id'];

            // 更新关联用户表
            $alliance_model = new Sher_Core_Model_Alliance();
            $alliance_model->inc_counter('total_count', $alliance_id);

        }
    }


	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
        $alliance_id = isset($options['alliance_id']) ? $options['alliance_id'] : null;
        $stage = isset($options['stage']) ? $options['stage'] : 0;

        // 更新联盟账户数量
        if(!empty($alliance_id)){
            $model = new Sher_Core_Model_Alliance();
            if($stage==5){
                $model->dec_counter('success_count', $alliance_id);           
            }
            $model->dec_counter('total_count', $alliance_id);
        }
		
		return true;
	}

    /**
     * 更新进度
     */
    public function set_stage($id, $stage, $options=array()){
        $row = $this->load($id);

        if(empty($row)) return false;
        $stage = (int)$stage;
        if($row['stage'] == $stage) return false;

        $query = array();
        $query['stage'] = $stage;
        // 退款情况，关闭结算
        if($stage==2){
            $query['status'] = 2;
        }

        $ok = $this->update_set($id, $query);
        if(!$ok) return false;
        // 更新联盟表数量
        if($stage==5){
            $alliance_id = $row['alliance_id'];
            $alliance_model = new Sher_Core_Model_Alliance();
            $alliance_model->inc_counter('success_count', $alliance_id);
            if(isset($options['price']) && !empty($options['price'])){
                $alliance_model->inc_counter('total_product_money', $alliance_id, (float)$options['price']);           
            }
        }
        return $ok;
    }

    /**
     * 记录推广商品信息（暂不用，分成两个方法统计）
     */
    public function record_balance($rid, $options=array()){
        $order = isset($options['order']) ? $options['order'] : array();
        if(empty($order)){
            $order_model = new Sher_Core_Model_Orders();
            $order = $order_model->find_by_rid($rid);
        }

        if(empty($order)){
            return false;
        }

        $alliance_model = new Sher_Core_Model_Alliance();
        $product_model = new Sher_Core_Model_Product();

        $is_referral = $is_storage = false;

        if(isset($options['is_referral']) && !empty($options['is_referral'])){
            $is_referral = true;
        }
        if(isset($options['is_storage']) && !empty($options['is_storage'])){
            $is_storage = true;       
        }

        for($i=0;$i<count($order['items']);$i++){
            $item = $order['items'][$i];
            $referral_code = isset($item['referral_code']) ? $item['referral_code'] : null;
            $storage_id = isset($item['storage_id']) ? $item['storage_id'] : null;
            // 记录推广链接
            $alliance = $product = null;
            // 记录共同需要的值，以防重复查询
            if(!empty($referral_code) && !empty($storage_id)){
                $product = $product_model->load((int)$item['product_id']);
                if(empty($alliance) || empty($product)) continue;
            
            }

            if(!empty($referral_code)){
                $alliance = $alliance_model->find_by_code($referral_code);
                if(empty($alliance)) continue;
                // 禁用的联盟账户不会记录
                if($alliance['status']==0) continue;

                // 账户加成
                $addition = (float)$alliance['addition'];

                if(empty($product)){
                    $product = $product_model->load((int)$item['product_id']);
                }
                if(empty($product)) continue;
                $commision_percent = isset($product['commision_percent']) ? (float)$product['commision_percent'] : 0;
                $unit_price = (float)sprintf("%.2f", $item['price'] * $commision_percent * $addition);
                $total_price = (float)sprintf("%.2f", $unit_price * $item['quantity']);
                
                $row = array(
                    'alliance_id' => (string)$alliance['_id'],
                    'user_id' => $alliance['user_id'],
                    'order_rid' => $order['rid'],
                    'product_id' => $product['_id'],
                    'sku_id' => $item['sku'],
                    'quantity' => $item['quantity'],
                    'commision_percent' => $commision_percent,
                    'addition' => $addition,
                    'code' => $alliance['code'],
                    'sku_price' => $item['price'],
                    'unit_price' => $unit_price,
                    'total_price' => $total_price,
                    'kind' => 1,
                    'type' => $alliance['type'],
                    'from_site' => $order['from_site'],
                );
                $ok = $this->create($row);
            }

            // 记录地盘推广
            if(!empty($storage_id)){

            }

        }   // endfor
    
    }

    /**
     * 佣金统计
     * @param $target_id, $type
     */
    public function record_balance_by_commision($target_id, $type=1, $options=array()){
        // 默认值
        $target_id = '';
        $referral_code = '';
        $from_site = 1;
        $origin_price = 0;
        $quantity = 1;
        if($type==1){
            $order = isset($options['order']) ? $options['order'] : array();
            if(empty($order)){
                $order_model = new Sher_Core_Model_Orders();
                $order = $order_model->find_by_rid($target_id);
            }

            if(empty($order)){
                return false;
            }

            $referral_code = $order['referral_code'];
            $from_site = $order['from_site'];
            // 实际支付价减邮费(不包括优惠金额、邮费)
            $origin_price = $order['pay_money'] - $order['freight'];
              
        }

        $alliance_model = new Sher_Core_Model_Alliance();

        $alliance = $alliance_model->find_by_code($referral_code);
        if(empty($alliance)) {
            return false;
        }
        // 禁用的联盟账户不会记录
        if($alliance['status']==0) {
            return false;
        }

        // 账户加成
        $addition = (float)$alliance['addition'];
        $commision_percent = Sher_Core_Util_Constant::COMMISION_PERCENT;
        $unit_price = (float)sprintf("%.2f", $origin_price * $commision_percent * $addition);
        $total_price = (float)sprintf("%.2f", $unit_price * $quantity);

        $row = array(
            'alliance_id' => (string)$alliance['_id'],
            'user_id' => $alliance['user_id'],
            'target_id' => (string)$order['rid'],
            'order_rid' => $order['rid'],
            'quantity' => $quantity,
            'commision_percent' => $commision_percent,
            'addition' => $addition,
            'code' => $alliance['code'],
            'sku_price' => $origin_price,
            'unit_price' => $unit_price,
            'total_price' => $total_price,
            'kind' => 1,
            'type' => $alliance['type'],
            'from_site' => $from_site,
        );
        $ok = $this->create($row);
      
    }

    /**
     *  分成统计
     */
    public function record_balance_by_divide($rid, $type=1, $options=array()){
        $order = isset($options['order']) ? $options['order'] : array();
        if(empty($order)){
            $order_model = new Sher_Core_Model_Orders();
            $order = $order_model->find_by_rid($rid);
        }

        if(empty($order)){
            return false;
        }

        $alliance_model = new Sher_Core_Model_Alliance();
        $product_model = new Sher_Core_Model_Product();
        $scene_model = new Sher_Core_Model_SceneScene();

        for($i=0;$i<count($order['items']);$i++){
            $item = $order['items'][$i];
            $storage_id = isset($item['storage_id']) ? $item['storage_id'] : null;
            // 记录推广链接
            $alliance = $product = null;

            if(empty($storage_id)){
                continue;
            }
            $storage_id = (int)$storage_id;
    
            $scene = $scene_model->load($storage_id);
            if(empty($scene)){
                continue;
            }
            $code = isset($scene['alliance_code']) ? $scene['alliance_code'] : null;
            if(empty($code)) continue;
            $alliance = $alliance_model->find_by_code($code);
            if(empty($alliance)) continue;
            // 禁用的联盟账户不会记录
            if($alliance['status']==0) continue;
            $product = $product_model->load((int)$item['product_id']);
            if(empty($product)) continue;

            // 账户加成
            $addition = (float)$alliance['addition'];

            $commision_percent = isset($product['commision_percent']) ? (float)$product['commision_percent'] : 0;
            $unit_price = (float)sprintf("%.2f", $item['price'] * $commision_percent * $addition);
            $total_price = (float)sprintf("%.2f", $unit_price * $item['quantity']);
            
            $row = array(
                'alliance_id' => (string)$alliance['_id'],
                'user_id' => $alliance['user_id'],
                'target_id' => (string)$product['_id'],
                'order_rid' => $order['rid'],
                'product_id' => $product['_id'],
                'sku_id' => $item['sku'],
                'storage_id' => $storage_id,
                'quantity' => $item['quantity'],
                'commision_percent' => $commision_percent,
                'addition' => $addition,
                'code' => $alliance['code'],
                'sku_price' => $item['price'],
                'unit_price' => $unit_price,
                'total_price' => $total_price,
                'kind' => 2,    // 地盘分成
                'type' => $alliance['type'],
                'from_site' => $order['from_site'],
            );
            $ok = $this->create($row);

        }   // endfor
    
    }

    /**
     * 更新stage进度
     * @param rid: 订单编号 type: 1.佣金；2.地盘分成；
     */
    public function update_success_stage($rid, $type=1, $options=array()){
        $order_model = new Sher_Core_Model_Orders();
        $order = $order_model->find_by_rid($rid);

        if(empty($order)){
            return false;
        }

        if($type==1){
            // 更新佣金状态
            if(!empty($order['referral_code'])){
                $balance = $this->first(array('target_id'=>(string)$rid, 'order_rid'=>$rid, 'kind'=>1, 'status'=>0));
                if(empty($balance)) continue;
                $balance_id = (string)$balance['_id'];
                $ok = $this->set_stage($balance_id, 5);
            }       
        }

        if($type==2){
            for($i=0;$i<count($order['items']);$i++){
                $item = $order['items'][$i];
                $referral_code = isset($item['referral_code']) ? $item['referral_code'] : null;
                $storage_id = isset($item['storage_id']) ? (int)$item['storage_id'] : null;

                // 更新分成状态
                if(!empty($storage_id)){
                    // 如果是退款状态，跳过
                    if(isset($item['refund_status']) && !empty($item['refund_status'])){
                        continue;
                    }
                    $balance = $this->first(array('storage_id'=>$storage_id, 'order_rid'=>$rid, 'sku_id'=>$item['sku'], 'kind'=>2, 'status'=>0));
                    if(empty($balance)) continue;
                    $price = sprintf("%.2f", ($item['price'] * $item['quantity']));
                    $balance_id = (string)$balance['_id'];
                    $ok = $this->set_stage($balance_id, 5, array('price'=>$price));
                }
            }   // endfor       
        }
    }

    /**
     * 更新stage 退款进度
     * @param rid 订单编号; sku_id:skuID
     */
    public function update_refund_stage($rid, $sku_id){

        if(empty($rid) || empty($sku_id)){
            return false;
        }
        $balances = $this->find(array('order_rid'=>(string)$rid, 'sku_id'=>(int)$sku_id, 'kind'=>2, 'status'=>0));
        if(empty($balances)) continue;
        for($j=0;$j<count($balances);$j++){
            if($balances[$j]['stage'] != 1) continue;
            $balance_id = (string)$balances[$j]['_id'];
            $ok = $this->set_stage($balance_id, 2);
        } // endfor
    
        return true;
    }


	
}

