<?php
/**
 * 银行账户信息
 * @author tianshuai
 */
class Sher_Core_Model_PaymentCard extends Sher_Core_Model_Base  {

    protected $collection = "payment_card";

	# 支付类型
	const KIND_BANK = 1;    // 银行卡
	const KIND_ALIPAY = 2;    // 支付宝
	
    protected $schema = array(
    	'user_id' => 0,
        'alliance_id' => '',
        'type' => 1,
        'kind' => self::KIND_BANK,
        'pay_type' => 1,
        'account' => '',
        'username' => '',
        'bank_address' => '',
		'phone' => null,
		'is_default' => 0,
        'status' => 1,
    );
	
    protected $joins = array(

    );
	
    protected $required_fields = array('user_id', 'account');
	
    protected $int_fields = array('user_id', 'type', 'kind', 'pay_type', 'is_default', 'status');
	

	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {
        switch($row['kind']){
            case 1:
                $row['kind_label'] = '银行卡';
                break;
            case 2:
                $row['kind_label'] = '支付宝';
                break;
            default:
                $row['kind_label'] = '--';
        }

        $row['pay_type_label'] = '';
        if($row['kind']==1){
            $bank_info = Sher_Core_Util_Constant::bank_options($row['pay_type']);
            $row['pay_type_label'] = $bank_info['name'];
        }

    }
	
}

