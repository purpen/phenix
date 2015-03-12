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
{
    $context = Doggy_Dispatcher_Context::getContext();
    $context->set('foo_test','foo');
    is($context->get('foo_test'),'foo','set/get action scope');
    $context->remove('foo_test');
    is($context->get('foo_test'),null,'remove');
    
    $request = new Doggy_Dispatcher_Request_Http();
    $response = new Doggy_Dispatcher_Response_Http();
    
    $context->setRequest($request);
    $context->setResponse($response);
    
    ok($context->getRequest() === $request,'set/get request');
    ok($context->getResponse() === $response,'set/get request');
    
    $context->putResult('foo_result','foo_result');
    is($context->getResult('foo_result'),'foo_result','set/get_result');
    
}
{
    $context->put('k1','k1');
    $another_data = array('k2'=>'k2','k3'=>'k3');
    $context->putAll($another_data);
    is($context->get('k1'),'k1','put_all');
    is($context->get('k3'),'k3','put_all');
    $context->putAll(array('k1'=>5,'k3'=>10));
    is($context->get('k1'),5,'put_all');
    is($context->get('k3'),10,'put_all');
    
    $context->clear();
    is($context->get('k3'),null,'clear');
}

{
    $session = $context->getSessionContext();
    ok($session instanceof Doggy_Session_Context,'getSessionContext');
    $context->setSession('mock_id','bbb');
    is($context->getSession('mock_id'),'bbb','set/get session');
    ok($context->hasSession('mock_id'),'hasSession');
    ok(!$context->hasSession('k1'),'hasSession');
    
}
?>