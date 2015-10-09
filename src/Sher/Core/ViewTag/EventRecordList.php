<?php
/**
 * 积分记录列表
 */
class Sher_Core_ViewTag_EventRecordList extends Doggy_Dt_Tag {
    protected $argstring;

    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }

    public function render($context, $stream) {
        $page = 1;
        $size = 30;

        $user_id = 0;
        $event_code = 0;
        $state = 0;

        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
        $sort_field = 'created_on';

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;

        $query = array();

        if($user_id){
          $query['user_id'] = (int)$user_id;
        }

        if($state){
          $query['state'] = (int)$state;
        }

        if($event_code){
          $query['event_code'] = $event_code;
        }

        $service = Sher_Core_Service_Point::instance();
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = $sort_field;

        $result = $service->get_event_list($query,$options);

        $context->set($var, $result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
