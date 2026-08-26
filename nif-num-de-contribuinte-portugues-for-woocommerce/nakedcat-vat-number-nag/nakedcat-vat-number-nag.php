<?php
/**
 * NakedCat_VAT_Number_Nag
 *
 * Bootstrap for the notice that introduces VAT Number and EU VIES Validation for
 * WooCommerce to the shops running this plugin.
 *
 * @version 1.0
 * @package NIF_WooCommerce
 */

namespace NakedCatPlugins\NIF\VAT_Number_Nag;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Bail if another bundled copy of this module has already booted in this request.
if (
	defined( 'NAKEDCAT_VAT_NUMBER_NAG' )
	||
	class_exists( 'NakedCatPlugins\NIF\VAT_Number_Nag\Nag' )
) {
	return;
}

define( 'NAKEDCAT_VAT_NUMBER_NAG', true );

require_once __DIR__ . '/class-nakedcat-vat-number-nag.php';

( new Nag() )->init();
