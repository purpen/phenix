<?php
abstract class Doggy_Sequence_Generator {
    
    protected static $instance;

    public abstract function _next($seq_name);
    public abstract function _drop($seq_name);
    
    public static function generator() {
        if (is_null(self::$instance)) {
            $class = isset(Doggy_Config::$vars['app.seq_generator']['class'])?
                Doggy_Config::$vars['app.seq_generator']['class']:'Doggy_Sequence_DbGenerator';
            $options = isset(Doggy_Config::$vars['app.seq_generator']['options']) ? 
                Doggy_Config::$vars['app.seq_generator']['options']:array();
            
            
            if (!is_subclass_of($class,'Doggy_Sequence_Generator')) {
                throw new Doggy_Exception("Sequence generator:< $class > is invalid.");
            }
            $generator = new $class($options);
            self::$instance = $generator;
            return $generator;
        }
        return self::$instance;
    }
    
    public static function reset() {
        self::$instance = null;
    }
    
    public static function next_id($seq_name) {
        return self::generator()->_next($seq_name);
    }
    
    public static function drop($seq_name) {
        return self::generator()->_drop($seq_name);
    }
    
}
?>