<?php
/**
 * Hash loader
 *
 * Load template from builtin scope array.
 */
class Doggy_Dt_Loader_HashLoader {
    
    /**
     * Constuct
     * 
     * $scope is a hash,the key is template's path,value is template content.
     * 
     * like:
     * 
     * $loader = new Doggy_Dt_Loader_HashLoader(
     *  array(
     *      'header.html'=>'{{title}}',
     *      'body.html'=>'this is body template',
     *      )
     * );
     *
     * @param array $scope template scope array
     * @param string $options 
     */
    function __construct($scope, $options = array()) {
        $this->scope = $scope;
    }
    
    public function setOptions() {}

    public function read($file,$parsed=true) {
        if (!isset($this->scope[$file])) {
            throw new Doggy_Dt_Exception_TemplateNotFound($file);
        }
        if (!$parsed) {
            return $this->scope[$file];
        }
        return $this->runtime->parse($this->scope[$file], $file);
    }
    
    public function read_cache($file) {
        return $this->read($file);
    }
}

?>