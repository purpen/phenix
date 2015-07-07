<?php
/**
 * 常用的字符串操作
 */
class Doggy_Util_String {
    /**
     * 将字符串转换为javascript可以输出的格式
     *
     * @param string $s
     * @return string
     */
    public static function escapeJavascriptString($s){
        $jsEscape = array(
            "\r"    => '\r',
            "\n"    => '\n',
            "\t"    => '\t',
            "'"     => "\\'",
            '"'     => '\"',
            '\\' => '\\\\'
        );
        return strtr($s,$jsEscape);
    }
}
?>