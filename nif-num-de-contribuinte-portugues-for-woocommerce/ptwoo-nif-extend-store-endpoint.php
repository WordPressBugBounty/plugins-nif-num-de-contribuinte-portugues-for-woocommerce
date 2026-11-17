<?php
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for extending the WooCommerce Store API
 */
class PTWoo_NIF_Extend_Store_Endpoint {

	/**
	 * The field's data sent with the Place Order request being processed, if any.
	 *
	 * @var array|null
	 */
	private $checkout_request_data = null;

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

		// The NIF also comes with the Place Order request, as the field's value when it was clicked.
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => $this->get_name(),
				'schema_callback' => array( $this, 'store_api_checkout_schema_callback' ),
				'schema_type'     => ARRAY_A,
			)
		);

		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => $this->get_name(),
				'callback'  => array( $this, 'store_api_update_callback' ),
			)
		);

		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'update_from_checkout_request' ), 10, 2 );
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
	 * Schema of the data the field sends with the Place Order request.
	 *
	 * Every property accepts null, which is what a request without them (a page still running an
	 * older version of the field's script) gets, so they can be told apart from an empty field.
	 *
	 * @return array
	 */
	public function store_api_checkout_schema_callback() {
		return array(
			'billingNif' => array(
				'description' => __( 'NIF / NIPC', 'nif-num-de-contribuinte-portugues-for-woocommerce' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
			),
			'isRequired' => array(
				'description' => 'Whether the field is required',
				'type'        => array( 'boolean', 'null' ),
				'context'     => array( 'view', 'edit' ),
			),
			'validate'   => array(
				'description' => 'Whether the field is validated',
				'type'        => array( 'boolean', 'null' ),
				'context'     => array( 'view', 'edit' ),
			),
		);
	}

	/**
	 * Keep the NIF sent with the Place Order request, for process_order() and is_nif_valid().
	 *
	 * The field also keeps the session up to date through cart updates, but those are sent after
	 * the shopper pauses typing, so the last one may not have been sent, or answered, when Place
	 * Order is clicked. The value sent with the order is the one in the field at that moment.
	 *
	 * It is kept on this object rather than written to the session: by the time the order is
	 * processed, later in the same request, the session no longer holds a value written here.
	 *
	 * @param \WC_Order        $order   Order object.
	 * @param \WP_REST_Request $request Checkout request.
	 * @return void
	 */
	public function update_from_checkout_request( $order, $request ) {
		$extensions = $request->get_param( 'extensions' );
		$data       = is_array( $extensions ) && isset( $extensions[ $this->get_name() ] ) ? $extensions[ $this->get_name() ] : null;

		// A page still running an older version of the field's script sends nothing (null once
		// sanitized): the session's NIF is used, as before.
		if ( ! is_array( $data ) || ! isset( $data['billingNif'] ) ) {
			return;
		}

		$this->checkout_request_data = array(
			'billingNif' => sanitize_text_field( (string) $data['billingNif'] ),
			'isRequired' => ! empty( $data['isRequired'] ),
			'validate'   => ! empty( $data['validate'] ),
		);
	}

	/**
	 * The field's data for the order being placed: the one sent with Place Order, or the
	 * session's.
	 *
	 * @return array
	 */
	public function get_nif_data() {
		return null !== $this->checkout_request_data ? $this->checkout_request_data : $this->get_session_data();
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
	 * @throws RouteException If the NIF is invalid.
	 */
	public function process_order( $order ) {

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$session_data = $this->get_nif_data();

		if ( empty( $session_data ) ) {
			return;
		}

		$billing_nif = $session_data['billingNif'];

		// Server-side validation. The client-side check in the Blocks checkout can be bypassed
		// (JS disabled, or the Store API called directly), so it must be enforced here too, just
		// like the classic checkout does in woocommerce_nif_checkout_process().
		if ( ! $this->is_nif_valid() ) {
			throw new RouteException(
				'nif_num_de_contribuinte_portugues_for_woocommerce_invalid_nif',
				wp_kses_post( woocommerce_nif_invalid_message() ),
				400
			);
		}

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
		$this->checkout_request_data = null;
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
		$session_data     = $this->get_nif_data();
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
