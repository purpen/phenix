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

$map = new Doggy_Model_IdentifyMap_Memcached('test');

{
    $map->put(5,array('id'=>5,'name'=>'P5'));
    $map->put(6,array('id'=>6,'name'=>'P6'));
    is($map->load(5),array('id'=>5,'name'=>'P5'),'basic,put/load');
    is($map->load(array(5,6)),array('5'=>array('id'=>5,'name'=>'P5'),'6'=>array('id'=>6,'name'=>'P6') ),'basic,put/load');
    
    $map->remove(6);
    ok($map->load(6)===false,'reomve');
    
    $map->put(5,array('name'=>'NP5','age'=>20));
    is($map->load(5),array('id'=>5,'name'=>'NP5','age'=>20),'put(update)');
    
    ok(!$map->add(5,array('name'=>'Add')),'add(false)');
    $map->remove(5);
    ok($map->add(5,array('name'=>'Add')),'add(true)');
    $map->remove(5);
}
{
    $map1 = new Doggy_Model_IdentifyMap_Memcached('dog');
    $map2 = new Doggy_Model_IdentifyMap_Memcached('cat');
    $map1->put(1,array('id'=>1,'name'=>'dog'));
    $map2->put(1,array('id'=>1,'name'=>'cat'));
    is($map2->load(1),array('id'=>1,'name'=>'cat'),'check diff model');
    is($map1->load(1),array('id'=>1,'name'=>'dog'),'check diff model');
    
    $map1->remove(1);
    $map2->remove(1);
    
    $map3 = new Doggy_Model_IdentifyMap_Memcached('dog2');
    $map3->put(1,array('id'=>1,'name'=>'dog'));
    $map3->put(2,array('id'=>1,'name'=>'cat'));
    $map3->clear();
    ok($map3->load(1) == false && $map3->load(2) == false,'clear');
}
?>