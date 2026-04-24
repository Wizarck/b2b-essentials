<?php
/**
 * Plugin bootstrap.
 *
 * @package B2bEssentials\Core
 */

namespace B2bEssentials\Core;

use B2bEssentials\Roles\RoleManager;
use B2bEssentials\Fiscal\NifValidator;
use B2bEssentials\Fiscal\ViesClient;
use B2bEssentials\Checkout\NifFieldManager;
use B2bEssentials\Checkout\PricingVisibility;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		// NOTE: register_activation_hook only works when called at plugin load
		// time (see b2b-essentials.php). Role registration is also performed on
		// every `init` via RoleManager::init() as an idempotent self-heal, so
		// fresh activations where this boot() runs post-activation are covered.
		RoleManager::init();

		$nif_validator = new NifValidator();
		$vies_client   = new ViesClient();

		( new NifFieldManager( $nif_validator, $vies_client ) )->register_hooks();
		( new PricingVisibility() )->register_hooks();
	}
}
