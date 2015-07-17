<?php
/**
 * 创建/输出符合百度<互联网新闻开放协议>格式的xml文件
 *
 * 关于百度的《互联网新闻开放协议》参考:
 * http://news.baidu.com/newsop.html
 *
 * @author NightSailer
 *
 */
class Doggy_Util_SiteMap_BaiduNews {
    private $items=array();
    private $email;
    private $website;
    private $updatePeri=30;

    /**
     * 添加一条新闻
     *
     * @param string $title 新闻标题
     * @param string $description 新闻内容简介
     * @param string $body 完整的新闻正文（仅包含正文文字，不包含HTML语言等其它字符）
     * @param string $url 新闻url地址，与单篇新闻一一对应
     * @param string $pubDate 新闻发布时间，与该篇新闻HTML页面上的发布时间保持一致。请精确到分钟
     * @param string $author 新闻作者
     * @param string $keywords 反映新闻主题内容的一个或多个关键词，关键词之间以空格隔开
     * @param string $source 新闻来源
     * @param array $images 新闻正文内相关图片，采用绝对地址。
     * @param string $headImage 有可能成为头条的新闻制作的头条图，采用绝对地址
     */
    function addNewsItem($title,$description,$body,$url,$pubDate,$author='',$keywords='',$source='',$images=array(),$headImage=NULL,$category=''){
        $text = '<item><title>'.htmlspecialchars(strip_tags($title))."</title>";
        $text = '<item><link>'.$url."</link>\n";
        $text .= '<description>'.htmlspecialchars(strip_tags($description)).'</description>';
        $text .= '<text>'.htmlspecialchars(strip_tags($body)).'</text>';
        $text .= '<category>'.htmlspecialchars(strip_tags($category)).'</category>';
        $text .= '<author>'.htmlspecialchars(strip_tags($author)).'</author>';
        $text .= '<source>'.htmlspecialchars(strip_tags($source)).'</source>';
        $text .= '<pubDate>'.$pubDate.'</pubDate>';
        foreach ($images as $m) {
        	$text.='<image>'.$m.'</image>';
        }
        if(!empty($headImage)){
            $text.='<headlineImg>'.$headImage.'</headlineImg>';
        }
        $text.='</item>';
        $this->items[]=$text;
    }
    /**
     * 保存到指定文件中
     *
     * @param string $file
     * @return bool
     */
    function save($file){
        $text = '<?xml version="1.0" encoding="utf-8" ?>'."\n";
        $text .= '<document><website>'.$this->website.'</website>';
        $text .='<webMaster>'.$this->email.'</webMaster>';
        $text .='<updatePeri>'.$this->updatePeri.'</updatePeri>';
        $text .= implode("\n",$this->items);
        $text .= '</document>';
        $ok = @file_put_contents($file,$text);
        return $ok;
    }
}
?>