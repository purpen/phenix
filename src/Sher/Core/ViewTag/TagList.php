<?php
/**
 * 关键词列表标签
 * @author purpen
 */
class Sher_Core_ViewTag_TagList extends Doggy_Dt_Tag {
    protected $argstring;
	
    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }

    /**
     * 列表的条件保持与索引顺序一致(non-PHPdoc)
     * @see Doggy/Dt/Doggy_Dt_Node#render()
     */
    public function render($context, $stream) {
		$keywords = '';
		
        $var = 'list';

        extract($this->resolve_args($context, $this->argstring, EXTR_IF_EXISTS));
        
        $result = array();
        $color_list = array('black', 'yellow', 'blue', 'orange', 'purple', 'red', 'teal');
		if($keywords){
            for($i=0;$i<count($keywords);$i++){
    			if(preg_match_all('/(设计|创意|创新)/i', $keywords[$i], $matches)){
    				$color = 'green';
    			}elseif(preg_match_all('/孵化/i', $keywords[$i], $matches)){
    			    $color = 'magenta';
    			}elseif(preg_match_all('/(软件|开发|制造|生产)/i', $keywords[$i], $matches)){
    			    $color = 'blue';
    			}else{
    			    $color = 'orange';
    			}
                
                array_push($result, array('name'=>$keywords[$i], 'color'=>$color));
            }
		}
        
        $context->set($var, $result);
    }
}