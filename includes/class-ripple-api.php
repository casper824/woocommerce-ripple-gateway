<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * API class
 */
class RippleApi
{

    private $address;
    private $url;
    private $port;

    public function __construct($address)
    {
        $this->address = $address;
        $this->url     = 'https://data.ripple.com/v2/';
        $this->port    = 443;
    }

    private function get($endpoint, $params = array())
    {

        $query = false;
        if (count($params) > 0) {
            foreach ($params as $k => $v) {
                $query .= $k . '=' . $v . '&';
            }
            $query = '?' . rtrim($query, '&');
        }

        $ch = curl_init();
        // curl_setopt ($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_URL, $this->url . $endpoint . $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if (curl_errno($ch)) {
            print_r(curl_errno($ch), true);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result);

    }

    public function validAccount($address)
    {
        $result = $this->get('account/' . $address);
        return $result->result === 'success' ? true : false;
    }

    public function rate($base)
    {
        $result = $this->get('exchange_rates/' . $base . '+' . $this->address . '/XRP');
        return $result->rate;
    }

    public function findByDestinationTag($dt)
    {

        $params = array(
            'type'            => 'received',
            'currency'        => 'XRP',
            'descending'      => true,
            'destination_tag' => $dt,
            'start'           => Date('Y-m-d\TH:i:s\Z', strtotime("-15 minutes")),
        );
        $result = $this->get('accounts/' . $this->address . '/payments', $params);
        if ($result->count == 0) {
            return array(
                'result' => false,
            );
        }

        foreach ($result->payments as $payment) {
            $transaction = $this->get('transactions/' . $payment->tx_hash);
            if ($transaction->transaction->meta->TransactionResult === 'tesSUCCESS') {
                return array(
                    'result'  => true,
                    'tx_hash' => $payment->tx_hash,
                    'amount'  => $payment->amount,
                );
            }
        }
        return array(
            'result' => false,
        );
    }

    public function getTransaction($tx_hash)
    {
        return $this->get('transactions/' . $tx_hash);
    }

}
