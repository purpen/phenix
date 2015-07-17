<?php
/**
 * 本接口确定Action是否需要启用file upload支持.
 * 
 * 本接口和Doggy_Dispatcher_Interceptor_UploadSupport配合,如果
 * 当前有上传的文件,则该interceptor将通过setUploadFiles将上传文件的信息
 * 设置到action中。
 */
interface Doggy_Dispatcher_Action_Interface_UploadSupport{
    /**
     * 提供用于将上传文件的信息注入到action中
     *
     * $files是一个数组，每个数组的值是一个独立的上传文件相关信息的数组,
     * 数组格式如下:
     * array(
     * type=>上传文件的mime信息
     * size=>上传文件的大小
     * name=>原始的文件名
     * id=>对应于前端的form的input的名称
     * path=>临时文件的路径
     * )
     * 
     * @param array $files
     */
    function setUploadFiles($files);
}
/**vim:sw=4 et ts=4 **/
?>