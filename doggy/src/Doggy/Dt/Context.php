<?php
/**
 * Context object
 *  encapsulate context, resolve name
 */
class Doggy_Dt_Context implements ArrayAccess {
    /**
     * Safe class list
     *
     * @var array
     */
    public $safeClass = array('stdClass', 'Doggy_Dt_BlockContext');
    public $scopes;
    /**
     * options
     *
     * @var array
     */
    public $options;
    public $autoescape = true;
    
    private $arrayMethods = array('first'=> 0, 'last'=> 1, 'length'=> 2, 'size'=> 3);
    
    /**
     * External lookup table. 
     * 
     * Any item must be a valid callback.
     * That's meant,shoube a callable function'name, or 
     * an array with (class,method) or (object,class)
     * 
     * @var array
     * @static
     */
    static $lookupTable = array();
    
    public function __construct($context = array(), $options = array()){
        if (is_object($context))
           $context = get_object_vars($context);
        $this->scopes = array($context);
        
        if (isset($options['safeClass'])) 
            $this->safeClass = array_merge($this->safeClass, $options['safeClass']);
            
        if (isset($options['autoescape'])) 
            $this->autoescape = $options['autoescape'];
            
        $this->options = $options;
    }
    /**
     * push a layer
     *
     * @param array $layer 
     * @return void
     */
    public function push($layer = array()){
        return array_unshift($this->scopes, $layer);
    }

    /**
     * pop the most recent layer
     */
    public function pop() {
        if (!isset($this->scopes[1]))
            throw new Exception('cannnot pop from empty stack');
        return array_shift($this->scopes);
    }

    public function offsetExists($offset) {
        foreach ($this->scopes as $layer) {
            if (isset($layer[$offset])) return true;
        }
        return false;
    }

    public function offsetGet($key) {
        foreach ($this->scopes as $layer) {
            if (isset($layer[$key]))
                return $layer[$key];
        }
        return;
    }
    
    public function offsetSet($key, $value) {
        if (strpos($key, '.') > -1)
            throw new Exception('cannot set non local variable');
        return $this->scopes[0][$key] = $value;
    }
    
    public function offsetUnset($key) {
        foreach ($this->scopes as $layer) {
            if (isset($layer[$key])) unset($layer[$key]);
        }
    }

    /**
     * Extends/merge given context data
     *
     * @param array $context 
     * @return void
     */
    public function extend($context) {
        $this->scopes[0] = array_merge($this->scopes[0], $context);
    }

    /**
     * set a value
     *
     * @param string $key 
     * @param string $value 
     * @return void
     */
    public function set($key, $value) {
        return $this->offsetSet($key, $value);
    }
    /**
     * Returns given value
     *
     * @param string $key 
     * @return void
     */
    public function get($key) {
        return $this->offsetGet($key);
    }

    /**
     * check value is defined
     *
     * @param string $key 
     * @return boolean
     */
    public function is_defined($key) {
        return $this->offsetExists($key);
    }
    
    /**
     *  Resolve a name to its value
     * 
     * @param $name
     * @return mixed NUll if the name not resolved.
     */
    public function resolve($name) {
        # Lookup basic types, null, boolean, numeric and string
        # Variable starts with : (:users.name) to short-circuit lookup
        if ($name[0] === ':') {
            $object =  $this->get_var(substr($name, 1));
            if (!is_null($object)) return $object;
        } else {
            if ($name === 'true') {
                return true;
            }
            elseif ($name === 'false') {
                return false;
            } 
            elseif (preg_match('/^-?\d+(\.\d+)?$/', $name, $matches)) {
                return isset($matches[1])? floatval($name) : intval($name);
            }
            elseif (preg_match('/^"([^"\\\\]*(?:\\.[^"\\\\]*)*)"|' .
                           '\'([^\'\\\\]*(?:\\.[^\'\\\\]*)*)\'$/', $name)) {            
                return stripcslashes(substr($name, 1, -1));
            }
        }
        if (!empty(self::$lookupTable)) {
            return $this->external_lookup($name);
        }
        return null;
    }
    
    /**
     * find/fetch a var-expression to its value
     * 
     * a var expression is
     * 
     * scalar variable'name
     * array/object with . operator:
     *  like:
     * a.b
     * a.0
     * 
     * ., dot operator,can be follow:
     *  array's key
     *  object safe method
     *  virtual method,(like first,last,size)
     *
     * @param string $name var expression
     * @return void
     */    
    public function get_var($name) {
        # Local variables. this gives as a bit of performance improvement
        if (!strpos($name, '.'))
            return $this->offsetGet($name);

        # Prepare for Big lookup
        $parts = explode('.', $name);
        $object = $this[array_shift($parts)];

        # Lookup context
        foreach ($parts as $part) {
            if (is_array($object) or $object instanceof ArrayAccess) {
                if (isset($object[$part]))
                    $object = $object[$part];
                elseif ($part === 'first')
                    $object = $object[0];
                elseif ($part === 'last')
                    $object = $object[count($object) -1];
                elseif ($part === 'size' or $part === 'length')
                    return count($object);
                else return null;
            }
            elseif (is_object($object)) {
                if (isset($object->$part))
                    $object = $object->$part;
                elseif (is_callable(array($object, $part))) {
                    $methodAllowed = in_array(get_class($object), $this->safeClass) || 
                        (isset($object->dt_safe) && (
                            $object->dt_safe === true || in_array($part, $object->dt_safe)
                        )
                    );
                    $object = $methodAllowed ? $object->$part() : null;
                }
                else return null;
            }
            else return null;
        }
        return $object;
    }

    public function apply_filters($object, $filters) {
        $safe = false;
       
        foreach ($filters as $filter) {
            $name = substr(array_shift($filter), 1);
            $args = $filter;
            $safe = !$safe && $name === 'safe';
            
            if ($this->autoescape && $escaped = $name === 'escape')
                continue;
            // Doggy_Log_Helper::debug("apply filter:$name");
            if (isset(Doggy_Dt::$filters[$name]) && is_callable(Doggy_Dt::$filters[$name])) {
                 
                foreach ($args as $i => $argument) {
                    # name args
                    if (is_array($argument)) {
                        foreach ($argument as $n => $arg) {
                            $args[$i][$n] = $this->resolve($arg);
                        }
                    } 
                    else {
                    # resolve argument values
                       $args[$i] = $this->resolve($argument);
                    }
                }
                array_unshift($args, $object);
                // Doggy_Log_Helper::debug("call filter".print_r(Doggy_Dt::$filters[$name],true)." args:".print_r($args,true));
                $object = call_user_func_array(Doggy_Dt::$filters[$name], $args);
            }
            else {
                Doggy_Log_Helper::warn('unkown filter:'.$name);
            }
        }
        $should_escape = $this->autoescape || isset($escaped) && $escaped;
        
        if ($should_escape && !$safe) {
            // Doggy_Log_Helper::debug('<<<<<<<<escape output ...');
            $object = htmlspecialchars($object,ENT_QUOTES,'UTF-8',false);
            // Doggy_Log_Helper::debug('do htmlentities end');
        }
        return $object;
    }
    
    /**
     * Call external table's functions.
     *
     * External call should defined as valid callback.
     * 
     * @param string $name 
     * @return mixed NULL,if no calling success
     */
    public function external_lookup($name) {
        if (!empty(self::$lookupTable)) {
            foreach (self::$lookupTable as $lookup) {
                $tmp = call_user_func_array($lookup, array($name, $this));
                if ($tmp !== null) {
                    return $tmp;
                }
            }
        }
        return null;
    }
}
?>