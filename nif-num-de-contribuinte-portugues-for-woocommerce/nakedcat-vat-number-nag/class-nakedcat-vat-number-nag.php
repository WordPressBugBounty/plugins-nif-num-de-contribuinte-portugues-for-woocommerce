<?php
/**
 * Class Nag
 *
 * @version 1.0
 * @package NIF_WooCommerce
 */

namespace NakedCatPlugins\NIF\VAT_Number_Nag;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The admin notice introducing VAT Number and EU VIES Validation for WooCommerce.
 *
 * @package NakedCatPlugins\NIF\VAT_Number_Nag
 */
class Nag {

	/**
	 * Identifier used for the notice element, the AJAX action and the nonce.
	 */
	const NOTICE_ID = 'nakedcat_vat_number_nag';

	/**
	 * Product page the notice links to, before the campaign parameters.
	 */
	const PRODUCT_URL = 'https://nakedcatplugins.com/product/vat-number-and-eu-vies-validation-for-woocommerce/';

	/**
	 * Campaign this plugin is credited with in the product page URL.
	 */
	const UTM_CAMPAIGN = 'nifportugues_woocommerce_plugin';

	/**
	 * Coupon offered to the shops running this plugin.
	 */
	const COUPON = 'niftovat';

	/**
	 * When the general launch offer ends, in ISO 8601 with the Lisbon offset.
	 *
	 * This governs the launch offer only, not the notice and not the coupon. The coupon
	 * has no deadline of its own: it runs until COUPON_LIMIT customers have used it, so
	 * it outlives the launch offer and the notice goes on being worth showing afterwards.
	 * Once this passes, the parts of the copy that only make sense next to the launch
	 * offer are dropped, and the rest stands on its own.
	 *
	 * The date itself is never printed: it belongs on the product page, where it can be
	 * changed without a plugin release.
	 */
	const OFFER_ENDS = '2026-09-30T23:59:59+01:00';

	/**
	 * How many customers the coupon is limited to. Stated in the copy so nobody counts on
	 * it still being available.
	 */
	const COUPON_LIMIT = 50;

	/**
	 * `user_meta` key holding the timestamp until which this user dismissed the notice.
	 */
	const DISMISSED_USER_META = 'nakedcat_vat_number_nag_dismissed_until';

	/**
	 * `user_meta` key counting how many times this user has dismissed the notice.
	 */
	const DISMISSALS_USER_META = 'nakedcat_vat_number_nag_dismissals';

	/**
	 * How long a dismissal is remembered, in days, once the launch offer is over.
	 */
	const DISMISS_DAYS = 180;

	/**
	 * Shortest gap, in days, that a dismissal made during the launch offer may produce.
	 *
	 * While the offer runs a dismissal lasts half the time left on it, so the notice gets
	 * one more showing before the offer ends whenever it was dismissed. Below this many
	 * days that halving stops being a reminder and starts being a pest, so a dismissal
	 * near the end falls back to the normal gap and the launch offer simply goes unseen.
	 */
	const LAUNCH_MIN_GAP_DAYS = 10;

	/**
	 * How many times the notice may be shown to one user before it stops for good.
	 *
	 * Counted in dismissals, so this is three showings: the notice no longer switches
	 * itself off with the launch offer, and without a cap it would come back twice a year
	 * forever for somebody who has already decided.
	 */
	const MAX_SHOWINGS = 3;

	/**
	 * Screens the notice is allowed on.
	 *
	 * Deliberately not every admin page: this is a promotion, and it has no business
	 * interrupting an unrelated plugin's screen.
	 *
	 * @var array
	 */
	private $screens = array(
		'dashboard',
		'plugins',
		'woocommerce_page_wc-settings',
		'woocommerce_page_wc-orders',
		'edit-shop_order',
	);

	/**
	 * Register the hooks.
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'render' ) );
		add_action( 'wp_ajax_' . self::NOTICE_ID . '_dismiss', array( $this, 'ajax_dismiss' ) );
	}

	/**
	 * Whether the notice should be rendered for the current user, on the current screen.
	 *
	 * @return bool
	 */
	private function should_show(): bool {

		// Already using the plugin being promoted.
		if ( class_exists( 'NakedCat_VAT_Number' ) ) {
			return false;
		}

		// Only the people who could act on it.
		if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return false;
		}

		if ( ! $this->on_allowed_screen() ) {
			return false;
		}

		// Asked and answered enough times.
		if ( intval( get_user_meta( get_current_user_id(), self::DISMISSALS_USER_META, true ) ) >= self::MAX_SHOWINGS ) {
			return false;
		}

		return intval( get_user_meta( get_current_user_id(), self::DISMISSED_USER_META, true ) ) < time();
	}

	/**
	 * Whether the general launch offer is still running.
	 *
	 * The coupon is deliberately not covered by this: see the OFFER_ENDS docblock.
	 *
	 * @param  int $now Timestamp to judge against. Defaults to now. Only passed by tests,
	 *                  which cannot move the clock any other way.
	 * @return bool
	 */
	private function launch_offer_running( int $now = 0 ): bool {
		$now  = $now ? $now : time();
		$ends = strtotime( self::OFFER_ENDS );
		return $ends ? ( $now <= $ends ) : true;
	}

	/**
	 * Whether the current screen is one of the allowed ones.
	 *
	 * @return bool
	 */
	private function on_allowed_screen(): bool {

		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return ( $screen && in_array( $screen->id, $this->screens, true ) );
	}

	/**
	 * Render the notice.
	 */
	public function render() {

		if ( ! $this->should_show() ) {
			return;
		}

		$link_open  = sprintf( '<a href="%s" target="_blank">', esc_url( $this->product_url() ) );
		$link_close = '</a>';
		$allowed    = array(
			'strong' => array(),
			'code'   => array(),
			'a'      => array(
				'href'   => array(),
				'target' => array(),
			),
		);
		?>
		<div id="<?php echo esc_attr( self::NOTICE_ID ); ?>" class="notice notice-info is-dismissible">
			<div style="display: flex; flex-wrap: wrap; align-items: flex-start; gap: 1em;">
			<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'icon-vat-number.svg' ); ?>" alt="" style="flex: 0 0 auto; width: 100px; height: auto; margin-top: 1em;"/>
			<div style="flex: 1 1 320px; min-width: 0;">
				<p style="line-height: 1.5em; font-size: 15px;">
					<strong><?php esc_html_e( 'Special deal, exclusive to NIF (Num. de Contribuinte Português) for WooCommerce users', 'nif-num-de-contribuinte-portugues-for-woocommerce' ); ?></strong>
				</p>
				<p style="line-height: 1.5em;">
					<?php
					echo wp_kses(
						sprintf(
							$this->launch_offer_running()
								/* translators: %1$s: coupon code, %2$s: link opening tag, %3$s: link closing tag, %4$d: how many customers the coupon is limited to */
								? __( 'Add the coupon %1$s to a yearly licence of %2$sVAT Number and EU VIES Validation for WooCommerce%3$s and get an extra 50%% off the first year <strong>and</strong> 50%% off every renewal, forever. Limited to the first %4$d customers.', 'nif-num-de-contribuinte-portugues-for-woocommerce' )
								/* translators: %1$s: coupon code, %2$s: link opening tag, %3$s: link closing tag, %4$d: how many customers the coupon is limited to */
								: __( 'Add the coupon %1$s to a yearly licence of %2$sVAT Number and EU VIES Validation for WooCommerce%3$s and get 50%% off the first year <strong>and</strong> 50%% off every renewal, forever. Limited to the first %4$d customers.', 'nif-num-de-contribuinte-portugues-for-woocommerce' ),
							'<code>' . esc_html( self::COUPON ) . '</code>',
							$link_open,
							$link_close,
							intval( self::COUPON_LIMIT )
						),
						$allowed
					);
					?>
				</p>
				<p style="line-height: 1.5em;">
					<strong><?php esc_html_e( 'Do you also sell to businesses in other European Union countries?', 'nif-num-de-contribuinte-portugues-for-woocommerce' ); ?></strong>
					<br/>
					<?php
					echo wp_kses(
						sprintf(
							/* translators: %1$s: link opening tag, %2$s: link closing tag */
							__( '%1$sVAT Number and EU VIES Validation for WooCommerce%2$s collects the customer VAT identification number, pre-validates it on your own server, confirms it against VIES and removes VAT on qualifying intra-EU B2B orders. It replaces NIF (Num. de Contribuinte Português) for WooCommerce rather than extending it, and its <strong>Zero-Touch Migration</strong> reads the NIF numbers already stored here, so there is nothing to export, import or schedule.', 'nif-num-de-contribuinte-portugues-for-woocommerce' ),
							$link_open,
							$link_close
						),
						$allowed
					);
					?>
				</p>
			<?php if ( $this->launch_offer_running() ) { ?>
				<p style="line-height: 1.5em;">
					<?php
					echo wp_kses(
						sprintf(
							/* translators: %1$s: link opening tag, %2$s: link closing tag, %3$s: coupon code */
							__( '%1$sLaunch offer%2$s, for everybody: 50%% off the first year, or 25%% off a lifetime licence. <strong>Special offer for you:</strong> 50%% on top + 50%% forever on all renewals. Use the %3$s coupon.', 'nif-num-de-contribuinte-portugues-for-woocommerce' ),
							'<strong>' . $link_open,
							$link_close . '</strong>',
							'<code>' . esc_html( self::COUPON ) . '</code>'
						),
						$allowed
					);
					?>
				</p>
			<?php } ?>
				<p>
					<a href="<?php echo esc_url( $this->product_url() ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Get to know the plugin and claim the special deal before it expires', 'nif-num-de-contribuinte-portugues-for-woocommerce' ); ?></a>
				</p>
			</div>
			</div>
		</div>
		<script type="text/javascript">
		( function () {
			var notice = document.getElementById( '<?php echo esc_js( self::NOTICE_ID ); ?>' );
			if ( ! notice ) {
				return;
			}
			notice.addEventListener( 'click', function ( event ) {
				if ( ! event.target.classList.contains( 'notice-dismiss' ) ) {
					return;
				}
				var data = new FormData();
				data.append( 'action', '<?php echo esc_js( self::NOTICE_ID ); ?>_dismiss' );
				data.append( 'nonce', '<?php echo esc_js( wp_create_nonce( self::NOTICE_ID ) ); ?>' );
				window.fetch( ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body: data
				} );
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * The product page URL, with the campaign parameters.
	 *
	 * The source is this shop own host name rather than a fixed string, so the product
	 * page analytics show which shops the visits came from.
	 *
	 * @return string
	 */
	private function product_url(): string {

		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return add_query_arg(
			array(
				'utm_source'   => rawurlencode( $host ? $host : 'unknown' ),
				'utm_medium'   => 'link',
				'utm_campaign' => self::UTM_CAMPAIGN,
			),
			self::PRODUCT_URL
		);
	}

	/**
	 * Remember this user dismissed the notice.
	 */
	public function ajax_dismiss() {

		check_ajax_referer( self::NOTICE_ID, 'nonce' );

		$user_id = get_current_user_id();

		update_user_meta( $user_id, self::DISMISSALS_USER_META, intval( get_user_meta( $user_id, self::DISMISSALS_USER_META, true ) ) + 1 );
		update_user_meta( $user_id, self::DISMISSED_USER_META, time() + $this->dismissal_gap() );

		wp_die();
	}

	/**
	 * How long the dismissal being made right now should last, in seconds.
	 *
	 * @param  int $now Timestamp the dismissal is being made at. Defaults to now. Only
	 *                  passed by tests, which cannot move the clock any other way.
	 * @return int
	 */
	private function dismissal_gap( int $now = 0 ): int {

		$now    = $now ? $now : time();
		$normal = self::DISMISS_DAYS * DAY_IN_SECONDS;

		if ( ! $this->launch_offer_running( $now ) ) {
			return $normal;
		}

		$remaining = strtotime( self::OFFER_ENDS ) - $now;
		$half      = intval( floor( $remaining / 2 ) );

		return ( $half >= ( self::LAUNCH_MIN_GAP_DAYS * DAY_IN_SECONDS ) ) ? $half : $normal;
	}
}
