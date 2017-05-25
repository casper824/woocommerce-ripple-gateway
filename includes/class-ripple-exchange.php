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
        // $_r     = new RippleApi( 'rMwjYedjc7qqtKYVLiAccJSmCwih4LnE2q' );
        // $rate = $_r->rate( strtoupper($currency) );
        // echo $rate;
        // return $amount * $rate;

        // 20170525 - gives a very optimistic result for XRPEUR pair, a view into the future perhaps? going with cryptonator for now

        $url = "https://api.cryptonator.com/api/ticker/". strtolower($currency) ."-xrp";
        $ch = curl_init();
        // curl_setopt ($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if (curl_errno($ch)) {
            print_r(curl_errno($ch), true);
        }

        $result = json_decode(curl_exec($ch));
        $rate = $result->ticker->price;
		return round($amount * $rate,6);
    }
}
