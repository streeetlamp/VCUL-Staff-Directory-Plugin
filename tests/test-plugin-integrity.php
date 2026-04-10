<?php

/**
 * Tests for general plugin integrity and the privacy_check() utility.
 *
 * These tests document known issues and will fail until those issues are fixed:
 *
 * - The Version field in the plugin file header (1.0.13) does not match the
 *   string returned by plugin_version() (0.0.10). Assets are cache-busted with
 *   the plugin_version() value, so the declared version is effectively unused.
 *
 * - privacy_check() gates access to internal fields solely on the value of the
 *   HTTP_SEC_FETCH_SITE header. Any HTTP client can send an arbitrary header
 *   value; authentication state is never consulted.
 */
class VCUL_Directory_Plugin_Integrity_Tests extends WP_UnitTestCase {

	// -------------------------------------------------------------------------
	// Version consistency
	// -------------------------------------------------------------------------

	/**
	 * The Version declared in the plugin file header must match plugin_version().
	 *
	 * Fails: the header declares 1.0.13 but plugin_version() returns 0.0.10.
	 */
	public function test_plugin_version_matches_header_declaration() {
		$plugin_file = dirname( __DIR__ ) . '/plugin.php';
		$data        = get_file_data( $plugin_file, array( 'Version' => 'Version' ) );

		$this->assertEquals(
			$data['Version'],
			\VCUL\Directory\plugin_version(),
			sprintf(
				'Plugin header version (%s) must match plugin_version() return value (%s)',
				$data['Version'],
				\VCUL\Directory\plugin_version()
			)
		);
	}

	// -------------------------------------------------------------------------
	// privacy_check() unit tests
	// -------------------------------------------------------------------------

	/**
	 * privacy_check() must not expose internal fields solely because the
	 * Sec-Fetch-Site header claims same-origin when the user is not authenticated.
	 *
	 * Fails: privacy_check() returns true whenever $origin === 'same-origin',
	 * with no regard for authentication state.
	 */
	public function test_privacy_check_requires_authentication_not_just_header() {
		wp_set_current_user( 0 ); // unauthenticated

		// Simulates a request where HTTP_SEC_FETCH_SITE was set to same-origin,
		// either legitimately or by a spoofing client.
		$origin = 'same-origin';
		$field_is_internal = '1'; // internal_phone_only or internal_pic_only = 1

		$result = \VCUL\Directory\privacy_check( $origin, $field_is_internal );

		$this->assertFalse(
			$result,
			'privacy_check() must return false for unauthenticated requests even when Sec-Fetch-Site is same-origin'
		);
	}

	/**
	 * privacy_check() must hide internal fields when the request has no
	 * Sec-Fetch-Site header (e.g. curl, scripts, API clients).
	 *
	 * This documents expected baseline behavior.
	 */
	public function test_privacy_check_hides_internal_fields_when_no_header() {
		$result = \VCUL\Directory\privacy_check( null, '1' );

		$this->assertFalse(
			$result,
			'privacy_check() must return false (hide data) when origin is null and field is internal-only'
		);
	}

	/**
	 * privacy_check() must expose non-internal fields to any origin.
	 *
	 * Fields with $field == false (not marked internal-only) are public and
	 * should be returned regardless of the Sec-Fetch-Site header.
	 */
	public function test_privacy_check_exposes_public_fields_regardless_of_origin() {
		$public  = \VCUL\Directory\privacy_check( null, false );
		$public2 = \VCUL\Directory\privacy_check( 'cross-site', false );

		$this->assertTrue( $public,  'Non-internal fields must be exposed when origin is null' );
		$this->assertTrue( $public2, 'Non-internal fields must be exposed for cross-site origins' );
	}
}
