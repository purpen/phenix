<?php
/**
 * Basic class for an extended "Tag" node.
 *
 */
class Doggy_Dt_Tag extends Doggy_Dt_Node {

    protected function resolve_args($context,$args) {
        if (is_string($args)) {
            $args = Doggy_Dt_Parser::parse_args($args);
        }
        
        $result = array();
        
        foreach ($args as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $key => $value) {
                    $result[$key] = $context->resolve($value);
                }
            }
            else {
                $result[] = $context->resolve($arg);
            }
        }
        return $result;
    }
}
?>