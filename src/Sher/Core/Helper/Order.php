<?php
/**
 * 订单辅助工具
 *
 * @package default
 * @auth tianshuai
 */
class Sher_Core_Helper_Order {

    /**
     * 可退款订单状态数组
    */
    public static function refund_order_status_arr($status=0){
        $allow_refund_status = array(
          Sher_Core_Util_Constant::ORDER_READY_REFUND,
          Sher_Core_Util_Constant::ORDER_READY_GOODS,
          Sher_Core_Util_Constant::ORDER_SENDED_GOODS,
          Sher_Core_Util_Constant::ORDER_EVALUATE,
          //Sher_Core_Util_Constant::ORDER_PUBLISHED,
        );
        if(empty($status)){
          return $allow_refund_status;
        }else{
          return in_array((int)$status, $allow_refund_status);
        }
    }

    /**
     * 自动计算退款金额
     */
    public static function reckon_refund_price($rid, $sku_id, &$order=null){
  
        $result = array();
        $result['success'] = false;
        $result['message'] = '';
        $result['data'] = array();

        if(empty($rid) || empty($sku_id)){
            $result['message'] = '缺少请求参数!';
            return $result;
        }

        if(!$order){
            $orders_model = new Sher_Core_Model_Orders();
            $order = $orders_model->find_by_rid($rid);       
        }
        if(!$order){
            $result['message'] = '订单不存在!';
            return $result;
        }

        if($order['pay_money']==0){
            $result['message'] = '此订单不支付退款操作!';
            return $result;       
        }
        $item_count = count($order['items']);
        // 如果是单个商品，直接返回订单支付金额
        if($item_count==1){
            $result['data']['refund_price'] = $order['pay_money'];
            $result['success'] = true;
            return $result;
        }

        // 如果多个商品，判断是否使用优惠券
        $discount = $order['total_money'] - $order['pay_money'];
        $product_price = 0;
        $quantity = 0;
        for($i=0;$i<$item_count;$i++){
            if($sku_id==$order['items'][$i]['sku']){
                $product_price = $order['items'][$i]['sale_price'];
                $quantity = $order['items'][$i]['quantity'];
                break;
            }
        
        } // endfor

        if(empty($product_price) || empty($quantity)){
            $result['message'] = '产品信息未找到!';
            return $result;        
        }

        if(empty($discount)){   // 未使用优惠券，直接返回商品价格
            $result['data']['refund_price'] = (float)$product_price*$quantity;
            $result['success'] = true;
            return $result;
        }else{  // 使用优惠券
            $result['data']['refund_price'] = (float)$product_price*$quantity;
            $result['success'] = true;
            return $result;  
        }
  
    }

    /**
     * 运费计算
    */
    public static function freight_stat($rid, $addbook_id, $options=array()){
        $freight = (int)Doggy_Config::$vars['app.default_freight'];

        $items = isset($options['items']) ? $options['items'] : array();
        $is_vop = isset($options['is_vop']) ? (int)$options['is_vop'] : 0;
        $total_money = isset($options['total_money']) ? (float)$options['total_money'] : 0;
        // 虚拟产品不发货无邮费
        $is_fictitious = 1;
        if(empty($items)){
            // 调用临时订单
            $model = new Sher_Core_Model_OrderTemp();
            $order = $model->find_by_rid($rid);
            $items = $order['dict']['items'];
            $total_money = $order['dict']['total_money'];
            $is_vop = $order['is_vop'];
        }

        if(empty($items)){
            return $freight;
        }

        $product_model = new Sher_Core_Model_Product();

        for($i=0;$i<count($items);$i++){
            $item = $items[$i];
            $product_id = isset($item['product_id']) ? (int)$item['product_id'] : 0;
            if(empty($product_id)) continue;
            $pro = $product_model->load($product_id);
            if(empty($pro)) continue;
            if(!isset($pro['kind']) || $pro['kind']==1){
                $is_fictitious = 0;
            }
                
        }   // endfor

        // 如果是虚拟产品，不产品邮费
        if($is_fictitious === 1){
            return 0;
        }

        // 获取京东邮费
        if(!empty($is_vop)){
            $vop_skus = array();
            for($i=0;$i<count($items);$i++){
                $item = $items[$i];
                $vop_id = isset($item['vop_id']) ? $item['vop_id'] : null;
                $quantity = $item['quantity'];
                if(!empty($vop_id)){
                    array_push($vop_skus, array('skuId'=>$vop_id, 'num'=>$quantity));
                }
            }   // endfor
            if(empty($vop_skus)){
                return 0;
            }

            $add_arr = array();
            //验证地址
            if ($addbook_id) {
                $add_book_model = new Sher_Core_Model_DeliveryAddress();
                $add_book = $add_book_model->find_by_id($addbook_id);

                if(empty($add_book)){
                    return 0;
                }

                $add_arr = array(
                    'province' => $add_book['province_id'],
                    'city' => $add_book['city_id'],
                    'county' => $add_book['county_id'],
                    'town' => $add_book['town_id'],
                );           
            }else{
                if(!empty($options['addbook'])){
                    $add_book = $options['addbook'];
                    $add_arr = array(
                        'province' => $add_book['province_id'],
                        'city' => $add_book['city_id'],
                        'county' => $add_book['county_id'],
                        'town' => $add_book['town_id'],
                    );
                }
            }

            if(!empty($add_arr)){
                $result = Sher_Core_Util_Vop::fetch_freight($vop_skus, 4, $add_arr);
                if($result['success'] && isset($result['data']['freight'])){
                    return (float)$result['data']['freight'];
                }else{
                    return 0;
                }           
            }
        }   // endif is_vop
        
        if($total_money>=99){
            return 0;
        }

        return $freight;
    }

    /**
     * app下单随机减
     */
    public static function app_rand_reduce($total){
        $total = (float)$total;
        if(empty($total)) return 0;

        if($total>0 && $total<=50){
            return rand(1, 2);
        }elseif($total>50 && $total<=100){
            return rand(1, 3);
        }elseif($total>100 && $total<=300){
            return rand(2, 5);
        }elseif($total>300 && $total<=500){
            return rand(3, 7);
        }elseif($total>500){
            return rand(5, 10);
        }else{
            return 0;
        }
    }


    /**
     * 综合计算(查询产品表)
    */
    public static function comprehensive_stat($rid, $options=array()){
        $result = array();
        $result['disabled_app_reduce'] = 0;

        $items = isset($options['items']) ? $options['items'] : array();

        if(empty($items)){
            // 调用临时订单
            $model = new Sher_Core_Model_OrderTemp();
            $order = $model->find_by_rid($rid);
            $items = $order['dict']['items'];
        }

        if(empty($items)){
            return $result;
        }

        $product_model = new Sher_Core_Model_Product();

        $disabled_app_reduce = 1;
        for($i=0;$i<count($items);$i++){
            $item = $items[$i];
            $product_id = isset($item['product_id']) ? (int)$item['product_id'] : 0;
            if(empty($product_id)) continue;
            $pro = $product_model->load($product_id);
            if(empty($pro)) continue;
            $disabled_app_reduce = isset($pro['extra']['disabled_app_reduce']) ? (int)$pro['extra']['disabled_app_reduce'] : 0;
            if(empty($disabled_app_reduce)) $disabled_app_reduce = 0;
                
        }   // endfor

        $result['disabled_app_reduce'] = $disabled_app_reduce;

        return $result;
    }

}

