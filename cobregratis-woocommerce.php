<?php
/**
 * Cobre Grátis for WooCommerce.
 *
 * @package   WC_Cobregratis
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 * @copyright 2013 Cobre Grátis
 *
 * @wordpress-plugin
 * Plugin Name:       Cobre Grátis for WooCommerce
 * Plugin URI:        https://github.com/Cobre Grátis/cobregratis-woocommerce
 * Description:       Start getting money by bank billet in your checking account using Cobre Grátis
 * Version:           1.0.1
 * Author:            Cobre Grátis, claudiosanches
 * Author URI:        http://cobregratis.com.br/
 * Text Domain:       cobregratis-woocommerce
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/Cobre Grátis/cobregratis-woocommerce
 */

/**
 * WooCommerce is missing notice.
 *
 * @since  1.0.0
 *
 * @return string WooCommerce is missing notice.
 */
function wc_cobregratis_woocommerce_is_missing() {
	echo '<div class="error"><p>' . sprintf( __( 'Cobre Gr&aacute;tis for WooCommerce depends on the last version of %s to work!', 'cobregratis-woocommerce' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __( 'WooCommerce', 'cobregratis-woocommerce' ) . '</a>' ) . '</p></div>';
}

/**
 * Initialize the Cobre Grátis gateway.
 *
 * @since  1.0.0
 *
 * @return void
 */
function wc_cobregratis_gateway_init() {

	// Checks with WooCommerce is installed.
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		add_action( 'admin_notices', 'wc_cobregratis_woocommerce_is_missing' );

		return;
	}

	/**
	 * Load textdomain.
	 */
	load_plugin_textdomain( 'cobregratis-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * Add the Cobre Grátis gateway to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 *
	 * @return array          Payment methods with Cobre Grátis.
	 */
	function wc_cobregratis_add_gateway( $methods ) {
		$methods[] = 'WC_Cobregratis_Gateway';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'wc_cobregratis_add_gateway' );

	// Include the WC_Cobregratis_Gateway class.
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-cobregratis-gateway.php';
}

add_action( 'plugins_loaded', 'wc_cobregratis_gateway_init', 0 );

/**
 * Hides the Cobre Grátis with payment method with the customer lives outside Brazil.
 *
 * @param  array $available_gateways Default Available Gateways.
 *
 * @return array                     New Available Gateways.
 */
function wc_cobregratis_hides_when_is_outside_brazil( $available_gateways ) {

	// Remove standard shipping option.
	if ( isset( $_REQUEST['country'] ) && 'BR' != $_REQUEST['country'] ) {
		unset( $available_gateways['cobregratis'] );
	}

	return $available_gateways;
}

add_filter( 'woocommerce_available_payment_gateways', 'wc_cobregratis_hides_when_is_outside_brazil' );

/**
 * Display pending payment instructions in order details.
 *
 * @param  int $order_id Order ID.
 *
 * @return string        Message HTML.
 */
function wc_cobregratis_pending_payment_instructions( $order_id ) {
	$order = new WC_Order( $order_id );

	if ( 'on-hold' === $order->status && 'cobregratis' == $order->payment_method ) {
		$html = '<div class="woocommerce-info">';
		$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', get_post_meta( $order->id, 'cobregratis_url', true ), __( 'Billet print', 'cobregratis-woocommerce' ) );

		$message = sprintf( __( '%sAttention!%s Not registered the billet payment for this order yet.', 'cobregratis-woocommerce' ), '<strong>', '</strong>' ) . '<br />';
		$message .= __( 'Please click the following button and pay the billet in your Internet Banking.', 'cobregratis-woocommerce' ) . '<br />';
		$message .= __( 'If you prefer, print and pay at any bank branch or home lottery.', 'cobregratis-woocommerce' ) . '<br />';
		$message .= __( 'Ignore this message if the payment has already been made​​.', 'cobregratis-woocommerce' ) . '<br />';

		$html .= apply_filters( 'woocommerce_cobregratis_pending_payment_instructions', $message, $order );

		$html .= '</div>';

		echo $html;
	}
}

add_action( 'woocommerce_view_order', 'wc_cobregratis_pending_payment_instructions' );
