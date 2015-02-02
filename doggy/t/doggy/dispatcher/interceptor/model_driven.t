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

require dirname(__FILE__).'/model_driven_test.php';

$dba = Doggy_Dba_Manager::get_model_dba();

$dba->execute('DROP TABLE IF EXISTS doggy');
$dba->execute('CREATE TABLE  `doggy` (
         `id` INT NOT NULL ,
         `name` VARCHAR( 40 ) NOT NULL ,
         `age` INT NOT NULL ,
         `updated_on` TIMESTAMP NOT NULL
        ) '
);

{
    $content = Doggy_Dispatcher_Context::getContext();
    $request = new Doggy_Dispatcher_Request_Http();
    $request->setParam('name','Pan');
    $request->setParam('age',30);
    $content->setRequest($request);
    
    $invocation = new Doggy_Dispatcher_ActionInvocation($content,'Doggy_Dispatcher_Interceptor_MockAction','execute');
    $interceptor = new Doggy_Dispatcher_Interceptor_ModelDriven();
    $interceptor->intercept($invocation);
    $action = $invocation->getAction();

    $model = $action->wiredModel();
    is($model->getName(),'Pan','model_driven interceptor');
    is($model->getAge(),30,'model_driven interceptor');
}

$dba->execute('DROP TABLE IF EXISTS doggy');
$dba->dropSeq('doggy');
?>