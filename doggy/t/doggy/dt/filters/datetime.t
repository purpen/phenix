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
is(Doggy_Dt_Filters_DateTime::date('2009-07-01','Y-m-d'),date('Y-m-d',strtotime('2009-07-01')),'date');


$time = time() - 60*60;
like(Doggy_Dt_Filters_DateTime::relative_time($time),'/hour/','relative_time');

$time = time() - 60*60*3;
like(Doggy_Dt_Filters_DateTime::relative_time($time),'/hours/','relative_time');


$time = time() - 10;
like(Doggy_Dt_Filters_DateTime::relative_time($time),'/seconds  ago/','relative_time');


like(Doggy_Dt_Filters_DateTime::relative_date(time()),'/today/','relative_date');

$time = time() - 86400;
like(Doggy_Dt_Filters_DateTime::relative_date($time),'/yesterday/','relative_date');

$time = time() + 86400;
like(Doggy_Dt_Filters_DateTime::relative_date($time),'/tomorrow/','relative_date');

$time = time() - 86400*3;
like(Doggy_Dt_Filters_DateTime::relative_date($time),'/days/','relative_date');

$time = time() -5;
like(Doggy_Dt_Filters_DateTime::relative_datetime($time),'/seconds  ago/','relative_datetime');
$time = time() - 86400*3;
like(Doggy_Dt_Filters_DateTime::relative_datetime($time),'/days ago/','relative_datetime');
?>