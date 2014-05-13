<?php
/**
 * 附件的维护工具
 */
class Sher_Core_Util_Asset extends Doggy_Object {
    
    /**
     * 将文件存储到云存储
 	 *
     * @param string $path
     * @param string $file
     */
	public static function store_asset_cloud($key, $file){
		$accessKey = Doggy_Config::$vars['app.qiniu.key'];
		$secretKey = Doggy_Config::$vars['app.qiniu.secret'];
		$bucket = Doggy_Config::$vars['app.qiniu.bucket'];
		
		$client = \Qiniu\Qiniu::create(array(
		    'access_key' => $accessKey,
		    'secret_key' => $secretKey,
		    'bucket'     => $bucket
		));
		
		$res = $client->uploadFile($file, $key);
		
		if ($res->ok()){
			// 获取返回的数据
			$data = $res->toArray();
		} else {
			$data = array(
				'error' => $res->error,
				'msg' => $res->debug
			);
		}
		
		return $data;
	}
	
    /**
     * 将内容存储到云存储
 	 *
     * @param string $path
     * @param string $file
     */
	public static function store_data_cloud($key, $content){
		$accessKey = Doggy_Config::$vars['app.qiniu.key'];
		$secretKey = Doggy_Config::$vars['app.qiniu.secret'];
		$bucket = Doggy_Config::$vars['app.qiniu.bucket'];
		
		$client = \Qiniu\Qiniu::create(array(
		    'access_key' => $accessKey,
		    'secret_key' => $secretKey,
		    'bucket'     => $bucket
		));
		
		$res = $client->upload($content, $key);
		
		if ($res->ok()){
			// 获取返回的数据
			$data = $res->toArray();
		} else {
			$data = array(
				'error' => $res->error,
				'msg' => $res->debug
			);
		}
		
		return $data;
	}
	
    /**
     * 将文件存储到附件区域
     *
     * @param string $domain
     * @param string $path
     * @param string $file
     */
    public static function storeAsset($domain,$path,$file){
        $storage = Doggy_Storage_Manager::getDomainByKey($domain);
        try{
            $storage->storeFile($path, $file);
        }catch(Doggy_Storage_Exception $e){
            self::error("Failed store asset:[domain:$domain][path:$path][file:$file]",__CLASS__);
            throw new Sher_Core_Util_Exception('Failed store asset into '.$path);
        }
    }
	
    
	
    /**
     * 将字符串存储为文件
     *
     * @param string $domain
     * @param string $path
     * @param string $content
     */
    public static function storeData($domain,$path,$content){
        $storage = Doggy_Storage_Manager::getDomainByKey($domain);
        try{
            $storage->store($path,$content);
        }catch(Doggy_Storage_Exception $e){
            self::error("Failed store asset:[domain:$domain][path:$path]",__CLASS__);
            throw new Sher_Core_Util_Exception('Failed store asset into '.$path);
        }
    }
    /**
     * 删除指定域的附件
     *
     * @param string $domain
     * @param string $path
     * @return boolean
     */
    public static function deleteAsset($domain,$path){
        $storage = Doggy_Storage_Manager::getDomainByKey($domain);
        try{
            $storage->delete($path);
            return true;
        }catch(Doggy_Storage_Exception $e){
            self::error("failed delete asset:[domain:$domain][path:$path]",__METHOD__);
        }
        return false;
    }
    /**
     * 获得附件绝对路径
     *
     * @param string $domain
     * @param string $path
     * @return string
     */
    public static function getAssetPath($domain,$path){
        $storage = Doggy_Storage_Manager::getDomainByKey($domain);
        return $storage->getPath($path);
    }
    /**
     * 获得附件的url
     *
     * @param string $domain
     * @param string $path
     * @return string
     */
    public static function getAssetUrl($domain,$path){
        $storage = Doggy_Storage_Manager::getDomainByKey($domain);
        return $storage->getUri($path);
    }    
}
/**vim:sw=4 et ts=4 **/
?>