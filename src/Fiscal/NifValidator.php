<?php
/**
 * Spanish NIF / CIF / NIE format validator.
 *
 * @package B2bEssentials\Fiscal
 */

namespace B2bEssentials\Fiscal;

defined( 'ABSPATH' ) || exit;

/**
 * Class NifValidator
 *
 * Algorithmic check digit validation. Does not call AEAT's censo API.
 * VIES (for intra-EU) is handled separately by ViesClient.
 *
 * Three document shapes:
 *   - NIF (natural person, Spanish):  8 digits + letter.
 *   - NIE (foreigner):                X/Y/Z + 7 digits + letter.
 *   - CIF (company):                  letter + 7 digits + control.
 */
final class NifValidator {

	private const DNI_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';
	private const CIF_LETTERS = 'JABCDEFGHI';
	// Companies whose CIF first letter makes the control digit alphabetic:
	private const CIF_ALPHA_SERIES = 'KLMNPQRSW';

	public function is_valid( string $value ): bool {
		$value = strtoupper( trim( $value ) );
		if ( '' === $value ) {
			return false;
		}
		if ( preg_match( '/^[0-9]{8}[A-Z]$/', $value ) ) {
			return $this->check_dni( $value );
		}
		if ( preg_match( '/^[XYZ][0-9]{7}[A-Z]$/', $value ) ) {
			return $this->check_nie( $value );
		}
		if ( preg_match( '/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/', $value ) ) {
			return $this->check_cif( $value );
		}
		return false;
	}

	/**
	 * Shape: 'DNI' | 'NIE' | 'CIF' | null.
	 */
	public function detect_type( string $value ): ?string {
		$value = strtoupper( trim( $value ) );
		if ( preg_match( '/^[0-9]{8}[A-Z]$/', $value ) ) {
			return 'DNI';
		}
		if ( preg_match( '/^[XYZ][0-9]{7}[A-Z]$/', $value ) ) {
			return 'NIE';
		}
		if ( preg_match( '/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/', $value ) ) {
			return 'CIF';
		}
		return null;
	}

	private function check_dni( string $value ): bool {
		$number = (int) substr( $value, 0, 8 );
		$letter = substr( $value, -1 );
		return self::DNI_LETTERS[ $number % 23 ] === $letter;
	}

	private function check_nie( string $value ): bool {
		$prefix_map = array( 'X' => '0', 'Y' => '1', 'Z' => '2' );
		$first      = substr( $value, 0, 1 );
		if ( ! isset( $prefix_map[ $first ] ) ) {
			return false;
		}
		$number = (int) ( $prefix_map[ $first ] . substr( $value, 1, 7 ) );
		$letter = substr( $value, -1 );
		return self::DNI_LETTERS[ $number % 23 ] === $letter;
	}

	private function check_cif( string $value ): bool {
		$letter = substr( $value, 0, 1 );
		$digits = substr( $value, 1, 7 );
		$ctrl   = substr( $value, -1 );

		$sum_even = 0;
		for ( $i = 1; $i < 7; $i += 2 ) {
			$sum_even += (int) $digits[ $i ];
		}
		$sum_odd = 0;
		for ( $i = 0; $i < 7; $i += 2 ) {
			$doubled  = (int) $digits[ $i ] * 2;
			$sum_odd += intdiv( $doubled, 10 ) + ( $doubled % 10 );
		}
		$total    = $sum_even + $sum_odd;
		$unit     = $total % 10;
		$expected = 0 === $unit ? 0 : 10 - $unit;

		if ( str_contains( self::CIF_ALPHA_SERIES, $letter ) ) {
			return self::CIF_LETTERS[ $expected ] === $ctrl;
		}
		if ( ctype_digit( $ctrl ) ) {
			return (int) $ctrl === $expected;
		}
		return self::CIF_LETTERS[ $expected ] === $ctrl;
	}
}
