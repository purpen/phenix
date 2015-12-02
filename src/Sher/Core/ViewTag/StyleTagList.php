<?php
/**
 * 分类标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_StyleTagList extends Doggy_Dt_Tag {
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

		    $user_id = 0;
        $domain = 0;
        $stick = 0;
        $kind = 0;
        $mark = 0;
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
		if($mark){
			$query['mark'] = $mark;
    }

		
        $service = Sher_Core_Service_StyleTag::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;

		// 设置排序
		switch ($sort) {
			case 1:
				$options['sort_field'] = 'sort';
				break;
			case 2:
				$options['sort_field'] = 'stick';
				break;
			case 3:
				$options['sort_field'] = 'stick:latest';
				break;
		}

        $result = $service->get_style_tag_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

