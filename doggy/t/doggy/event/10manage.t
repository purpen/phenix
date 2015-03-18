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
class Doggy_Event_ManagerTest_Subject extends Doggy_Event_Subject_Basic{
    public function getListeners(){
        return $this->_listeners;
    }
}
class Doggy_Event_ManagerTest_Observer implements Doggy_Event_Observer {
    public static $message;
    public function update($message){
        self::$message=$message;
    }
}
class Doggy_Event_ManagerTest_Observer_MQ extends Doggy_Event_ManagerTest_Observer {
    public static $message;
    public function update($message){
        self::$message=$message;
    }
}


$subject['t1']='Doggy_Event_ManagerTest_Subject';
$subject['t2']='Doggy_Event_ManagerTest_Subject';
$subject['mq']='Doggy_Event_ManagerTest_Subject';
$observer['Doggy_Event_ManagerTest_Observer']=array('t1','t2');
$observer['Doggy_Event_ManagerTest_Observer_MQ']=array('mq');
Doggy_Config::set('app.event.subjects',$subject);
Doggy_Config::set('app.event.observers',$observer);


{
    $manager = Doggy_Event_Manager::getInstance();
    ok($manager instanceof Doggy_Event_Manager,'getInstance');
    $sub = $manager->getSubject('t1');
    ok($sub instanceof Doggy_Event_ManagerTest_Subject,'getSubject');
    ok($manager->getSubject('unkonwn') === null,'getSubject');
    Doggy_Event_Manager::reset();
}

{
    $manager = Doggy_Event_Manager::getInstance();
    $manager->sendMessage('t1','ok1');
    is(Doggy_Event_ManagerTest_Observer::$message,"ok1",'sendMessage');
    is(Doggy_Event_ManagerTest_Observer_MQ::$message,NULL,'sendMessage');
    $manager->sendMessage('t2','ok2');

    is(Doggy_Event_ManagerTest_Observer::$message,"ok2",'sendMessage');
    is(Doggy_Event_ManagerTest_Observer_MQ::$message,NULL,'sendMessage');
}
?>