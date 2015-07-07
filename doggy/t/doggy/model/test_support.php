<?php
class Doggy_Model_Man extends Doggy_Model_Lite {
    protected $model_name='man';
    private $event=array();
    private $event_ticks = 0;
    private $validate_ok=true;
    private $after_find=false;
    
    //mock test event trigger
    protected function before_save(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }
    protected function after_save(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }
    protected function before_create(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }
    protected function after_create(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }
    protected function before_validation(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }

    protected function after_validation(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }

    protected function before_destroy(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }

    protected function after_destroy(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }


    protected function before_update(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }

    protected function after_update(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }
    protected function validate(){
        $this->event[__METHOD__]=$this->event_ticks++;
        if(!$this->validate_ok){
            //$this->pushValidateError('validate error');
        }
        return $this->validate_ok;
    }
    public function reset_event_marks(){
        $this->event=array();
        $this->event_ticks=0;
    }
    public function get_event_ticks($key){
        $key = __CLASS__.'::'.$key;
        return isset($this->event[$key])?$this->event[$key]:null;
    }
    
}

function setup_test_table($db) {
    $db->execute('DROP TABLE IF EXISTS man');
    $db->execute('DROP TABLE IF EXISTS SEQ_MAN');
    $db->execute('CREATE TABLE  `man` (
        `id` INT NOT NULL ,
        `name` VARCHAR( 40 ) NOT NULL,
        `woman_id` INT NULL,
        `created_on` TIMESTAMP NULL,
        `updated_on` TIMESTAMP NULL
    )');
}
function clean_test_table($db) {
    $db->execute('DROP TABLE IF EXISTS man');
    $db->execute('DROP TABLE IF EXISTS SEQ_MAN');
}
function reset_test_data($db) {
    $db->execute('DELETE FROM man');
    $db->execute('DROP TABLE IF EXISTS SEQ_MAN');
}
?>