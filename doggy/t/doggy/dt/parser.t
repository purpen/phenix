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
{
    $tpl ="{* this is comment *}\n{{ title }} age is {{ age }}\n";

    $dt = new Doggy_Dt();
    $dt->set('title','Title');
    $dt->set('age',10);

    $parser = new Doggy_Dt_Parser($tpl,'',$dt,Doggy_Dt_Options::merge());
    $node_list = $parser->parse();
    $i=0;
    ok(is_string($node_list->list[$i++]));
    ok($node_list->list[$i++] instanceof Doggy_Dt_VariableNode);
    ok(is_string($node_list->list[$i++]));
    ok($node_list->list[$i++] instanceof Doggy_Dt_VariableNode);
    ok(is_string($node_list->list[$i++]));
    ok(empty($node_list->list[$i]));
    
}
{
    $result = Doggy_Dt_Parser::parse_args('page,0.5,30,v3|default 25');
    $i=0;
    is($result[$i++],':page','parse_args');
    is($result[$i++],0.5,'parse_args');
    is($result[$i++],30,'parse_args');
    is($result[$i++],':v3','parse_args');
    is($result[$i++],array(':default',25),'parse_args');
    
    $result = Doggy_Dt_Parser::parse_args('query page:1,size:25');
    $i=0;
    is($result[$i++],':query','parse_args/named args');
    is($result[$i++],array('page'=>1,'size'=>25),'parse_args/named args');
    
    $result = Doggy_Dt_Parser::parse_args('if a>10');
    $i=0;
    is($result[$i++],':if','parse_args/operator');
    is($result[$i++],':a','parse_args/operator');
    
    is($result[$i++],array('operator'=>'gt'),'parse_args/operator');
    is($result[$i++],10,'parse_args/operator');
    
}




?>