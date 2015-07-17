<?php
/**
 * 创建Google Sitemap的工具类
 *
 * SiteMap 协议参考
 * https://www.google.com/webmasters/sitemaps/docs/zh_CN/protocol.html
 *
 * @author nightsailer
 */
class Doggy_Util_SiteMap_Google {
     private $url=array();
     private $map=array();
     /**
      * 添加一个或多个sitemap到索引中
      *
      * @param mixed $loc
      * @param int $time
      */
     public function addChildMap($loc,$time=NULL){
        if(is_array($loc)){
            foreach ($loc as $l) {
            	$this->_addChildMap($l,$time);
            }
        }else{
            $this->_addChildMap($loc,$time);
        }
     }
     public function _addChildMap($loc,$time=NULL){
        $text='<sitemap>';
        $text.='<loc>'.$loc.'</loc>';
        if(is_null($time))$time = $this->date_8601(time());
        $text.='<lastmod>'.$time.'</lastmod>';
        $text.='</sitemap>';
        $this->map[]=$text;
     }
     /**
      * 保存sitemap索引到指定文件
      *
      * @param string $file
      */
     public function saveSitemapIndex($file){
         $feed 	= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		 $feed	.= "<sitemapindex xmlns=\"http://www.google.com/schemas/sitemap/0.84\"\n";
		 $feed	.= "			  xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
		 $feed	.= "			  xsi:schemaLocation=\"http://www.google.com/schemas/sitemap/0.84\n";
		 $feed	.= "			  http://www.google.com/schemas/sitemap/0.84/siteindex.xsd\">\n";
		 $feed  .= implode("\n",$this->map);
		 $feed  .= "</sitemapindex>\n";
		 $ok = @file_put_contents($file,$feed);
		 return $ok;
     }
     /**
      * 输出8601格式的日期
      *
      * @param int $date
      * @return string
      */
     function date_8601($date){
        if(is_string($date))$date=strtotime($date);
        return gmdate('Y-m-d\Th:i:s\Z',$date);
     }
     /**
      * 添加一个网址
      *
      * @param string $priority
      * @param string $freq  daily|hourly|weekly|never|aways
      */
     function addUrl($url,$priority='0.5',$freq='daily'){
         $this->url[] = '<url><loc>'.$url.'</loc><priority>'.$priority.'</priority><changefreq>'.$freq."</changefreq></url>\n";
     }
     /**
      * 生成sitemap到指定文件
      *
      * @param string $file
      * @return bool
      */
     function saveSitemap($file){
         $text = '<?xml version="1.0" encoding="utf-8" ?>'."\n";
         $text.= '<urlset xmlns="http://www.google.com/schemas/sitemap/0.84">'."\n";
         $text	.= "			  xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
		 $text	.= "			  xsi:schemaLocation=\"http://www.google.com/schemas/sitemap/0.84\n";
		 $text	.= "			  http://www.google.com/schemas/sitemap/0.84/sitemap.xsd\">\n";
         $text.= implode($this->url,"\n");
         $text.= "</urlset>\n";
         $ok = @file_put_contents($file,$text);
		 return $ok;
     }
     function pingGoogle($url){

     }
     function clear(){
         $this->map=array();
         $this->url=array();
     }
 }
?>