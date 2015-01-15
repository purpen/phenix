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

$context = Doggy_Session_Context::getContext();

#get/set
{
    is($context->set('test','A')->get('test'),'A','session set/get');
}
#has
{
    $context->set('id',5);
    ok($context->has('id'),'has');
    ok(!$context->has('should_not_exists'),'has');
}
#get session_id
{
    $id = $context->getSessionId();
    $check_id = @session_id();
    ok(!empty($id),'get session id');
    is($id,$check_id,'get session id');
    
}
#session restart
{
    Doggy_Session_Context::restart('s001');
    $context = Doggy_Session_Context::getContext();
    $context->set('a','destory_a');
    
    Doggy_Session_Context::restart('s002');
    $context = Doggy_Session_Context::getContext();
    $context->set('b','destory_b');
    
    Doggy_Session_Context::restart('s001');
    $context = Doggy_Session_Context::getContext();
    is($context->get('a'),'destory_a','session restart s001');
    is($context->get('b'),NULL,'session restart s001');
    
    Doggy_Session_Context::restart('s002');
    $context = Doggy_Session_Context::getContext();
    is($context->get('b'),'destory_b','session restart s002');
    is($context->get('a'),NULL,'session restart s002');
}
#session destory
{
    Doggy_Session_Context::restart('d001');
    $context = Doggy_Session_Context::getContext();
    $context->set('a','destory_a');
    $context->destory();
    is($context->get('a'),NULL,'destory current session');
    
    Doggy_Session_Context::restart('d002');
    $context = Doggy_Session_Context::getContext();
    $context->set('b','destory_b');
    
    Doggy_Session_Context::restart('d003');
    $context = Doggy_Session_Context::getContext();
    $context->set('c','destory_c');
    $context->destory('d002');
    is($context->get('c'),'destory_c','destory by session_id');

    Doggy_Session_Context::restart('d002');
    $context = Doggy_Session_Context::getContext();
    is($context->get('b'),NULL,'destory by session_id');
}


?>