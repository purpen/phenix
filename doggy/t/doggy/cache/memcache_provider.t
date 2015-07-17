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
if (!extension_loaded('memcache')) {
    plan('skip_all','memcache extension missing');
}

$options = array(
    'host'=>'127.0.0.1:11211',
    'compress'=>true,
    );
    
$cache = new Doggy_Cache_Provider_Memcached($options);

{
    $cache->set('k1',1);
    is($cache->get('k1'),1,'set/get');
    
    $cache->set('k1','g1','g1');
    is($cache->get('k1','g1'),'g1','set/get group');
    isnt($cache->get('k1'),$cache->get('k1','g1'),'set/get group');
    
}
{
    $g1 = 'group1';
    $g2 = 'group2';
    $cache->set('t1','t1');
    $cache->set('t1','t1_g1',$g1);
    $cache->set('t1','t1_g2',$g2);
    
    $cache->remove('t1',$g1);
    cmp_ok($cache->get('t1',$g1),'===',null,'remove');
    is($cache->get('t1',$g2),'t1_g2','remove');
    is($cache->get('t1'),'t1','remove');
    $cache->remove('t1');
    cmp_ok($cache->get('t1'),'===',null,'remove');
    
}
{
    $cache->set('t2','t2');
    $cache->set('t3','t3');
    $cache->clear();
    cmp_ok($cache->get('t2'),'===',null,'clear');
    cmp_ok($cache->get('t3'),'===',null,'clear');
    
    $cache->set('t4','t4',$g1);
    $cache->set('t5','t5',$g2);
    
    $cache->clear($g2);
    
    cmp_ok($cache->get('t5',$g2),'===',null,'clear:group');
    is($cache->get('t4',$g1),'t4','clear:group');
}

$cache->clear();

?>