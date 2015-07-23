<?php
/**
 * 微信里嵌套淘宝链接
 * @author purpen
 */
class Sher_Wap_Action_Taobao extends Sher_Wap_Action_Base {
	public $stash = array(
	    'id' => '',
	);
    
    // 链接转变
    public $urls = array(
        array(
          'id' => 28708,
          'url' => 'http://dwz.cn/PWRfV',  
        ),
    );
	
	protected $exclude_method_list = array('execute');
	
	public function execute(){
		if(empty($this->stash['id'])){
			return $this->show_message_page('缺少请求参数ID！');
		}
        
        for($i=0;$i<count($this->urls);$i++){
            if((int)$this->stash['id'] == $this->urls[$i]['id']){
                $url = $this->urls[$i]['url'];
            }
        }
        
        $this->stash['url'] = $url;
        
		return $this->to_html_page("wap/taobao.html");
	}
}