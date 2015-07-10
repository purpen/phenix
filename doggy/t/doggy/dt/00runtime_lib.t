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
#short alias
include 'Doggy/Dt/RuntimeLib.php';
{
    is(doggy_dt_symbol('ok'),':ok','doggy_dt_symbol');
    ok(doggy_dt_is_sym(':t') && !doggy_dt_is_sym('t'),'doggy_dt_is_sym');

    is(doggy_dt_sym_to_str(':t'),'t','doggy_dt_sym_to_str');

    

    is(doggy_dt_strip_regex('/\w{5,1}/'),'\w{5,1}','doggy_dt_strip_regex');
    is(doggy_dt_strip_regex('|\w{5,1}.*+|'),'\w{5,1}.*+','doggy_dt_strip_regex');


    ok(doggy_dt_file_loader('/tmp') instanceof Doggy_Dt_Loader,'doggy_dt_file_loader');
    ok(doggy_dt_hash_loader() instanceof Doggy_Dt_Loader_HashLoader,'doggy_dt_hash_loader');
    
    ok(!doggy_dt_cache());
    ok(doggy_dt_cache(array('cache'=>'XCache')));
    ok(doggy_dt_cache(array('cache'=>'adapter')));
}

?>