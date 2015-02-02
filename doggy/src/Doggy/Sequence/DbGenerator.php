<?php
/**
 * A generator use model's db to emulate sequence.
 *
 */
class Doggy_Sequence_DbGenerator extends Doggy_Sequence_Generator {
    
    private $db_id;
    
    public function __construct($options=array()) {
        $db = 'default';
        extract($options,EXTR_IF_EXISTS);
        $this->db_id = $db;
    }

    public function _next($seq_name) {
        $db = Doggy_Db_Manager::get_db($this->db_id);
        return $db->gen_seq($seq_name);
    }
    public function _drop($seq_name) {
        $db = Doggy_Db_Manager::get_db($this->db_id);
        return $db->drop_seq($seq_name);
    }
}
?>