=== NIF (Num. de Contribuinte Português) for WooCommerce ===
Contributors: webdados, ptwooplugins
Tags: ecommerce, nif, nipc, vat, tax
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.0
Stable tag: 6.5
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin adds the Portuguese NIF/NIPC as a new field to WooCommerce checkout and order details, if the billing address / customer is from Portugal.

== Description ==

This plugin adds the Portuguese VAT identification number (NIF/NIPC) as a new field to WooCommerce checkout and order details, if the billing address is from Portugal.

= Features: =

* Adds the Portuguese VAT identification number (NIF/NIPC) to the WooCommerce Checkout fields, Order admin fields, Order Emails and "Thank You" page;
* It’s possible to edit the customer’s NIF/NIPC field on "My Account - Billing Address" and on the User edit screen on wp-admin.
* NIF/NIPC check digit validation (if activated via filter, or block option)
* WooCommerce High-Performance Order Storage compatible
* WooCommerce Checkout Block compatible (in beta)

= Are you already issuing automatic invoices on your WooCommerce store? =

If not, get to know our new plugin: [Invoicing with InvoiceXpress for WooCommerce](https://wordpress.org/plugins/woo-billing-with-invoicexpress/)

== Already know our other WooCommerce (premium) plugins? ==

* [Portuguese Postcodes for WooCommerce](https://ptwooplugins.com/product/portuguese-postcodes-for-woocommerce-technical-support/) - Automatic filling of the address details at the checkout, including street name and neighborhood, based on the postal code
* [Invoicing with InvoiceXpress for WooCommerce](https://invoicewoo.com/) - Automatically issue invoices directly from the WooCommerce order
* [DPD Portugal for WooCommerce](https://ptwooplugins.com/product/dpd-portugal-for-woocommerce/) - Create shipping and return guide in the DPD webservice directly from the WooCommerce order
* [Feed KuantoKusta for WooCommerce](https://ptwooplugins.com/product/feed-kuantokusta-for-woocommerce-pro/) - Publish your products on Kuanto Kusta with this easy to use feed generator
* [Multibanco, MBWAY, Credit card, Payshop and Cofidis Pay for WooCommerce – PRO add-on](https://ptwooplugins.com/product/multibanco-mbway-credit-card-payshop-ifthenpay-woocommerce-pro-add-on/) - Extra features for the plugin you already trust to receive payments on your WooCommerce store
* [Simple Custom Fields for WooCommerce Blocks Checkout](https://ptwooplugins.com/product/simple-custom-fields-for-woocommerce-blocks-checkout/) - Add custom fields to the new WooCommerce Block-based Checkout
* [Simple WooCommerce Order Approval](https://ptwooplugins.com/product/simple-woocommerce-order-approval/) - The hassle-free solution for WooCommerce order approval before payment
* [Shop as Client for WooCommerce](https://ptwooplugins.com/product/shop-as-client-for-woocommerce-pro-add-on/) - Quickly create orders on behalf of your customers
* [Taxonomy/Term and Role based Discounts for WooCommerce](https://ptwooplugins.com/product/taxonomy-term-and-role-based-discounts-for-woocommerce-pro-add-on/) - Easily create bulk discount rules for products based on any taxonomy terms (built-in or custom)
* [DPD / SEUR / Geopost Pickup and Lockers network for WooCommerce](https://ptwooplugins.com/product/dpd-seur-geopost-pickup-and-lockers-network-for-woocommerce/) - Deliver your WooCommerce orders on the DPD and SEUR Pickup network of Parcelshops and Lockers in 21 European countries

== Installation ==

* Use the included automatic install feature on your WordPress admin panel and search for "NIF WooCommerce".

== Frequently Asked Questions ==

= How to make the NIF field required? =

Classic checkout - Add this to your theme’s functions.php file or code snippets plugin:

`add_filter( 'woocommerce_nif_field_required', '__return_true' );`

Blocks checkout - Activate the "Required" option on the NIF block.

= Is it possible to validate the check digit to ensure a valid Portuguese NIF/NIPC is entered by the customer? =

Yes, it is!

Classic checkout - Add this to your theme’s functions.php file or code snippets plugin:

`add_filter( 'woocommerce_nif_field_validate', '__return_true' );`

Blocks checkout - Activate the "Validate" option on the NIF block.

We only recommend validating the NIF if your shop only sells to Portugal.

= How to change the NIF field position on the checkout? =

Classic checkout - Add this to your theme’s functions.php file or code snippets plugin:

`add_filter( 'woocommerce_nif_field_priority', function( $priority) {
	// Default is 120 - Adjust the number to move the field up (lower number) or down (higher number)
	return 120;
} );`

Blocks checkout - For now, the block is fixed after the billing address and before the shipping options. We’re working on making it possible to move the block where you want.
Future instructions for the Block checkout - Move the NIF block up or down as wanted. We do not recommend moving this field above the billing and shipping addresses, as its visibility may depend on the chosen country.

= Is this plugin compliant with the new EU General Data Protection Regulation (GDPR)? =

First of all, it’s the website owner’s responsibility to make your whole website compliant because no personal details whatsoever are transmitted to us, the plugin developers.
Anyway, we can help you by documenting how this plugin handles the collected data:
* The NIF/NIPC field is collected via the checkout process and stored as an order meta value, alongside all the other WooCommerce order fields;
* The NIF/NIPC field can also be set on the "My Account - Billing Address" form and it’s then stored as a user meta value, alongside all the other WordPress and WooCommerce user fields;
* The NIF/NIPC field is shown and editable on the order edit and user edit screens on the backend, by the store owner;
* The NIF/NIPC field is shown on the order transactional emails;

= I need help, can I get technical support? =

This is a free plugin. It’s our way of giving back to the wonderful WordPress community.

There’s a support tab on the top of this page, where you can ask the community for help. We’ll try to keep an eye on the forums but we cannot promise to answer support tickets.

If you reach us by email or any other direct contact means, we’ll assume you need, premium, and of course, paid-for support.

= Where do I report security vulnerabilities found in this plugin? =  
 
You can report any security bugs found in the source code of this plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/nif-num-de-contribuinte-portugues-for-woocommerce). The Patchstack team will assist you with verification, CVE assignment and take care of notifying the developers of this plugin.

== Changelog ==

= 6.5 - 2024-10-08 =
* [FIX] Load text domain at the right time to avoid PHP notices on WordPress 6.7 and above
* [DEV] Tested with WordPress 6.7-beta1-59184 and WooCommerce 9.4.0-beta.2

= 6.4 - 2024-07-25 =
* [NEW] `woocommerce_nif_invalid_message` filter to change the invalid Portuguese NIF/NIPC message on checkout
* [DEV] Make blocks checkout use the same invalid message as the classic checkout, thus benefiting from the same filter
* [DEV] Block lock position defined in block.json
* [DEV] Tested with WordPress 6.7-alpha-58796 and WooCommerce 9.1.3

= 6.3 - 2024-03-27 =
* [DEV] Set `Requires Plugins` tag to `woocommerce`
* [DEV] Tested with WordPress 6.5-RC3-57875 and WooCommerce 8.8.0-beta.1

= 6.2 - 2024-02-18 =
* [DEV] Simplify block build process and implement SVG accessibility attributes
* [DEV] Tested with WordPress 6.5-beta1-57650 and WooCommerce 8.6.0

= 6.1 - 2024-02-01 =
* [FIX] Correctly load block i18n on the block javascript files
* [DEV] Declare cart anc checkout blocks compatibility (just in case)
* [DEV] Tested with WordPress 6.5-alpha-57505 and WooCommerce 8.6-0-beta.1

= 6.0 - 2024-01-30 =
* [NEW] Block-based Checkout compatibility (in beta)
* [TWEAK] Improve filters_examples.php
* [DEV] Requires WordPress 5.6 and WooCommerce 6.0
* [DEV] Tested with WordPress 6.5-alpha-57378 and WooCommerce 8.5.2

= 5.6 - 2024-01-10 =
* Fix checkout validation feedback on WooCommerce 8.5 and above
* Tested with WordPress 6.5-alpha-57258 and WooCommerce 8.5

= 5.5 - 2023-11-17 =
* Tested with WordPress 6.5-alpha-57114 and WooCommerce 8.3.0

= 5.4 - 2023-09-07 =
* Do not accept "000000000" as NIF number, if validation is active
* Fixed HTML markup on the "Thank You" page
* Tested with WordPress 6.4-alpha-56530 and WooCommerce 8.1.0-rc.1

= 5.3 - 2023-04-18 =
* Fixed a bug introduced on version 5.2 when using the `woocommerce_nif_field_validate` filter to validate the Portuguese NIF

= 5.2 - 2023-04-17 =
* WordPress Coding Standards
* Tested with WordPress 6.3-alpha-55644 and WooCommerce 7.6.0

= 5.1 - 2022-11-10 =
* Tested and confirmed WooCommerce HPOS compatibility
* Requires WooCommerce 5.0
* Tested with WordPress 6.2-alpha-54748 and WooCommerce 7.1

= 5.0.0 - 2022-09-20 =
* If NIF validation is active by using the `woocommerce_nif_field_validate` filter, the field will be set as invalid om the checkout form when the validation is performed and fails
* Removed all WooCommerce pre 3.0 code
* Requires WooCommerce 4.0
* Tested with WordPress 6.1-alpha-54043 and WooCommerce 6.9.2


= 4.3.0 - 2022-06-29 =
* New brand: PT Woo Plugins 🥳
* Requires WordPress 5.0, WooCommerce 3.0 and PHP 7.0
* Tested with WordPress 6.1-alpha-53556 and WooCommerce 6.7.0-beta.2

= 4.2.6 - 2022-02-23 =
* Check if the `is_checkout` function exists before calling it
* Tested with WordPress 6.0-alpha-52790 and WooCommerce 6.3.0-rc.1

= 4.2.5 - 2021-03-10 =
* Tested with WordPress 5.8-alpha-50516 and WooCommerce 5.1.0

= 4.2.4.2 =
* Tested with WordPress 5.6-alpha-49064 and WooCommerce 4.6.0-beta.1

= 4.2.4.1 =
* Tested with WooCommerce 4.0.0

= 4.2.4 =
* Changes on the InvoiceXpress banner
* Tested with WordPress 5.3.3-alpha-46995 and WooCommerce 3.9.0-rc.2

= 4.2.3 =
* Hide the InvoiceXpress nag if the invoicing is already installed and active
* Tested with WordPress 5.3.1-alpha-46798 and WooCommerce 3.8.1

= 4.2.2 =
* Change InvoiceXpress nag interval from 30 to 90 days
* Tested with WordPress 5.2.4-alpha-46074 and WooCommerce 3.8.0-beta.1
* Requires PHP 5.6

= 4.2.1 =
* NIF field on Subscriptions via the admin order screen (thanks @ptravassos)
* Tested with WooCommerce 3.6.5 and WordPress 5.2.2

= 4.2 =
* Add NIF to the WooCommerce REST `orders` and `customers` endpoints

= 4.1.3 =
* Fixed a fatal error when the `woocommerce_nif_field_validate` was set to true and the customer doesn’t have a country associated yet
* Tested with WooCommerce 3.5.5 and WordPress 5.0.3

= 4.1.2 =
* Tested with WooCommerce 3.5.2
* Bumped `WC tested up` tag
* Bumped `Requires at least` tag

= 4.1.1 =
* Tested with WooCommerce 3.5
* Bumped `WC tested up` tag
* Bumped `Requires at least` tag

= 4.1 =
* Using `WC()->customer->get_billing_country()` instead of `WC()->customer->get_country()` on WooCommerce 3.0 and above

= 4.0 =
* Toggle the NIF field via javascript on the checkout page by default - if you need to get back to the old mechanism, you should false on the `woocommerce_nif_use_javascript` filter
* New `woocommerce_nif_show_all_countries` to show the field for all countries instead of only to portuguese customers (not recommended)
* Tested with WooCommerce 3.3
* Filters examples updated
* Improved the FAQ

= 3.3 =
* Experimental javascript toggle of the NIF field on the checkout page, so that the field is shown or hidden depending on the billing country selection (by returning true on the `woocommerce_nif_use_javascript` filter) - this will probably be default on a later version, after heavy user testing
* GDPR information
* Improved the FAQ

= 3.2 =
* Fixed a bug where if the validation is active but the field is not required, the checkout wouldn’t go ahead if the client didn’t fill the field
* Validation of the field on the "My Account - Billing Address" form
* Bumped `Tested up to` and `WC tested up to` tags
* Better code formatting standards
* Improved the FAQ

= 3.1 =
* Removed the translation files from the plugin `lang` folder (the translations are now managed on WordPress.org’s GlotPress tool and will be automatically downloaded from there)
* Tested with WooCommerce 3.2
* Added `WC tested up to` tag on the plugin main file
* Bumped `Tested up to` tag

= 3.0 =
* It’s now possible to validate the Portuguese NIF/NIPC check digit entered by the customer (by returninig true on the `woocommerce_nif_field_validate` filter)
* Tested with WooCommerce 3.0.0-rc.2
* Changed version tests from 2.7 to 3.0
* New `autocomplete` parameter set to 'on'
* New `priority` parameter set to '120'
* New `maxlength` parameter set to '9'
* New filters to manipulate the field `label`, `placeholder`, `required`, `class`, `clear`, `autocomplete` and `maxlength` parameters (check filters_examples.php)
* Bumped `Tested up to` tag

= 2.1 =
* WooCommerce 2.7 compatibility
* NIF/NIPC is also shown and editable, in admin, on the user edit screen (alongside with other Billing Address fields)

= 2.0.2 =
* Fix typos in the readme.txt file (Thanks Daniel Matos)

= 2.0.1 =
* Bumped `Tested up to` and `Requires at least` tags

= 2.0 =
* Completely rewritten
* NIF/NIPC is added to the Billing Address fields on the Checkout (as long as the customer country is Portugal)
* You can also edit the user NIF/NIPC on the "My Account - Billing Address" form
* NIF/NIPC is also shown and editable on the order screen (alongside with other Billing Address fields)
* NIF/NIPC is added to the Customer Details section on Emails and Thank You page.

= 1.3 =
* Adds the field to the My Acccount / Edit Billing Address form

= 1.2.2 =
* The value is now auto-filled with the last one used

= 1.2.1 =
* Small fix to avoid php notices

= 1.2 =
* WordPress Multisite support

= 1.1.1 =
* Forgot to update version number on the php file.

= 1.1 =
* Bug fix after WooCommerce 2.1 changes.

= 1.0 =
* Initial release.