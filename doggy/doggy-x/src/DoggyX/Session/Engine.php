<?php
/**
 * Session engine public interface
 */
interface DoggyX_Session_Engine {
    /**
     * Destory all expired session if backend support
     *
     * @param int $expired_time 
     * @return void
     */
    public function gc($expired_time);
    /**
     * Load a session from backend, if session not found,
     * will return NULL
     *
     * @param string $sid 
     * @return bool If session loaded,  return true, else false.
     */
    public function load_session($sid);
    /**
     * Create a new session
     *
     * @param string $sid 
     * @param array $data 
     * @return void
     */
    public function create_session($sid,array $data=array());
    /**
     * Close current session, flush session data into backend.
     * 
     * Closed session can't read/write anyway.
     *
     * @param array $data 
     * @return bool
     */
    public function close(array  $data=array());
    
    /**
     * destory a session
     *
     * @param string $sid 
     * @return bool
     */
    public function destroy_session($sid);
    /**
     * 创建一个唯一的session id
     *
     * @return string
     */
    public function build_session_id();
    /**
     * Transparent put a variable into session
     *
     * @return void
     */
    public function __set($key,$value);
    /**
     * Transparent retrieve a value from session
     *
     * @param string $key 
     * @return mixed
     */
    public function __get($key);
    
    /**
     * Check the varible exists in the session
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key);
}