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
            'port' => 11211,
            'weight'=>1
        ),
    ),
    'ttl'=>0,
    'namespace'=>'doggy_test',
    'tag'=>'global',
    );

Doggy_Config::set('app.memcached.default',$options);

$memcached = Doggy_Cache_Memcached::get_cluster();

ok($memcached instanceof Doggy_Cache_Memcached,'get_cluster');

# tag basics
{
    $memcached->flush();
    $memcached->set('c','c1','tag1');
    $memcached->set('c','c2','tag2');
    is($memcached->get('c','tag1'),'c1','basic tag');
    is($memcached->get('c','tag2'),'c2','basic tag');
    $memcached->delete('c','tag1');
    is($memcached->get('c','tag1'),null,'basic tag');
    is($memcached->get('c','tag2'),'c2','basic tag');
    
    $memcached->add('b1','b1','tag1');
    $memcached->add('b2','b2','tag1');
    $memcached->add('b3','b3','tag1');
    is($memcached->get('b1','tag1'),'b1','basic tag');
    
}
#basics
{
    $tag= 'g1';
    
    $memcached->add('t1','t1',$tag);
    is($memcached->get('t1',$tag),'t1','basics,add/get');
    
    $memcached->set('t2_list','a',$tag);
    $memcached->append('t2_list','b',$tag);
    is($memcached->get('t2_list',$tag),'ab','basics,append');
    
    $memcached->prepend('t2_list','0',$tag);
    is($memcached->get('t2_list',$tag),'0ab','basics,prepend');
    
    $memcached->replace('t2_list','ok',$tag);
    is($memcached->get('t2_list',$tag),'ok','basics,replace');
    
    $memcached->delete('t2_list',$tag);
    ok($memcached->get('t2_list',$tag) === false && $memcached->get_result_code()==Memcached::RES_NOTFOUND,'basics,delete');
    
    $memcached->set('counter',0,$tag);
    is($memcached->inc('counter',$tag),1,'basics,inc');
    is($memcached->inc('counter',$tag),2,'basics,inc');
    is($memcached->dec('counter',$tag),1,'basics,dec');    

}


#batch
{
    $tag = 'g3';
    
    $data= array(
        'b1'=>1,
        'b2'=>2,
        'b3'=>3
        );
    $memcached->m_set($data,$tag);
    $result = $memcached->m_get(array('b1','b2','b3'),$tag);
    is($result,$data,'batch set/get');
    
}
#delay fetch
{
    $tag = 'g4';
    $memcached->set('d1','1',$tag);
    $memcached->set('d2','2',$tag);
    $memcached->set('d3','3',$tag);
    $memcached->get_delayed(array('d1','d2','d3'),$tag);
    $result = $memcached->fetch();
    ok($result['key']=='d1' && $result['value'] == '1','get_delayed,fetch');
    $result = $memcached->fetch_all();
    ok($result[0]['key']=='d2' && $result[0]['value'] == '2','get_delayed,fetch_all');
    ok($result[1]['key']=='d3' && $result[1]['value'] == '3','get_delayed,fetch_all');
}

# cas 
{
    $tag = 'g5';
    $memcached->set('cas1',1,$tag);
    $memcached->get('cas1',$tag,null,$cas);
    ok($memcached->cas($cas,'cas1',2,$tag),'cas');

    $memcached->get('cas1',$tag,null,$cas);
    $memcached->set('cas1',100,$tag);
    ok(!$memcached->cas($cas,'cas1',3,$tag),'cas');
     
}

#flush_tag
{
    $tag = 'g6';
    $memcached->set('d1','1',$tag);
    $memcached->set('d2','2',$tag);
    $memcached->set('d3','3',$tag);
    $tag2 = 'g7';
    $memcached->set('d1','1',$tag2);
    $memcached->set('d2','2',$tag2);
    $memcached->set('d3','3',$tag2);
    
    $memcached->flush_tag($tag);
    is($memcached->get('d1',$tag),null,'flush tag');
    is($memcached->get('d1',$tag2),'1','flush tag');
    
}

$memcached->flush();
?>