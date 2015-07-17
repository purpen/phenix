<?php
class Doggy_Dt_Info {
    public static $dt_safe= array('filters', 'extensions', 'tags');
    public static $name = 'Doggy DTemplate engine';
    public static $description = "A django style template system";
    public static $version = DOGGY_VERSION;

    function filters() {
        return array_keys(Doggy_Dt::$filters);
    }
    
    function tags() {
        return array_keys(Doggy_Dt::$tags);
    }
    
    function extensions() {
        return array_keys(Doggy_Dt::$extensions);
    }    
}
?>