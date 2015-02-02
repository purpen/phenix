<?php
/**
 * JQuery的helper functions
 *　
 * 所有的elements command注册为smarty的block函数
 * 
 * 所有的non-elements command注册为smarty的自定义函数
 * 
 */
class Doggy_Util_JQuery_Core extends Doggy_Object {
    
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    //
    // Elements command
    //
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
   
    public static function _taconiteCommand($commands,$selects,$content){
        if(is_array($selects)){
            $selects = array();
        }
        $result='';
        if(!is_array($commands)){
            $commands = array($commands);
        }
        foreach($commands as $command){
            foreach($selects as $s){
                $result.= "<$command id='$s'>\n".$content."\n</$command>\n";
            }
        }
        return $result;
    }
    
    /**
     * 对指定的select依次输出指定的内容
     * 
     */
    public static function e_j_ec($params,$content,$smarty,&$repeat){
        $command=null;
        $select=null;
        extract($params,EXTR_IF_EXISTS);
        if(is_null($select)||is_null($command)){
            throw new Doggy_Util_Smarty_Exception('e_j_nc:command or select cannot be null!');
        }
        if(is_null($content)){
            $repeat=true;   
        }else{
            $command_queue = explode(',',$command);
            $content = self::_taconiteCommand($command_queue,$select,$content);
        }
        return $content;
    }
   
    /**
     * 
     * @param array $params
     * @param mixed $content
     * @param Doggy_Util_Smarty_Base
     * @return string
     */
    public static function e_j_eval($params,$content,$smarty,&$repeat){
        if(is_null($content)){
            $repeat=true;
        }else{
            $content = "<eval>\n<![CDATA[ \n".$content."\n]]>\n</eval>";
        }
        return $content;
    }
    
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    //
    // Non Elements command
    //
    //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    
    
}
/**vim:sw=4 et ts=4 **/
?>