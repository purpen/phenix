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
class Doggy_Auth_Authentication_ManagerTest_MockProvider implements Doggy_Auth_Authentication_Provider {
    public static $called=null;
    /**
     * 撤消和失效指定的凭据信息
     *
     * @param Doggy_Auth_Authentication $authen
     */
    function revoke(Doggy_Auth_Authentication $authen=null){
        self::$called= 20;
    }
    /**
     * 创建一个未经过验证的授权凭证信息
     * 
     * @return Doggy_Auth_Authentication
     */
    function createAuthentication(){
        
    }
    
}

$auth_conf = array('class'=>'Doggy_Auth_Authentication_ManagerTest_MockProvider');
Doggy_Config::set('app.auth.provider.default',$auth_conf);

{
    $provider = Doggy_Auth_Authentication_Manager::getProvider();
    ok($provider instanceof Doggy_Auth_Authentication_ManagerTest_MockProvider,'getProvider');
    $provider2 = Doggy_Auth_Authentication_Manager::getProvider('default');
    ok($provider===$provider2,'getProvider');
    ok(Doggy_Auth_Authentication_Manager::provider()===$provider2,'provider');
}

{
    Doggy_Auth_Authentication_Manager::revokeCurrent();
    is(Doggy_Auth_Authentication_ManagerTest_MockProvider::$called,20,'revokeCurrent');
    Doggy_Auth_Authentication_ManagerTest_MockProvider::$called = null;
    Doggy_Auth_Authentication_Manager::revoke();
    is(Doggy_Auth_Authentication_ManagerTest_MockProvider::$called,20,'revoke');
}
?>