<?php
/**
 * Plugin Name: NIF (Num. de Contribuinte Português) for WooCommerce
 * Plugin URI: https://www.webdados.pt/wordpress/plugins/nif-de-contribuinte-portugues-woocommerce-wordpress/
 * Description: This plugin adds the Portuguese VAT identification number (NIF/NIPC) as a new field to WooCommerce checkout and order details, if the billing address is from Portugal.
 * Version: 6.5
 * Author: PT Woo Plugins (by Webdados)
 * Author URI: https://ptwooplugins.com
 * Text Domain: nif-num-de-contribuinte-portugues-for-woocommerce
 * Domain Path: /lang
 * Requires at least: 5.6
 * Tested up to: 6.7
 * Requires PHP: 7.0
 * WC requires at least: 6.0
 * WC tested up to: 9.4
 * Requires Plugins: woocommerce
 **/

/* WooCommerce CRUD ready */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants.
define( 'PTWOO_NIF_VERSION', '5.6' );
define( 'PTWOO_NIF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PTWOO_NIF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( 'WooCommerce' ) && defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '6.0', '>=' ) ) {

			/**
			 * Init, load textdomain and enqueue scripts
			 */
			function woocommerce_nif_init() {
				load_plugin_textdomain( 'nif-num-de-contribuinte-portugues-for-woocommerce' );
				add_action( 'wp_enqueue_scripts', 'woocommerce_nif_billing_fields_enqueue_scripts' );
			}
			add_action( 'init', 'woocommerce_nif_init' );

			/**
			 * Enqueue Javascript - Only on the legacy checkout
			 */
			function woocommerce_nif_billing_fields_enqueue_scripts() {
				if (
					function_exists( 'is_checkout' )
					&&
					is_checkout()
					&&
					( ! has_block( 'woocommerce/checkout' ) ) // Not on the Blocks checkout
					&&
					apply_filters( 'woocommerce_nif_use_javascript', true ) // Default - USE Javascript (since 4.0)
				) {
					if ( ! function_exists( 'get_plugin_data' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					$plugin = get_plugin_data( __FILE__, false, false );
					wp_enqueue_script( 'woocommerce-nif', plugins_url( 'js/functions.js', __FILE__ ), array( 'jquery' ), $plugin['Version'], true );
					wp_localize_script(
						'woocommerce-nif',
						'woocommerce_nif',
						array(
							'show_all_countries' => woocommerce_nif_show_all_countries() ? 1 : 0,
							'validate'           => woocommerce_nif_field_validate() ? 1 : 0,
						)
					);
				}
			}

			/**
			 * Add field to billing address fields - Globally
			 *
			 * @param array  $fields The billing fields.
			 * @param string $country The billing country.
			 */
			function woocommerce_nif_billing_fields( $fields, $country ) {
				$fields['billing_nif'] = array(
					'type'         => 'text',
					'label'        => woocommerce_nif_field_label(),
					'placeholder'  => apply_filters( 'woocommerce_nif_field_placeholder', __( 'Portuguese VAT identification number', 'nif-num-de-contribuinte-portugues-for-woocommerce' ) ),
					'class'        => apply_filters( 'woocommerce_nif_field_class', array( 'form-row-first' ) ), // Should be an option (?)
					'required'     => (
											( $country === 'PT' ) || ( woocommerce_nif_show_all_countries() )
											?
											woocommerce_nif_field_required() // Should be an option (?)
											:
											false
										),
					'clear'        => apply_filters( 'woocommerce_nif_field_clear', true ), // Should be an option (?)
					'autocomplete' => apply_filters( 'woocommerce_nif_field_autocomplete', 'on' ),
					'priority'     => apply_filters( 'woocommerce_nif_field_priority', 120 ), // WooCommerce should order by this parameter but it doesn't seem to be doing so
					'maxlength'    => woocommerce_nif_field_maxlength(),
					'validate'     => (
											$country === 'PT'
											?
											(
												woocommerce_nif_field_validate()
												?
												array( 'nif_pt' ) // Does nothing, actually - Validation is down there on the 'woocommerce_checkout_process' action
												:
												array()
											)
											:
											false
										),
				);
				return $fields;
			}
			add_filter( 'woocommerce_billing_fields', 'woocommerce_nif_billing_fields', 10, 2 );

			/**
			 * Add field to order admin panel
			 *
			 * @param array $billing_fields The billing fields.
			 */
			function woocommerce_nif_admin_billing_fields( $billing_fields ) {
				// HPOS - Start
				global $post, $theorder;
				if ( ! empty( $theorder ) ) {
					$order = $theorder;
				} elseif ( isset( $post ) ) {
					$order = wc_get_order( $post->ID );
				}
				// HPOS - End
				if ( ! empty( $order ) ) {
					$countries       = new WC_Countries();
					$billing_country = $order->get_billing_country();
					// Customer is portuguese or it's a new order ?
					if ( $billing_country === 'PT' || ( $billing_country === '' && $countries->get_base_country() === 'PT' ) || woocommerce_nif_show_all_countries() ) {
						$billing_fields['nif'] = array(
							'label' => woocommerce_nif_field_label(),
						);
					}
				}
				return $billing_fields;
			}
			add_filter( 'woocommerce_admin_billing_fields', 'woocommerce_nif_admin_billing_fields' );

			/**
			 * Add field to ajax billing get_customer_details - See https://github.com/woothemes/woocommerce/commit/5c43b340027fc9dea78e15825f12191768f7d2ed
			 */
			function woocommerce_nif_admin_init_found_customer_details() {
				add_filter( 'woocommerce_ajax_get_customer_details', 'woocommerce_nif_ajax_get_customer_details', 10, 3 );
			}
			add_action( 'admin_init', 'woocommerce_nif_admin_init_found_customer_details' );

			/**
			 * See https://github.com/woocommerce/woocommerce/issues/12654
			 *
			 * @param array   $customer_data The costumer data.
			 * @param object  $customer The customer.
			 * @param integer $user_id The user ID.
			 */
			function woocommerce_nif_ajax_get_customer_details( $customer_data, $customer, $user_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				if ( ( isset( $customer_data['billing']['country'] ) && $customer_data['billing']['country'] === 'PT' ) || woocommerce_nif_show_all_countries() ) {
					$customer_data['billing']['nif'] = $customer->get_meta( 'billing_nif' );
				}
				return $customer_data;
			}

			/**
			 * Add field to the admin user edit screen
			 *
			 * @param array $show_fields The fields.
			 */
			function woocommerce_nif_customer_meta_fields( $show_fields ) {
				if ( isset( $show_fields['billing'] ) && is_array( $show_fields['billing']['fields'] ) ) {
					$show_fields['billing']['fields']['billing_nif'] = array(
						'label'       => woocommerce_nif_field_label(),
						'description' => apply_filters( 'woocommerce_nif_field_placeholder', __( 'Portuguese VAT identification number', 'nif-num-de-contribuinte-portugues-for-woocommerce' ) ),
					);
				}
				return $show_fields;
			}
			add_action( 'woocommerce_customer_meta_fields', 'woocommerce_nif_customer_meta_fields' );

			/**
			 * Add field to customer details on the Thank You page
			 *
			 * @param object $order The WooCommerce order.
			 */
			function woocommerce_nif_order_details_after_customer_details( $order ) {
				$billing_country = $order->get_billing_country();
				$billing_nif     = $order->get_meta( '_billing_nif' );
				if ( ( $billing_country === 'PT' || woocommerce_nif_show_all_countries() ) && $billing_nif ) {
					?>
					<p id="woocommerce_nif_info">
						<span id="woocommerce_nif_info_label">
							<?php echo esc_html( woocommerce_nif_field_label() ); ?>:
						</span>
						<span id="woocommerce_nif_info_value">
							<?php echo esc_html( $billing_nif ); ?>
						</span>
					</p>
					<?php
				}
			}
			add_action( 'woocommerce_order_details_after_customer_details', 'woocommerce_nif_order_details_after_customer_details' );

			/**
			 * Add field to customer details on Emails
			 *
			 * @param array  $fields        The fields shown on email.
			 * @param bool   $sent_to_admin If this email is sent to admin.
			 * @param object $order         The WooCommerce order.
			 */
			function woocommerce_nif_email_customer_details_fields( $fields, $sent_to_admin, $order ) {
				$billing_nif = $order->get_meta( '_billing_nif' );
				if ( $billing_nif ) {
					$fields['billing_nif'] = array(
						'label' => woocommerce_nif_field_label(),
						'value' => wptexturize( $billing_nif ),
					);
				}
				return $fields;
			}
			add_filter( 'woocommerce_email_customer_details_fields', 'woocommerce_nif_email_customer_details_fields', 10, 3 );

			/**
			 * Add field to the REST API - Order
			 *
			 * @param array  $order_data The data sent on the REST API request.
			 * @param object $order      The WooCommerce order.
			 */
			function woocommerce_nif_woocommerce_api_order_response( $order_data, $order ) {
				// Order
				if ( isset( $order_data['billing_address'] ) ) {
					$billing_nif                          = $order->get_meta( '_billing_nif' );
					$order_data['billing_address']['nif'] = $billing_nif;
				}
				return $order_data;
			}
			add_filter( 'woocommerce_api_order_response', 'woocommerce_nif_woocommerce_api_order_response', 11, 2 ); // After WooCommerce own add_customer_data

			/**
			 * Add field to the REST API - Customer
			 *
			 * @param array  $customer_data The data sent on the REST API request.
			 * @param object $customer      The WooCommerce customer.
			 */
			function woocommerce_nif_woocommerce_api_customer_response( $customer_data, $customer ) {
				// Customer
				if ( isset( $customer_data['billing_address'] ) ) {
					$billing_nif                             = $customer->get_meta( 'billing_nif' );
					$customer_data['billing_address']['nif'] = $billing_nif;
				}
				return $customer_data;
			}
			add_filter( 'woocommerce_api_customer_response', 'woocommerce_nif_woocommerce_api_customer_response', 10, 2 );

			/**
			 * Validation - Checkout
			 */
			function woocommerce_nif_checkout_process() {
				if ( woocommerce_nif_field_validate() ) {
					$customer_country = WC()->customer->get_billing_country();
					$countries        = new WC_Countries();
					// If the field is NOT required and it's empty, we shouldn't validate it
					if ( $customer_country === 'PT' || ( $customer_country === '' && $countries->get_base_country() === 'PT' ) ) {
						$billing_nif = wc_clean( isset( $_POST['billing_nif'] ) ? $_POST['billing_nif'] : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, (because wc_clean takes care of it)
						if (
							!
							(
								woocommerce_valida_nif( $billing_nif, true )
								||
								( trim( $billing_nif ) === '' && ! woocommerce_nif_field_required() )
							)
						) {
							wc_add_notice(
								woocommerce_nif_invalid_message(),
								'error',
								array(
									'id' => 'billing_nif',
								)
							);
						}
					} //else {
						// Not Portugal
					// }
				} //else {
					// All good - No validation required
				// }
			}
			add_action( 'woocommerce_checkout_process', 'woocommerce_nif_checkout_process' );

			/**
			 * Validation - Save address
			 *
			 * @param int    $user_id      User ID being saved.
			 * @param string $load_address Type of address e.g. billing or shipping.
			 * @param array  $address      The address fields.
			 */
			function woocommerce_nif_after_save_address_validation( $user_id, $load_address, $address ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				if ( $load_address === 'billing' ) {
					if ( woocommerce_nif_field_validate() ) {
						$country = wc_clean( isset( $_POST['billing_country'] ) ? $_POST['billing_country'] : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, (because wc_clean takes care of it)
						if ( $country === 'PT' ) {
							$billing_nif = wc_clean( isset( $_POST['billing_nif'] ) ? $_POST['billing_nif'] : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, (because wc_clean takes care of it)
							// If the field is NOT required and it's empty, we shouldn't validate it
							if (
								!
								(
									woocommerce_valida_nif( $billing_nif, true )
									||
									( trim( $billing_nif ) === '' && ! woocommerce_nif_field_required() )
								)
							) {
								wc_add_notice(
									/* translators: %s NIF field name */
									sprintf( __( 'You have entered an invalid %s for Portugal.', 'nif-num-de-contribuinte-portugues-for-woocommerce' ), '<strong>' . woocommerce_nif_field_label() . '</strong>' ),
									'error'
								);
							}
						}
					}
				}
			}
			add_action( 'woocommerce_after_save_address_validation', 'woocommerce_nif_after_save_address_validation', 10, 3 );

			/**
			 * NIF Validation
			 *
			 * @param string $nif         The NIF number.
			 * @param bool   $ignore_first Ignore first digit validation or not.
			 */
			function woocommerce_valida_nif( $nif, $ignore_first = true ) {
				// Limpamos eventuais espaços a mais
				$nif = preg_replace( '/\s+/', '', $nif );
				// Verificamos se é numérico e tem comprimento 9
				if ( ! is_numeric( $nif ) || strlen( $nif ) !== 9 || $nif === '000000000' ) {
					return false;
				} else {
					$nif_split = str_split( $nif );
					// O primeiro digíto tem de ser 1, 2, 5, 6, 8 ou 9
					// Ou não, se optarmos por ignorar esta "regra"
					if (
						in_array( $nif_split[0], array( '1', '2', '5', '6', '8', '9' ), true )
						||
						$ignore_first
					) {
						// Calculamos o dígito de controlo
						$check_digit = 0;
						for ( $i = 0; $i < 8; $i++ ) {
							$check_digit += intval( $nif_split[ $i ] ) * ( 10 - $i - 1 );
						}
						$check_digit = 11 - ( $check_digit % 11 );
						// Se der 10 então o dígito de controlo tem de ser 0
						if ( $check_digit >= 10 ) {
							$check_digit = 0;
						}
						// Comparamos com o último dígito
						if ( intval( $check_digit ) === intval( $nif_split[8] ) ) {
							return true;
						} else {
							return false;
						}
					} else {
						return false;
					}
				}
			}

			/**
			 * Return the field label.
			 *
			 * @return string
			 */
			function woocommerce_nif_field_label() {
				return apply_filters( 'woocommerce_nif_field_label', __( 'NIF / NIPC', 'nif-num-de-contribuinte-portugues-for-woocommerce' ) );
			}

			/**
			 * Return if the field is required.
			 *
			 * @return bool
			 */
			function woocommerce_nif_field_required() {
				return apply_filters( 'woocommerce_nif_field_required', false );
			}

			/**
			 * Return if the field is to validate.
			 *
			 * @return bool
			 */
			function woocommerce_nif_field_validate() {
				return apply_filters( 'woocommerce_nif_field_validate', false );
			}

			/**
			 * Return the field maxlength.
			 *
			 * @return bool
			 */
			function woocommerce_nif_field_maxlength() {
				return apply_filters( 'woocommerce_nif_field_maxlength', 9 );
			}

			/**
			 * Return whether the field should be displayed only for PT or all countries.
			 *
			 * @return bool
			 */
			function woocommerce_nif_show_all_countries() {
				return apply_filters( 'woocommerce_nif_show_all_countries', false );
			}

			/**
			 * Return the invalid field message.
			 *
			 * @return string
			 */
			function woocommerce_nif_invalid_message() {
				return apply_filters(
					'woocommerce_nif_invalid_message',
					/* translators: %s NIF field name */
					sprintf( __( 'You have entered an invalid %s for Portugal.', 'nif-num-de-contribuinte-portugues-for-woocommerce' ), '<strong>' . woocommerce_nif_field_label() . '</strong>' )
				);
			}
		}
	},
	1
);

/* HPOS & Checkout Blocks Compatible */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

/* Checkout Block Compatible */
add_action(
	'woocommerce_blocks_loaded',
	function () {
		require_once __DIR__ . '/ptwoo-nif-blocks-integration.php';
		require_once __DIR__ . '/ptwoo-nif-extend-store-endpoint.php';

		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new PTWoo_NIF_Blocks_Integration() );
			}
		);

		( new PTWoo_NIF_Extend_Store_Endpoint() )->initialize();
	}
);

/* InvoiceXpress nag */
add_action(
	'admin_init',
	function () {
		if (
		( ! defined( 'WEBDADOS_INVOICEXPRESS_NAG' ) )
		&&
		( ! class_exists( '\Webdados\InvoiceXpressWooCommerce\Plugin' ) )
		&&
		empty( get_transient( 'webdados_invoicexpress_nag' ) )
		) {
			define( 'WEBDADOS_INVOICEXPRESS_NAG', true );
			require_once 'webdados_invoicexpress_nag/webdados_invoicexpress_nag.php';
		}
	}
);

/* If you're reading this you must know what you're doing ;-) Greetings from sunny Portugal! */
