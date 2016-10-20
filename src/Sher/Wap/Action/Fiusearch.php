<?php
/**
 * 高级搜索
 * @author purpen
 */
class Sher_Wap_Action_Fiusearch extends Sher_Wap_Action_Base {
    
    public $stash = array(
		'page' => 1,
    'size' => 20,
		'q' => '',
		'ref' => '',
		't' => 1,
		'index_name' => 'full',
    'evt' => 'content',
    'db' => 'phenix',
    's' => 1,
    'asc' => 0,
	);

	protected $exclude_method_list = array('execute', 'xc');
	
    public function execute() {
        $this->set_target_css_state('page_find');
        return $this->xc();
	}

    /**
    * 迅搜引擎,不走数据库/图片和用户名需要查询数据库
    */
    public function xc(){
		return $this->to_html_page('wap/fiusearch.html');
    }

    
}
