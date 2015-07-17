<?php
/**
 * Database adapter interface
 *
 * note:
 * 这是简化的database adapter接口,和Doggy_Db_Adapter相比，这个接口主要是用后端的ice服务来实现
 * 数据库的操作.
 *
 */
interface Doggy_Dba_Adapter{
    /**
     * 链接到数据库
     *
     */
    function connect();
    /**
     * 关闭当前链接
     *
     */
    function close();
    /**
     * 执行查询
     *
     *
     * @param string $sql 要执行的sql语句
     * @param int $size optional,返回每页记录行数,默认为-1表示不分页(即返回全部记录)
     * @param int $page optional,返回第几页(0 based),默认为0
     * @param array $vars optional,要在sql中绑定的variable的value数组
     * @return array
     */
    function query($sql,$size=-1,$page=1,$vars=array());

    /**
     * 执行一个不返回结果集的SQL查询
     *
     * @param string $sql
     * @param array $vars
     */
    function execute($sql,$vars=array());
    /**
     * 生成指定sequence的下一个值
     *
     * @param string $name
     * @return int
     */
    function genSeq($name);
    
    /**
     * Drop given sequence
     *
     * @param string $name
     */
    function dropSeq($name);

    /**
     * 返回指定表的Field meta 数组
     * 一个Field Meta是一个如下的数组:
     * array(
     * '字段名'=>array(
     *  'size'=>字段长度,
     *  'type'=>字段类型: N:数值 S:字符 D:日期型 T:时间戳
     *  )
     *
     * )
     *
     *
     * @param string $table
     * @return array
     */
    function getFieldMetaList($table);
    /**
     * 返回当前数据库的表名
     *
     * @return array
     */
    function getTableList();
}
/** vim:ts=4:tw=4:expandtab */
?>