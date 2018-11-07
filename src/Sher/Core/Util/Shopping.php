<?php
/**
 * 购物流程工具方法
 * 
 * @author purpen
 * @version $Id$
 */ 
class Sher_Core_Util_Shopping extends Doggy_Object {
	# 默认运费
	const DEFAULT_FEES = 0;
	
    /**
     * 获取快递费用
     */
	public function validate_express_fees($city, $overweight=false){
		
	}
	
	/**
     * 获取快递费用
     */
	public static function getFees(){
		return self::DEFAULT_FEES;
	}
	
	/**
	 * 更新商品预约信息
	 * @param int $product_id
	 * @return array
	 */
	public static function update_appoint_product($product_id){
		$product_id = (int)$product_id;
		
		$product = new Sher_Core_Model_Product();
		$product->inc_counter('appoint_count', 1, $product_id);
		
		return $product->load($product_id);
	}
	
	/**
	 * 获取宝贝标题
	 */
	public static function get_product_title($sku, $product_id){
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$product_id);
		
		if($sku == $product_id){
			return $product['title'];
		}
		
		$inventory = new Sher_Core_Model_Inventory();
		$sku = $inventory->load((int)$sku);
		
		return $product['title'].'('.$sku['mode'].')';
	}
	
	/**
	 * 获取礼品码
	 */
	public static function get_gift_money($code, $product_id){
		$model = new Sher_Core_Model_Gift();
		$gift = $model->find_by_code($code);
		
		if(empty($gift)){
			throw new Sher_Core_Model_Exception('礼品码不存在！');
		}
		// 是否对应产品id
		if(!empty($gift['product_id']) && $gift['product_id'] != $product_id){
			throw new Sher_Core_Model_Exception('此礼品码不能购买该产品！');
		}
		// 是否使用过
		if($gift['used'] == Sher_Core_Model_Gift::USED_OK){
			throw new Sher_Core_Model_Exception('礼品码已被使用！');
		}
		// 是否过期
		if($gift['expired_at'] && $gift['expired_at'] < time()){
			throw new Sher_Core_Model_Exception('礼品码已过期！');
		}

		$product_model = new Sher_Core_Model_Product();
		$product = $product_model->find_by_id((int)$product_id);
		
    //验证产品
		if (empty($product)){
			throw new Sher_Core_Model_Exception('产品不存在！');
		}

    // 预售产品不可使用
		if ($product['stage'] == 5){
 			throw new Sher_Core_Model_Exception('预售产品不可使用！');
		}

    // 礼品券最低消费限额
    if(!empty($gift['min_cost']) && $gift['min_cost']>$product['sale_price']){
 			throw new Sher_Core_Model_Exception('该礼品券要求当前商品最低消费限额 '.$gift['min_cost'].'元！');   
    }

		$gift_money = $gift['amount'];
		
		return $gift_money;
	}
	
	/**
	 * 获取红包金额 (已不用此方法)
	 */
	public static function get_card_money($code, $total_money=0, $product_id=0){
		$model = new Sher_Core_Model_Bonus();
		$bonus = $model->find_by_code($code);
		$card_money = 0.0;
		
		if (empty($bonus)){
			throw new Sher_Core_Model_Exception('红包不存在！');
		}
		// 是否使用过
		if ($bonus['used'] == Sher_Core_Model_Bonus::USED_OK){
			throw new Sher_Core_Model_Exception('红包已被使用！');
		}
		//是否冻结中
		if ($bonus['status'] != Sher_Core_Model_Bonus::STATUS_OK && $bonus['status'] != Sher_Core_Model_Bonus::STATUS_GOT){
			throw new Sher_Core_Model_Exception('红包不能使用！');
		}
		// 是否过期
		if ($bonus['expired_at'] && $bonus['expired_at'] < time()){
			throw new Sher_Core_Model_Exception('红包已过期！');
    }
    //是否满足限额条件
    if(!empty($bonus['min_amount']) && (int)$bonus['min_amount'] > (int)$total_money){
 			throw new Sher_Core_Model_Exception('此红包满'.$bonus['min_amount'].'元才可使用！');   
    }

    // 指定商品ID
    if(isset($bonus['product_id']) && !empty($bonus['product_id'])){
      if($bonus['product_id'] != (int)$product_id){
 			  throw new Sher_Core_Model_Exception('该红包只能用于指定商品！'); 
      }
    }

		$product_model = new Sher_Core_Model_Product();
		$product = $product_model->find_by_id((int)$product_id);
		
    //验证产品
		if (empty($product)){
 			throw new Sher_Core_Model_Exception('该红包只能用于指定商品！');
		}

    // 预售产品不可使用
		if ($product['stage'] != 9){
 			throw new Sher_Core_Model_Exception('该产品不可使用红包！');
		}

		$card_money = $bonus['amount'];
		
		return $card_money;
	}

	/**
	 * 验证红包是否可用--用于app验证
	 */
	public static function check_bonus($rid, $code, $user_id, &$order_temp=null){

        $bonus_model = new Sher_Core_Model_Bonus();
        $bonus = $bonus_model->find_by_code($code);
        if(empty($bonus)){
          return array('code'=>4000, 'msg'=>"找不到此红包!");
        }

        if($bonus['user_id'] != $user_id){
          return array('code'=>4001, 'msg'=>"没有权限使用!");   
        }

        if($bonus['used'] == Sher_Core_Model_Bonus::USED_OK){
          return array('code'=>4002, 'msg'=>"红包已被使用!");
        }

        if($bonus['expired_at'] < time()){
          return array('code'=>4003, 'msg'=>"红包已过期!");
        }

        $is_current_product = $is_current_active = true;
        $active_product_ids = array();
        $active_title = '';
        $msg = null;

        // 是否指定活动产品
        if(isset($bonus['active_mark']) && !empty($bonus['active_mark'])){
            $is_current_active = false;
            $bonus_active_model = new Sher_Core_Model_BonusActive();
            $bonus_active = $bonus_active_model->first(array('mark'=>$bonus['active_mark']));
            if($bonus_active){
                $active_product_ids = $bonus_active['product_ids'];
                $active_title = $bonus_active['title'];
            }
        }

        // 是否指定商品
        if(!empty($bonus['product_id'])){
            $is_current_product = false;
        }

        // 验证商品是否可以红包购买
        $pass = true;

        if(empty($order_temp)){
            $order_temp_model = new Sher_Core_Model_OrderTemp();
          $order_temp = $order_temp_model->first(array('rid'=>$rid));
          if(empty($order_temp) || empty($order_temp['dict']['items'])){
            return array('code'=>4005, 'msg'=>"找不到临时订单表");
          }  
        }

        if($order_temp['user_id'] != $user_id){
          return array('code'=>4009, 'msg'=>"没有权限使用!");   
        }

        // 红包属性
        $items = $order_temp['dict']['items'];
        $total_money = (float)$order_temp['dict']['total_money'];
        $min_amount = isset($bonus['min_amount']) ? (float)$bonus['min_amount'] : 0;

        if(!empty($min_amount) && $total_money<$min_amount){
          return array('code'=>4008, 'msg'=>"没达到最低使用限额");   
        }

            $inventory_mode = new Sher_Core_Model_Inventory();
            $product_mode = new Sher_Core_Model_Product();

        
        foreach($items as $key=>$val){
          // 参数初始化
          $sku_id = (int)$val['sku'];
          $product_id = (int)$val['product_id'];
          //sku
          if(!empty($sku_id)){
            $sku = $inventory_mode->load($sku_id);
            if($sku){
              $product_id = (int)$sku['product_id'];
            }
          }
          $product = $product_mode->load($product_id);
          if(empty($product)){
            return array('code'=>4006, 'msg'=>"订单商品不存在!");
          }

          // 指定商品ID
          if(!$is_current_product){
            if($bonus['product_id'] == $product['_id']){
                $is_current_product = true;
            }
          }

          // 指定活动商品
          if(!$is_current_active){
              if(in_array($product_id, $active_product_ids)){
                  $is_current_active = true;
              }
          }

        }// endfor

        if(!$is_current_product){
            $pass = false;           
            $msg = sprintf("该红包只限于编号[%s]的产品使用!", $bonus['product_id']);
        } 
        if(!$is_current_active){
            $pass = false;
            $msg = sprintf("该红包只用于[%s]活动下的产品使用!", $active_title);
        }
        if($pass){
          return array('code'=>0, 'msg'=>"success!", 'coin_code'=>$bonus['code'], 'coin_money'=>$bonus['amount']);   
        }else{
          return array('code'=>4008, 'msg'=>$msg);   
        }

	}


	/**
	 * 验证鸟币--抛出异常
	 */
	public static function check_bird_coin($bird_coin, $user_id, $product_id){
    $bird_coin = (int)$bird_coin;
		if (empty($bird_coin)){
			throw new Sher_Core_Model_Exception('请输入正确数量！');
		}
		$model = new Sher_Core_Model_Product();
		$product = $model->find_by_id((int)$product_id);
		
		if (empty($product)){
			throw new Sher_Core_Model_Exception('产品不存在！');
		}
		// 是否可以积分兑换
		if (!$product['exchanged']){
			throw new Sher_Core_Model_Exception('该商品不能使用鸟币！');
		}
		//未设置最高使用鸟币数
		if (empty($product['max_bird_coin'])){
			throw new Sher_Core_Model_Exception('未设置鸟币最高兑换数量！');
		}
    //最低鸟币兑换
    if(!empty($product['min_bird_coin'])){
      if($bird_coin<$product['min_bird_coin']){
			  throw new Sher_Core_Model_Exception('您输入的鸟币数量小于指定数量！');
      }
    }

    // 预售产品不可使用
		if ($product['stage'] == 5){
 			throw new Sher_Core_Model_Exception('预售产品不可使用鸟币！');
		}

		//您输入的鸟币数量超过指定数量
		if ($bird_coin > $product['max_bird_coin']){
			throw new Sher_Core_Model_Exception('您输入的鸟币数量超过指定数量！');
		}

    //验证当前用户鸟币是否足够
    // 用户实时积分
    $point_model = new Sher_Core_Model_UserPointBalance();
    $current_point = $point_model->load((int)$user_id);
    if(!$current_point){
      $current_bird_coin = 0;
    }else{
      $current_bird_coin = isset($current_point['balance']['money'])?(int)$current_point['balance']['money']:0;
    }
    if(empty($current_bird_coin) || $current_bird_coin<$bird_coin){
 			throw new Sher_Core_Model_Exception('鸟币数量不足！');   
    }

    $bird_coin_money = self::bird_coin_transf_money($bird_coin);
		
		return $bird_coin_money;
	}

	/**
	 * 验证鸟币--返回布尔类型
	 */
	public static function check_and_freeze_bird_coin($bird_coin, $user_id, $product_id){
    $message['stat'] = false;
    $message['msg'] = null;
    $bird_coin = (int)$bird_coin;
    if (empty($bird_coin)){
      $message['msg'] = '请输入正确数量';
      //return $message;
		}
		$model = new Sher_Core_Model_Product();
		$product = $model->find_by_id((int)$product_id);
		
		if (empty($product)){
      $message['msg'] = '产品不存在';
      return $message;
		}
		// 是否可以积分兑换
		if (!$product['exchanged']){
      $message['msg'] = '该商品不能使用鸟币';
      return $message;
		}
		//未设置最高使用鸟币数
		if (empty($product['max_bird_coin'])){
      $message['msg'] = '未设置鸟币最高兑换数量!';
      return $message;
		}

		//您输入的鸟币数量超过指定数量
		if ($bird_coin > $product['max_bird_coin']){
      $message['msg'] = '您输入的鸟币数量超过指定数量!';
      return $message;
		}

    //最低鸟币兑换
    if(!empty($product['min_bird_coin'])){
      if($bird_coin<$product['min_bird_coin']){
        $message['msg'] = '您输入的鸟币数量小于指定数量!';
        return $message;
      }
    }

    // 预售产品不可使用
		if ($product['stage'] == 5){
      $message['msg'] = '预售产品不可使用鸟币!';
      return $message;
		}

    //验证当前用户鸟币是否足够
    // 用户实时积分
    $point_model = new Sher_Core_Model_UserPointBalance();
    $current_point = $point_model->load((int)$user_id);
    if(!$current_point){
      $current_bird_coin = 0;
    }else{
      $current_bird_coin = isset($current_point['balance']['money'])?(int)$current_point['balance']['money']:0;
    }
    if(empty($current_bird_coin) || $current_bird_coin<$bird_coin){
      $message['msg'] = '鸟币数量不足!';
      return $message;
    }

		$message['stat'] = true;
		return $message;
	}

  /**
   * 鸟币数量转换价格
   * 当前比例1:1
   */
  public static function bird_coin_transf_money($bird_coin){
    $bird_money = (int)$bird_coin*1;
    return $bird_money;
  }

  /**
   * 红包赠于
   */
  public static function give_bonus($user_id, $options=array()){
    if(empty($options)){
      return false;
    }
    // 获取红包
    $bonus = new Sher_Core_Model_Bonus();
    $result_code = $bonus->pop($options['xname']);

    $product_id = isset($options['product_id']) ? (int)$options['product_id'] : 0;
    $active_mark = isset($options['active_mark']) ? $options['active_mark'] : '';   // 指定某个活动(限制条件上)
    
    // 获取为空，重新生产红包
    while(empty($result_code)){
      //指定生成红包
      $bonus->create_specify_bonus($options['count'], $options['xname'], $options['bonus'], $options['min_amounts'], $product_id, $active_mark);
      $result_code = $bonus->pop($options['xname']);
      // 跳出循环
      if(!empty($result_code)){
        break;
      }
    }
    
    // 赠与红包 使用默认时间1月 $end_time = 30(天)
    $end_time = 0;
    if(isset($options['day'])){
      $end_time = (int)$options['day'];
    }
    $code_ok = $bonus->give_user($result_code['code'], $user_id, $end_time);
    return $code_ok;
  }
	
}

