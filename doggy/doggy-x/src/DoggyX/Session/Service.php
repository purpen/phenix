<?php
/**
 * Simple/lite session basic service.
 *
 * Any application should inherits from this class.
 * 
 * code ported from czone.
 * 
 */
class DoggyX_Session_Service {
    
    /**
     * Session engine
     *
     * @var DoggyX_Session_Engine
     */
    public $session;
    public $session_ttl_delta;
    public $session_id = null;

    private static $instance;
    
    private $session_started = false;
    
    public $login_user;

    /**
     * Singleton instance
     *
     * @return DoggyX_Session_Service
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            $service_class = Doggy_Config::get('app.session.service','DoggyX_Session_Service');
            return self::$instance = new $service_class();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $class = Doggy_Config::get('app.session.engine','DoggyX_Session_Engine_Mongo');
        $this->session_ttl_delta = Doggy_Config::get('app.session.ttl',1800);
        $this->session = new $class($this->session_ttl_delta,Doggy_Config::get('app.session.engine.options',array()));
        $this->on_service_init();
    }
    
    public function __destruct() {
        if ($this->session_started) {
            $this->session->close();
            $this->session_started = false;
        }
    }
    
    /**
     * Terminate a visitor session
     */
    public function stop_visitor_session() {
        $this->session_started = false;
        $this->on_session_stop();
        $this->session->close();
    }
    
    
    /**
     * Start or resume a visitor session.
     *
     * @param string $sid 
     * @return void
     */
    public function start_visitor_session($sid=null) {
        $session = $this->session;
        if ($sid) {
            $session_loaded = $session->load_session($sid);
        }
        else {
            $session_loaded = false;
        }
        if (!$session_loaded) {
            $sid = $session->build_session_id();
            $session->create_session($sid,array(
                'user_id' => 0,
                'is_login' => 0,
                ));
        }
        $this->session_id = $sid;
        $this->load_visitor();
        $this->on_session_start();
        $this->session_started = true;
    }
    
    /**
     * Write current session id into cookie
     *
     * @param string $sid 
     * @return void
     */
    public function set_session_cookie($sid=null) {
        if (empty($sid)) {
            $sid = $this->session_id;
        }
        
        $sid_key = isset(Doggy_Config::$vars['app.session.sid'])?Doggy_Config::$vars['app.session.sid']:Doggy_Config::$vars['app.id'].'_sid';
        $ttl = $this->session_ttl_delta;
        // force great than 5 min, otherwise cause IE weird bug!
        if ($ttl < 300) {
            $ttl = 300;
        }
        $path = Doggy_Config::get('app.session.path','/');
        $domain = Doggy_Config::get('app.session.domain','');
        @setcookie($sid_key,$sid,time()+$ttl,'/',$domain, false, true);
    }
    
    public function is_session_started() {
        return $this->session_started;
    }
    
    /**
     * Delegated helper to load $login_user.
     *
     * @return void
     */
    public function load_visitor() {
    }
    
    /**
     * Delegated helper to inject any session information into the action.
     *
     * @param string $action 
     * @return void
     */
    public function bind_session_to_action($action){
    }
    // hook
    public function on_service_init() {
    }
    public function on_session_start() {
    }
    public function on_session_stop() {
    }
}