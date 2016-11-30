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
    public static function freight_stat($total_money, $addbook_id, $options=array()){
        if($total_money>=99) return 0;
        return 10;
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

}

