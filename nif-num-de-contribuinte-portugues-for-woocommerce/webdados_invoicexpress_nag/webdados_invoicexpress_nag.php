<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Add InvoiceXpress for WooCommerce nag
 */
function webdados_invoicexpress_nag() {
	?>
		<script type="text/javascript">
		jQuery(function($) {
			$( document ).on( 'click', '#webdados_invoicexpress_nag .notice-dismiss', function () {
				//AJAX SET TRANSIENT FOR 90 DAYS
				$.ajax( ajaxurl, {
					type: 'POST',
					data: {
						action: 'dismiss_webdados_invoicexpress_nag',
					}
				});
			});
		});
		</script>
		<div id="webdados_invoicexpress_nag" class="notice notice-info is-dismissible">
			<p style="line-height: 1.4em;">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'invoicexpress-woocommerce-logo.png' ); ?>" style="float: left; max-width: 100px; height: auto; margin-right: 1em;"/>
				<strong><?php esc_html_e( 'Are you already issuing automatic invoices on your WooCommerce store?', 'nif-num-de-contribuinte-portugues-for-woocommerce' ); ?></strong>
				<br/>
			<?php
				echo sprintf(
					/* translators: %1$s link opening tag, %2$s link cosing tag */
					__( 'If not, get to know our new plugin: %1$sInvoicing with InvoiceXpress for WooCommerce%2$s', 'nif-num-de-contribuinte-portugues-for-woocommerce' ), //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					sprintf(
						'<a href="%s" target="_blank">',
						esc_url( __( 'https://invoicewoo.com/', 'nif-num-de-contribuinte-portugues-for-woocommerce' ) )
					),
					'</a>'
				);
			?>
				<br/>
				<?php _e( 'Use the coupon <strong>webdados</strong> for 10% discount!', 'nif-num-de-contribuinte-portugues-for-woocommerce' ); //phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction ?>
			</p>
		</div>
		<?php
}
add_action( 'admin_notices', 'webdados_invoicexpress_nag' );

/**
 * Dismiss InvoiceXpress for WooCommerce nag
 */
function dismiss_webdados_invoicexpress_nag() {
	$days       = 90;
	$expiration = $days * DAY_IN_SECONDS;
	set_transient( 'webdados_invoicexpress_nag', 1, $expiration );
	wp_die();
}
add_action( 'wp_ajax_dismiss_webdados_invoicexpress_nag', 'dismiss_webdados_invoicexpress_nag' );
