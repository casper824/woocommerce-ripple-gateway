<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gateway class
 */
class WcRippleGateway extends WC_Payment_Gateway
{
    public $id;
    public $title;
    public $form_fields;
    public $addresses;

    public function __construct()
    {

        $this->id          			= 'ripple';
        $this->title       			= $this->get_option('title');
        $this->description 			= $this->get_option('description');
        $this->address   			= $this->get_option('address');
        $this->secret   			= $this->get_option('secret');
        $this->test_mode   			= $this->get_option('test_mode');
        $this->order_button_text 	= __('Awaiting transfer..','woocommerce-ripple-gateway');
        $this->has_fields 			= true;

        $this->initFormFields();

        $this->initSettings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options',
        ));
        add_action('wp_enqueue_scripts', array($this, 'paymentScripts'));

        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyouPage'));        

    }

    public function initFormFields()
    {
        parent::init_form_fields();
        $this->form_fields = RippleSettings::fields();
    }

    public function initSettings()
    {
    	// sha1( get_bloginfo() )
        parent::init_settings();
    }
   
    public function payment_fields()
    {
    	global $woocommerce;
    	$woocommerce->cart->get_cart();

        $user       = wp_get_current_user();
        // print_r($woocommerce->cart->get_order());
        $total_converted = RippleExchange::convert(get_woocommerce_currency(), $this->get_order_total());

        $destination_tag = hexdec( substr(sha1( Date('u') . key ($woocommerce->cart->cart_contents )  ), 0, 7) );

        // set session data 
        WC()->session->set('ripple_payment_total', $total_converted);
        WC()->session->set('ripple_destination_tag', $destination_tag);
        WC()->session->set('ripple_data_hash', sha1( $this->secret . $destination_tag . $total_converted ));

        echo '<div id="ripple-form">';
        //QR uri
		$url = "https://ripple.com//send?to=". $this->address ."&dt=". $destination_tag ."&amount=". $total_converted;

        echo '<div class="ripple-container">';
        echo '<label class="ripple-label"><img class="ripple-logo" src="'. plugins_url('assets/images/ripple_logo.png', WcRipple::$plugin_basename) .'"/ width="200px"></label>';
        echo '<div>';

        
        if ($this->description) {
        	echo '<div class="separator"></div>';
        	echo '<div id="ripple-description">';
            echo apply_filters( 'wc_ripple_description', wpautop(  $this->description ) );
            echo '</div>';
        }


        echo '<div class="separator"></div>';
        echo '<div class="ripple-container">';
        echo '<label class="ripple-label">' . __('amount', 'woocommerce-ripple-gateway') . ' (1 '. get_woocommerce_currency() .' = '.RippleExchange::convert(get_woocommerce_currency(),1) .' XRP)</label>';
        echo '<p class="ripple-amount"><span class="copy" data-success-label="'. __('copied','woocommerce-ripple-gateway') .'" data-clipboard-text="' . esc_attr($total_converted) . '">' . esc_attr($total_converted) . '</span></p>';
        echo '</div>';
        echo '</div>';
        echo '<div class="separator"></div>';
        echo '<div class="ripple-container">';
        echo '<label class="ripple-label">' . __('destination address', 'woocommerce-ripple-gateway') . '</label>';
        echo '<p class="ripple-address"><span class="copy" data-success-label="'. __('copied','woocommerce-ripple-gateway') .'" data-clipboard-text="' . esc_attr($this->address) . '">' . esc_attr($this->address) . '</span></p>';
        echo '</div>';
        echo '<div class="separator"></div>';
        echo '<div class="ripple-container">';
        echo '<label class="ripple-label">' . __('destination tag', 'woocommerce-ripple-gateway') . '</label>';
        echo '<p class="ripple-address"><span class="copy" data-success-label="'. __('copied','woocommerce-ripple-gateway') .'" data-clipboard-text="' . esc_attr($destination_tag) . '">' . esc_attr($destination_tag) . '</span></p>';
        echo '</div>';
        echo '<div class="separator"></div>';

        echo '</div>';
        echo '<div id="ripple-qr-code" data-contents="'. $url .'"></div>';
        echo '<div class="separator"></div>';
        echo '<div class="ripple-container">';
        echo '<p>'. sprintf(__('Send a payment of exactly %s to the address above (click the links to copy or scan the QR code). We will check in the background and notify you when the payment has been validated.', 'woocommerce-ripple-gateway'), '<strong>'. esc_attr($total_converted) .'</strong>' ) .'</p>';
        echo '<p>'. sprintf(__('Please send your payment within %s.', 'woocommerce-ripple-gateway'), '<strong><span class="ripple-countdown" data-minutes="10">10:00</span></strong>' ) .'</p>';
        echo '<p class="small">'. __('When the timer reaches 0 this form will refresh and update the destination tag as well as the total amount using the latest conversion rate.', 'woocommerce-ripple-gateway') .'</p>';
        echo '</div>';
        
        echo '<input type="hidden" name="tx_hash" id="tx_hash" value="0"/>';
        echo '</div>';

    }

    public function process_payment( $order_id ) 
    {
    	global $woocommerce;
	    $order = wc_get_order( $order_id );
	    $woocommerce->cart->get_cart();
        
	    // Mark as on-hold
	    $order->update_status( 'on-hold', __( 'Awaiting payment', 'wc-gateway-offline' ) );

	    $payment_total   = WC()->session->get('ripple_payment_total');
        $destination_tag = WC()->session->get('ripple_destination_tag');

        if (sha1($this->secret . $destination_tag . $payment_total) != WC()->session->get('ripple_data_hash')) {
            exit('sha1');
            return
                array(
                    'result' => 'failure',
                    'messages' => 'possible fraud attempt',
                );
        }

	    $ra = new RippleApi($this->address);
	    $transaction = $ra->getTransaction( $_POST['tx_hash']);

	    if($transaction->transaction->tx->DestinationTag != $destination_tag){
	    	exit('destination');
	    	return array(
		        'result'    => 'failure',
		        'messages' 	=> 'destination_tag mismatch'
		    );
	    }

	    if($transaction->transaction->tx->Amount != ($payment_total * 1000000) && $this->test_mode == 'no'){
	    	return array(
		        'result'    => 'failure',
		        'messages' 	=> 'amount mismatch'
		    );
	    }

	   	update_post_meta($order_id, 'ripple_tx_hash', $transaction->transaction->hash);
	   	update_post_meta($order_id, 'ripple_destination_tag', $transaction->transaction->tx->DestinationTag);
	   	update_post_meta($order_id, 'ripple_account', $transaction->transaction->tx->Account);
	   	update_post_meta($order_id, 'ripple_amount', $transaction->transaction->tx->Amount);
	   	update_post_meta($order_id, 'ripple_date', $transaction->transaction->date);
	            
	    // Reduce stock levels
	    $order->reduce_order_stock();

        //Mark as paid
        $order->payment_complete();

        // Remove cart
        $woocommerce->cart->empty_cart();
	            
	    // Return thankyou redirect
	    return array(
	        'result'    => 'success',
	        'redirect'  => $this->get_return_url( $order )
	    );
	}

    public function paymentScripts()
    {
    	
        wp_enqueue_script('qrcode', plugins_url('assets/js/jquery.qrcode.min.js', WcRipple::$plugin_basename), array('jquery'), WcRipple::$version, true);
        wp_enqueue_script('initialize', plugins_url('assets/js/jquery.initialize.js', WcRipple::$plugin_basename), array('jquery'), WcRipple::$version, true);
        
        wp_enqueue_script('clipboard', plugins_url('assets/js/clipboard.js', WcRipple::$plugin_basename), array('jquery'), WcRipple::$version, true);
        wp_enqueue_script('woocommerce_ripple_js', plugins_url('assets/js/ripple.js', WcRipple::$plugin_basename), array(
            'jquery',
        ), WcRipple::$version, true);
        wp_enqueue_style('woocommerce_ripple_css', plugins_url('assets/css/ripple.css', WcRipple::$plugin_basename), array(), WcRipple::$version);

        // //Add js variables
        $ripple_vars = array(
            'wc_ajax_url' => WC()->ajax_url(),
            'nonce'      => wp_create_nonce("woocommerce-ripple-gateway"),
        );

        wp_localize_script('woocommerce_ripple_js', 'ripple_vars', apply_filters('ripple_vars', $ripple_vars));

    }

}
