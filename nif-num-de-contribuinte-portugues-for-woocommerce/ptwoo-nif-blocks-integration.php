<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for integrating with WooCommerce Blocks
 */
class PTWoo_NIF_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ptwoo_nif';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->register_block_frontend_scripts();
		$this->register_block_editor_scripts();
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'ptwoo-nif-block-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'ptwoo-nif-block-editor' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {

		$data = array(
			'defaultLabel'            => woocommerce_nif_field_label(),
			'defaultIsRequired'       => woocommerce_nif_field_required(),
			'defaultValidate'         => woocommerce_nif_field_validate(),
			'defaultMaxLength'        => woocommerce_nif_field_maxlength(),
			'defaultShowAllCountries' => woocommerce_nif_show_all_countries(),
			'defaultInvalidMessage'   => strip_tags( woocommerce_nif_invalid_message() ),
		);

		return $data;
	}

	/**
	 * Register block editor scripts.
	 *
	 * @return void
	 */
	public function register_block_editor_scripts() {
		$script_url        = PTWOO_NIF_PLUGIN_URL . 'build/ptwoo-nif-block.js';
		$script_asset_path = PTWOO_NIF_PLUGIN_DIR . 'build/ptwoo-nif-block.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'ptwoo-nif-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'ptwoo-nif-block-editor',
			'nif-num-de-contribuinte-portugues-for-woocommerce'
		);
	}

	/**
	 * Register block frontend scripts.
	 *
	 * @return void
	 */
	public function register_block_frontend_scripts() {
		$script_url        = PTWOO_NIF_PLUGIN_URL . 'build/ptwoo-nif-block-frontend.js';
		$script_asset_path = PTWOO_NIF_PLUGIN_DIR . 'build/ptwoo-nif-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'ptwoo-nif-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'ptwoo-nif-block-frontend',
			'nif-num-de-contribuinte-portugues-for-woocommerce'
		);
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}

		return PTWOO_NIF_VERSION;
	}
}
