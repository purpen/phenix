<?php
/**
 * A simple authentication action implements.
 * 
 * This action just redirect to predefined url.
 */
class Doggy_Auth_Authentication_SimpleAction extends Doggy_Dispatcher_Action_Lite implements Doggy_Auth_Authentication_Action {
    private $next_url = null;
    public function login($value='') {
        $url = Doggy_Config::get('app.url.login');
        if (empty($url)) {
            return $this->to_raw('Login is disabled.(app.url.login not defined.)');
        }
        else {
            return $this->_rediect_to($url);
        }
    }
    public function deny() {
        $url = Doggy_Config::get('app.url.deny');
        if (empty($url)) {
            return $this->to_raw('403 your action is forbidden.');
        }
        else {
            return $this->_rediect_to($url);
        }
    }
    
    private function _rediect_to($url) {
        if (isset($this->next_url)) {
            if (strpos($url,'?')===false) {
                $to = $url.'?return_url='.urlencode($this->next_url);
            }
            else {
                $to = $url .'&return_url='.urlencode($this->next_url);
            }
        }
        else {
            $to = $url;
        }
        return $this->to_redirect($to);
    }
    
    public function logout() {
        $url = Doggy_Config::get('app.url.logout');
        if (empty($url)) {
            return $this->to_raw('Logout action disabled.<app.url.logout> not defined.');
        }
        else {
            return $this->_rediect_to($url);
        }
    }
    
    public function register() {
        $url = Doggy_Config::get('app.url.register');
        if (empty($url)) {
            return $this->to_raw('Register is disabled.(app.url.register not defined.)');
        }
        else {
            return $this->_rediect_to($url);
        }
    }
    public function _setNextUrl($url) {
        $this->next_url = $url;
    }
}
?>