<?php
/**
 * A global id generator based on Flare key/value store.
 *
 */
class Doggy_Sequence_FlareGenerator extends Doggy_Sequence_Generator {
    private $flare;
    private $key_prefix;
    public function __construct($options=array()) {
        $host='127.0.0.1';
        $port='12121';
        $namespace=null;
        extract($options,EXTR_IF_EXISTS);
        $flare = new Memcached();
        if (is_null($namespace)) {
            $namespace = Doggy_Config::$vars['app.id'];
        }
        $flare->addServer($host,$port);
        $flare->setOption(Memcached::OPT_PREFIX_KEY,'seq:'.$namespace.':');;
        $this->flare = $flare;
    }
    public function _next($seq_name) {
        $offset = 1;
        $result = $this->flare->increment($seq_name,$offset);
        if ($result === false) {
            $ok = $this->flare->add($seq_name,$offset);
            if ($ok === false ) {
                $result =  $this->flare->increment($seq_name,$offset);
                if ($result === false) {
                    throw new Doggy_Id_Exception("failed to generate sequence:< $seq_name >, error:".$this->flare->getResultMessage());
                }
                return $result;
            }
            else {
                return $offset;
            }
        }
        return $result;
    }
    public function _drop($seq_name) {
        $this->flare->delete($seq_name);
    }
}
?>