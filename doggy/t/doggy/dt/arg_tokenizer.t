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
defined('DOGGY_DT_RUNTIME_LIB') or include "Doggy/Dt/RuntimeLib.php";
{
    $lexer = new Doggy_Dt_ArgumentTokenizer('page,0.5,30,v3|default 25');
    $tokens = $lexer->parse();
    $i=0;
    is($tokens[$i++],array('name','page'));
    is($tokens[$i++],array('separator',null));
    is($tokens[$i++],array('number',0.5));
    is($tokens[$i++],array('separator',null));
    is($tokens[$i++],array('number',30));
    is($tokens[$i++],array('separator',null));
    is($tokens[$i++],array('name','v3'));
    is($tokens[$i++],array('filter_start','|'));
    is($tokens[$i++],array('name','default'));
    is($tokens[$i++],array('number',25));
    is($tokens[$i++],array('filter_end',null));
}
#operator
{
    $lexer = new Doggy_Dt_ArgumentTokenizer('if b>10');
    $tokens = $lexer->parse();
    $i=0;
    is($tokens[$i++],array('name','if'),'operator');
    is($tokens[$i++],array('name','b'),'operator');
    is($tokens[$i++],array('operator','gt'),'operator');
    is($tokens[$i++],array('number',10),'operator');    
}
#named argument
{
    $lexer = new Doggy_Dt_ArgumentTokenizer('test b:10,c:20');
    $tokens = $lexer->parse();
    $i=0;
    is($tokens[$i++],array('name','test'),'named_argument');
    is($tokens[$i++],array('named_argument','b:10'),'named_argument');
    is($tokens[$i++],array('separator',null),'named_argument');
    is($tokens[$i++],array('named_argument','c:20'),'named_argument');
}
?>