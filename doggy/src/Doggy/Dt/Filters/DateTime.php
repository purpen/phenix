<?php
class Doggy_Dt_Filters_DateTime implements Doggy_Dt_FilterLib {
    public static function date($time, $format = 'Y-m-d H:i:s') {
        if (empty($time)) {
            return '';
        }
        if ($time instanceof DateTime) {
            $time  = (int) $time->format('U');
        }
        else {
            $time = is_numeric($time) ? $time: strtotime($time);
        }
        return date($format, $time);
    }

    static function relative_time($timestamp, $format = 'g:iA') {
        if (empty($timestamp)) {
            return '';
        }
        if ($timestamp instanceof DateTime) 
            $timestamp = (int) $timestamp->format('U');

        $timestamp = is_numeric($timestamp) ? $timestamp: strtotime($timestamp);
        
        $time   = mktime(0, 0, 0);
        $delta  = time() - $timestamp;
        $string = '';
        
        if ($timestamp < $time - 86400) {
            return date("n月j日 H点i分", $timestamp);
        }
        if ($delta > 86400 && $timestamp < $time) {
            return date("H:i分", $timestamp).', 昨天';
        }

        if ($delta > 7200)
            $string .= floor($delta / 3600) . "小时, ";
        else if ($delta > 3660)
            $string .= "1小时, ";
        else if ($delta >= 3600)
            $string .= "1小时 ";
        $delta  %= 3600;
        
        if ($delta > 60)
            $string .= floor($delta / 60) . "分钟 ";
        else
            $string .= $delta . "秒 ";
        return "$string 前";
    }

    static function relative_date($time) {
        if (empty($time)) {
            return '';
        }
        if ($time instanceof DateTime) 
            $time = (int) $time->format('U');

        $time = is_numeric($time) ? $time: strtotime($time);
        $today = strtotime(date('M j, Y'));
        $reldays = ($time - $today)/86400;
        
        if ($reldays >= 0 && $reldays < 1)
            return '今天';
        else if ($reldays >= 1 && $reldays < 2)
            return '明天';
        else if ($reldays >= -1 && $reldays < 0)
            return '昨天';

        if (abs($reldays) < 7) {
            if ($reldays > 0) {
                $reldays = floor($reldays);
                return '在' . $reldays . '天' . ($reldays != 1 ? '' : '');
            } else {
                $reldays = abs(floor($reldays));
                return $reldays . '天'  . ($reldays != 1 ? '' : '') . '前';
            }
        }
        return date('Y-m-d',$time ? $time : time());
        // if (abs($reldays) < 182)
        //     return date('Y-m-d',$time ? $time : time());
        // else
        //     return date('Y-m-d',$time ? $time : time());
    }
    
    static function relative_datetime($time) {
        // Doggy_Log_Helper::debug("relative_datetime:$time");
        $date = self::relative_date($time);
        if ($date === '今天')
            return self::relative_time($time);
        
        return $date;
    }
}