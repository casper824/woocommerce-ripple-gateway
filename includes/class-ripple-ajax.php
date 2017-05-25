<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax class
 */
class RippleAjax
{

    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        add_action('wp_ajax_check_ripple_payment', array(__CLASS__, 'checkRipplePayment'));
    }

    public function checkRipplePayment()
    {
        global $woocommerce;
        $woocommerce->cart->get_cart();

        $options = get_option('woocommerce_ripple_settings');

        $payment_total   = WC()->session->get('ripple_payment_total');
        $destination_tag = WC()->session->get('ripple_destination_tag');

        if (sha1($options['secret'] . $destination_tag . $payment_total) != WC()->session->get('ripple_data_hash')) {
            echo json_encode(
                array(
                    'result' => false,
                    'reason' => 'possible fraud attempt',
                )
            );
            exit();
        }

        $ra     = new RippleApi($options['address']);
        $result = $ra->findByDestinationTag($destination_tag);

        $result['match'] = ($result['amount'] == $payment_total || $options['test_mode'] == 'yes') ? true : false;

        echo json_encode($result);
        exit();
    }

} 

RippleAjax::getInstance();
