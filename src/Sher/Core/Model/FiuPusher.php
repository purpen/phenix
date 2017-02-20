<?php
/**
 * 手机设备推送信息-Fiu
 * @author purpen
 */
class Sher_Core_Model_FiuPusher extends Sher_Core_Model_Base  {
	protected $collection = "fiu_pusher";

  const FROM_IOS = 1;
  const FROM_ANDROID = 2;
  const FROM_WIN = 3;
  const FROM_IPAD = 4;
	
	protected $schema = array(
  	    'user_id' => 0,
		'uuid' => '',
		
  	    'push_count' => 0,
        'from_to' => self::FROM_IOS,
        // 是否登录
        'is_login' => 1,
        // 应用最后登录时间
        'last_time' => 0,
        // 渠道ID
        'channel_id' => 0,
  	  	
		'state' => 1,
  	);

  	protected $required_fields = array('uuid', 'user_id');
	
  	protected $int_fields = array('user_id', 'push_count', 'state', 'from_to', 'is_login');
	

    protected $joins = array(
        'user'  => array('user_id'  => 'Sher_Core_Model_User'),
    );
	
    protected function extra_extend_model_row(&$row) {
      // 来源
      switch($row['from_to']){
        case 1:
          $row['from'] = 'IOS';
          break;
        case 2:
          $row['from'] = 'Android';
          break;
        case 3:
          $row['from'] = 'Win';
          break;
        case 4:
          $row['from'] = 'IPad';
          break;
        default:
          $row['from'] = '--';
      }

      // 渠道说明
      if(isset($row['channel_id'])){
        $row['channel_label'] = Sher_Core_Helper_View::fetch_channel_name($row['channel_id']); 
      }

    }
	
	/**
	 * 绑定设备与用户
	 */
	public function binding($uuid, $user_id, $from_to, $channel=0){
		if(empty($uuid) || empty($user_id)){
			throw new Sher_Core_Model_Exception('绑定操作缺少参数！');
		}

        $result = array();
        $result['success'] = false;
        $result['is_bonus'] = 0;
		
        // 检测是否已绑定
        $pusher = $this->first(array('uuid'=>$uuid, 'from_to'=>$from_to));
        if($pusher){
          $ok = $this->update_set((string)$pusher['_id'], array('is_login'=>1, 'user_id'=>(int)$user_id, 'last_time'=>time()));
        }else{
          // 新增记录
            $result['is_bonus'] = 1;
          $data = array(
            'user_id' => (int)$user_id,
            'uuid' => $uuid,
            'from_to' => (int)$from_to,
            'last_time' => time(),
            'channel_id' => (int)$channel,
          );
          $ok = $this->create($data);
          // 首次绑定送红包
          if($ok){
            Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'DA100', 'bonus'=>'B', 'min_amounts'=>'G', 'day'=>30));
            Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'DA50', 'bonus'=>'A', 'min_amounts'=>'H', 'day'=>30));
            Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'DA30', 'bonus'=>'C', 'min_amounts'=>'D', 'day'=>30));
            Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'DA08', 'bonus'=>'J', 'min_amounts'=>'C', 'day'=>30));

            /**
            // 随机赠送9.9红包做活动
            $rand = rand(1, 15);
            if($rand==10){
              $third_site_stat_model = new Sher_Core_Model_ThirdSiteStat();
              $visitor_count = $third_site_stat_model->count(array('kind'=>3));
              if($visitor_count<=60){
                Sher_Core_Util_Shopping::give_bonus((int)$user_id, array('count'=>5, 'xname'=>'APS', 'bonus'=>'F', 'min_amounts'=>'C'));
                $data = array(
                  'user_id' => (int)$user_id,
                  'kind' => 3,
                  'cid' => $from_to,
                  'ip' => Sher_Core_Helper_Auth::get_ip(),
                );
                $ok = $third_site_stat_model->create($data);          
              }
            
            }
             */
          }
        }
        
        if($ok){
            $result['success'] = true;       
        }
		return $result;
	}
	
	/**
	 * 解绑设备与用户
	 */
	public function unbinding($uuid, $from_to){
		if(empty($uuid) || empty($from_to)){
			throw new Sher_Core_Model_Exception('绑定操作缺少参数！');
		}
        $pusher = $this->first(array('uuid'=>$uuid, 'from_to'=>$from_to));
        if($pusher){
          $ok = $this->update_set((string)$pusher['_id'], array('is_login'=>0));
          return $ok;
        }else{
          return false;
        }
	}
	
}

