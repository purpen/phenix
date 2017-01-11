<?php
/**
 * 个人中心
 * @author tianshuai
 */
class Sher_League_Action_My extends Sher_League_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(

	);

	protected $page_tab = 'page_my';
	protected $page_html = 'league/my/index.html';

	public function _init() {
		$this->stash['user_id'] = $this->visitor->id;
        $this->stash['user'] = &$this->stash['visitor'];
		if (empty($this->stash['user'])) {
	       return $this->display_note_page('用户不存在或未登录，请先登录！');
	    }

        $model = new Sher_Core_Model_Alliance();
        $alliance = $model->first($this->visitor->id);
        if(empty($alliance)){
 	        return $this->display_note_page('您还未申请太火鸟联盟账户！');       
        }
        if($alliance['status']==0){
 	        return $this->display_note_page('账户已被禁用！');
        }
        if($alliance['status']==1){
 	        return $this->display_note_page('账户等待审核！');
        }
        if($alliance['status']==2){
 	        return $this->display_note_page('账户未通过审核，请重新提交资料！');
        }

        $this->stash['alliance'] = $alliance;

    }

	public function execute(){
		return $this->index();
	}

	/**
	 * 账户首页
	 */
	public function index(){
		return $this->to_html_page("league/my/index.html");
	}

	/**
	 * 分成列表
	 */
	public function balance_list(){
		return $this->to_html_page("league/my/balance_list.html");
	}

	/**
	 * 结算列表
	 */
	public function balance_record_list(){
		return $this->to_html_page("league/my/balance_record_list.html");
	}

	/**
	 * 结算名细
	 */
	public function balance_item_list(){
		return $this->to_html_page("league/my/balance_item_list.html");
	}

	/**
	 * 提现列表
	 */
	public function withdraw_cash_list(){
		return $this->to_html_page("league/my/withdraw_cash_list.html");
	}

	/**
	 * 提现详情
	 */
	public function withdraw_cash_view(){
		return $this->to_html_page("league/my/withdraw_cash_view.html");
	}

}
