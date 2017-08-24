<?php

/**
 * WooCommerce Ripple Gateway
 *
 * Plugin Name: WooCommerce Ripple Gateway
 * Plugin URI: www.q-invoice.com
 * Description: Show prices in XRP and accept Ripple payments in your woocommerce webshop
 * Version: 0.0.5
 * Author: Casper Mekel
 * License: GPLv2 or later
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 * Text Domain: woocommerce-ripple-gateway
 * Domain Path: /languages/
 *
 * Copyright 2017 Casper Mekel
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WcRipple')) {

    class WcRipple
    {

        private static $instance;
        public static $version = '0.0.3';
        public static $plugin_basename;
        public static $plugin_path;
        public static $plugin_url;

        protected function __construct()
        {
            self::$plugin_basename = plugin_basename(__FILE__);
            self::$plugin_path     = trailingslashit(dirname(__FILE__));
            self::$plugin_url      = plugin_dir_url(self::$plugin_basename);
            add_action('plugins_loaded', array($this, 'init'));
        }

        public static function getInstance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function init()
        {
            $this->initGateway();
        }

        public function initGateway()
        {

            if (!class_exists('WC_Payment_Gateway')) {
                return;
            }

            if (class_exists('WC_Ripple_Gateway')) {
                return;
            }

            /*
             * Include gateway classes
             * */
            include_once plugin_basename('includes/class-ripple-gateway.php');
            include_once plugin_basename('includes/class-ripple-api.php');
            include_once plugin_basename('includes/class-ripple-exchange.php');
            include_once plugin_basename('includes/class-ripple-settings.php');
            include_once plugin_basename('includes/class-ripple-ajax.php');

            add_filter('woocommerce_payment_gateways', array($this, 'addToGateways'));

            // add_filter('woocommerce_currencies', array('WcRippleGateway', 'addToCurrencies'));
            // add_filter('woocommerce_currency_symbol', array('WcRippleGateway', 'addCurrencySymbol'), 10, 2);

            add_filter('woocommerce_get_price_html', array($this, 'filterPriceHtml'), 10, 2);
            add_filter('woocommerce_cart_item_price', array($this, 'filterCartItemPrice'), 10, 3);
            add_filter('woocommerce_cart_item_subtotal', array($this, 'filterCartItemSubtotal'), 10, 3);
            add_filter('woocommerce_cart_subtotal', array($this, 'filterCartSubtotal'), 10, 3);
            add_filter('woocommerce_cart_totals_order_total_html', array($this, 'filterCartTotal'), 10, 1);

        }

        public static function addToGateways($gateways)
        {
            $gateways['ripple'] = 'WcRippleGateway';
            return $gateways;
        }

        public function filterCartTotal($value)
        {
            $total = WC()->cart->total;
            $value = $this->convertToXrp($value, $total);
            return $value;
        }
        public function filterCartSubtotal($cart_subtotal, $compound, $that)
        {
            $cart_subtotal = $this->convertToXrp($cart_subtotal, $that->subtotal);
            return $cart_subtotal;
        }

        public function filterPriceHtml($price, $that)
        {
            $price = $this->convertToXrp($price, $that->price);
            return $price;
        }

        public function filterCartItemPrice($price, $cart_item, $cart_item_key)
        {
            $price = $this->convertToXrp($price, ($cart_item['line_subtotal'] + $cart_item['line_subtotal_tax']) / $cart_item['quantity']);
            return $price;
        }

        public function filterCartItemSubtotal($price, $cart_item, $cart_item_key)
        {
            $price = $this->convertToXrp($price, $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax']);
            return $price;
        }

        public function convertToXrp($price_string, $price)
        {
            $currency = get_woocommerce_currency();

            $options = get_option('woocommerce_ripple_settings');

            if ($options['show_prices'] == 'yes') {

                $xrp_price = round(RippleExchange::convert($currency, $price), 2, PHP_ROUND_HALF_UP);
                // subtract discount
                if (is_numeric($options['discount']) && $options['discount'] > 0) {
                    $xrp_price -= ($xrp_price * $options['discount'] / 100);
                }
                if ($xrp_price) {
                    $new_price_string = $price_string . '&nbsp;(<span class="woocommerce-price-amount amount">' . $xrp_price . '&nbsp;</span><span class="woocommerce-price-currencySymbol">XRP)</span>';
                    return $new_price_string;
                }
            }

            return $price_string;
        }
    }

}

WcRipple::getInstance();
