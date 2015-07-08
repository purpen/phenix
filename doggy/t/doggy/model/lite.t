#!/usr/bin/env php
<?php
if (getenv('DOGGY_TEST_CLASS_PATH')) {
    set_include_path(getenv('DOGGY_TEST_CLASS_PATH'));
}
require "Test.php";
require "Doggy.php";

if (getenv('DOGGY_APP_ROOT')) {
    $doggy_app_root = getenv('DOGGY_APP_ROOT');
    define('DOGGY_APP_ROOT',$doggy_app_root);
    require $doggy_app_root.'/var/test.rc';
}


/*
Test howto:
-----------------------------------------------------------
plan($num); # plan $num tests
# or
plan('no_plan'); # We don't know how many
# or
plan('skip_all'); # Skip all tests
# or
plan('skip_all', $reason); # Skip all tests with a reason

diag('message in test output') # Trailing \\n not required
# $test_name is always optional and should be a short description of
# the test, e.g. "some_function() returns an integer"

# Various ways to say "ok"
ok($have == $want, $test_name);

# Compare with == and !=
is($have, $want, $test_name);
isnt($have, $want, $test_name);

# Run a preg regex match on some data
like($have, $regex, $test_name);
unlike($have, $regex, $test_name);

# Compare something with a given comparison operator
cmp_ok($have, '==', $want, $test_name);
# Compare something with a comparison function (should return bool)
cmp_ok($have, $func, $want, $test_name);

# Recursively check datastructures for equalness
is_deeply($have, $want, $test_name);

# Always pass or fail a test under an optional name
pass($test_name);
fail($test_name);

# TODO tests, these are want to fail but won't fail the test run,
# unwant success will be reported
todo_start("integer arithmetic still working");
ok(1 + 2 == 3);
{
    # TODOs can be nested
    todo_start("string comparison still working")
    is("foo", "bar");
    todo_end();
}
todo_end();
*/
//Now, let's rock!
include_once dirname(__FILE__).'/test_support.php';

$db = Doggy_Model_DbHelper::get_model_db('man');

setup_test_table($db);
{
    $man = new Doggy_Model_Man();
    is($man->db_table_name(),'man','db_table_name');
    is($man->model_name(),'man','model_name');
    is($man->model_class(),'Doggy_Model_Man','model_class');
    is($man->table_prefix('test'),'test','table_prefix');
    is($man->table_prefix(),'test','table_prefix');
    is($man->db_table_name(),'test_man','table_prefix');
    is($man->pk(),null,'pk');
    is($man->pk(1),1,'pk');
    is($man->pk(),1,'pk');
}
#save/create
{
    $man = new Doggy_Model_Man();
    //basic operation
    //1.0 create 
    $man->name = 'T1';
    $man->save();
    is($man->id,1,'save');
    ok($man->is_saved(),'save');
    
    $result = $db->query('select * from man where id=1');
    is($result[0]['name'],'T1','save');
    ok(!empty($result[0]['created_on']),'autotimestamp fields/create');
    ok(empty($result[0]['updated_on']),'autotimestamp fields/update');
    
    $man->create(array('name'=>'T2'));
    is($man->id,2,'save/create');
    ok($man->is_saved(),'save/create');
    $result = $db->query('select * from man where id=2');
    is($result[0]['name'],'T2','save/create');

    $man->name = 'T3';
    $man->save();
    
    $result = $db->query('select name from man where id=2');
    is($result[0]['name'],'T3','save/modify');
    
    $man->load(1);
    $man->save();
    $result = $db->query('select * from man where id=1');
    ok(!empty($result[0]['created_on']),'autotimestamp fields/create');
    ok(!empty($result[0]['updated_on']),'autotimestamp fields/update');
    
    //check events trigger
    $man->reset_event_marks();
    $man->create(array('name'=>'T4'));
    
    $i=0;
    is($man->get_event_ticks('before_validation'),$i++,'create event');
    is($man->get_event_ticks('validate'),$i++,'create event');
    is($man->get_event_ticks('after_validation'),$i++,'create event');
    is($man->get_event_ticks('before_save'),$i++,'create event');
    is($man->get_event_ticks('before_create'),$i++,'create event');
    is($man->get_event_ticks('after_create'),$i++,'create event');
    is($man->get_event_ticks('after_save'),$i++,'create event');
    
    //update mode events
    $man->reset_event_marks();
    $man->save();
    $i=0;
    is($man->get_event_ticks('before_validation'),$i++,'update event');
    is($man->get_event_ticks('validate'),$i++,'update event');
    is($man->get_event_ticks('after_validation'),$i++,'update event');
    is($man->get_event_ticks('before_save'),$i++,'update event');
    is($man->get_event_ticks('before_update'),$i++,'update event');
    is($man->get_event_ticks('after_update'),$i++,'update event');
    is($man->get_event_ticks('after_save'),$i++,'update event');
}
#insert
{
    reset_test_data($db);
    $man = new Doggy_Model_Man();
    $man->name = 'T';
    $man->insert();
    ok(!$man->is_saved(),'insert');
    is($man->name,null,'insert');
    
    $man->insert(array('name'=>'T2'));
    is($man->name,'T2','insert(init data)');
    ok(!$man->is_saved(),'insert(init data)');
    
    $man->save();
    $id = $man->pk();
    
    $result = $man->find_by_pk($id);
    $man2 = new Doggy_Model_Man();
    $man2->insert();
    $man2->apply($result);
    ok($man->is_saved(),'apply loaded data');
    
}
#destroy
{
    reset_test_data($db);
    $man = new Doggy_Model_Man();
    $man->create(array('name'=>'F1'));
    $man->destroy();
    $result = $db->query('SELECT count(*) AS cnt FROM man WHERE id=1');
    is($result[0]['cnt'],0,'default destroy self');
    
    $man->create(array('name'=>'F2'));
    $man->create(array('name'=>'F3'));
    $man->destroy(2);
    $result = $db->query('SELECT count(*) AS cnt FROM man WHERE id=2');
    is($result[0]['cnt'],0,'destroy by id');
    $result = $db->query('SELECT count(*) AS cnt FROM man WHERE id=3');
    is($result[0]['cnt'],1,'destroy by id');
    
    
    $man->reset_event_marks();
    $man->destroy(3);
    $i=0;
    is($man->get_event_ticks('before_destroy'),$i++,'destroy event');
    is($man->get_event_ticks('after_destroy'),$i++,'destroy event');
}

#destroy_all
{
    reset_test_data($db);
    $man = new Doggy_Model_Man();
    $man->create(array('name'=>'T1'));
    $man->create(array('name'=>'T2'));
    $man->create(array('name'=>'T3'));
    $man->create(array('name'=>'T4'));
    
    
    $man->destroy_all("name=?",array('T4'));
    
    $result = $db->query('SELECT count(*) AS cnt FROM man WHERE `name` = ?',1,1,array('T4'));
    is($result[0]['cnt'],0,'destroy_all');
    
    $result = $db->query('SELECT count(*) AS cnt FROM man ');
    is($result[0]['cnt'],3,'destroy_all');
    
    $man->destroy_all('id < ?',array(3));
    $result = $db->query('SELECT count(*) AS cnt FROM man ');
    is($result[0]['cnt'],1,'destroy_all');
}

#find related
{
    reset_test_data($db);
    $man = new Doggy_Model_Man();
    for ($i=0; $i < 20; $i++) { 
        $man->create(array('name' => "T$i"));
    }
    
    //shortcut 
    $result = $man->find_by_pk(1);
    is($result['name'],'T0','find_by_pk');
    
    $result = $man->find_by_pk(array(4,5,3));
    is(count($result),3,'find_by_pk(multi keys)');
    is($result[5]['name'],'T4','find_by_pk(multi keys)');
    
    $man->load(2);
    is($man->name,'T1','load');
    
    
    
    #1. page size test
    $result = $man->find(array('page'=>1,'size'=>5));
    is($result[0]['name'],'T0','find');
    is($result[4]['name'],'T4','find');
    
    
    $result = $man->find(array('page'=>4,'size'=>5));
    is($result[0]['name'],'T15','find');
    is($result[4]['name'],'T19','find');
    
    $result = $man->find(array('page'=>6,'size'=>5));
    is(count($result),0,'find');
    
    
    #2. misc
    $result = $man->find(array('condition'=>'name=?','vars'=>array('T10')));
    is($result[0]['name'],'T10','find');
}
#first
{
    reset_test_data($db);
    $man = new Doggy_Model_Man();
    $man->create(array('name'=>'F1'));
    $man->create(array('name'=>'F2'));
    $man->create(array('name'=>'F3'));
    $result = $man->first(array('order_by'=>'name DESC'));
    is('F3',$result['name'],'first');
}
#count_if
{
    reset_test_data($db);
    $man = new Doggy_Model_Man();
    $man->create(array('name'=>'F1'));
    $man->create(array('name'=>'F2'));
    $man->create(array('name'=>'F3'));
    
    is($man->count_if('id>?',array(4)),0,'count_if');
    is($man->count_if('name like ?',array('F%')),3,'count_if');
}
#has
{
    reset_test_data($db);
    $man = new Doggy_Model_Man();
    $man->create(array('name'=>'F1'));
    $man->create(array('name'=>'F2'));
    $man->create(array('name'=>'F3'));
    
    ok($man->has(1),'has');
    ok($man->has(array(1,4,5)),'has,TRUE,if anyone of id lists');
    ok(!$man->has(4),'has');
    
}
#has_if
{
    reset_test_data($db);
    $man = new Doggy_Model_Man();
    
    $man->create(array('name'=>'F1'));
    $man->create(array('name'=>'F2'));
    $man->create(array('name'=>'F3'));
    
    ok(!$man->has_if("name like 'A%' "),'has_if');
    ok(!$man->has_if("id > 4 "),'has_if');
    ok($man->has_if("id < ? ",array(4)),'has_if');
    
}
clean_test_table($db);
?>