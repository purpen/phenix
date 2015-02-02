<?php
/**
 * MogileFS client
 *
 *
 * @see http://danga.com/mogilefs/
 */
class Doggy_Util_MogileFs_Client extends Doggy_Object{
    const DELETE       = 'DELETE';
    const GET_DOMAINS  = 'GET_DOMAINS';
    const GET_PATHS    = 'GET_PATHS';
    const RENAME       = 'RENAME';
    const LIST_KEYS    = 'LIST_KEYS';
    const CREATE_OPEN  = 'CREATE_OPEN';
    const CREATE_CLOSE = 'CREATE_CLOSE';

    const SUCCESS      = 'OK';    // Tracker success code
    const ERROR        = 'ERR';   // Tracker error code
    const DEFAULT_PORT = 7001;    // Tracker port

    protected $domain;
    protected $class;
    protected $trackers;
    protected $socket;
    protected $requestTimeout;
    protected $putTimeout;
    protected $getTimeout;
    protected $debug;

    /**
     *
     * @param string $domain
     * @param string $class
     * @param array $trackers
     */
    public function __construct($domain, $class, $trackers){
        $this->setDomain($domain);
        $this->setClass($class);
        $this->setHosts($trackers);
        $this->setRequestTimeout(10);
        $this->setPutTimeout(4);
        $this->setGetTimeout(10);
    }

    /**
     * 请求超时时间
     * @return int
     */
    public function getRequestTimeout(){
        return $this->requestTimeout;
    }

    /**
     * 设置请求超时时间
     *
     * @param int $timeout
     * @return Doggy_Util_MogileFs_Client
     */
    public function setRequestTimeout($timeout){
        if($timeout > 0)
            $this->requestTimeout = $timeout;
        else{
            Doggy_Log_Helper::error("setRequestTimeout expects a positive integer",__CLASS__);
            throw new Doggy_Util_MogileFs_Exception("setRequestTimeout expects a positive integer");
        }
        return $this;
    }

    /**
     * Returns put timeout
     */
    public function getPutTimeout(){
        return $this->putTimeout;
    }

    /**
     * Set put timeout
     * @param int $timeout
     * @return Doggy_Util_MogileFs_Client_Client
     */
    public function setPutTimeout($timeout){
        if($timeout > 0){
            $this->putTimeout = $timeout;
        }else{
            Doggy_Log_Helper::error("setPutTimeout expects a positive integer",__CLASS__);
            throw new Doggy_Util_MogileFs_Exception("setPutTimeout expects a positive integer");
        }
        return $this;
    }

    /**
     * returns get operation timeout
     * @return int
     */
    public function getGetTimeout(){
        return $this->getTimeout;
    }

    /**
     * @return Doggy_Util_MogileFs_Client_Client
     */
    public function setGetTimeout($timeout){
        if($timeout > 0){
            $this->getTimeout = $timeout;
        } else{
            Doggy_Log_Helper::error("setGetTimeout expects a positive integer",__CLASS__);
            throw new Doggy_Util_MogileFs_Exception("setGetTimeout expects a positive integer");
        }
        return $this;
    }

    /**
     * Returns tracker hosts array
     *
     * @return array
     */
    public function getHosts(){
        return $this->trackers;
    }

    /**
     * Set tracker hosts
     * @param mixed $trackers
     * @return Doggy_Util_MogileFs_Client_Client
     */
    public function setHosts($trackers){
        if(is_scalar($trackers))
        $this->trackers = Array($trackers);
        elseif(is_array($trackers))
        $this->trackers = $trackers;
        else
        throw new Doggy_Util_MogileFs_Exception("setHosts unrecognized host argument");
        return $this;
    }

    /**
     * @return string
     */
    public function getDomain(){
        return $this->domain;
    }

    /**
     * Set domain
     * @param string $domain
     * @return Doggy_Util_MogileFs_Client_Client
     */
    public function setDomain($domain){
        if(is_scalar($domain))
            $this->domain = $domain;
        else
            throw new Doggy_Util_MogileFs_Exception("setDomain unrecognized domain argument");
        return $this;
        
    }

    /**
     * @return string
     */
    public function getClass(){
        return $this->class;
    }

    /**
     * @param string $class
     * @return Doggy_Util_MogileFs_Client_Client
     */
    public function setClass($class){
        if(is_scalar($class))
            $this->class = $class;
        else
        throw new Doggy_Util_MogileFs_Exception("setClass unrecognized class argument");
        return $this;
    }

    /**
     * Connect to a mogilefsd
     *
     * scans through the list of daemons and tries to connect one.
     *
     */
    public function getConnection(){
        if($this->socket && is_resource($this->socket) && !feof($this->socket))
        return $this->socket;

        foreach($this->trackers as $host) {
            $parts = parse_url($host);
            if(!isset($parts['port']))
            $parts['port'] = self::DEFAULT_PORT;

            $errno = null;
            $errstr = null;
            $this->socket = fsockopen($parts['host'], $parts['port'], $errno, $errstr, $this->requestTimeout);
            if($this->socket)
            break;
        }

        if(!is_resource($this->socket) || feof($this->socket)){
            throw new Doggy_Util_MogileFs_Exception("doConnection failed to obtain connection");
        } else{
            return $this->socket;
        }
    }


    /**
     * Send a request to mogilefsd and parse the result.
     *
     * @param string $cmd
     * @param array $args
     */
    protected function doRequest($cmd, $args = Array()){
        try {
            $args['domain'] = $this->domain;
            $args['class'] = $this->class;
            $params = '';
            foreach ($args as $key => $value)
            $params .= '&'.urlencode($key).'='.urlencode($value);

            $socket = $this->getConnection();

            $result = fwrite($socket, $cmd . $params . "\n");
            if($result === false)
            throw new Doggy_Util_MogileFs_Exception("doRequest write failed");
            $line = fgets($socket);
            if($line === false)
            throw new Doggy_Util_MogileFs_Exception("doRequest read failed");

            //print "[$line]\n";
            $words = explode(' ', $line);
            if($words[0] == self::SUCCESS){
                parse_str(trim($words[1]), $result);
            } else {
                if(!isset($words[1]))
                $words[1] = null;
                switch($words[1]){
                    case 'unknown_key':
                        throw new Doggy_Util_MogileFs_UnknownKeyException("doRequest unknown_key {$args['key']}");

                    case 'empty_file':
                        throw new Doggy_Util_MogileFs_Exception("doRequest empty_file {$args['key']}");

                    default:
                        throw new Doggy_Util_MogileFs_Exception("doRequest " . trim(urldecode($line)));
                }
            }
            return $result;

        } catch(Exception $e){
            // Clean up
            if(isset($socket))
            fclose($socket);
            // Recast the exception
            throw $e;
        }
    }

    /**
     * Return a list of domains
     */
    public function getDomains(){
        $res = $this->doRequest(self::GET_DOMAINS);

        $domains = Array();
        for($i=1; $i <= $res['domains']; $i++)
        {
            $dom = 'domain'.$i;
            $classes = Array();
            for($j=1; $j <= $res[$dom.'classes']; $j++)
            $classes[$res[$dom.'class'.$j.'name']] = $res[$dom.'class'.$j.'mindevcount'];
            $domains[] = Array('name' => $res[$dom],'classes' => $classes);
        }
        return $domains;
    }

    /**
     * check if given key exists in mogilefs
     * @param string $key
     * @return boolean
     */
    public function exists($key){
        if($key === null)
        throw new Doggy_Util_MogileFs_Exception(get_class($this) . "::exists key cannot be null");

        try {
            $this->doRequest(self::GET_PATHS, Array('key' => $key));
            return true;
        } catch(Exception $e){
            return false;
        }
    }

    /**
     * Get an array of paths
     * 
     * @param string $key
     * @return array
     */
    public function getPaths($key){
        if($key === null){
            throw new Doggy_Util_MogileFs_Exception("getPaths key cannot be null");
        }

        try{
            $result = $this->doRequest(self::GET_PATHS, Array('key' => $key));
        }catch(Doggy_Util_MogileFs_UnknownKeyException $e){
            return array();
        }
        unset($result['paths']);
        return array_values($result);
    }

    /**
     * Delete a file from mogile system
     *
     * @param string $key
     * @return Doggy_Util_MogileFs_Client_Client
     */
    public function delete($key){
        if($key === null)
            throw new Doggy_Util_MogileFs_Exception("delete key cannot be null");
        try{
            $this->doRequest(self::DELETE, Array('key' => $key));
        }catch(Doggy_Util_MogileFs_UnknownKeyException $e){}
        return $this;
    }

    /**
     * Rename a file
     * @param $from string
     * @param $to string
     * @return Doggy_Util_MogileFs_Client_Client
     */
    public function rename($from, $to){
        if($from === null)
            throw new Doggy_Util_MogileFs_Exception("rename from key cannot be null");
        elseif($to === null)
            throw new Doggy_Util_MogileFs_Exception("rename to key cannot be null");
            
        $this->doRequest(self::RENAME, Array('from_key' => $from, 'to_key' => $to));
        
        return $this;
    }

    /**
     * List keys
     */
    public function listKeys($prefix = null, $lastKey = null, $limit = null){
        try {
            return $this->doRequest(self::LIST_KEYS, Array('prefix' => $prefix, 'after' => $lastKey, 'limit' => $limit));
        } catch(Exception $e){
            if(strstr($e->getMessage(), 'ERR none_match'))
                return Array();
            else
                throw $e;
        }
    }

    /**
     * Get a file from mogstored and return it as a string
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key){
        if($key === null){
            self::error("get key cannot be null",__CLASS__);
            throw new Doggy_Util_MogileFs_Exception("get key cannot be null");
        }
        $paths = $this->getPaths($key);
        
        if(empty($paths)) return null;
        
        foreach($paths as $path) {
            $contents = '';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, ($this->debug > 0 ? 1 : 0));
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            if($response === false)
            continue; // Try next source
            curl_close($ch);
            return $response;
        }
        throw new Doggy_Util_MogileFs_xception("get unable to retrieve {$key}");
    }

    /**
     * Get a file from mogstored and send it directly to stdout by way of fpassthru()
     */
    function getPassthru($key){
        if($key === null){
            Doggy_Log_Helper::error("getPassthru key cannot be null",__CLASS__);
            throw new Doggy_Util_MogileFs_Exception("getPassthru key cannot be null");
        }
        $paths = $this->getPaths($key);
        foreach($paths as $path){
            $fh = fopen($path, 'r');
            if($fh){
                if(fpassthru($fh) === false){
                    Doggy_Log_Helper::error("getPassthru failed",__CLASS__);
                    throw new Doggy_Util_MogileFs_Exception("getPassthru failed");
                }
                fclose($fh);
            }
            return $success;
        }
        Doggy_Log_Helper::error("getPassthru unable to retrieve {$key}",__CLASS__);
        throw new Doggy_Util_MogileFs_Exception("getPassthru unable to retrieve {$key}");
    }

    /**
     * Save a file to the MogileFS
     */
    public function setResource($key, $fh, $length){
        if($key === null){
            Doggy_Log_Helper::error("setResource key cannot be null",__CLASS__);
            throw new Doggy_Util_MogileFs_Exception("setResource key cannot be null");
        }

        $location = $this->doRequest(self::CREATE_OPEN, Array('key' => $key));
        $uri = $location['path'];
        $parts = parse_url($uri);
        $host = $parts['host'];
        $port = $parts['port'];
        $path = $parts['path'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, ($this->debug > 0 ? 1 : 0));
        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $length);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
        curl_setopt($ch, CURLOPT_PUT, $this->putTimeout);
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect: '));
        $response = curl_exec($ch);
        fclose($fh);
        if($response === false){
            $error=curl_error($ch);
            curl_close($ch);
            throw new Doggy_Util_MogileFs_Exception(get_class($this) . "::set $error");
        }
        curl_close($ch);
        $this->doRequest(self::CREATE_CLOSE, array(
            'key'   => $key,
            'devid' => $location['devid'],
            'fid'   => $location['fid'],
            'path'  => urldecode($uri)
            )
        );
        return true;
    }

    /**
     * Save data into mogilefs
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value){
        if($key === null){
            throw new Doggy_Util_MogileFs_Exception("set key cannot be null");
        }
        $fh = fopen('php://memory', 'rw');
        if($fh === false){
            Doggy_Log_Helper::error("set failed to open memory stream",__CLASS__);
            throw new Doggy_Util_MogileFs_Exception("set failed to open memory stream");
        }
        
        fwrite($fh, $value);
        rewind($fh);
        return $this->setResource($key, $fh, strlen($value));
    }

    /**
     * Save file into mogile fs
     *
     * @param string $key
     * @param string $filename
     *
     */
    public function setFile($key, $filename){
        if($key === null){
            Doggy_Log_Helper::error("setFile key cannot be null",__CLASS__);
            throw new Doggy_Util_MogileFs_Exception("setFile key cannot be null");
        }
        $fh = fopen($filename, 'r');
        if($fh === false){
            Doggy_Log_Helper::error("setFile failed to open path {$filename}",__CLASS__);
            throw new Doggy_Util_MogileFs_Exception("setFile failed to open path {$filename}");
        }
        return $this->setResource($key, $fh, filesize($filename));
    }
}
?>