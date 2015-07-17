<?php
class Doggy_Dt_Filters_Number implements Doggy_Dt_FilterLib {
    static function filesize ($bytes, $round = 1) {
        $bytes = (int) $bytes;
        if ($bytes === 0)
            return '0 bytes';
        elseif ($bytes === 1)
            return '1 byte';
    
        $units = array(
            'bytes' => pow(2, 0), 'KB' => pow(2, 10),
            'MB' => pow(2, 20), 'GB' => pow(2, 30),
            'TB' => pow(2, 40), 'PB' => pow(2, 50),
            'EB' => pow(2, 60), 'ZB' => pow(2, 70)
        );

        $lastUnit = 'bytes';
        foreach ($units as $unitName => $unitFactor) {
            if ($bytes >= $unitFactor) {
                $lastUnit = $unitName;
            } else {
                $number = round( $bytes / $units[$lastUnit], $round );
                return number_format($number) . ' ' . $lastUnit;
            }
        }
    }

    static function currency($amount, $currency = 'USD', $precision = 2, $negateWithParentheses = false) {
        $definition = array(
            'EUR' => array('€','.',','), 'GBP' => '￡', 'JPY' => '円', 
            'USD'=>'$', 'AU' => '$', 'CAN' => '$','CNY'=>'¥',
        );
        $negative = false;
        $separator = ',';
        $decimals = '.';
        $currency = strtoupper($currency);
    
        // Is negative
        if (strpos('-', $amount) !== false) {
            $negative = true;
            $amount = str_replace("-","",$amount);
        }
        $amount = (float) $amount;

        if (!$negative) {
            $negative = $amount < 0;
        }
        if ($negateWithParentheses) {
            $amount = abs($amount);
        }

        // Get rid of negative zero
        $zero = round(0, $precision);
        if (round($amount, $precision) === $zero) {
            $amount = $zero;
        }
    
        if (isset($definition[$currency])) {
            $symbol = $definition[$currency];
            if (is_array($symbol))
                @list($symbol, $separator, $decimals) = $symbol;
        } else {
            $symbol = $currency;
        }
        $amount = number_format($amount, $precision, $decimals, $separator);

        return $negateWithParentheses ? "({$symbol}{$amount})" : "{$symbol}{$amount}";
    }
}