<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exchange class
 */
class RippleExchange
{
    public static function convert($currency, $amount)
    {
        $_r     = new RippleApi( 'rMwjYedjc7qqtKYVLiAccJSmCwih4LnE2q' );
        $rate = $_r->rate( strtoupper($currency) );
        return $amount * $rate;
    }
}
