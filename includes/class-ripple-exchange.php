<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exchange class
 */
class RippleExchange
{
    private static $source = 'coinmarketcap';

    public static function convert($currency, $amount)
    {
        // $_r     = new RippleApi( 'rMwjYedjc7qqtKYVLiAccJSmCwih4LnE2q' );
        // $rate = $_r->rate( strtoupper($currency) );
        // return round($amount * $rate,6);

        // 20170525 - gives a very optimistic result for XRPEUR pair, a view into the future perhaps? going with cryptonator for now

        $rate = self::get($currency);

        return $rate == false ? -999.999999 : round($amount * $rate, 6);
    }

    public static function get($currency)
    {

        $rate = false;
        switch (self::$source) {
            case 'coinmarketcap':
                $url = 'https://api.coinmarketcap.com/v1/ticker/xrp/?convert=' . strtoupper($currency);
                break;
            case 'cryptonator':
                $url = "https://api.cryptonator.com/api/ticker/" . strtolower($currency) . "-xrp";
                break;
        }

        $result = json_decode(wp_remote_get($url));

        switch (self::$source) {
            case 'coinmarketcap':
                $key  = 'price_' . strtolower($currency);
                $rate = isset($result->error) ? false : $result->$key;
                break;
            case 'cryptonator':
                $rate = isset($result->ticker->price) ? $result->ticker->price : false;
                break;
        }

        if ($rate == false) {
            if (self::$source == 'coinmarketcap') {
                self::$source = 'cryptonator';
            } else {
                return false;
            }
            // round 2
            return self::get($currency);
        }
        return $rate;
    }
}
