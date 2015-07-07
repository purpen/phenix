<?php
/**
 * Example:
 *  $dt = new Doggy_Dt('./template.html', array("loader"=>'file'));
 *  
 *  
 *  $dt = new Doggy_Dt('template.html', array("loader"=>'hash'));
 */
class Doggy_Dt {
    public $searchpath;
    public $context;
    public $loader = false;
    public $options = array();
    
    public static $tags = array (
      'if' => 'Doggy_Dt_Tag_If',
      'block' => 'Doggy_Dt_Tag_Block',
      'cycle' => 'Doggy_Dt_Tag_Cycle',
      'extends' => 'Doggy_Dt_Tag_Extends',
      'for' => 'Doggy_Dt_Tag_For',
      'debug' => 'Doggy_Dt_Tag_Debug',
      'autoescape' => 'Doggy_Dt_Tag_Autoescape',
      'include' => 'Doggy_Dt_Tag_Include',
      'load' => 'Doggy_Dt_Tag_Load',
      'now' => 'Doggy_Dt_Tag_Now',
      'with' => 'Doggy_Dt_Tag_With',
      'read_app_url' => 'Doggy_Dt_Tag_ReadAppUrl',
      'format' => 'Doggy_Dt_Tag_Format',
      'cache' => 'Doggy_Dt_Tag_Cache',
      'cache_loader' => 'Doggy_Dt_Tag_CacheLoader',
      'comment' => 'Doggy_Dt_Tag_Comment',
      'mustache' => 'Doggy_Dt_Tag_MustacheTemplate',
    );
    
    public static $filters = array(
      'md5' => 'md5',
      'sha1' => 'sha1',
      'numberformat' => 'number_format',
      'wordwrap' => 'wordwrap',
      'trim' => 'trim',
      'upper' => 'strtoupper',
      'lower' => 'strtolower',
      'first' => 
      array (
        0 => 'Doggy_Dt_Filters_Core',
        1 => 'first',
      ),
      'last' => 
      array (
        0 => 'Doggy_Dt_Filters_Core',
        1 => 'last',
      ),
      'join' => 
      array (
        0 => 'Doggy_Dt_Filters_Core',
        1 => 'join',
      ),
      'urlencode' => 
      array (
        0 => 'Doggy_Dt_Filters_Core',
        1 => 'urlencode',
      ),
      'hyphenize' => 
      array (
        0 => 'Doggy_Dt_Filters_Core',
        1 => 'hyphenize',
      ),
      'urlize' => 
      array (
        0 => 'Doggy_Dt_Filters_Core',
        1 => 'urlize',
      ),
      'set_default' => 
      array (
        0 => 'Doggy_Dt_Filters_Core',
        1 => 'set_default',
      ),
      'humanize' => 
      array (
        0 => 'Doggy_Dt_Filters_String',
        1 => 'humanize',
      ),
      'capitalize' => 
      array (
        0 => 'Doggy_Dt_Filters_String',
        1 => 'capitalize',
      ),
      'titlize' => 
      array (
        0 => 'Doggy_Dt_Filters_String',
        1 => 'titlize',
      ),
      'capfirst' => 
      array (
        0 => 'Doggy_Dt_Filters_String',
        1 => 'capfirst',
      ),
      'tighten_space' => 
      array (
        0 => 'Doggy_Dt_Filters_String',
        1 => 'tighten_space',
      ),
      'escape' => 
      array (
        0 => 'Doggy_Dt_Filters_String',
        1 => 'escape',
      ),
      'force_escape' => 
      array (
        0 => 'Doggy_Dt_Filters_String',
        1 => 'force_escape',
      ),
      'e' => 
      array (
        0 => 'Doggy_Dt_Filters_String',
        1 => 'e',
      ),
      'safe' => 
      array (
        0 => 'Doggy_Dt_Filters_String',
        1 => 'safe',
      ),
      'truncate' => 
      array (
        0 => 'Doggy_Dt_Filters_String',
        1 => 'truncate',
      ),
      'filesize' => 
      array (
        0 => 'Doggy_Dt_Filters_Number',
        1 => 'filesize',
      ),
      'currency' => 
      array (
        0 => 'Doggy_Dt_Filters_Number',
        1 => 'currency',
      ),
      'base_url' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'base_url',
      ),
      'asset_url' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'asset_url',
      ),
      'image_tag' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'image_tag',
      ),
      'css_tag' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'css_tag',
      ),
      'script_tag' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'script_tag',
      ),
      'links_to' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'links_to',
      ),
      'links_with' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'links_with',
      ),
      'strip_tags' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'strip_tags',
      ),
      'linebreaks' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'linebreaks',
      ),
      'nl2br' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'nl2br',
      ),
      'nl2pbr' => 
      array (
        0 => 'Doggy_Dt_Filters_Html',
        1 => 'nl2pbr',
      ),
      'default' => 
      array (
        0 => 'Doggy_Dt_Filters_Core',
        1 => 'set_default',
      ),
      
      'date' => 
      array (
        0 => 'Doggy_Dt_Filters_DateTime',
        1 => 'date',
      ),
      
      'relative_time' => 
      array (
        0 => 'Doggy_Dt_Filters_DateTime',
        1 => 'relative_time',
      ),
      'relative_date' => 
      array (
        0 => 'Doggy_Dt_Filters_DateTime',
        1 => 'relative_date',
      ),
      'relative_time' => 
      array (
        0 => 'Doggy_Dt_Filters_DateTime',
        1 => 'relative_time',
      ),
      'relative_datetime' => 
      array (
        0 => 'Doggy_Dt_Filters_DateTime',
        1 => 'relative_datetime',
      ),
      
    );
    
    public static $extensions = array();
    public static $extension_libs = array();
    
    public function __construct($file = null, $options = array()) {
        # Init a environment
        $this->options = Doggy_Dt_Options::merge($options); 
        $loader = $this->options['loader'];

        if (!$loader)
            return true;

        if (is_object($loader)) {
            $this->loader = $loader;
            $this->loader->setOptions($this->options);
        } else {
            $loader = "Doggy_Dt_Loader_{$loader}";
            if (!class_exists($loader))
                throw new Doggy_Dt_Exception("Invalid template loader:$loader");
                
            if (isset($options['searchpath']))
                $this->searchpath = realpath($options['searchpath']).DIRECTORY_SEPARATOR;
            elseif ($file)
                $this->searchpath = dirname(realpath($file)).DIRECTORY_SEPARATOR;
            else
                $this->searchpath = getcwd().DIRECTORY_SEPARATOR;

            $this->loader = new $loader($this->searchpath, $this->options);        
        }
        $this->loader->runtime = $this;
        
        /*
        if (isset($options['i18n'])) {
            self::load('i18n');
            $this->i18n = new Doggy_Dt_I18n($this->searchpath, $options['i18n']);
        }
        */
        if ($file) {
            $this->nodelist = $this->load_template($file);
        }
    }
    
    /**
     * Load cached template
     *
     * @param string $file 
     * @return array
     */
    function load_template($file) {
        return $this->nodelist = $this->loader->read_cache($file);
    }
    
    /**
     * Load a sub template
     *
     * @param string $file 
     * @return string
     */
    public function load_sub_template($file) {
        return $this->loader->read($file);
    }
    
    /**
     * Build a finalized nodelist from template ready to be cached
     *
     * @param string $source 
     * @param string $filename 
     * @param string $env 
     * @return Doggy_Dt_NodeList
     */
    public function parse($source, $filename = '', $env = null) {
        if (!$env)
            $env = $this->options;


        $parser = new Doggy_Dt_Parser($source, $filename, $this, $env);
        $nodelist = $parser->parse();
        return $nodelist;
    }

    /**
     * set context value
     *
     * @param mixed $context 
     * @param string $value 
     * @return bool
     */
    public function set($context, $value = null) {
        # replace with new context object
        if (is_object($context) && $context instanceof Doggy_Dt_Context) {
            return $this->context = $context;
        }

        # Init context
        if (!$this->context) {
            $this->context = new Doggy_Dt_Context($this->defaultContext(), $this->options);
        }
        
        # Extend or set value
        if (is_array($context)) {
            return $this->context->extend($context);
        } 
        elseif (is_string($context)) {
            return $this->context[$context] = $value;
        }
        return false;
    }
    
    public function autoescape($on) {
        # Init context
        if (!$this->context) {
            $this->context = new Doggy_Dt_Context($this->defaultContext(), $this->options);
        }
        $this->context->autoescape=$on;
    }
    
    /**
     * Render the internal nodelist and output result.
     *
     * @param array $context 
     * @return string
     */
    public function render($context = array()) {
        $this->set($context);

        $this->stream = new Doggy_Dt_StreamWriter;
        $this->nodelist->render($this->context, $this->stream);
        return $this->stream->close();
    }
    
    /**
     * Pase string and return Doggy_Dt instance
     *
     * @param string $source 
     * @param array $options 
     * @return Doggy_Dt
     */
    public static function parse_string($source, $options = array()) {
        // $instance = new Doggy_Dt(null, array_merge($options, array('loader' => false)));
        $instance = new Doggy_Dt(null, $options);
        $instance->nodelist = $instance->parse($source);
        return $instance;
    }

    /**
     * Create a tag instance
     *
     * @param string $tag tag's name
     * @param array $args tag's arguments
     * @param Doggy_Dt_Parser $parser 
     * @param int $position 
     * @return Doggy_Dt_Node
     */
    public static function create_tag($tag, $args = null, $parser, $position = 0) {
        if (!isset(self::$tags[$tag])) {
            throw new Doggy_Dt_Exception($tag . " tag doesn't exist");
        }
        $tagClass = self::$tags[$tag];
        $tag = new $tagClass($args, $parser, $position);
        return $tag;
    }

    /**
     * Register a new tag
     *
     * 
     * Doggy_Dt::add_tag('tag_name', 'ClassName');
     * 
     * Doggy_Dt::add_tag(array(
     *      'tag_name' => 'MagClass',
     *      'tag_name2' => 'TagClass2'
     * ));
     *
     * Doggy_Dt::add_tag('tag_name');      // Doggy_Dt_Tag_{Tag_name}
     * 
     * @param mixed $tag
     * @param string $class
     */
    public static function add_tag($tag, $class = null) {
        $tags = array();
        if (is_string($tag)) {
            if (is_null($class)) {
                $class = 'Doggy_Dt_Tag_'.ucwords($tag);
            }
            $tags[$tag] = $class;
        } elseif (is_array($tag)) {
            $tags = $tag;
        }
        
        foreach ($tags as $tag => $tagClass) {
            if (is_integer($tag)) {        
                unset($tags[$tag]);
                $tag = $tagClass;
                $tagClass = 'Doggy_Dt_Tag_'.ucwords($tagClass);
            }
            if (!class_exists($tagClass)) {
                throw new Doggy_Dt_Exception("{$tagClass} tag is not found");
            }
            $tags[$tag] = $tagClass;
        }
        self::$tags = array_merge(self::$tags, $tags);
    }

    /**
     * Register a new filter to Doggy_Dt runtime instance
     *
     * @param mixed $filter
     * @param mixed $callback
     * @return bool
     */
    public static function add_filter($filter, $callback = null) {
        if (is_array($filter)) {
            $filters = $filter;
            foreach($filters as $key => $filter) {
                if (is_numeric($key)) {
                    $key = $filter;
                }
                self::add_filter($key, $filter);
            }
            return true;
        } elseif (is_string($filter) && class_exists($filter) && Doggy::is_implements($filter,'Doggy_Dt_FilterLib')) {
            foreach (get_class_methods($filter) as $f) {
                if (is_callable(array($filter, $f)))
                    self::$filters[$f] = array($filter, $f);
            }
            return true;
        }
        if (is_null($callback)) {
            $callback = $filter;
        }
            
        if (!is_callable($callback)) {
            return false;
        }
        self::$filters[$filter] = $callback;
        return true;
    }
    /**
     * Add lookup callback item
     *
     * @param mixed $callback 
     * @return void
     */
    public static function add_lookup($callback) {
        if (is_callable($callback)) {
            Doggy_Dt_Context::$lookupTable[] = $callback;
        }
        else {
            Doggy_Log_Helper::error("invalid lookup item:$callback");
            return false;
        }
    }
    
    /**
     * Load an extensions lib
     *
     * @param string $lib_id 
     * @return bool
     */
    public static function load($lib_id) {
        if (isset(self::$extensions[$lib_id])) {
            return true;
        }
        
        $lib_setting = Doggy_Config::get('app.dt.extension_lib.'.$lib_id);
        
        if (empty($lib_setting)) {
            Doggy_Log_Helper::warn("extension_lib < $lib_id > not defined!");
            return false;
        }
        
        isset($lib_setting['tags']) || $lib_setting['tags'] = array();
        isset($lib_setting['filters']) || $lib_setting['filters'] = array();
        
        foreach ($lib_setting['tags'] as $tag_id => $tag_class) {
            self::add_tag($tag_id,$tag_class);
        }
        foreach ($lib_setting['filters']  as $fitler_lib) {
            self::add_filter($fitler_lib);
        }
        self::$extensions[$lib_id] = $lib_setting;
        return true;
    }
    /**
     * Return default Doggy_Dt_Info
     *
     * @return array
     */
    public function defaultContext() {
        return array('dt' => new Doggy_Dt_Info);
    }
    
}

defined('DOGGY_DT_RUNTIME_LIB') or include_once 'Doggy/Dt/RuntimeLib.php';
?>