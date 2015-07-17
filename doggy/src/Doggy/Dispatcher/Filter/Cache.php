<?php
/**
* Page cache filter
* 
* This filter support Support NGINX Memcached and custom cache rule
* 
* @author night
*/
class Doggy_Dispatcher_Filter_Cache implements Doggy_Dispatcher_Filter {
    
    protected $cacheRules = array();
    protected $cacheTTL=120;
    protected $cacheGroup = 'page_cache_group';
    
    public function __construct(){
        $cache_setting = Doggy_Config::get('app.dispatcher.filters.cache',array());
        if(!empty($cache_setting['rules'])){
            $this->cacheRules = $cache_setting['rules'];
        }
        if(isset($cache_setting['ttl'])){
            $this->cacheTTL = $cache_setting['ttl'];
        }
        if(isset($cache_setting['group'])){
            $this->cacheGroup = $cache_setting['group'];
        }
    }
    public function before($request,$response){
		//first check NGINX CACHE_ID
        $cacheId = $request->getServer('PAGE_CACHE_ID');
        if (!empty($cacheId)) {
			$nginx = Doggy_Cache_Manager::get_cache('nginx');
			$content = $nginx->get($cacheId,null);
			if(!empty($content)){
				Doggy_Log_Helper::debug("HIT NGINX PAGE CACHE ID:$cacheId");
				$content = gzuncompress($content);
				$response->appendBuffer($content);
	            $response->flushResponse();
	            return true;
			}
        }
		//fallback common pages cache group
        $cacheId = $request->getRequestUri();
        $content = Doggy_Cache_Manager::get($cacheId,$this->cacheGroup);
        if(!empty($content)){
            Doggy_Log_Helper::debug("Cache HIT $uri");
            $response->appendBuffer($content);
            $response->flushResponse();
            return true;    
        }else{
            Doggy_Log_Helper::debug('Cache missed.');
        }
        return false;
    }
    /**
     * Detectmine wheather this filter will do filtering
     *
     * @param HttpRequest $request
     * @return boolean
     */
    public function matches($request){
        //first check NGINX CACHE_ID
        $cacheId = $request->getServer('PAGE_CACHE_ID');
        if (!empty($cacheId)) {
            Doggy_Log_Helper::debug("Found NGINX PAGE CACHE ID:$cacheId");
            return true;
        }

        $uri = $request->getRequestUri();
        $rules = $this->cacheRules;
        foreach($rules as $rule){
            if (preg_match($rule,$uri)) {
                Doggy_Log_Helper::debug("Request matche cache rule:$rule");
                return true;
            }
        }
        Doggy_Log_Helper::debug('Request Not Matches any cache rules!');
        return false;
    }
    /**
     * trigger before reponse send content
     *
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @param ServerContext $context
     */
    public function after($request,$response){
        
        $content = $response->getBuffer();
        if(empty($content)) return;
        
        $cacheId = $request->getServer('PAGE_CACHE_ID');
        
        if (!empty($cacheId)) {
            //found Nginx cache,cache it first
            Doggy_Log_Helper::debug("Cache content for Nginx[cacheId:$cacheId]");
            $nginx = Doggy_Cache_Manager::get_cache('nginx');
            $content = gzcompress($content);
            $nginx->set($cacheId,$content,null);
            return;
        }
        //custom cache rules
        $cacheId = $request->getRequestUri();
        Doggy_Cache_Manager::set($cacheId,$content,$this->cacheGroup,$this->cacheTTL);
    }
}
?>