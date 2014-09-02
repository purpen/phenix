<?php
/**
 * 用户列表
 */
class Sher_App_ViewTag_UserList extends Doggy_Dt_Tag {
    protected $argstring;

    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }
    
    public function render($context, $stream) {
        $page = 1;
        $size = 20;
        $only_pending = 0;
        $only_ok = 0;
        $only_blocked = 0;
        
        $only_system = 0;
        $only_admin = 0;
		$only_editor = 0;
        $only_common_user = 0;
        
        $only_dead = 0;
        $only_active = 0;
		
		// 某时间段内
		$start_time = 0;
		$end_time = 0;
		
		// 婚姻
		$marital = 0;
		// 性别
		$sex = 0;
		// 用户推荐
		$last_login = 0;
		
        $search_id = 0;
        $search_passport = 0;
		// 获取单个用户
        $user_id = 0;
		// 批量获取用户
		$user_ids = array();
        
        $sort = 'latest';
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
        
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
        $options['sort_field'] = $sort;
		
		if ($marital) {
			$query['marital'] = $marital;
		}
		if ($sex) {
			$query['sex'] = $sex;
		}
		
        if ($only_pending) {
            $query['state'] = Sher_Core_Model_User::STATE_PENDING;
        }
        if ($only_blocked) {
            $query['state'] = Sher_Core_Model_User::STATE_DISABLED;
        }
        if ($only_ok) {
            $query['state'] = Sher_Core_Model_User::STATE_OK;
        }
        
        if ($only_common_user) {
            $query['role_id'] = Sher_Core_Model_User::ROLE_USER;
        }
        if ($only_editor) {
            $query['role_id'] = Sher_Core_Model_User::ROLE_EDITOR;
        }
        if ($only_admin) {
            $query['role_id'] = Sher_Core_Model_User::ROLE_ADMIN;
        }
        if ($only_system) {
            $query['role_id'] = Sher_Core_Model_User::ROLE_SYSTEM;
        }
		
		// 获取某个时段内
		if ($start_time) {
			$query['created_on'] = array(
				'$gt' => $start_time,
				'$lt' => $end_time,
			);
		}
        
        //90天未登录的用户为休眠用户
        if ($only_dead) {
            $query['last_login'] = array(
                '$lt' => time() - 3600*24*90,
            );
        }
        //30天登录的用户为活跃用户
        if ($only_active) {
            $query['last_login'] = array(
                '$gt' => time() - 3600*24*30,
            );
        }
		// 获取注册时间大于某登录时间
		if($last_login) {
			$query['created_on'] = array('$gt' => $last_login);
		}
		
        if($search_id) {
            // $query['_id'] = (int)$search_id;
			$query['$or'] = array(
				array('_id' => (int)$search_id),
				array('profile.phone' => $search_id),
			);
        }
		
        if($search_passport) {
			$query['$or'] = array(
				array('account' => $search_passport),
				array('nickname' => $search_passport), 
			);
        }
		
        $options['page'] = $page;
        $options['size'] = $size;
		
        if ($user_id) {
            $result = DoggyX_Model_Mapper::load_model((int)$user_id,'Sher_Core_Model_User');
        } elseif(!empty($user_ids)){
        	$result = DoggyX_Model_Mapper::load_model_list($user_ids,'Sher_Core_Model_User');
        } else {
            $service = Sher_Core_Service_User::instance();
            $result = $service->get_user_list($query, $options);
        }
		
        $context->set($var,$result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
?>