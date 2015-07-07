<?php
/**
 * An improved  DT result ,add many features.(port from czone)
 * 
 * Recomment this as default result type.
 *
 * @author n.s.
 */
class DoggyX_View_HtmlPage extends Doggy_Dispatcher_Result_Dt {

    public function render() {
        $action = $this->invocation->getAction();
        if (empty($action->stash['_view']['content_type'])) {
            $action->stash['_view']['content_type'] = 'text/html';
        }
        $action->stash['_view']['charset'] = 'utf-8';
        Doggy_Dt::$filters['json_encode'] = 'json_encode';
        Doggy_Dt::$filters['json_decode'] = 'json_decode';
        $dt_view_vars = isset($action->stash['_view']['dt'])?$action->stash['_view']['dt']:array();
        // it worth?
        // put all app.url.xxx into stash as app_url_xxxx
        foreach ( Doggy_Config::$vars as $key => $value) {
            if (substr($key,0,8) == 'app.url.') {
                $this->dt->set(str_replace('.','_',$key),$value);
            }
        }
        if (isset($action->stash['_view']['autoescape'])) {
            // Doggy_Log_Helper::debug('********set_autoescape:'.$action->stash['_view']['autoescape']);
            $this->dt->autoescape($action->stash['_view']['autoescape']);
        }
        $this->dt->set('app_id',Doggy_Config::$vars['app.id']);
        $this->dt->set('app_version',Doggy_Config::$vars['app.version']);
        $response = $this->invocation->getInvocationContext()->getResponse();
        if (!isset($action->stash['_view']['force_cache'])) {
            $response->set_no_cache();
        }
        return parent::render();
    }
}