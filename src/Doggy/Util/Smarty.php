<?php
/**
 * Smarty extends for Doggy project
 *
 * + support namespace.
 *
 * @version $Id:Smarty.php 6369 2007-10-18 07:17:33Z night $
 * @author Night
 * @package Doggy
 * @subpackage Util
 */
abstract class Doggy_Util_Smarty {
    /**
     * Smarty singleton instance
     *
     * @var Doggy_Util_Smarty_Base
     */
    private static $smarty;
    
    /**
     * factory a smarty instance
     *
     * NOTE:
     * default will factory a singleton instance,
     * if $forceNew is true,will force create a new
     * instance.
     *
     *
     * @return Doggy_Util_Smarty_Base
     */
    public static function factory($forceNew=false){
        if($forceNew){
            return new Doggy_Util_Smarty_Base();
        }
        if(self::$smarty===null){
            self::$smarty = new Doggy_Util_Smarty_Base();
        }
        return self::$smarty;
    }
   
    /**
     * clone当前smarty对象实例
     */
    public static function factoryClone(){
        return clone self::factory();
    }
}
?>