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
Doggy_Dt::add_tag('include');
Doggy_Dt::add_tag('block');

$option = array('loader' => doggy_dt_hash_loader(array(
    'page.html' => 
        "{% include '_header.html' %}{% block body %}layout text{% endblock %}{% include '_footer.html' %} ",
    '_menu.html' => 
        "<div id='menu'>page menu</div>",
    '_header.html' => 
        '<div id="header">{% include "_menu.html" %}</div>',
    '_footer.html' =>
        '<div id="footer">Page footer</div>',
    'page2.html' => "{%include page%}"
)));

{
    like(doggy_dt('_header.html',$option)->render(),'/page menu/','include sub template');
}
{
    $result = doggy_dt('page.html',$option)->render();
    like($result,'/page menu/','layout include');
    like($result,'/layout text/','layout include');
    like($result,'/Page footer/','layout include');
}

{
    $dt = new Doggy_Dt(null, $option);
    $dt->set('page','_header.html');
    $dt->load_template('page2.html');
    $result = $dt->render();
    like($result,'/page menu/','include by var');
}
?>