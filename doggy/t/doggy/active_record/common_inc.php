<?php
require "Doggy.php";

/**
 * 测试对象关系
 * 
 */
class Doggy_ActiveRecord_BaseTest_Me extends Doggy_ActiveRecord_Base {
    protected $tableName='me';
    
    //定义关系
    protected $RelationMap=array(
        'children'=>array(
            'type'=>self::HAS_MANY,
            'class'=>'Doggy_ActiveRecord_BaseTest_Child'
        ),
        'wife'=>array(
            'type'=>self::HAS_ONE,
            'class'=>'Doggy_ActiveRecord_BaseTest_Women',
            'foreign_key'=>'husband'
        ),
        'friends'=>array(
            'type'=>self::HAS_AND_BELONGS_TO_MANY,
            'class'=>'Doggy_ActiveRecord_BaseTest_People',
            'this_foreign_key'=>'my_id',
            'join_table'=>'my_friend',
            'habm_other_fields'=>array('type'),
            'options'=>array('condition'=>'type=1')
        )
    
    );
    
    protected $MagicField = array(
        'm1'=>'magicM1',
        'm2'=>'magicM2'
    );
    private $event=array();
    private $event_ticks = 0;
    private $validateOk=true;
    private $after_find=false;
    
    public static function getModel($data=null,$modelClass=__CLASS__){
        return parent::getModel($data,$modelClass);
    }
    public function setName($value){
        $this->set('name',$value);
    }
    public function getName(){
        return $this->get('name');
    }
    
    public function setWife($value){
        $this->set('women_id',$value);
    }
    public function getWife(){
        return $this->get('women_id');
    }
    //mock test event trigger
    protected function beforeSave(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }
    protected function afterSave(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }
    protected function beforeCreate(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }
    protected function afterCreate(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }
    protected function beforeValidation(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }

    protected function afterValidation(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }

    protected function beforeDestroy(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }

    protected function afterDestroy(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }


    protected function beforeUpdate(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }

    protected function afterUpdate(){
        $this->event[__METHOD__]=$this->event_ticks++;
    }
    protected function validate(){
        $this->event[__METHOD__]=$this->event_ticks++;
        if(!$this->validateOk){
            $this->pushValidateError('validate error');
        }
        return $this->validateOk;
    }
    public function resetEventMarks(){
        $this->event=array();
        $this->event_ticks=0;
    }
    public function getEventTicks($key){
        $key = __CLASS__.'::'.$key;
        return isset($this->event[$key])?$this->event[$key]:null;
    }
    
    public function setValidateOk($v){
        $this->validateOk=$v;
    }
    protected function afterFind($singelMode){
        $this->after_find=true;
    }
    public function getAfterFindTrigger(){
        return $this->after_find;
    }
    //magic field
    protected function magicM1(){
        return $this->getName().'M1';
    }    
    protected function magicM2(){
        return $this->getName().'M2';
    }
}

class Doggy_ActiveRecord_BaseTest_Women extends Doggy_ActiveRecord_Base{
    protected $tableName='women';
    
    //定义关系
    protected $RelationMap=array(
        'children'=>array(
            'type'=>self::HAS_MANY,
            'class'=>'Doggy_ActiveRecord_BaseTest_Child'
        ),
        'husband'=>array(
            'type'=>self::HAS_ONE,
            'class'=>'Doggy_ActiveRecord_BaseTest_Me'
        ),
        'friends'=>array(
            'type'=>self::HAS_AND_BELONGS_TO_MANY,
            'class'=>'Doggy_ActiveRecord_BaseTest_People',
            'this_foreign_key'=>'my_id',
            'other_foreign_key'=>'people_id',
            'join_table'=>'my_friend',
            'habm_other_fields'=>array('type'),
            'options'=>array('condition'=>'type=2')
        )
    
    );
    
    public function setName($value){
        $this->set('name',$value);
    }
    public function getName(){
        return $this->get('name');
    }
    public function setHusband($value){
        $this->set('husband',$value);
    }
    public function getHusband(){
        return $this->get('husband');
    }
}

class Doggy_ActiveRecord_BaseTest_People extends Doggy_ActiveRecord_Base{
    protected $tableName='people';
    
    //定义关系
    protected $RelationMap=array(
        'friends'=>array(
            'type'=>self::HAS_AND_BELONGS_TO_MANY,
            'class'=>'Doggy_ActiveRecord_BaseTest_People',
            'this_foreign_key'=>'my_id',
            'other_foreign_key'=>'people_id',
            'habm_other_fields'=>array('type'),
            'join_table'=>'my_friend',
            'options'=>array('condition'=>'type=3')
        )
    
    );
    
    
    
    public function setName($value){
        $this->set('name',$value);
    }
    public function getName(){
        return $this->get('name');
    }
}

class Doggy_ActiveRecord_BaseTest_Child extends Doggy_ActiveRecord_Base{
    protected $tableName='child';
    
    //定义关系
    protected $RelationMap=array(
        'father'=>array(
            'type'=>self::BELONGS_TO,
            'class'=>'Doggy_ActiveRecord_BaseTest_Me'
        ),
        'mother'=>array(
            'type'=>self::BELONGS_TO,
            'class'=>'Doggy_ActiveRecord_BaseTest_Women'
        ),
        'friends'=>array(
            'type'=>self::HAS_AND_BELONGS_TO_MANY,
            'class'=>'Doggy_ActiveRecord_BaseTest_People',
            'this_foreign_key'=>'my_id',
            'join_table'=>'my_friend',
            'other_foreign_key'=>'people_id',
            'habm_other_fields'=>array('type'),
            'options'=>array('condition'=>'type=4')
        )
    
    );
    
    public function setName($value){
        $this->set('name',$value);
    }
    public function getName(){
        return $this->get('name');
    }
    
    public function setMeId($value){
        $this->set('me_id',$value);
    }
    public function getMeId(){
        return $this->get('me_id');
    }
    public function setWomenId($value){
        $this->set('women_id',$value);
    }
    public function getWomenId(){
        return $this->get('women_id');
    }
    
    

}



function setup_test_tables($fill_data=true) {
    
    global $dba;

    $dba->execute('DROP TABLE IF EXISTS me');
    $dba->execute('DROP TABLE IF EXISTS people');
    $dba->execute('DROP TABLE IF EXISTS women');
    $dba->execute('DROP TABLE IF EXISTS child');
    $dba->execute('DROP TABLE IF EXISTS my_friend');
    //me table
    $dba->execute('CREATE TABLE  `me` (
             `id` INT NOT NULL ,
             `name` VARCHAR( 40 ) NOT NULL,
             `women_id` INT NULL
            ) '
    );
    $dba->execute('CREATE TABLE  `child` (
             `id` INT NOT NULL ,
             `me_id` INT NOT NULL,
             `women_id` INT NOT NULL,
             `name` VARCHAR( 40 )
            ) '
    );
    $dba->execute('CREATE TABLE  `people` (
             `id` INT NOT NULL ,
             `name` VARCHAR( 40 ) NOT NULL
            ) '
    );
    $dba->execute('CREATE TABLE  `women` (
             `id` INT NOT NULL ,
             `name` VARCHAR( 40 ) NOT NULL,
             `husband` INT NULL
            ) '
    );
    $dba->execute('CREATE TABLE  `my_friend` (
            `my_id` INT NOT NULL,
            `people_id` INT NOT NULL,
            `type` TINYINT NOT NULL
            ) '
    );
    if ($fill_data) {
    	for($i=1;$i<10;$i++){
	        $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array($i,'A'.$i));
	    }
    }
    
}
function clean_test_tables() {
    
    global $dba;
    
    $dba->execute('DROP TABLE IF EXISTS me');
    $dba->execute('DROP TABLE IF EXISTS SEQ_ME');
    
    $dba->execute('DROP TABLE IF EXISTS people');
    $dba->execute('DROP TABLE IF EXISTS SEQ_PEOPLE');
    
    $dba->execute('DROP TABLE IF EXISTS women');
    $dba->execute('DROP TABLE IF EXISTS SEQ_WOMEN');
    $dba->execute('DROP TABLE IF EXISTS child');
    
    $dba->execute('DROP TABLE IF EXISTS SEQ_CHILD');
    $dba->execute('DROP TABLE IF EXISTS my_friend');
}

?>