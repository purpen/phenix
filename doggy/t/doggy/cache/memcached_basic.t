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
$options = array(
    'servers' => array(
        array(
            'host'=>'127.0.0.1',
            'port' => 11211
        )
        ),
    'ttl'=>0,
    'namespace'=>'doggy_test',
    );

Doggy_Config::set('app.cache.memcached.default',$options);

$memcached = Doggy_Cache_Memcached::get_cluster();

ok($memcached instanceof Doggy_Cache_Memcached,'get_cluster');

#basics
{
    $memcached->add('t1','t1');
    is($memcached->get('t1'),'t1','basics,add/get');
    
    $memcached->set('t2_list','a');
    $memcached->append('t2_list','b');
    
    is($memcached->get('t2_list'),'ab','basics,append');
    
    
    $memcached->prepend('t2_list','0');
    is($memcached->get('t2_list'),'0ab','basics,prepend');
    
    $memcached->replace('t2_list','ok');
    is($memcached->get('t2_list'),'ok','basics,replace');
    
    $memcached->delete('t2_list');
    ok($memcached->get('t2_list') === false && $memcached->get_result_code()==Memcached::RES_NOTFOUND,'basics,delete');
    
    $memcached->set('counter',0);
    is($memcached->inc('counter'),1,'basics,inc');
    is($memcached->inc('counter'),2,'basics,inc');
    is($memcached->dec('counter'),1,'basics,dec');
    

}

#batch
{
    $data= array(
        'b1'=>1,
        'b2'=>2,
        'b3'=>3
        );
    $memcached->m_set($data);
    $result = $memcached->m_get(array('b1','b2','b3'));
    is($result,$data,'batch set/get');
    
}
#delay fetch
{
    $memcached->set('d1','1');
    $memcached->set('d2','2');
    $memcached->set('d3','3');
    $memcached->get_delayed(array('d1','d2','d3'));
    $result = $memcached->fetch();
    ok($result['key']=='d1' && $result['value'] == '1','get_delayed,fetch');
    
    $result = $memcached->fetch_all();
    ok($result[0]['key']=='d2' && $result[0]['value'] == '2','get_delayed,fetch_all');
    ok($result[1]['key']=='d3' && $result[1]['value'] == '3','get_delayed,fetch_all');
}

# cas 
{
     $memcached->set('cas1',1);
     $memcached->get('cas1',null,null,$cas);
     ok($memcached->cas($cas,'cas1',2),'cas');
     
     $memcached->get('cas1',null,null,$cas);
     $memcached->set('cas1',100);
     ok(!$memcached->cas($cas,'cas1',3),'cas');
     
}

#misc
{
    $stats = $memcached->get_stats();
    ok(!empty($stats),'get_stats');
    $server_list = $memcached->get_server_list();
    is($server_list[0]['host'],'127.0.0.1','get_server_list');
    
    $memcached->set_option(Memcached::OPT_HASH, Memcached::HASH_MURMUR);
    is($memcached->get_option(Memcached::OPT_HASH),Memcached::HASH_MURMUR,'set/get option');
    
    is($memcached->get_option(Memcached::OPT_PREFIX_KEY),'doggy_test','namespace');
    $memcached->set_option(Memcached::OPT_HASH, Memcached::HASH_DEFAULT);
    
    
}
$memcached->flush();
?>