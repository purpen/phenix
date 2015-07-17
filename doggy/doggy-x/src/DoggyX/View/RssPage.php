<?php
/**
 * Special response output for rss output
 * 
 * This result support ETag and 304 response.
 * 
 * @author n.s.
 */
class DoggyX_View_RssPage extends DoggyX_View_HtmlPage {
    public function render() {
        $action = $this->invocation->getAction();
        $action->stash['_view']['content_type'] = 'text/xml';
        $action->stash['_view']['charset'] = 'utf-8';
        parent::render();
        $etag = md5($this->getBuffer());
        $request = $this->invocation->getInvocationContext()->getRequest();
        // handle e-tag
        $headers = Doggy_Dispatcher_Request_Http::getHeaders();
        $last_modified = $action->stash['last_modified_ts'];
        $response = $this->invocation->getInvocationContext()->getResponse();
        $force_refresh = isset($headers['HTTP-CACHE-CONTROL']);
        if ($force_refresh || DoggyX_Util_HttpCacheValidator::is_expired($last_modified,$etag,$headers)) {
            $response->setRawHeader('Last-Modified: '.date(DATE_RSS,$last_modified));
            $response->setRawHeader("Etag: $etag");
            return;
        }
        else {
            Doggy_Log_Helper::debug('304 hit!');
            $this->setBuffer('');
            $response->setHttpResponseCode(304);
        }
    }
}