<?php
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for extending the WooCommerce Store API
 */
class PTWoo_NIF_Extend_Store_Endpoint {

	/**
	 * The name of the extension.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ptwoo-nif';
	}

	/**
	 * When called invokes any initialization/setup for the extension.
	 */
	public function initialize() {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => $this->get_name(),
				'schema_callback' => array( $this, 'store_api_schema_callback' ),
				'data_callback'   => array( $this, 'store_api_data_callback' ),
				'schema_type'     => ARRAY_A,
			)
		);

		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => $this->get_name(),
				'callback'  => array( $this, 'store_api_update_callback' ),
			)
		);

		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'process_order' ) );
	}

	/**
	 * Add Store API schema data.
	 *
	 * @return array
	 */
	public function store_api_schema_callback() {
		return array(
			'billingNif' => array(
				'description' => __( 'NIF / NIPC', 'nif-num-de-contribuinte-portugues-for-woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'optional'    => ! woocommerce_nif_field_required(),
			),
		);
	}

	/**
	 * Add Store API endpoint data.
	 *
	 * @return array
	 */
	public function store_api_data_callback() {
		$customer     = wc()->customer;
		$session_data = $this->get_session_data();
		$billing_nif  = $session_data['billingNif'];

		if ( null === $billing_nif ) {

			// Fallback to customer NIF (meta) if there's no NIF in session.
			if ( $customer instanceof \WC_Customer ) {
				$billing_nif = $customer->get_meta( 'billing_nif' );
			}
		}

		$data = array(
			'billingNif' => $billing_nif,
			'isValid'    => $this->is_nif_valid(),
		);

		return $data;
	}

	/**
	 * Update callback to be executed by the Store API.
	 *
	 * @param  array $data Extension data.
	 * @return void
	 */
	public function store_api_update_callback( $data ) {

		// Sets the WC customer session if one is not set.
		if ( ! ( isset( wc()->session ) && wc()->session->has_session() ) ) {
			wc()->session->set_customer_session_cookie( true );
		}

		wc()->session->set( $this->get_name(), $data );
	}

	/**
	 * Process order.
	 *
	 * @param  \WC_Order $order Order object.
	 * @return void
	 */
	public function process_order( $order ) {

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$session_data = $this->get_session_data();

		if ( empty( $session_data ) ) {
			return;
		}

		$billing_nif = $session_data['billingNif'];

		// Store NIF in order meta.
		$order->update_meta_data( '_billing_nif', $billing_nif );

		// Store NIF in customer meta, if logged in.
		$customer_id = $order->get_customer_id();
		if ( ! empty( $customer_id ) ) {
			$customer = new \WC_Customer( $customer_id );
			$customer->update_meta_data( 'billing_nif', $billing_nif );
			$customer->save_meta_data();
		}

		$order->save();

		// Clear the extension's session data.
		wc()->session->__unset( $this->get_name() );
	}

	/**
	 * Retrieve session data.
	 *
	 * @return array
	 */
	public function get_session_data() {
		$data = wc()->session->get( $this->get_name() );

		if ( isset( $data['billingNif'] ) ) {
			$data['billingNif'] = sanitize_text_field( $data['billingNif'] );
		} else {
			$data['billingNif'] = null;
		}

		if ( isset( $data['isRequired'] ) ) {
			$data['isRequired'] = boolval( $data['isRequired'] );
		} else {
			$data['isRequired'] = false;
		}

		if ( isset( $data['validate'] ) ) {
			$data['validate'] = boolval( $data['validate'] );
		} else {
			$data['validate'] = false;
		}

		return $data;
	}

	/**
	 * Determine if the NIF is valid.
	 *
	 * @return boolean
	 */
	public function is_nif_valid() {
		$session_data     = $this->get_session_data();
		$billing_nif      = $session_data['billingNif'];
		$is_required      = $session_data['isRequired'];
		$needs_validation = $session_data['validate'];

		// Skip if NIF is not required, needs validation but it's empty.
		if ( ! $is_required && $needs_validation && empty( $billing_nif ) ) {
			return true;
		}

		// Check if NIF is required and not provided.
		if ( $is_required && empty( $billing_nif ) ) {
			return false;
		}

		// If validation is not needed, consider it valid.
		if ( ! $needs_validation ) {
			return true;
		}

		$customer           = wc()->customer;
		$show_all_countries = woocommerce_nif_show_all_countries();

		if (
			! empty( $show_all_countries )
			|| ( $customer instanceof \WC_Customer && 'PT' === $customer->get_billing_country() )
		) {
			$is_nif_valid = woocommerce_valida_nif( $billing_nif, true );
			return $is_nif_valid;
		}

		return true;
	}
}
