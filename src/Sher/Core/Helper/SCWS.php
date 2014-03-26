<?php
class Sher_Core_Helper_SCWS {
    /**
     * 对文本进行分词后返回分词数组用于创建索引
     *
     * @param string $scws 
     * @param string $text 
     * @return array
     */
    public static function segment_index_word($scws,$text) {
        $result = array();
		
        if (empty($text)) {
            return $result;
        }
		
        $scws->set_ignore(true);
        $scws->send_text($text);
		
        while ($words = $scws->get_result()) {
            foreach ($words as $w) {
                // 忽略单字
                if ($w['len'] <= 3 && $w['attr'] != 'n' && $w['attr'] != 'en' ) {
                    continue;
                }
                $result[] = $w['word'];
            }
        }
        // workaround for mongoDB driver bug.
        return array_values(array_unique($result));
    }
    /**
     * 对查询的语句进行分析，目前仅仅实现和索引一样进行分词
     *
     * @param string $scws 
     * @param string $query_text 
     * @return void
     */
    public static function segment_query_word($scws,$query_text) {
        // if (mb_strlen($query_text)<=6) {
        //     return array($query_text);
        // }
        return self::segment_index_word($scws,$query_text);
    }
}
?>