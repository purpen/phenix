<?php
/**
 * 分类标签记录
 * @author tianshuai
 */
class Sher_Core_ViewTag_StyleTagRecordList extends Doggy_Dt_Tag {
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

        $target_id = 0;
		    $user_id = 0;
        $domain = 0;
        $stick = 0;
        $kind = 0;
        $state = 0;
        $sort = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		$sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

		
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($domain){
			$query['domain'] = (int)$domain;
    }
		if($stick){
			$query['stick'] = (int)$stick==-1 ? 0 : 1;
    }
		if($kind){
			$query['kind'] = (int)$kind;
    }
		if($state){
			$query['state'] = (int)$state==-1 ? 0 : 1;
		}
		if($target_id){
			$query['target_id'] = (int)$target_id;
    }

		
        $service = Sher_Core_Service_StyleTagRecord::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;

		// 设置排序
		switch ((int)$sort) {
			case 1:
				$options['sort_field'] = 'stick';
				break;
		}

        $result = $service->get_style_tag_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

