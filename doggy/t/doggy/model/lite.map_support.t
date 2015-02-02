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
//Now, let's rock!
include_once dirname(__FILE__).'/test_support.php';

$db = Doggy_Model_DbHelper::get_model_db('man');
setup_test_table($db);

#enable identify_map support
Doggy_Config::$vars['app.model._global.map'] = array(
    'class'=>'Doggy_Model_IdentifyMap_Memcached',
    );
{
    $man = new Doggy_Model_Man();
    
    $map = $man->get_map();
    
    ok($map instanceof Doggy_Model_IdentifyMap_Memcached,'model identify map created');
    
    $man->create(array('name'=>'T1'));
    $man->create(array('name'=>'T2'));
    $man->create(array('name'=>'T3'));
    
    $row1 = $map->load(1);
    $row2 = $map->load(2);
    $row3 = $map->load(3);
    is($row1['name'],'T1','create, should put map');
    is($row2['name'],'T2','create, should put map');
    is($row3['name'],'T3','create, should put map');
    
    $man->load(2);
    $man->name = 'T200';
    $man->save();
    $row2 = $map->load(2);
    
    is($row2['name'],'T200','update row, also update map');
    
    $man->destroy(3);
    ok($map->load(3)===false,'destory row, should remove map');
    
    $man->destroy(array(1,2));
    ok( $map->load(1)===false and $map->load(2)===false,'destory mulity row, should remove map');
    
    
    $man->create(array('name'=>'T4'));
    $man->create(array('name'=>'T5'));
    $man->create(array('name'=>'T6'));
    
    $man->destroy_all('id > ?',array(4));
    ok( $map->load(4)!==false and $map->load(5)===false and $map->load(6) === false,'destory all, should remove related map');
    
    
    $man->create(array('name'=>'T7'));
    $man->create(array('name'=>'T8'));
    $man->create(array('name'=>'T9'));
    
    #test find_by_pk multi
    
    $result = $man->find_by_pk(array(7,8,9));
    is($result[8]['name'],'T8','find_by_pk(multi keys)');
    
    $map->remove(8);
    $result = $man->find_by_pk(array(7,8,9,20));
    is(count($result),3,'find_by_pk(reload missing data)');
    is($result[8]['name'],'T8','find_by_pk(reload missing data)');
    ok($map->load(8) !== false,'find_by_pk(missing data added)');
}

$map->clear();
clean_test_table($db);
?>