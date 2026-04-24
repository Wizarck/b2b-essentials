<?php
/**
 * NIF field on WC checkout + user profile.
 *
 * @package B2bEssentials\Checkout
 */

namespace B2bEssentials\Checkout;

use B2bEssentials\Fiscal\NifValidator;
use B2bEssentials\Fiscal\ViesClient;

defined( 'ABSPATH' ) || exit;

/**
 * Class NifFieldManager
 *
 * Adds a 'billing_nif' field to the WC checkout, validates format on the
 * server side (format only — blocking VIES call would break checkout for
 * intermittent VIES outages). VIES validation is scheduled async after
 * order creation and persisted to the customer profile.
 */
final class NifFieldManager {

	public function __construct(
		private readonly NifValidator $validator,
		private readonly ViesClient $vies,
	) {}

	public function register_hooks(): void {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_field' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_on_checkout' ) );
		add_action( 'woocommerce_checkout_update_user_meta', array( $this, 'persist_on_checkout' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_created', array( $this, 'schedule_vies_check' ) );
	}

	public function add_field( array $fields ): array {
		$fields['billing']['billing_nif'] = array(
			'label'       => __( 'NIF / CIF / NIE', 'b2b-essentials' ),
			'required'    => true,
			'class'       => array( 'form-row-wide' ),
			'priority'    => 31,
			'placeholder' => 'B12345678',
		);
		return $fields;
	}

	public function validate_on_checkout(): void {
		$nif = isset( $_POST['billing_nif'] ) ? wc_clean( wp_unslash( (string) $_POST['billing_nif'] ) ) : '';
		if ( '' === $nif ) {
			wc_add_notice( __( 'El NIF / CIF es obligatorio.', 'b2b-essentials' ), 'error' );
			return;
		}
		if ( ! $this->validator->is_valid( $nif ) ) {
			wc_add_notice( __( 'El NIF / CIF introducido no es válido.', 'b2b-essentials' ), 'error' );
		}
	}

	public function persist_on_checkout( int $user_id, array $data ): void {
		$nif = isset( $data['billing_nif'] ) ? wc_clean( (string) $data['billing_nif'] ) : '';
		if ( '' === $nif ) {
			return;
		}
		update_user_meta( $user_id, '_b2b_billing_nif', strtoupper( $nif ) );
	}

	/**
	 * Schedule VIES validation after checkout. We DO NOT block the order.
	 */
	public function schedule_vies_check( \WC_Order $order ): void {
		$user_id = $order->get_customer_id();
		if ( $user_id <= 0 ) {
			return;
		}
		$country = $order->get_billing_country();
		$nif     = (string) $order->get_meta( '_b2b_billing_nif' );

		// Only intra-EU non-ES customers need VIES; ES-to-ES is domestic.
		if ( 'ES' === $country || '' === $country || '' === $nif ) {
			return;
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				'b2b_essentials_vies_check',
				array( $user_id, $country . $nif ),
				'b2b-essentials'
			);
		} else {
			// No Action Scheduler → run inline (acceptable worst case).
			$this->run_vies_check( $user_id, $country . $nif );
		}
	}

	/**
	 * AS handler. Also callable directly.
	 */
	public function run_vies_check( int $user_id, string $vat_id ): void {
		$result = $this->vies->check( $vat_id );
		if ( null === $result ) {
			return;
		}

		update_user_meta( $user_id, '_b2b_billing_nif_vies_verified', $result['valid'] ? 1 : 0 );
		update_user_meta( $user_id, '_b2b_billing_nif_vies_checked_at', gmdate( 'c' ) );

		$event = $result['valid'] ? 'nif.vies.validated' : 'nif.vies.rejected';
		do_action( 'wc_ops_emit', $event, array( 'user_id' => $user_id, 'vat_id' => $vat_id ) + $result );
	}
}
