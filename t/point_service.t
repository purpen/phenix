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

$service = Sher_Core_Service_Point::instance();

$user_id = 10;

$ok = $service->make_exp_in($user_id, 100, 'test');
ok($ok, 'make_exp_in');
$ok = $service->make_exp_out($user_id, 100, 'test');
ok($ok, 'make_exp_out');

$ok = $service->make_money_in($user_id, 100, 'test money in');
ok($ok, 'make_money_in');
$ok = $service->make_money_out($user_id, 100, 'test money out');
ok($ok, 'make_money_out');

$model = new Sher_Core_Model_UserPointBalance();
$model->load($user_id);

$now_val = $model->balance['exp'];
$ok = $service->make_exp_in($user_id, 100, 'test');

$after_row = $model->load($user_id);
var_dump($after_row);

is($model->balance['exp'], $now_val + 100, 'make_exp_in verify');

$service->make_transaction($user_id, 100, 'test', Sher_Core_Util_Constant::TRANS_TYPE_IN, 'exp');
$model->load($user_id);
is($model->balance['exp'], $now_val + 200, 'make_transaction_in verify');


$ok = $service->make_transaction(10, 5, '奖励【关注他人】', 1, 'exp', '552744eb206d45fa7c0041aa', 1428636907);

ok($ok, 'test');


