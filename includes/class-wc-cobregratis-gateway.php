<?php
/**
 * Cobre Grátis for WooCommerce.
 *
 * @package   WC_Cobregratis
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 * @copyright 2013 BielSystems
 */

/**
 * Cobra Grátis payment gateway class.
 *
 * @package WC_Cobregratis_Gateway
 * @author  Claudio Sanches <contato@claudiosmweb.com>
 * @since   1.0.0
 */
class WC_Cobregratis_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->id                 = 'cobregratis';
		$this->plugin_slug        = 'cobregratis-woocommerce';
		$this->icon               = apply_filters( 'woocommerce_cobregratis_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = __( 'Cobre Gr&aacute;tis', $this->plugin_slug );
		$this->method_description = __( 'Start getting money by bank billet in your checking account using Cobre Grátis.', $this->plugin_slug );

		// API.
		$this->api_url = 'https://app.cobregratis.com.br/';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->username    = $this->get_option( 'username' );
		$this->password    = $this->get_option( 'password' );
		$this->debug       = $this->get_option( 'debug' );

		// Actions.
		// add_action( 'woocommerce_api_wc_cobregratis_gateway', array( $this, 'check_ipn_response' ) );
		// add_action( 'valid_cobregratis_ipn_request', array( $this, 'successful_request' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $this->woocommerce_instance()->logger();
			}
		}

		// Display admin notices.
		$this->admin_notices();
	}

	/**
	 * Backwards compatibility with version prior to 2.1.
	 *
	 * @since  1.0.0
	 *
	 * @return object Returns the main instance of WooCommerce class.
	 */
	protected function woocommerce_instance() {
		if ( function_exists( 'WC' ) ) {
			return WC();
		} else {
			global $woocommerce;
			return $woocommerce;
		}
	}

	/**
	 * Displays notifications when the admin has something wrong with the configuration.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	protected function admin_notices() {
		if ( is_admin() ) {
			// Checks if username is not empty.
			if ( empty( $this->username ) ) {
				add_action( 'admin_notices', array( $this, 'username_missing_message' ) );
			}

			// Checks if password is not empty.
			if ( empty( $this->password ) ) {
				add_action( 'admin_notices', array( $this, 'password_missing_message' ) );
			}

			// Checks that the currency is supported.
			if ( ! $this->using_supported_currency() ) {
				add_action( 'admin_notices', array( $this, 'currency_not_supported_message' ) );
			}
		}
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @since  1.0.0
	 *
	 * @return bool
	 */
	protected function using_supported_currency() {
		return ( get_woocommerce_currency() == 'BRL' );
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @since  1.0.0
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		$available = ( 'yes' == $this->get_option( 'enabled' ) ) &&
					! empty( $this->username ) &&
					! empty( $this->password ) &&
					$this->using_supported_currency();

		return $available;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', $this->plugin_slug ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Cobre Gr&aacute;tis', $this->plugin_slug ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', $this->plugin_slug ),
				'desc_tip'    => true,
				'default'     => __( 'Bank billet', $this->plugin_slug )
			),
			'description' => array(
				'title'       => __( 'Description', $this->plugin_slug ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', $this->plugin_slug ),
				'default'     => __( 'Pay using bank billet', $this->plugin_slug )
			),
			'username' => array(
				'title'       => __( 'Cobre Gr&aacute;tis Username', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'Please enter your Cobre Gr&aacute;tis username. This is needed in order to take payment.', $this->plugin_slug ),
				'desc_tip'    => true,
				'default'     => ''
			),
			'password' => array(
				'title'       => __( 'Cobre Gr&aacute;tis Password', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'Please enter your Cobre Gr&aacute;tis password. This is needed to process the payment.', $this->plugin_slug ),
				'desc_tip'    => true,
				'default'     => ''
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', $this->plugin_slug ),
				'type'        => 'title',
				'description' => ''
			),
			'debug' => array(
				'title'       => __( 'Debug Log', $this->plugin_slug ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', $this->plugin_slug ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Cobre Gr&aacute;tis events, such as API requests, inside %s', $this->plugin_slug ), '<code>woocommerce/logs/cobregratis-' . sanitize_file_name( wp_hash( 'cobregratis' ) ) . '.txt</code>' )
			)
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @since  1.0.0
	 *
	 * @param  int    $order_id Order ID.
	 *
	 * @return array            TODO.
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
	}

	/**
	 * Gets the admin url.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	protected function admin_url() {
		if ( version_compare( $this->woocommerce_instance()->version, '2.1', '>=' ) ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_cobregratis_gateway' );
		}

		return admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Cobregratis_Gateway' );
	}

	/**
	 * Adds error message when not configured the username.
	 *
	 * @since  1.0.0
	 *
	 * @return string Error Mensage.
	 */
	public function username_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'Cobre Gr&aacute;tis', $this->plugin_slug ) . '</strong>: ' . sprintf( __( 'You should inform your username. %s', $this->plugin_slug ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', $this->plugin_slug ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Adds error message when not configured the password.
	 *
	 * @since  1.0.0
	 *
	 * @return string Error Mensage.
	 */
	public function password_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'Cobre Gr&aacute;tis', $this->plugin_slug ) . '</strong>: ' . sprintf( __( 'You should inform your password. %s', $this->plugin_slug ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', $this->plugin_slug ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Adds error message when an unsupported currency is used.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function currency_not_supported_message() {
		echo '<div class="error"><p><strong>' . __( 'Cobre Gr&aacute;tis', $this->plugin_slug ) . '</strong>: ' . sprintf( __( 'Currency <code>%s</code> is not supported. Works only with Brazilian Real.', $this->plugin_slug ), get_woocommerce_currency() ) . '</p></div>';
	}
}
