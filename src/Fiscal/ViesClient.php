<?php
/**
 * EU VIES VAT ID validator (async-friendly).
 *
 * @package B2bEssentials\Fiscal
 */

namespace B2bEssentials\Fiscal;

defined( 'ABSPATH' ) || exit;

/**
 * Class ViesClient
 *
 * Wraps the EU Commission's VIES SOAP endpoint. We use wp_remote_post with
 * a hand-written SOAP body to avoid pulling in a SOAP extension at runtime.
 *
 * Policy: never block checkout on VIES. Caller is expected to invoke this
 * asynchronously (scheduled action) and fall back gracefully on timeout.
 */
final class ViesClient {

	private const ENDPOINT = 'https://ec.europa.eu/taxation_customs/vies/services/checkVatService';

	/**
	 * @param string $vat_id E.g. "ESB67262048".
	 * @return array{valid:bool, country:string, number:string, name?:string, address?:string}|null Null on network failure.
	 */
	public function check( string $vat_id ): ?array {
		$vat_id = strtoupper( preg_replace( '/\s+/', '', $vat_id ) ?? '' );
		if ( strlen( $vat_id ) < 3 ) {
			return array( 'valid' => false, 'country' => '', 'number' => $vat_id );
		}

		$country = substr( $vat_id, 0, 2 );
		$number  = substr( $vat_id, 2 );

		$body = sprintf(
			'<?xml version="1.0" encoding="UTF-8"?>
			<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
			                  xmlns:urn="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
				<soapenv:Header/>
				<soapenv:Body>
					<urn:checkVat>
						<urn:countryCode>%s</urn:countryCode>
						<urn:vatNumber>%s</urn:vatNumber>
					</urn:checkVat>
				</soapenv:Body>
			</soapenv:Envelope>',
			esc_html( $country ),
			esc_html( $number )
		);

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'text/xml; charset=utf-8',
					'SOAPAction'   => '',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code >= 300 ) {
			return null;
		}

		$xml = (string) wp_remote_retrieve_body( $response );

		return array(
			'valid'   => (bool) preg_match( '#<valid>\s*true\s*</valid>#i', $xml ),
			'country' => $country,
			'number'  => $number,
			'name'    => $this->extract_tag( $xml, 'name' ),
			'address' => $this->extract_tag( $xml, 'address' ),
		);
	}

	private function extract_tag( string $xml, string $tag ): ?string {
		if ( preg_match( '#<' . preg_quote( $tag, '#' ) . '>([^<]*)</' . preg_quote( $tag, '#' ) . '>#i', $xml, $m ) ) {
			$value = trim( $m[1] );
			return '' === $value ? null : $value;
		}
		return null;
	}
}
