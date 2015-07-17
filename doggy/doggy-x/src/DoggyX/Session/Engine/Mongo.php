<?php
/**
 * A session engine use mongoDB as backend store.
 * 
 * merged from czone project
 * 
 * @author n.s.
 * 
 */
class DoggyX_Session_Engine_Mongo extends DoggyX_Model_Mongo_Base implements DoggyX_Session_Engine {
    protected $collection = 'session';

    protected $mongo_id_style = self::MONGO_ID_CUSTOM;

    protected $schema = array();
    protected $required_fields = array();
    
    // protected $auto_update_timestamp = true;
    protected $created_timestamp_fields = array('created_on','alive');
    protected $updated_timestamp_fields = array('alive');
    
    public $ttl = 3600;
    
    protected $opened = false;

    protected $secret_key = false;

    const BLANK_SECRET_KEY = 'CHANGE_IT';

    public function __construct($ttl=3600, $options=array()) {
        $this->ttl = $ttl;
        // custom collection name
        if (!empty($options['collection'])) {
            $this->collection = $options['collection'];
        }
        if (!empty(Doggy_Config::$vars['app.session.secret_key'])) {
            $this->secret_key = Doggy_Config::$vars['app.session.secret_key'];
            if ($this->secret_key == $this::BLANK_SECRET_KEY) {
                throw new DoggyX_Session_Exception('Session secret_key is initial value, you MUST manual set it in [app.session.secret_key]');
            }
        } else {
            throw new DoggyX_Session_Exception('Session secret_key is NULL, you MUST set it in [app.session.secret_key]');
        }
        parent::__construct();
    }
    

    public function gc($expired_time) {
        return $this->remove(array('alive' => array('$lt' => $expired_time)));
    }
    
    public function create_session($sid,array $data=array()) {
        if ($this->opened) {
            throw new DoggyX_Session_Exception('session is started, please close it first!');
        }
        $this->reset();
        $data['_id'] = $sid;
        $data['created_on'] = $data['alive'] = time();
        $data['serial_no'] = $this->build_sequence_no();
        $this->create($data);
        $this->opened = true;
        Doggy_Log_Helper::debug("New session $sid is open");
    }
    
    public function load_session($sid) {
        if ($this->opened) {
            throw new DoggyX_Session_Exception('session is started, please close it first!');
        }
        
        $this->reset();
        $data = $this->load($sid);
        if (empty($data)) {
            Doggy_Log_Helper::debug("session $sid is NULL");
            return false;
        }
        if ($data['alive'] < ( time() - $this->ttl ) ) {
            $this->remove($sid);
            $this->reset();
            return false;
        }
        $this->opened = true;
        return true;
    }

    public function close(array $data=array()) {
        if ($this->opened) {
            $this->save($data);
        }
        $this->opened = false;
    }
    
    public function destroy_session($sid) {
        if ($sid == $this->id) {
            $this->reset();
        }
        $this->remove($sid);
    }
    
    public function build_session_id() {
        $sid_data = (string) new MongoId();
        // if hash extension installed, use it first, or fallback to pure php implements
        if (function_exists('hash_hmac')) {
            $key = hash_hmac('sha1', $sid_data, $this->secret_key);
        } else {
            $key = Doggy_Util_Crypt_Util::hmac_sha1($this->secret_key, $sid_data);
        }
        return $key;
    }
    
	/**
	 * 生成一个排序数字
	 */
	public function build_sequence_no() {
		$col_name = 'serialno';
		
		$val = $this->next_seq_id($col_name);
		
		Doggy_Log_Helper::debug("Gen to sequence no [$val]");
		
		return (int)$val;
	}
}