<?php
/**
 * @package B2bEssentials\Tests\Unit
 */

namespace B2bEssentials\Tests\Unit;

use B2bEssentials\Fiscal\NifValidator;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic tests — no WordPress bootstrap required.
 */
final class NifValidatorTest extends TestCase {

	private NifValidator $validator;

	protected function setUp(): void {
		$this->validator = new NifValidator();
	}

	public function test_real_cif_caribbean_yummy(): void {
		$this->assertTrue( $this->validator->is_valid( 'B67262048' ) );
		$this->assertSame( 'CIF', $this->validator->detect_type( 'B67262048' ) );
	}

	public function test_known_valid_dnis(): void {
		$this->assertTrue( $this->validator->is_valid( '00000000T' ) );
		$this->assertTrue( $this->validator->is_valid( '12345678Z' ) );
	}

	public function test_known_valid_nie(): void {
		$this->assertTrue( $this->validator->is_valid( 'X1234567L' ) );
	}

	public function test_case_insensitive_and_whitespace(): void {
		$this->assertTrue( $this->validator->is_valid( ' b67262048 ' ) );
	}

	public function test_rejects_garbage(): void {
		$this->assertFalse( $this->validator->is_valid( '' ) );
		$this->assertFalse( $this->validator->is_valid( '12345678A' ) );
		$this->assertFalse( $this->validator->is_valid( 'NOT_A_NIF' ) );
	}

	public function test_rejects_wrong_check_digit(): void {
		// Swap the control of a real CIF → must fail.
		$this->assertFalse( $this->validator->is_valid( 'B67262040' ) );
	}
}
