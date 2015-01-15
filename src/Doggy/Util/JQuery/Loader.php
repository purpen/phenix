<?php
/**
 * 自定义加载jQuery的smarty帮助标签
 *
 */
class Doggy_Util_JQuery_Loader extends Doggy_Object {
    /**
     * Load JQuery plugins
     */
    public static function load($smarty){
        $plugins = array('Doggy_Util_JQuery_Core');
        foreach($plugins as $class){
            $methods = get_class_methods($class);
            foreach($methods as $method){
                $prefix = substr($method,0,2);
                $tag = substr($method,2);
                switch ($prefix) {
                	case 'e_':
                	    $smarty->register_block($tag,array($class,$method));
                	    break;
                	
                	case 'n_':
                	    $smarty->register_function($tag,array($class,$method));
                	    break;
                	default:
                		;
                	break;
                }
                
            }
        }
    }
}
/**vim:sw=4 et ts=4 **/
?>