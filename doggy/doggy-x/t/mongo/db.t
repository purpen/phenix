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

$db = DoggyX_Mongo_Manager::get_db();
ok($db instanceof DoggyX_Mongo_Db,'get_db');

{
    ok($db->save('t1',array('name'=>'t1','age'=>20)),'save');
    ok($db->update('t1',array('name'=>'t1'),array('name'=>'t1','age'=>30)),'update');
    $cnt = $db->count('t1');
    is($cnt,1,'count');
}
{
    $row = $db->find('t1',array('name'));
}
{
    ok($db->drop('t1'),'drop');
}

{
    for ($i=0; $i < 20; $i++) { 
        $rows[] = array('name'=>'n'.$i,'idx'=>$i);
    }
    $db->batch_insert('t2',$rows);
    is($db->count('t2'),20,'batch_insert');
    
    $result = $db->find_field('t2','name');
    is($result[0],'n0','find_field');
    
    $option['page'] = 2;
    $option['size'] = 2;
    $option['fields'] = array('name'=>1);
    $query = array();
    $result = $db->find('t2',$query,$option);
    is($result[0]['name'],'n2','find/page,size');
    $row2_id = $result[0]['_id'];
    
    $first = $db->first('t2',array());
    is($first['name'],'n0','first');
    
    $row2 = $db->first('t2',$row2_id);
    is($row2['name'],'n2','first by id');
    
    $db->remove('t2',$row2_id);
    is($db->count('t2',array('name'=>'n2')),0,'remove');
}
//modifier tests
{
    $db->inc('t2',array('name'=>'n2'),'inc_f',2);
    $inc_v = $db->first('t2',array('name'=>'n2'),array('inc_f'));
    is($inc_v['inc_f'],2,'inc');
    $db->inc('t2',array('name'=>'n2'),'inc_f',1);
    $inc_v = $db->first('t2',array('name'=>'n2'),array('inc_f'));
    is($inc_v['inc_f'],3,'inc');
    
    $db->push('t2',array('name'=>'n2'),'a_f','a');
    $v = $db->first('t2',array('name'=>'n2'),array('a_f'));
    is($v['a_f'],array('a'),'push');
    $db->push('t2',array('name'=>'n2'),'a_f','b');
    $v = $db->first('t2',array('name'=>'n2'),array('a_f'));
    is($v['a_f'],array('a','b'),'push');
    
    $db->push_all('t2',array('name'=>'n2'),'a_f',array('c','d'));
    $v = $db->first('t2',array('name'=>'n2'),array('a_f'));
    is($v['a_f'],array('a','b','c','d'),'push_all');
    
    $db->pull('t2',array('name'=>'n2'),'a_f','c');
    $v = $db->first('t2',array('name'=>'n2'),array('a_f'));
    is($v['a_f'],array('a','b','d'),'pull');

    $db->pull_all('t2',array('name'=>'n2'),'a_f',array('a','d'));
    $v = $db->first('t2',array('name'=>'n2'),array('a_f'));
    is($v['a_f'],array('b'),'pull_all');
    
    $db->set('t2',array('name'=>'n100'),array('title'=>'n100','age'=>30));
    $v = $db->first('t2',array('name'=>'n100'));
    is(empty($v),'set/none');
    $db->set('t2',array('name'=>'n100'),array('title'=>'t100','age'=>30),true);
    $v = $db->first('t2',array('name'=>'n100'));
    ok($v['name']=='n100' && $v['age']==30 && $v['title']=='t100','set/upsert');
    
    $db->set('t2',array('name'=>'n100'),array('title'=>'t200'));
    $v = $db->first('t2',array('name'=>'n100'));
    ok($v['name']=='n100' && $v['age']==30 && $v['title']=='t200','set');
    
}
{
    $db->ensure_index('t2','name');
    $keys = $db->get_index_info('t2');
    is($keys[1]['key'],array('name'=>1),'ensure_index');
    ok($db->delete_indexes('t2'),'delete_indexes');
}

{
    $db->store_server_function('x','function(){ return i+5; }');
    $i = 10;
    $ok = $db->call_function('x',array('i' => $i));
    is($ok,$i+5,'call_function');
    $ok = $db->call_function('x',array('i' => $i+10));
    is($ok,25,'call_function');
}

{
    $row = $db->find_and_modify('t2',array(
        'query' => array('name' => 'n100'),
        'update' => array('$inc' => array('age' => 4)),
        'fields' => array('age' => 1),
        'new' => true,
        ));
    is($row['age'],34,'find_and_modify/inc');
    $v = $db->first('t2',array('name'=>'n100'));
    is($v['age'],34,'find_and_modify/verify');
}

ok($db->drop('t2'),'drop');

?>