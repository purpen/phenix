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
        $check_loved = 0;
        $current_user_id = 0;
		
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

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'earliest';
				break;
			case 1:
				$options['sort_field'] = 'latest';
				break;
			case 2:
				$options['sort_field'] = 'hotest';
				break;
		}

        $options['page'] = $page;
        $options['size'] = $size;

        $service = Sher_Core_Service_Comment::instance();
        $result = $service->get_comment_list($query,$options);

        // 验证当前用户是否点赞了
        if(!empty($check_loved) && !empty($current_user_id)){
          if(!empty($result['rows'])){
            $favorite = new Sher_Core_Model_Favorite();
            for($i=0;$i<count($result['rows']);$i++){
              $is_loved = $favorite->check_loved((int)$current_user_id, (string)$result['rows'][$i]['_id'], Sher_Core_Model_Favorite::TYPE_COMMENT);
              $result['rows'][$i]['is_loved'] = $is_loved;
            }
            unset($favorite);
          }
		    }
		
    	$context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}
?>
