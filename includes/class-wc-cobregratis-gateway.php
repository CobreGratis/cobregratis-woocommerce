<?php
/**
 * Cobre Grátis for WooCommerce.
 *
 * @package   WC_Cobregratis
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 * @copyright 2013 Cobre Grátis
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
		$this->method_description = __( 'Start getting money by bank billet in your checking account using Cobre Gr&aacute;tis.', $this->plugin_slug );

		// API.
		$this->api_url = 'https://app.cobregratis.com.br/';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title         = $this->get_option( 'title' );
		$this->description   = $this->get_option( 'description' );
		$this->token         = $this->get_option( 'token' );
		$this->code          = $this->get_option( 'code' );
		$this->days_to_pay   = $this->get_option( 'days_to_pay', 5 );
		$this->demonstrative = $this->get_option( 'demonstrative' );
		$this->instructions  = $this->get_option( 'instructions' );
		$this->fines         = $this->get_option( 'fines' );
		$this->interest_day  = $this->get_option( 'interest_day' );
		$this->notification  = $this->get_option( 'notification' );
		$this->debug         = $this->get_option( 'debug' );

		// Actions.
		add_action( 'woocommerce_api_wc_cobregratis_gateway', array( $this, 'check_webhook_notification' ) );
		add_action( 'woocommerce_cobregratis_webhook_notification', array( $this, 'successful_webhook_notification' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 2 );

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
			// Checks if token is not empty.
			if ( empty( $this->token ) ) {
				add_action( 'admin_notices', array( $this, 'token_missing_message' ) );
			}

			// Checks if security code is not empty.
			if ( empty( $this->code ) ) {
				add_action( 'admin_notices', array( $this, 'code_missing_message' ) );
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
					! empty( $this->token ) &&
					$this->using_supported_currency();

		return $available;
	}

	/**
	 * Add error message in checkout.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $message Error message.
	 *
	 * @return string          Displays the error message.
	 */
	protected function add_error( $message ) {
		if ( version_compare( $this->woocommerce_instance()->version, '2.1', '>=' ) ) {
			wc_add_notice( $message, 'error' );
		} else {
			$this->woocommerce_instance()->add_error( $message );
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @since  1.0.0
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
			'token' => array(
				'title'       => __( 'Cobre Gr&aacute;tis Token', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'Please enter your Cobre Gr&aacute;tis token. This is needed to process the payment.', $this->plugin_slug ) . '<br />' . sprintf( __( 'You can generate a token by clicking %s.', $this->plugin_slug ), '<a href="https://app.cobregratis.com.br/myinfo" target="_blank">' . __( 'here', $this->plugin_slug ) . '</a>' ),
				'default'     => ''
			),
			'code' => array(
				'title'       => __( 'Webhook security code', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'Please enter your notification security code. This is needed to receive notifications when the billet is paid.', $this->plugin_slug ) . '<br />' . sprintf( __( 'You can configure a notification by clicking %s.', $this->plugin_slug ), '<a href="https://app.cobregratis.com.br/web_hooks/new" target="_blank">' . __( 'here', $this->plugin_slug ) . '</a>' ) . '<br />' . sprintf( __( 'Be sure to set the %s url for your notification.', $this->plugin_slug ), '<code>' . home_url( '/?wc-api=WC_Cobregratis_Gateway' ) . '</code>' ),
				'default'     => ''
			),
			'options' => array(
				'title'       => __( 'Billet options', $this->plugin_slug ),
				'type'        => 'title',
				'description' => ''
			),
			'days_to_pay' => array(
				'title'       => __( 'Days to pay', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'Enter with the number of days the customer will have to pay the billet.', $this->plugin_slug ),
				'desc_tip'    => true,
				'default'     => '5'
			),
			'demonstrative' => array(
				'title'       => __( 'Demonstrative', $this->plugin_slug ),
				'type'        => 'textarea',
				'default'     => ''
			),
			'instructions' => array(
				'title'       => __( 'Cashier instructions', $this->plugin_slug ),
				'type'        => 'textarea',
				'default'     => ''
			),
			'fines' => array(
				'title'       => __( 'Fines percentage', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'Enter with an integer.', $this->plugin_slug ),
				'desc_tip'    => true,
				'default'     => ''
			),
			'interest_day' => array(
				'title'       => __( 'Percentage of interest per day', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'Enter with an integer.', $this->plugin_slug ),
				'desc_tip'    => true,
				'default'     => ''
			),
			'notification' => array(
				'title'       => __( 'Non-payment notification', $this->plugin_slug ),
				'type'        => 'checkbox',
				'description' => __( 'If the ticket has not been paid after 2 days of winning you will receive a notification in your Cobre Gr&aacute;tis email.', $this->plugin_slug ),
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
				'description' => sprintf( __( 'Log Cobre Gr&aacute;tis events, such as API requests, inside %s', $this->plugin_slug ), '<code>woocommerce/logs/' . $this->id . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.txt</code>' )
			)
		);
	}

	/**
	 * Create the payment data.
	 *
	 * @since  1.0.0
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return array           Payment data.
	 */
	protected function payment_data( $order ) {
		$args = array(
			// Customer data.
			'name'                 => $order->billing_first_name . ' ' . $order->billing_last_name,

			// Order data.
			'amount'               => number_format( $order->order_total, 2, ',', '' ),
			'expire_at'            => date( 'd/m/Y', time() + ( $this->days_to_pay * 86400 ) ),

			// Document data.
			'quantity'             => 1,
			'document_amount'      => number_format( $order->order_total, 2, ',', '' ),
			'document_date'        => date( 'd/m/Y' ),
			'document_number'      => ltrim( $order->get_order_number(), '#' ),
			'document_type'	       => 'FAT',
			'description'          => $this->demonstrative,
			'instructions'         => $this->instructions,
			'percent_fines'        => $this->fines,
			'percent_interest_day' => $this->interest_day,

			// Meta for IPN.
			'meta'                 => 'order-' . $this->id
		);

		// WooCommerce Extra Checkout Fields for Brazil person type fields.
		if ( isset( $order->billing_persontype ) && ! empty( $order->billing_persontype ) ) {
			if ( 2 == $order->billing_persontyp ) {
				$args['cnpj_cpf'] = $order->billing_cnpj;
			} else {
				$args['cnpj_cpf'] = $order->billing_cpf;
			}
		}

		// Address.
		if ( isset( $order->billing_postcode ) && ! empty( $order->billing_postcode ) ) {
			$args['address'] = $order->billing_address_1;
			$args['city']    = $order->billing_city;
			$args['state']   = $order->billing_state;
			$args['zipcode'] = $order->billing_postcode;

			// WooCommerce Extra Checkout Fields for Brazil neighborhood field.
			if ( isset( $order->billing_neighborhood ) && ! empty( $order->billing_neighborhood ) ) {
				$args['neighborhood'] = $order->billing_neighborhood;
			}

			// WooCommerce Extra Checkout Fields for Brazil number field.
			if ( isset( $order->billing_number ) && ! empty( $order->billing_number ) ) {
				$args['address'] .= ', ' . $order->billing_number;
			}

			// Address complement.
			if ( ! empty( $order->billing_address_2 ) ) {
				$args['address'] .= ', ' . $order->billing_address_2;
			}
		}

		// Notification.
		if ( 'yes' == $this->notification ) {
			$args['notify_overdue'] = true;
		}

		// Sets a filter for custom arguments.
		$args = apply_filters( 'woocommerce_cobregratis_billet_data', $args, $order );

		return $args;
	}

	/**
	 * Generate the billet on Cobre Grátis.
	 *
	 * @since  1.0.0
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return bool           Fail or success.
	 */
	protected function generate_billet( $order ) {
		$url  = $this->api_url . 'bank_billets.json';
		$body = $this->payment_data( $order );

		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Creating billet for order ' . $order->get_order_number() . ' with the following data: ' . print_r( $body, true ) );
		}

		$params = array(
			'method'     => 'POST',
			'body'       => json_encode( $body ),
			'sslverify'  => false,
			'timeout'    => 60,
			'headers'    => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $this->token . ':X' )
			)
		);

		$response = wp_remote_post( $url, $params );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'WP_Error in generate the billet: ' . $response->get_error_message() );
			}
		} elseif ( 201 == $response['response']['code'] && 'Created' == $response['response']['message'] ) {
			try {
				$data = json_decode( $response['body'] );
			} catch ( Exception $e ) {
				$data = '';

				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error while parsing the Cobre Gratis response: ' . print_r( $e->getMessage(), true ) );
				}
			}
      
			if ( isset( $data->id ) ) {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Billet created with success! The ID is: ' . $data->id );
				}

				// Save billet data in order meta.
				add_post_meta( $order->id, 'cobregratis_id', $data->id );
				add_post_meta( $order->id, 'cobregratis_url', $data->external_link );

				return true;
			}
		}

		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Request error: ' . print_r( $response, true ) );
		}

		return false;
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @since  1.0.0
	 *
	 * @param  int    $order_id Order ID.
	 *
	 * @return array            Redirect when has success and display error notices when fail.
	 */
	public function process_payment( $order_id ) {
		// Gets the order data.
		$order = new WC_Order( $order_id );

		// Generate the billet.
		$billet = $this->generate_billet( $order );

		if ( $billet ) {
			// Mark as on-hold (we're awaiting the payment).
			$order->update_status( 'on-hold', __( 'Awaiting billet payment.', $this->plugin_slug ) );

			// Reduce stock levels.
			$order->reduce_order_stock();

			// Remove cart.
			$this->woocommerce_instance()->cart->empty_cart();

			// Sets the return url.
			if ( version_compare( $this->woocommerce_instance()->version, '2.1', '>=' ) ) {
				$url = $order->get_checkout_order_received_url();
			} else {
				$url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'thanks' ) ) ) );
			}

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $url
			);
		} else {
			// Added error message.
			$this->add_error( '<strong>' . $this->title . '</strong>: ' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', $this->plugin_slug ) );

			return array(
				'result' => 'fail'
			);
		}
	}

	/**
	 * Adds payment instructions on thankyou page.
	 *
	 * @since  1.0.0
	 *
	 * @param  int    $order_id Order ID.
	 *
	 * @return string           Payment instructions.
	 */
	public function thankyou_page( $order_id ) {
		$url = get_post_meta( $order_id, 'cobregratis_url', true );

		$html = '<div class="woocommerce-message">';
		$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', $url, __( 'Billet print', $this->plugin_slug ) );

		$message = sprintf( __( '%sAttention!%s You will not get the billet by Correios.', $this->plugin_slug ), '<strong>', '</strong>' ) . '<br />';
		$message .= __( 'Please click the following button and pay the billet in your Internet Banking.', $this->plugin_slug ) . '<br />';
		$message .= __( 'If you prefer, print and pay at any bank branch or home lottery.', $this->plugin_slug ) . '<br />';

		$html .= apply_filters( 'woocommerce_cobregratis_thankyou_page_instructions', $message, $order_id );

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Adds payment instructions on customer email.
	 *
	 * @since  1.0.0
	 *
	 * @param  WC_Order $order         Order data.
	 * @param  bool     $sent_to_admin Sent to admin.
	 *
	 * @return string                  Payment instructions.
	 */
	public function email_instructions( $order, $sent_to_admin ) {
		if ( $sent_to_admin || $order->status !== 'on-hold' || $order->payment_method !== $this->id ) {
			return;
		}

		$html = '<h2>' . __( 'Payment', $this->plugin_slug ) . '</h2>';

		$html .= '<p class="order_details">';

		$message = sprintf( __( '%sAttention!%s You will not get the billet by Correios.', $this->plugin_slug ), '<strong>', '</strong>' ) . '<br />';
		$message .= __( 'Please click the following link and pay the billet in your Internet Banking.', $this->plugin_slug ) . '<br />';
		$message .= __( 'If you prefer, print and pay at any bank branch or home lottery.', $this->plugin_slug ) . '<br />';

		$html .= apply_filters( 'woocommerce_cobregratis_email_instructions', $message, $order );

		$html .= '<br />' . sprintf( '<a class="button" href="%s" target="_blank">%s</a>', get_post_meta( $order->id, 'cobregratis_url', true ), __( 'Billet print &rarr;', $this->plugin_slug ) ) . '<br />';

		$html .= '</p>';

		echo $html;
	}

	/**
	 * Check API Response.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function check_webhook_notification() {
		@ob_clean();

		if ( isset( $_POST['service_code'] ) && $this->code == $_POST['service_code'] ) {
			header( 'HTTP/1.1 200 OK' );
			do_action( 'woocommerce_cobregratis_webhook_notification', $_POST );
		} else {
			wp_die( __( 'Cobre Gr&aacute;tis request failure', $this->plugin_slug ) );
		}
	}

	/**
	 * Successful notification.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $data $_POST data from the webhook.
	 *
	 * @return void        Updated the order status to processing.
	 */
	public function successful_webhook_notification( $data ) {
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Received the notification with the following data: ' . print_r( $data, true ) );
		}

		$order_id = intval( str_replace( 'order-', '', $data['meta'] ) );
		$order = new WC_Order( $order_id );

		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Updating to processing the status of the order ' . $order->get_order_number() );
		}

		// Complete the order.
		$order->add_order_note( __( 'Cobre Gr&aacute;tis: Payment approved.', $this->plugin_slug ) );
		$order->payment_complete();
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
	 * Adds error message when not configured the token.
	 *
	 * @since  1.0.0
	 *
	 * @return string Error Mensage.
	 */
	public function token_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'Cobre Gr&aacute;tis', $this->plugin_slug ) . '</strong>: ' . sprintf( __( 'You should inform your token. %s', $this->plugin_slug ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', $this->plugin_slug ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Adds error message when not configured the security code.
	 *
	 * @since  1.0.0
	 *
	 * @return string Error Mensage.
	 */
	public function code_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'Cobre Gr&aacute;tis', $this->plugin_slug ) . '</strong>: ' . sprintf( __( 'You should inform your notification security code. %s', $this->plugin_slug ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', $this->plugin_slug ) . '</a>' ) . '</p></div>';
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
