<?php
class Doggy_Dt_Evaluator {
    
    public static function gt($l, $r) { 
        return $l > $r; 
    }
    
    public static function ge($l, $r) { 
        return $l >= $r; 
    }

    public static function lt($l, $r) {
        return $l < $r;
    }
    
    public static function le($l, $r) {
        return $l <= $r; 
    }

    public static function eq($l, $r) {
        return $l == $r;
    }
    
    public static function ne($l, $r) {
        return $l != $r;
    }

    public static function not_($bool) {
        return !$bool;
    }
    
    public static function and_($l, $r) {
        return ($l && $r);
    }
    
    public static function or_($l, $r) {
        return ($l || $r);
    }

    # Currently only support single expression with no preceddence ,no boolean expression
    #    [expression] =  [optional binary] ? operant [ optional compare operant]
    #    [operant] = variable|string|numeric|boolean
    #    [compare] = > | < | == | >= | <=
    #    [binary]    = not | !
    public static function exec($args, $context) {
        $argc = count($args);
        $first = array_shift($args);
        switch ($argc) {
            case 1 :
                $first = $context->resolve($first);
                return $first;
            case 2 :
                if (is_array($first) && isset($first['operator']) && $first['operator'] == 'not') {
                    $operant = array_shift($args);
                    $operant = $context->resolve($operant);
                    return !($operant);
                }
            case 3 :
                list($op, $right) = $args;
                $first = $context->resolve($first);
                $right = $context->resolve($right);
                return call_user_func(array("Doggy_Dt_Evaluator", $op['operator']), $first, $right);
            default:
                return false;
        }
    }
}
?>