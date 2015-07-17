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
Doggy_Dt::add_tag('if');

{
    is(doggy_dt('{%if 4>3 %}is_ok{%else%}is_failed{%endif%}')->render(),'is_ok','basics');
    is(doggy_dt('{%if 4==3 %}is_ok{%else%}is_failed{%endif%}')->render(),'is_failed','basics');
    is(doggy_dt('{%if true %}is_ok{%else%}is_failed{%endif%}')->render(),'is_ok','basics');
    is(doggy_dt('{%if false %}is_ok{%else%}is_failed{%endif%}')->render(),'is_failed','basics');
}
{
    is(doggy_dt('{%if a>3 %}is_ok{%else%}is_failed{%endif%}')->render(array('a'=>4)),'is_ok','variable');
    is(doggy_dt('{%if a>3 %}is_ok{%else%}is_failed{%endif%}')->render(array('a'=>3)),'is_failed','variable');
}

{
    is(doggy_dt('{%if a>3 %}{%if b<8 %}a1{%else%}a2{%endif%}{%else%}a3{%endif%}')->render(array('a'=>4,'b'=>7)),'a1','nested if else');
    is(doggy_dt('{%if a>3 %}{%if b<8 %}a1{%else%}a2{%endif%}{%else%}a3{%endif%}')->render(array('a'=>4,'b'=>8)),'a2','nested if else');
    is(doggy_dt('{%if a>3 %}{%if b<8 %}a1{%else%}a2{%endif%}{%else%}a3{%endif%}')->render(array('a'=>1,'b'=>8)),'a3','nested if else');
}
#todo ,unsupport features
todo_start('multi-expression condition');
{
    is(doggy_dt('{%if 4 >= 3 %}is_ok{%else%}is_failed{%endif%}')->render(),'is_ok','basics(>=)');
    is(doggy_dt('{%if 3 <= 4 %}is_ok{%else%}is_failed{%endif%}')->render(),'is_ok','basics(<=)');
    is(doggy_dt('{%if a >= 3 %}is_ok{%else%}is_failed{%endif%}')->render(array('a'=>10)),'is_ok','basics(>=)');
    is(doggy_dt('{%if a <= 4 %}is_ok{%else%}is_failed{%endif%}')->render(array('a'=>1)),'is_ok','basics(<=)');
    is(doggy_dt('{%if 5>4 and 10<3 %}is_ok{%else%}is_failed{%endif%}')->render(),'is_failed','a && b');
    is(doggy_dt('{%if a>4 and b<3 %}is_ok{%else%}is_failed{%endif%}')->render(array('a'=>10,'b'=>1)),'is_ok','a && b');
}   
todo_end();
?>