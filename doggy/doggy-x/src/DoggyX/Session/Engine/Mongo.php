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
    
    protected $schema = array();
    protected $required_fields = array();
    
    // protected $auto_update_timestamp = true;
    protected $created_timestamp_fields = array('created_on','alive');
    protected $updated_timestamp_fields = array('alive');
    
    public $ttl = 3600;
    
    protected $opened = false;
    
    
    public function __construct($ttl=3600,$options=array()) {
        $this->ttl = $ttl;
        // custom collection name
        if (!empty($options['collection'])) {
            $this->collection = $options['collection'];
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
        $data['_id'] = DoggyX_Mongo_Db::id($sid);
        $data['created_on'] = $data['alive'] = time();
        $this->create($data);
        $this->opened = true;
    }
    
    public function load_session($sid) {
        if ($this->opened) {
            throw new DoggyX_Session_Exception('session is started, please close it first!');
        }
        
        $this->reset();
        $data = $this->load($sid);
        if (empty($data)) {
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
        return (string) new MongoId();
    }
}
?>