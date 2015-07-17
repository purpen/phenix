<?php
/**
 * Loader to load tempalte file.
 *
 */
class Doggy_Dt_Loader_FileLoader extends Doggy_Dt_Loader {

    /**
     * construct
     *
     * @param string $searchpath template search path
     * @param string $options 
     */
    public function __construct($searchpath, $options = array()) {
        if (is_file($searchpath)) {
            $searthpath = dirname($searchpath).DIRECTORY_SEPARATOR;
        }
        if (!is_dir($searchpath))
            throw new Doggy_Dt_Exception_TemplateNotFound($searchpath);

        $this->searchpath = realpath($searchpath) . DIRECTORY_SEPARATOR;
        $this->setOptions($options);
    }

    /**
     * Set options
     *
     * @param string $options 
     * @return void
     */
    public function setOptions($options = array()) {
        if (isset($options['cache']) && $options['cache']) {
            $this->cache = doggy_dt_cache($options);
        }
    }
    
    /**
     * Read template's content and return parsed nodelist
     *
     * @param string $filename 
     * @return array()
     */
    public function read($filename,$parsed=true) {
        if (!is_file($filename))
            $filename = $this->searchpath . $filename;

        if (is_file($filename)) {
            $source = file_get_contents($filename);
            if (!$parsed) {
                return $source;
            }
            return $this->runtime->parse($source);
        } else {
            throw new Doggy_Dt_Exception_TemplateNotFound($filename);
        }
    }

    /**
     * Lookup cache and load,parse template,return parsed nodelist
     *
     * @param string $filename 
     * @return array
     */
    public function read_cache($filename) {
        if (!$this->cache)
             return $this->read($filename);

        if (!is_file($filename))
            $filename = $this->searchpath . $filename;

        $filename = realpath($filename);
        $cache = md5($filename);
        $object = $this->cache->read($cache);
        $this->cached = $object && !$this->expired($object);
        
        if (!$this->cached) {
            $nodelist = $this->read($filename);
            $object = array(
                'filename' => $filename,
                'content' => serialize($nodelist),
                'created' => time(),
                'templates' => $nodelist->parser->storage['templates'],
                'included' => $nodelist->parser->storage['included'] + array_values(Doggy_Dt::$extensions)
            );
            $this->cache->write($cache, $object);
        } else {
           /* foreach($object->included as $ext => $file) {
                include_once (Doggy_Dt::$extensions[$ext] = $file);
            } */
        }
        return unserialize($object['content']);
    }

    /**
     * flush cache
     *
     * @return void
     */
    public function flush_cache() {
        $this->cache->flush();
    }
    
    /**
     * check the object is expired
     *
     * @param mixed $object 
     * @return boolean
     */
    public function expired($object) {
        if (!$object) return false;
        
        $files = array_merge(array($object['filename']), $object['templates']);
        foreach ($files as $file) {
            if (!is_file($file))
                $file = $this->searchpath.$file;
            
            if ($object['created'] < filemtime($file))
                return true;
        }
        return false;
    }
}
?>