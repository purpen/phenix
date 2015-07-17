<?php
if (!extension_loaded('syck')) {
    require 'spyc.php';
}
else {
    define('SYCK_OK',1);
}

/**
 *  Doggy configuration
 *
 */
class Doggy_Config {
    /**
     * Current config variables stash
     *
     * @var array
     * @access public
     */
    public static $vars = array();

    public static function load_builtin_configs(){
        $builtin_config_file = dirname(__FILE__).'/Config.yml';
        self::load_file($builtin_config_file);
    }
    
    public static function load_file($config_file,$dont_merge=false,$force_expand_value=false){
        if(!file_exists($config_file)) return ;
        if (defined('SYCK_OK')) {
            $setting = syck_load(@file_get_contents($config_file));
        }
        else {
            // $yml = new Spyc();
            // $setting = $yml->load($config_file);
            $setting = spyc_load_file($config_file);
        }
        
        if(empty($setting)) {
            return ;
        }
        else {
            // expand values...
            // @since v1.3.7
            if ($force_expand_value) {
                foreach ($setting as $key => $value) {
                    $setting[$key] = self::expand_value($value,$setting);
                }
            }
            if(!$dont_merge){
                self::add($setting);
            }
        }
        return $setting;
    }
    
    public static function load($config_file,$dont_merge=false){
        return self::load_file($config_file,$dont_merge);
    }
    
    /**
     * Load all config files from the directory.
     *
     * @param string $dir
     * @return void
     **/
    public static function load_all_configs($dir){
        $dir = rtrim($dir, '/\\');
		if (file_exists($dir) && false !== ($dh = @opendir($dir))) {
			while (false !== ($file = readdir($dh))) {
				if ('.' == $file || '..' == $file)
					continue;
				self::load_file($dir.DIRECTORY_SEPARATOR.$file);
			}
		}
    }
    
    /**
     * 获取参数值
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function get($name, $default = null){
        return isset(self::$vars[$name])?self::$vars[$name]:$default;
    }
    /**
     * Set configuration paramter value
     *
     * @param string $name
     * @param mixed $value
     */
    public static function set($name,$value){
        self::$vars[$name]=$value;
    }
    /**
     * 检测是否有参数
     *
     * @param string $name
     * @return mixed
     */
    public static function has($name){
        return isset(self::$vars[$name]);
    }
    /**
     * Clear current configuration
     *
     */
    public static function clear(){
        self::$vars = array();
    }
    /**
     * Direct add config value array
     *
     * @param array $vars
     */
    public static function add(array $vars){
        self::$vars = array_merge(self::$vars,$vars);
    }
    /**
     * Dump config data into a PHP file, it can include later.
     *
     * @param string $file 
     * @return boolean
     */
    public static function dump_to($file) {
        self::expand_all();
        $data = "<?php\n//dump on ".date('Y-m-d H:i:s')."\nDoggy_Config::add( ".var_export(self::$vars,true).");?>";
        return file_put_contents($file,$data);
    }
    static public function all(){
        return self::$vars;
    }
    
    /**
     * Expand a string value(replace variable placeholder with its value)
     *
     * @param string $value 
     * @param array $value_array 
     * @return mixed
     * @since 1.3.7
     */
    public static function expand_value($value,$value_array) {
        static $var_stack = array();
        $result = $value;
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $result[$k] = self::expand_value($v,$value_array);
            }
        }
        elseif (is_string($value)) {
            if (preg_match_all('/\{?\s*\$([a-zA-Z0-9\._]+)\s*\}?/',$value,$matches,PREG_PATTERN_ORDER)) {
                // var_dump($matches);
                foreach ($matches[1] as $t) {
                    $reg_s = '/\{\s*\$'.$t.'\s*\}/';
                    // check circle reference error: 
                    // app.var1 = ${app.var1}
                    // or:
                    // app.var1=${app.var2}
                    // app.var2=${app.var1}
                    if (in_array($t,$var_stack)) {
                        throw new Doggy_Exception("Invalid config,cicle-reference var :{\$$t} found!");
                    }
                    // var defined in current to-expand array,so, expand it first
                    if (isset($value_array[$t])) {
                        array_push($var_stack,$t);
                        $replace_value = self::expand_value($value_array[$t],$value_array);
                        array_pop($var_stack);
                        $value = preg_replace($reg_s,$replace_value,$value);
                    }
                }
                $result = $value;
            }
        }
        return $result;
    }
    
    /**
     * Expand all value in config vars.
     *
     * @return void
     */
    public static function expand_all() {
        foreach (self::$vars as $key => $value) {
            self::$vars[$key] = self::expand_value($value,self::$vars);
        }
    }
    
}
?>