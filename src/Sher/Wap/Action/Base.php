<?php
/**
 * Wap Shop
 * @author purpen
 */
class Sher_Wap_Action_Base extends Sher_Core_Action_Authorize {
	
    /**
     * alsia display_note_page
     */
    public function show_message_page($note, $url = null, $delay = 3000){
    	return $this->display_note_page($note,$url,$delay);
    }
	
    /**
     * 显示一个通用的信息跳转页面
     */
    public function display_note_page($note, $url = null, $delay = 3000) {
        if (!empty($url)) {
            $this->stash['redirect_url'] = $url;
        	$this->stash['delay'] = $delay;
		}
        $this->stash['note'] = $note;
        return $this->to_html_page('wap/note_page.html');
    }
	
}
?>
