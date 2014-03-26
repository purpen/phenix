<?php
class Sher_App_ViewTag_Pager extends Doggy_Dt_Tag {
    protected $argstring;
    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }
    public function render($context, $stream) {
        $total_page=0;
        $current_page=1;
        //每组页码个数
        $pager_size=9;
        $var = 'pager';
        $current_css='active';
        $more_text='...';
        $url ='#p#';
        $total_rows = 0;
        
        $is_prepage = 0;
        $model_class = null; 
        $pre_query = array();
        $pre_options = array();
        
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));

        $current_page = max(min($total_page,$current_page),1);
        $offset = $current_page % $pager_size;
        //first-index
        if ( $offset == 1) {
            $pager_start = max($current_page-1,1);
            $pager_end = min($pager_start+$pager_size-1,$total_page);
        }
        elseif ($offset == 0) {
            $pager_end = min($current_page+1,$total_page);
            $pager_start = max($pager_end-$pager_size+1,1);
        }
        else {
            $pager_start = max($current_page-$offset+1,1);
            $pager_end = min($pager_start+$pager_size-1,$total_page);
        }
        // last page,from end-page rollback
        if ($current_page >= $total_page) {
            $pager_end = $total_page;
            $pager_start = max($total_page - $pager_size+1,1);
        }
        
        $pages = array();
        $page_index = $pager_start;
        for ($i=0; $i < $pager_size && $page_index<=$pager_end;$i++) {
            $page['page_index'] = $page_index;
            $page['css'] = $current_page == $page_index ? $current_css:'';
            $page['url'] = str_replace('#p#',$page_index,$url);
            $pages[] = $page;
            $page_index++;
        }

        if ($pager_start > 1) {
            array_unshift($pages,array(
                'page_index'=>1,
                'css'=>'',
                'suffix_text'=> $more_text,
                'url'=>str_replace('#p#',1,$url),
                ));
        }
        if ($pager_end!=$total_page) {
            array_push($pages,array(
                'page_index'=>$total_page,
                'css'=>'',
                'prefix_text'=> $more_text,
                'url'=> str_replace('#p#',$total_page,$url),
                ));
        }

        $prev_page = max($current_page-1,1);
        $next_page = min($current_page+1,$total_page);

        if ($total_page <=1 || $current_page == 1) {
            $pager['show_prev'] = false;
        }
        else {
            $pager['show_prev'] = true;
        }
        if ($total_page <= 1 || $current_page == $total_page) {
            $pager['show_next'] = false;
        }
        else {
            $pager['show_next'] = true;
        }
        $pager['total_rows'] = $total_rows;
        $pager['current_page'] = $current_page;
        $pager['total_page'] = $total_page;
        $pager['prev_url'] = str_replace('#p#',$prev_page,$url);
        $pager['next_url'] = str_replace('#p#',$next_page,$url);
        
        if($is_prepage){
        	//若符合预载条件，则补充参数endmid
            if(!is_null($model_class) && !empty($pre_query) && !empty($pre_options)){
            	$model = new $model_class();
                $service = new Lgk_Core_Service_PreloadCursor();
                
                $pre_url_separator = strstr($url,'?') ? '&' : '?';
	            for($i=0;$i<count($pages);$i++){
	                $pre_cursor_id = $service->query_first($model,$pages[$i]['page_index'],$pre_query,$pre_options);
	                //echo "page:".$pages[$i]['page_index']."=>$pre_cursor_id\n";
	                if($pre_cursor_id){
	                    $pages[$i]['url'] .= $pre_url_separator.'endmid='.$pre_cursor_id;
	                }
	            }
                //上一页、下一页
	            $pager['prev_url'] .= $pre_url_separator.'endmid='.$service->query_first($model,$prev_page,$pre_query,$pre_options);
                $pager['next_url'] .= $pre_url_separator.'endmid='.$service->query_first($model,$next_page,$pre_query,$pre_options);
            }
        }
        $pager['pages'] = $pages;
        
        $context->set($var,$pager);
    }
}
?>