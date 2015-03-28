<?php
/**
 * 评论所属对象
 * @author purpen
 */
class Sher_App_ViewTag_CommentTarget extends Doggy_Dt_Tag {
    protected $argstring;

    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }

    public function render($context, $stream) {
        $target_id = 0;
        $type = 0;
		
        $var = 'target';

        extract($this->resolve_args($context,$this->argstring, EXTR_IF_EXISTS));
        
        Doggy_Log_Helper::debug("Comment Target [ $target_id ][ $type ] is OK!");
        
        $type = (int)$type;
        $result = array();
		switch($type){
			case Sher_Core_Model_Comment::TYPE_TOPIC:
				$model = new Sher_Core_Model_Topic();
				$result = $model->extend_load($target_id);
				break;
			case Sher_Core_Model_Comment::TYPE_TRY:
				$model = new Sher_Core_Model_Try();
                $result = $model->extend_load((int)$target_id);
				break;
			case Sher_Core_Model_Comment::TYPE_ACTIVE:
				$model = new Sher_Core_Model_Active();
				$result = $model->extend_load((int)$target_id);
				break;
			case Sher_Core_Model_Comment::TYPE_STUFF:
				$model = new Sher_Core_Model_Stuff();
				$result = $model->extend_load((int)$target_id);
				break;
			case Sher_Core_Model_Comment::TYPE_PRODUCT:
				$model = new Sher_Core_Model_Product();
				$result = $model->extend_load((int)$target_id);
				break;
		}
        
    	$context->set($var, $result);	
    }
}