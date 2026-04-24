<?php
/**
 * B2B role management.
 *
 * @package B2bEssentials\Roles
 */

namespace B2bEssentials\Roles;

defined( 'ABSPATH' ) || exit;

/**
 * Class RoleManager
 *
 * Registers two roles:
 *   - b2b_customer          : approved B2B buyer (sees prices, can checkout)
 *   - b2b_customer_pending  : registered but awaiting admin approval
 *
 * Capabilities mirror WooCommerce's 'customer' role. Role creation is
 * idempotent and triggered on activation; init() is called each boot as
 * a belt-and-suspenders self-heal.
 */
final class RoleManager {

	public const ROLE_APPROVED = 'b2b_customer';
	public const ROLE_PENDING  = 'b2b_customer_pending';

	/**
	 * Activation hook: create roles if missing.
	 */
	public static function register_roles(): void {
		self::ensure_role( self::ROLE_APPROVED, __( 'B2B Customer', 'b2b-essentials' ) );
		self::ensure_role( self::ROLE_PENDING, __( 'B2B Customer (Pending Approval)', 'b2b-essentials' ) );
	}

	/**
	 * Runtime init — idempotent self-heal + approval transitions.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_roles' ) );
		add_action( 'b2b_essentials_approve_customer', array( __CLASS__, 'approve_customer' ) );
		add_action( 'b2b_essentials_reject_customer', array( __CLASS__, 'reject_customer' ) );
	}

	public static function approve_customer( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		$user->remove_role( self::ROLE_PENDING );
		$user->add_role( self::ROLE_APPROVED );
		do_action( 'wc_ops_emit', 'client.b2b.approved', array( 'user_id' => $user_id ) );
	}

	public static function reject_customer( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		$user->remove_role( self::ROLE_PENDING );
		do_action( 'wc_ops_emit', 'client.b2b.rejected', array( 'user_id' => $user_id ) );
	}

	private static function ensure_role( string $role, string $display_name ): void {
		if ( get_role( $role ) instanceof \WP_Role ) {
			return;
		}
		$customer = get_role( 'customer' );
		$caps     = $customer ? $customer->capabilities : array( 'read' => true );
		add_role( $role, $display_name, $caps );
	}
}
