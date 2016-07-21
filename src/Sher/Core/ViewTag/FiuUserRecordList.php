<?php
/**
 * Fiu用户激活列表标签
 * @author tianshuai
 */
class Sher_Core_ViewTag_FiuUserRecordList extends Doggy_Dt_Tag {
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

		    $uuid = 0;
		    $channel_id = 0;
		    $kind = 0;
		    $device = 0;

		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
		    $sort_field = 'latest';
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();

		
        if($uuid){
          $query['uuid'] = $uuid;
        }
        if($channel_id){
          $query['channel_id'] = (int)$channel_id;
        }
        if($kind){
          $query['kind'] = (int)$kind;
        }
        if($device){
          $query['device'] = (int)$device;
        }

        $service = Sher_Core_Service_FiuUserRecord::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort_field;
        $result = $service->get_fiu_user_record_list($query,$options);
		
        $context->set($var, $result);
		
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
		
    }
}

