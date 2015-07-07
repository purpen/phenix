<?php
interface Doggy_Storage_Provider {
    /**
     * 将以指定的Key保存数据
     *
     * @param string $id
     * @param string $data
     * @return Doggy_Storage_Provider
     */
    public function store($id,$data);
    /**
     * 将本地文件保存到后端
     *
     * @param string $id
     * @param string $file
     * @return Doggy_Storage_Provider
     */
    public function storeFile($id,$file);
    /**
     * 删除指定id的数据
     *
     * @param string $id
     * @return Doggy_Storage_Provider
     */
    public function delete($id);
    /**
     * 以字符串形式返回指定id的数据内容
     *
     * @param string $id
     * @return string
     */
    public function get($id);
    /**
     * 返回后端指定id的数据的Path以便客户端可以用fopen进行后续操作
     *
     * @param string $id
     * @return string
     */
    public function getPath($id);
    /**
     * 返回指定id的uri访问地址（如果可能)
     * 
     * 如果资源不存在或不支持uri访问则返回null
     *
     * @param string $id
     * @return string
     */
    public function getUri($id);
    /**
     * 检测是否已经存在指定id的数据
     *
     * @param string $id
     * @return boolean
     */
    public function exists($id);
    /**
     * 复制指定id的数据到新的id,若新的id已经存在则将被覆盖
     * 
     * 注意：复制是无条件复制,因此客户端应自行检查是否存在目标id
     *
     * @param string $id
     * @param string $copyId
     * @return Doggy_Storage_Provider
     */
    public function copy($id,$copyId);
    /**
     * 将旧id修改为新的id
     *
     * 如旧id不存在则抛出一个异常。
     * 
     * @param string $oldId
     * @param string $newId
     * @return Doggy_Storage_Provider
     */
    public function rename($oldId,$newId);
}
/**vim:sw=4 et ts=4 **/
?>