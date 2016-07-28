<?php
/*
 *  Plugin Name: WooCommerce Payfull Gateway
 *  Plugin URI: https://www.payfull.com
 *  Description: Integrate PayFull payment service with WooCommerce checkout
 *  Text Domain: payfull
 *  Domain Path: /i18n/languages/
 *  Version: 1.0.0
 *  Author: Houmam WAZZEH <houmam@payfull.com>
 *  Author URI: https://www.payfull.com
 * */

ini_set('display_errors', 1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function woo_gateway_payfull_init() {
    if(!defined('WOOCOMMERCE_VERSION')) {
        throw new \Exception('The WooCommerce is not activated.');
    }
    
    require_once dirname(__FILE__).'/src/WC_Gateway_Payfull.php';
    $instance = new WC_Gateway_Payfull(false);
    $instance->initApiService();
}


function woo_gateway_payfull_add_class( $methods ) {
	$methods[] = 'WC_Gateway_Payfull';
    if (!class_exists('WC_Gateway_Payfull')) {
        return [];
    }
	return $methods;
}

function woo_gateway_payfull_activate() {
	global $user_ID;
    $new_post = array(
		'post_title' => 'Payfull Payment Result',
		'post_content' => '[payfull_payment_result]',
		'post_status' => 'publish',
		'post_date' => date('Y-m-d H:i:s'),
		'post_author' => $user_ID,
		'post_type' => 'page',
		'post_category' => array(0)
	);
	$post_id = wp_insert_post($new_post);
	update_option('woo_payfull_payment_result_page_id', $post_id);
}

function woo_gateway_payfull_deactivate() {
	$pid = get_option('woo_payfull_payment_result_page_id', null);
	if($pid) {
		wp_trash_post( $pid );
	}
}

function woo_gateway_payfull_payment_result_shortcode( $atts ) {
	$html[] =  "woo_gateway_payfull_payment_result_shortcode";
    $html[] = "<pre>";
    $html[] = print_r($_GET, 1);
    $html[] = "</pre>";
    return implode('', $html);
}


add_action('init', 'woo_gateway_payfull_init', 0);
add_filter( 'woocommerce_payment_gateways', 'woo_gateway_payfull_add_class' );
register_activation_hook( __FILE__, 'woo_gateway_payfull_activate' );
register_deactivation_hook( __FILE__, 'woo_gateway_payfull_deactivate' );
add_shortcode( 'payfull_payment_result', 'woo_gateway_payfull_payment_result_shortcode' );
