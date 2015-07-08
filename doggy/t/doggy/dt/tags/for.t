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
Doggy_Dt::add_tag(array('if','for'));
{
    is(doggy_dt('{% for e in items %}{{ e }}{% endfor %}')->render(
        array('items'=> array(1,2,3,4,5))
        ),'12345','basic loop');
    is(doggy_dt('{% for e in items reversed %}{{ e }}{% endfor %}')->render(
        array('items'=> array(1,2,3,4,5))
        ),'54321','basic loop,reversed');
}

{
    is(doggy_dt('{% for e in items limit:3 %}{{ e }}{%endfor%}')->render(
        array('items'=> array('a','b','c','d','e'))
        ),'abc','limited loop');
        
    is(doggy_dt('{% for e in items limit:3 reversed %}{{ e }}{%endfor%}')->render(
        array('items'=> array('a','b','c','d','e'))
        ),'edc','limited loop,reversed');
        
}

{
    
    is(doggy_dt('{% for e in items %}{{ loop.counter }}{%endfor%}')->render(
        array('items'=> array('a','b','c','d','e'))
        ),'12345','counter');
        
    is(doggy_dt('{% for e in items %}{{ loop.counter0 }}{%endfor%}')->render(
        array('items'=> array('a','b','c','d','e'))
        ),'01234','counter,0 based');
    is(doggy_dt('{% for e in items %}{{ loop.revcounter }}{%endfor%}')->render(
        array('items'=> array('a','b','c','d','e'))
        ),'54321','revcounter');
    is(doggy_dt('{% for e in items %}{{ loop.revcounter0 }}{%endfor%}')->render(
        array('items'=> array('a','b','c','d','e'))
        ),'43210','revcounter,0 based');
}
{
    is(doggy_dt('{% for e in items %}{% if loop.first %}first:{% else %}{{ e }}{% endif %}{%endfor%}')->render(
        array('items'=> array('a','b','c','d','e'))
        ),'first:bcde','loop.first');
    
    is(doggy_dt('{% for e in items %}{% if loop.last %}-last:{% else %}{{ e }}{% endif %}{%endfor%}')->render(
        array('items'=> array('a','b','c','d','e'))
        ),'abcd-last:','loop.last');
    
    is(doggy_dt('{% for e in items %}{% if loop.even%}even{% else %}{{ e }}{% endif %}{%endfor%}')->render(
        array('items'=> array(1,2,3,4,5))
        ),'1even3even5','loop.even');
    
    is(doggy_dt('{% for e in items %}{% if loop.odd%}odd{% else %}{{ e }}{% endif %}{%endfor%}')->render(
        array('items'=> array(1,2,3,4,5))
        ),'odd2odd4odd','loop.odd');
}
?>