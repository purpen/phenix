<?php
/**
 * 扩展的Action基础类
 * 
 * merge from czone related project.
 *
 * 这个Action封装了更多实用的方法,经实践，多数项目均可复用
 * @author n.s.
 */
class DoggyX_Action_Base extends Doggy_Dispatcher_Action_Lite {
    /**
     * 当前用户/访客
     *
     * @var string
     */
    public $visitor = null;
    
    /**
     * 定义自动加载的Dt view tag扩展库
     *
     * @var string
     */
    protected $dt_view_tags= array();
    
    /**
     * 将result直接封装为JSON后输出
     *
     * @param string $result 
     * @return void
     */
    public function to_raw_json($result) {
        return $this->to_raw(json_encode($result));
    }
    
    /**
     * 输出MongoDB GridFile
     *
     * @param string $fs 
     * @return void
     */
    public function to_mongofs($fs) {
        $this->stash['_view']['fs'] = $fs;
        return 'mongofs';
    }
    
    /**
     * A Dt based html page.
     *
     * @param string $path 
     * @return string
     */
    public function to_html_page($path) {
        $this->stash['_view']['template'] = $path;
        $this->before_to_view();
        return 'html';
    }
    /**
     * Prepare common view initialize work
     *
     * @return void
     */
    protected function before_to_view() {
        $this->_load_dt_view_tags();
    }
    
    protected function _load_dt_view_tags() {
        if (!empty($this->dt_view_tags)) {
            foreach ($this->dt_view_tags as $key) {
                Doggy_Dt::load($key);
            }
        }
    }
    /**
     * 显示taconite output(和前端jquery.taconite.js配合使用)
     *
     * @param string $path 
     * @return void
     */
    public function to_taconite_page($path) {
        $this->stash['_view']['template'] = $path;
        $this->before_to_view();
        return 'taconite';
    }
    /**
     * if current is ajax request
     *
     * @return bool
     */
    protected function is_ajax_request() {
        return Doggy_Dispatcher_Context::getContext()->getRequest()->isAjaxRequest();
    }
    
    /**
     * Return current page referer if any.
     *
     * @return void
     */
    protected function current_page_ref() {
        $request = Doggy_Dispatcher_Context::getContext()->getRequest();
        return $request->HTTP_REFERER;
    }
    
    /**
     * 简单封装的Ajax response page(taconite page).
     *
     * 很多时候，当处理结果是错误的时候要显示一个错误信息，而正常则进行一些后续动作（比如隐藏、显示某些dom node),
     * 这里通过对result的code进行了简单的处理,若code==200,表示当前是正常结果，否则表示当前是错误,并将错误信息传递给模版
     * 
     * @param string $page Taconite page模版
     * @param array $result 指示当前回应结果是否正常
     * @return void
     */
    public function ajax_response($page,$result=array()) {
        if ($result['code'] == 200) {
            $this->stash['is_error'] = false;
            if (isset($result['message'])) {
                $this->stash['message'] = $result['message'];
            }
        }
        else {
            $this->stash['is_error'] = true;
            $this->stash['message'] = $result['message'];
        }
        return $this->to_taconite_page($page);
    }
    /**
     * 显示一个RSS类型的页面
     *
     * @param string $path 
     * @return string
     */
    public function to_rss_page($path) {
        $this->stash['_view']['template'] = $path;
        $this->_load_dt_view_tags();
        return 'rss';
    }
    
}