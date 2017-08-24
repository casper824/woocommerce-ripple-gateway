<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 */
if (!class_exists('RippleSettings')) {

    class RippleSettings
    {

        public static function fields()
        {

            return apply_filters('wc_ripple_settings',

                array(
                    'enabled'     => array(
                        'title'   => __('Enable/Disable', 'woocommerce-ripple-gateway'),
                        'type'    => 'checkbox',
                        'label'   => __('Enable Ripple payments', 'woocommerce-ripple-gateway'),
                        'default' => 'yes',
                    ),
                    'title'       => array(
                        'title'       => __('Title', 'woocommerce-ripple-gateway'),
                        'type'        => 'text',
                        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-ripple-gateway'),
                        'default'     => __('Ripple (XRP) payment', 'woocommerce-ripple-gateway'),
                        'desc_tip'    => true,
                    ),
                    'description' => array(
                        'title'   => __('Customer Message', 'woocommerce-ripple-gateway'),
                        'type'    => 'textarea',
                        'default' => __('Pay online using your favorite crypto currency, Ripple.'),
                    ),
                    'address'     => array(
                        'title'       => __('Destination address', 'woocommerce-ripple-gateway'),
                        'type'        => 'text',
                        'default'     => '',
                        'description' => __('This addresses will be used for receiving funds.', 'woocommerce-ripple-gateway'),
                    ),
                    'show_prices' => array(
                        'title'   => __('Convert prices', 'woocommerce-ripple-gateway'),
                        'type'    => 'checkbox',
                        'label'   => __('Add prices in XRP', 'woocommerce-ripple-gateway'),
                        'default' => 'no',
                    ),
                    'discount'    => array(
                        'title'       => __('Discount percentage', 'woocommerce-ripple-gateway'),
                        'type'        => 'text',
                        'default'     => '0',
                        'description' => __('Add a discount % users will get when using this gateway. (numbers only)', 'woocommerce-ripple-gateway'),
                    ),
                    'test_mode'   => array(
                        'title'   => __('Test mode active', 'woocommerce-ripple-gateway'),
                        'type'    => 'checkbox',
                        'label'   => __('All payments with the matching destination tag will be accepted without checking the amount. CAREFUL!', 'woocommerce-ripple-gateway'),
                        'default' => 'no',
                    ),
                    'secret'      => array(
                        'type'    => 'hidden',
                        'default' => sha1(get_bloginfo() . Date('U')),

                    ),
                )
            );
        }
    }

}
