<?php
/**
 * Hide WC prices from non-approved B2B visitors.
 *
 * @package B2bEssentials\Checkout
 */

namespace B2bEssentials\Checkout;

use B2bEssentials\Roles\RoleManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class PricingVisibility
 *
 * Guests and pending B2B customers see "Inicia sesión para ver precios"
 * instead of the WC price HTML. Approved customers (b2b_customer) and
 * logged-in admins see prices as usual.
 */
final class PricingVisibility {

	public function register_hooks(): void {
		add_filter( 'woocommerce_get_price_html', array( $this, 'mask_price_html' ), 20, 2 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'gate_purchasable' ), 20, 2 );
	}

	public function mask_price_html( string $price_html, $product ): string {
		if ( ! $this->should_hide() ) {
			return $price_html;
		}
		$message = (string) apply_filters(
			'b2b_essentials_hidden_price_message',
			__( 'Inicia sesión y solicita acceso B2B para ver precios.', 'b2b-essentials' )
		);
		return '<span class="b2b-essentials-hidden-price">' . esc_html( $message ) . '</span>';
	}

	public function gate_purchasable( bool $purchasable, $product ): bool {
		if ( $this->should_hide() ) {
			return false;
		}
		return $purchasable;
	}

	private function should_hide(): bool {
		if ( ! is_user_logged_in() ) {
			return (bool) apply_filters( 'b2b_essentials_hide_prices_for_guests', true );
		}
		$user = wp_get_current_user();
		if ( in_array( RoleManager::ROLE_PENDING, (array) $user->roles, true ) ) {
			return true;
		}
		return false;
	}
}
