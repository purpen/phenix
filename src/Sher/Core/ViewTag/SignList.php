<?php
/**
 * 用户签到列表标签
 * @author caowei@taihuoniao.com
 */
class Sher_Core_ViewTag_SignList extends Doggy_Dt_Tag {
    
	protected $argstring;
	
    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }

    /**
     * 列表的条件保持与索引顺序一致(non-PHPdoc)
     * @see Doggy/Dt/Doggy_Dt_Node#render()
     */
    public function render($context, $stream) {
        
		$page = 1;
        $size = 10;
		
        // 搜索类型
        $s_type = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		// extract()从数组中将变量导入到当前的符号表
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

        // 搜索
        if($s_type){
            switch ((int)$s_type){
                case 1:
                    $query['_id'] = (int)$s_mark;
                    break;
                case 2:
                    $query['title'] = array('$regex'=>$s_mark);
                    break;
                case 3:
                    $query['tags'] = array('$all'=>array($s_mark));
                    break;
            }
        }
		
		// 访问Service类里面的instance方法
		$service = Sher_Core_Service_Sign::instance();
        $options['page'] = $page;
        $options['size'] = $size;

        $options['sort_field'] = $sort;
		
		// 设置排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'last_date';
				break;
			case 1:
				$options['sort_field'] = 'sign_times';
				break;
			case 2:
				$options['sort_field'] = 'exp_count';
				break;
			case 3:
				$options['sort_field'] = 'money_count';
				break;
		}
        
		// 调用签到列表的获取方法
        $result = $service->get_sign_list($query, $options); // 获取到的列表数据
		
		// 加载用户表
        if($load_user){
			
            $user = null;
			// 实例化用户数据模型类
            $user_model = new Sher_Core_Model_User();
			
            for($i=0; $i<count($result['rows']); $i++){
				$user_id = isset($result['rows'][$i]['_id']) ? $result['rows'][$i]['_id'] : 0;
                if(empty($user_id)){
                    continue;
                }
				// 调用用户信息查询方法
                $user = $user_model->extend_load((int)$user_id);
                if($user){
                    $result['rows'][$i]['user'] = $user;              
                }
            }
            unset($user_model);
        }
		
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var, $result['pager']);
        }
        
    }
}
