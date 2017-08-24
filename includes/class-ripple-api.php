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
        return json_decode(wp_remote_get($this->url . $endpoint . $query));
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
