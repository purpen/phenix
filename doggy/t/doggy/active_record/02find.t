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
include dirname(__FILE__).'/common_inc.php';


$dba = Doggy_Dba_Manager::get_model_dba();
setup_test_tables();

Doggy_ActiveRecord_Base::setDba($dba);
Doggy_ActiveRecord_Base::disableInternalCache();
$model = new Doggy_ActiveRecord_BaseTest_Me();

#findFirst
{
    $result= $model->findFirst(array('order'=>'id ASC'));
    is($result['name'],'A1','findFirst ..ASC');
    
    $result= $model->findFirst(array('order'=>'id DESC'));
    is($result['name'],'A9','findFirst ..DESC');
    
    
    $result= $model->findFirst(array('condition'=>'name="A3"'));
    is($result['id'],3,'findFirst condition');
    
}

#findById
{
    
    $model->findById(1);
    is($model['name'],'A1','findById .. 1');
    $model->findById(9);
    is($model['name'],'A9','findById .. 2');
    
    $model->findById(999);
    is($model->count(),0,'findById .. empty');
    
}

#findBySql
{
     $result= $model->findBySql('SELECT * FROM '.$model->tablelize().' WHERE id=?',array('vars'=>array(1),'size'=>1,'page'=>1));
     is(count($result),1,'findBySql .. count');
     is($result[0]['id'],1,'findBySql .. check id');
     is($result[0]['name'],'A1','findBySql .. check name');
}



#find result var
{
    //$result just work as $model !
    $result= $model->find(array('condition'=>'id=1'));
    $result->find(array('condition'=>'id>6'));
    is(count($result),3,'find result is model .. count');
    is($result[0]['name'],'A7','find result is model .. 0');
    is($result[1]['name'],'A8','find result is model .. 1');
    is($result[2]['name'],'A9','find result is model .. 2');
    
    
    //force fetch result as array
    $data = $result->getResultArray();
    is(count($result),3,'find result as array .. count');
    is($data[0]['name'],'A7','find result as array .. 1');
    is($data[1]['name'],'A8','find result as array .. 2');
    is($data[2]['name'],'A9','find result as array .. 3');
}



clean_test_tables();

?>