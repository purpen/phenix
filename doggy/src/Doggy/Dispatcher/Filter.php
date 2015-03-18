<?php
/**
 * Filter interface
 *
 * Filter will be execute before action process flow.
 */
interface  Doggy_Dispatcher_Filter {
    /**
     * Execute filter
     *
     * @param Doggy_Dispatcher_Request_Http $request
     * @param Doggy_Dispatcher_Response_Http $response
     * @return boolean If want interrupt action process flow,return true
     */
    function before($request,$response);
    /**
     * Detectmine wheather this filter will do filtering
     *
     * @param Doggy_Dispatcher_Request_Http $request
     * @return boolean
     */
    function matches($request);
    /**
     * trigger before reponse send content
     *
     * @param Doggy_Dispatcher_Request_Http $request
     * @param Doggy_Dispatcher_Response_Http $response
     */
    function after($request,$response);
}
?>