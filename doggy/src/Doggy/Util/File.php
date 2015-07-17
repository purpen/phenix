<?php
/**
 * 文件处理类
 *
 * port from EPS project
 */
class Doggy_Util_File {
    /**
     * 创建目录结构
     *
     * @param string $path
     * @param int $mode
     * @return bool
     */
    public static function mk($path,$mode=0777){
        if(file_exists($path)) return true;
        $dirs = preg_split('/[\/]/',$path);
	    $p = '';
	    for($i=0;$i<count($dirs);$i++){
	        $p.= $dirs[$i].'/';
	        if(is_dir($p))continue;
	        mkdir($p);
	        chmod($p,$mode);
	    }
	    return true;
    }
   
    /**
     * 重命名
     *
     * @param string $old
     * @param string $new
     * @return bool
     */
    public static function rename($old,$new){
        if(!file_exists($old)) return false;
        if (!@rename($old,$new)) {
            self::mk(dirname($new));
            if (@copy ($old,$new)) {
                @unlink($old);
                return TRUE;
            }
            return FALSE;
         }
         return TRUE;
    }
    
    /**
     * Move file
     *
     * @param string $old 
     * @param string $new 
     * @return bool
     */
    public static function mv($old,$new){
        self::mk(dirname($new));
        if (@copy ($old,$new)) {
            @unlink($old);
            return true;
        }
        return false;
    }
    
    /**
	 * Get file extension
	 *
	 * @param string $filename
	 * @return string $ext return file extension
	 * @deprecated
	 */
	public static function getFileExtension($filename){
    	return strtolower(substr(strrchr($filename,'.'),1));
	}
    
    /**
     * Returns file extension name
     *
     * @param string $file_name 
     * @return string
     */
    public static function get_file_ext($file_name) {
        return strtolower(substr(strrchr($file_name,'.'),1));
    }
    
	/**
     * 将数据写入指定路径的文件
     * 默认将文件属性改为0666
     * 
     * @param	string	$path
     * @param	string	$data
     * @param  bool $dontchmod disable chmod target file
     * @return	bool
     */
    public static function write_file($path, $data,$dontchmod=false){
        $ok = file_put_contents($path,$data,LOCK_EX);
        if($ok===false) return false;
    	if(!$dontchmod){
    	   @chmod($path,0666);
    	}
    	return true;
    }
    /**
     * writeFile
     *
     * @param string $path 
     * @param string $data 
     * @param string $dontchmod 
     * @return bool
     * @deprecated
     */
    public static function writeFile($path,$data,$dontchmod=false) {
        return self::write_file($path,$data,$dontchmod);
    }
    
    /**
     * 锁定并返回文件内容
     * 
     * @param string $filename
     * @return string
     */
    public static function flockGetContents($filename){
        $return = FALSE;
        if(!is_readable($filename)){
            return false;
        }
        if($fp = @fopen($filename, 'r')){
            while(!$return){
                if(flock($handle, LOCK_SH)){
                    if($return = file_get_contents($filename)){
                        flock($handle, LOCK_UN);
                    }
                }
            }
        }
        fclose($fp);
        return $return;
    }
	
    /**
     * Return file list in "dir" folder matching "pattern"
     *
     * @param string $dir
     * @param string $pattern
     * @return array
     */
    public static function ls($dir="./",$pattern="*.*"){
        settype($dir,"string");
        settype($pattern,"string");
        $ls=array();
        $regexp=preg_quote($pattern,"/");
        $regexp=preg_replace("/[\\x5C][\x2A]/",".*",$regexp);
        $regexp=preg_replace("/[\\x5C][\x3F]/",".", $regexp);
        if(is_dir($dir) && ($dir_h=@opendir($dir))!==FALSE){
            while(($file=readdir($dir_h))!==FALSE)
                if(preg_match("/^".$regexp."$/",$file))array_push($ls,$file);
            closedir($dir_h);
        }
        sort($ls,SORT_STRING);
        return $ls;
    }

	/**
     * 删除指定路径及其所有子目录
     * 
     * @param string $path
     * @param boolean $deleteTopDir  true:只清除目录下文件不删除该目录 false:删除该目录
     * @return boolean
     */
    public static function rm($path,$deleteTopDir=true){
        
        if(!file_exists($path)) return true;

        if(is_file($path)){
            return unlink($path);
        }
        $files = scandir($path);
        foreach ($files as $f) {
        	if($f=='.'||$f=='..')continue;
        	$f_path = $path.'/'.$f;
        	if(is_dir($f_path)) self::rm($f_path);
        	if(is_file($f_path)) unlink($f_path);
        }
        if($deleteTopDir) rmdir($path);
        return true;
    }
	
	/**
	 * 删除指定目录中的所有文件和子目录
	 * 
	 * @param string $path
	 * @return boolean
	 */
	public static function clear($path){
        return self::rm($path,false);
	}
	
	/**
	 * Concate pathes
	 *
	 * @return string
	 */
	public static function normalize_path() {
	    $dirs = func_get_args();
	    if(empty($dirs)) return;
	    $result = array_shift($dirs);
	    while( $dir = array_shift($dirs) ){
	        $result .= '/'.ltrim($dir,'/');
	    }
	    return rtrim($result,'/');
	}

    /**
     * returns file's content type
     *
     * @param string $value 
     * @return string
     */
    public static function mime_content_type($file) {
        static $mimeTypes = array (
		    'ez'=>'application/andrew-inset',
            'hqx'=>'application/mac-binhex40',
            'cpt'=>'application/mac-compactpro',
            'doc'=>'application/msword',
            'bin'=>'application/octet-stream',
            'dms'=>'application/octet-stream',
            'lha'=>'application/octet-stream',
            'lzh'=>'application/octet-stream',
            'exe'=>'application/octet-stream',
            'class'=>'application/octet-stream',
            'oda'=>'application/oda',
            'pdf'=>'application/pdf',
            'ai'=>'application/postscript',
            'eps'=>'application/postscript',
            'ps'=>'application/postscript',
            'smi'=>'application/smil',
            'smil'=>'application/smil',
            'mif'=>'application/vnd.mif',
            'xls'=>'application/vnd.ms-excel',
            'ppt'=>'application/vnd.ms-powerpoint',
            'wbxml'=>'application/vnd.wap.wbxml',
            'wmlc'=>'application/vnd.wap.wmlc',
            'wmlsc'=>'application/vnd.wap.wmlscriptc',
            'bcpio'=>'application/x-bcpio',
            'vcd'=>'application/x-cdlink',
            'pgn'=>'application/x-chess-pgn',
            'cpio'=>'application/x-cpio',
            'csh'=>'application/x-csh',
            'dcr'=>'application/x-director',
            'dir'=>'application/x-director',
            'dxr'=>'application/x-director',
            'dvi'=>'application/x-dvi',
            'spl'=>'application/x-futuresplash',
            'gtar'=>'application/x-gtar',
            'hdf'=>'application/x-hdf',
            'js'=>'application/x-javascript',
            'skp'=>'application/x-koan',
            'skd'=>'application/x-koan',
            'skt'=>'application/x-koan',
            'skm'=>'application/x-koan',
            'latex'=>'application/x-latex',
            'nc'=>'application/x-netcdf',
            'cdf'=>'application/x-netcdf',
            'sh'=>'application/x-sh',
            'shar'=>'application/x-shar',
            'swf'=>'application/x-shockwave-flash',
            'sit'=>'application/x-stuffit',
            'sv4cpio'=>'application/x-sv4cpio',
            'sv4crc'=>'application/x-sv4crc',
            'tar'=>'application/x-tar',
            'tcl'=>'application/x-tcl',
            'tex'=>'application/x-tex',
            'texinfo'=>'application/x-texinfo',
            'texi'=>'application/x-texinfo',
            't'=>'application/x-troff',
            'tr'=>'application/x-troff',
            'roff'=>'application/x-troff',
            'man'=>'application/x-troff-man',
            'me'=>'application/x-troff-me',
            'ms'=>'application/x-troff-ms',
            'ustar'=>'application/x-ustar',
            'src'=>'application/x-wais-source',
            'zip'=>'application/zip',
            'au'=>'audio/basic',
            'snd'=>'audio/basic',
            'mid'=>'audio/midi',
            'midi'=>'audio/midi',
            'kar'=>'audio/midi',
            'mpga'=>'audio/mpeg',
            'mp2'=>'audio/mpeg',
            'mp3'=>'audio/mpeg',
            'aif'=>'audio/x-aiff',
            'aiff'=>'audio/x-aiff',
            'aifc'=>'audio/x-aiff',
            'ram'=>'audio/x-pn-realaudio',
            'rm'=>'audio/x-pn-realaudio',
            'rpm'=>'audio/x-pn-realaudio-plugin',
            'ra'=>'audio/x-realaudio',
            'wav'=>'audio/x-wav',
            'pdb'=>'chemical/x-pdb',
            'xyz'=>'chemical/x-xyz',
            'bmp'=>'image/bmp',
            'gif'=>'image/gif',
            'ief'=>'image/ief',
            'jpeg'=>'image/jpeg',
            'jpg'=>'image/jpeg',
            'jpe'=>'image/jpeg',
            'png'=>'image/png',
            'tiff'=>'image/tiff',
            'tif'=>'image/tiff',
            'wbmp'=>'image/vnd.wap.wbmp',
            'ras'=>'image/x-cmu-raster',
            'pnm'=>'image/x-portable-anymap',
            'pbm'=>'image/x-portable-bitmap',
            'pgm'=>'image/x-portable-graymap',
            'ppm'=>'image/x-portable-pixmap',
            'rgb'=>'image/x-rgb',
            'xbm'=>'image/x-xbitmap',
            'xpm'=>'image/x-xpixmap',
            'xwd'=>'image/x-xwindowdump',
            'igs'=>'model/iges',
            'iges'=>'model/iges',
            'msh'=>'model/mesh',
            'mesh'=>'model/mesh',
            'silo'=>'model/mesh',
            'wrl'=>'model/vrml',
            'vrml'=>'model/vrml',
            'css'=>'text/css',
            'html'=>'text/html',
            'htm'=>'text/html',
            'asc'=>'text/plain',
            'txt'=>'text/plain',
            'rtx'=>'text/richtext',
            'rtf'=>'text/rtf',
            'sgml'=>'text/sgml',
            'sgm'=>'text/sgml',
            'tsv'=>'text/tab-separated-values',
            'wml'=>'text/vnd.wap.wml',
            'wmls'=>'text/vnd.wap.wmlscript',
            'etx'=>'text/x-setext',
            'xml'=>'text/xml',
            'mpeg'=>'video/mpeg',
            'mpg'=>'video/mpeg',
            'mpe'=>'video/mpeg',
            'qt'=>'video/quicktime',
            'mov'=>'video/quicktime',
            'avi'=>'video/x-msvideo',
            'movie'=>'video/x-sgi-movie',
            'ice'=>'x-conference/x-cooltalk',
		);
		
		$ext = self::get_file_ext($file);
	    if (isset( $mimeTypes[$ext])){
			return  $mimeTypes[$ext];
	    }
		return 'application/octet-stream';
    }
    /**
	 * 递归创建目录
	 * 
	 * @param string $path
	 * @param int $mode   创建目录的权限掩码,默认是0755(属主读写执行,其他读执行)
	 */
    public static function build_dir($path,$mode=0755){
        if(file_exists($path)) return true;
        $dirs = preg_split('/[\/]/',$path);
        $p = '';
        for($i=0;$i<count($dirs);$i++){
            $p.= $dirs[$i].'/';
            if(is_dir($p))continue;
            mkdir($p);
            chmod($p,$mode);
        }
        return true;
    }
    
    /**
     * 检测给定的路径是否是绝对路径
     *
     * @param string $path
     * @return boolean
     */
    static public function is_abs_path( $path ){
        if ($path == "") return false;
        // / or C:,D:/
        if (substr($path,0,1) == "/" || preg_match('/^(\w:)/', $path)){
            return true;
        }else{
            return false;
        }
    }
    

	/**
	 * Chops the extension from a file
	 * @param	string	Filename/Path
	 * @return	string
	 */
	public static function chopExtension($file) {
		$len = strlen(self::fileExtension($file));
		return $len ? substr($file, 0, strlen($file)-$len-1) : $file;
	}
	
	
	/**
	 * Returns a list of absolute paths to files and directories
	 * @param	string	Path to directory
	 * @return	array
	 */
	public static function scan_dir($dir) {
		$list = array();
		$dir = rtrim($dir, '/\\');

		if (file_exists($dir) && false !== ($dh = @opendir($dir))) {
			while (false !== ($file = readdir($dh))) {
				if ('.' == $file || '..' == $file)
					continue;
				$list[] = $dir.DIRECTORY_SEPARATOR.$file;
			}
		}

		return $list;
	}
}
?>