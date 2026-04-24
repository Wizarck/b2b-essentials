<?php
/**
 * Plugin Name: B2B Essentials for WooCommerce
 * Plugin URI: https://github.com/Wizarck/b2b-essentials
 * Description: B2B customer roles, moderated registration, hidden pricing until login, company fields with NIF/VIES validation. Company-agnostic, reusable.
 * Version: 0.1.0-dev
 * Author: Wizarck
 * License: LGPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/lgpl-3.0.html
 * Text Domain: b2b-essentials
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * WC requires at least: 9.0
 *
 * @package B2bEssentials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'B2B_ESSENTIALS_VERSION', '0.1.0-dev' );
define( 'B2B_ESSENTIALS_PATH', plugin_dir_path( __FILE__ ) );
define( 'B2B_ESSENTIALS_URL', plugin_dir_url( __FILE__ ) );

// HPOS compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

if ( file_exists( B2B_ESSENTIALS_PATH . 'vendor/autoload.php' ) ) {
	require_once B2B_ESSENTIALS_PATH . 'vendor/autoload.php';
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		if ( class_exists( '\B2bEssentials\Core\Plugin' ) ) {
			\B2bEssentials\Core\Plugin::instance()->boot();
		}
	},
	20
);
