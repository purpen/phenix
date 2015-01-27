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

is(Doggy_Dt_Filters_Core::first(array(1,2,3)),1,'first');

is(Doggy_Dt_Filters_Core::last(array(1,2,3)),3,'last');

is(Doggy_Dt_Filters_Core::join(array(1,2,3)),'1, 2, 3','join');
is(Doggy_Dt_Filters_Core::join(array(1,2,3),'-'),'1-2-3','join');

is(Doggy_Dt_Filters_Core::urlencode(array('a'=>1,'b'=>3)),'a=1&amp;b=3','urlencode');
is(Doggy_Dt_Filters_Core::urlencode('/test?a=4'),'%2Ftest%3Fa%3D4','urlencode');

is(Doggy_Dt_Filters_Core::hyphenize('hello world'),'hello-world','hyphenize');
is(Doggy_Dt_Filters_Core::hyphenize('hello_world'),'hello_world','hyphenize');
is(Doggy_Dt_Filters_Core::hyphenize('hello--man  world'),'hello-man-world','hyphenize');

is(Doggy_Dt_Filters_Core::set_default(null,'1'),'1','set_default');
is(Doggy_Dt_Filters_Core::set_default('2','1'),'2','set_default');


?>