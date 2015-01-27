<?php
/**
 * 评论列表
 * @author purpen
 */
class Sher_App_ViewTag_CommentList extends Doggy_Dt_Tag {
    protected $argstring;

    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }

    public function render($context, $stream) {
        $page = 1;
        $size = 10;
		
        $user_id = 0;
        $target_id = 0;
		    $type = 0;
		
        $sort = 'earliest';
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';

        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;

        $query = array();
        if ($user_id) {
            $query['user_id'] = (int) $user_id;
        }
		if ($target_id) {
			$query['target_id'] = (string)$target_id;
		}
		if ($type) {
			$query['type'] = (int)$type;
		}
		
        $options['sort_field'] = $sort;
        $options['page'] = $page;
        $options['size'] = $size;

        $service = Sher_Core_Service_Comment::instance();
        $result = $service->get_comment_list($query,$options);
		
    	$context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}
?>
