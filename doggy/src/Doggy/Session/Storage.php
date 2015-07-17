<?php
/**
 * Session存储层接口
 * 
 * Doggy_Session_Storage用于实现降sesion数据实际存储并持久化
 * 
 */
interface Doggy_Session_Storage{
    /**
     * 初始化storage,读取session数据
     *
     * @return array
     */
    function init();
    /**
     * 刷新session数据并持久化到后端容器中
     *
     * @param array
     */
    function store($data);
    
}
/** vim:sw=4 et ts=4 **/
?>