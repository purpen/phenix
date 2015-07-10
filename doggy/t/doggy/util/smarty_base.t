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

class Doggy_Util_Smarty_MockPlugin{
    public static function smarty_function_checkDate(){
        return 'checkDate';
    }
    
    public static function smarty_function_check_foo(){
        return 'check_foo';
    }
    public static function smarty_function_check_call(){
        return 'call';
    }
    public static function smarty_block_list($params, $content, &$smarty, &$repeat){
        static $c=0;
        if($c>3){
            $repeat=false;
        }else{
            $repeat=true;
        }
        $c++;
        return $content;
    }
    public static function smarty_compiler_timestamp($tag_arg, &$smarty){
        return "\n".'echo "timestamp"; ';
    }
    public static function smarty_modifier_url($url){
        return $url.'::smarty_modifier_url::';
    }
    public static function smarty_postfilter_trim($compiled,$smarty){
        $compiled.= "\n".'smarty_postfilter_trim';
        return $compiled;
    }
    public static function smarty_outputfilter_protectEmail($output,$smarty){
        return $output."\n".'[smarty_outputfilter_protect_email]';
    }
}

{
    $compiler = new Doggy_Util_Smarty_Base();
    try{
        $compiler->_syntax_error('error test',__FILE__,__LINE__);
        self::fail('smarty:_syntax_error');
    }catch(Doggy_Util_Smarty_Exception $e){}    
}


{
    $smarty = new Doggy_Util_Smarty_Base();
    $smarty->initRuntimeDirectory();
    $smarty->assign('footer.ok','smarty_include_ok');
    
    $content = $smarty->fetch('test.smarty.test');
    ok(!empty($content),'smarty:fetch');
    //block
    ok(substr_count($content,'List'),4,'smarty:block');
    //include
    ok(substr_count($content,'header'),1,'smarty:include');
    is(substr_count($content,'footer'),1,'smarty:include');
    //custom
    is(substr_count($content,'checkDate'),1,'smarty:custom');
    is(substr_count($content,'call'),1,'smarty:custom');
    is(substr_count($content,'check_foo'),1,'smarty:custom');
    //modifier
    is(substr_count($content,'chinavisual.com::smarty_modifier_url::'),1,'smarty:modifier');
    
    //compiler
    ok(1,substr_count($content,'timestamp'));
    //outputfilter
    ok(1,substr_count($content,'[smarty_outputfilter_protect_email]'));
}

{
    //测试递归使用smarty_include是否有效
    $smarty = new Doggy_Util_Smarty_Base();
    $smarty->initRuntimeDirectory();
    $content = $smarty->fetch('test.smarty.test_include');
    is(substr($content,0,6),"T1T2T3",'smarty:rescursive include');
    ok(strstr($content,'checkDate')>=0,'smarty:rescursive include');
    
    //test circular reference
    $smarty = new Doggy_Util_Smarty_Base();
    $smarty->initRuntimeDirectory();
    try{
        $smarty->fetch('test.smarty.test_include4');
        self::fail('smarty:circular reference not detected');
    }catch(Doggy_Util_Smarty_Exception $e){
        
    }
    
    $smarty = new Doggy_Util_Smarty_Base();
    $smarty->initRuntimeDirectory();
    try{
        $smarty->fetch('test.smarty.test_include7');
        self::fail('circular reference not detected');
    }catch(Doggy_Util_Smarty_Exception $e){
        
    }
}

?>