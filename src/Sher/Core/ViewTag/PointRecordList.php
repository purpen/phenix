<?php
/**
 * 积分记录列表
 */
class Sher_Core_ViewTag_PointRecordList extends Doggy_Dt_Tag {
    protected $argstring;

    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }

    public function render($context, $stream) {
        $page = 1;
        $size = 10;
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
        $sort_field = 'time';

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;

        $query = array();

        if($user_id){
          $query['user_id'] = (int)$user_id;
        }

        $service = Sher_Core_Service_Point::instance();
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = $sort_field;

        $result = $service->get_point_record_list($query,$options);

        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
