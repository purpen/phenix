<?php
/**
 * Block style comment
 *
 * @since 1.3.5 
 */
class Doggy_Dt_Tag_Comment extends Doggy_Dt_Tag {
    public function __construct($argstring, $parser, $position = 0) {
        $parser->raw_skip_to('endcomment');
    }
}
?>