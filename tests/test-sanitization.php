<?php

/**
 * Tests for post meta sanitization on save.
 *
 * These tests document known issues and will fail until those issues are fixed:
 *
 * - save_post() checks whether a `sanitize_callback` is registered for each
 *   field but then saves the raw $_POST value without calling it. The callback
 *   is a no-op during the admin save path.
 *
 * - sanitize_js(), registered as the sanitize_callback for `directory_guides`,
 *   calls base64_encode() on the value. That is encoding, not sanitization; it
 *   does not remove or neutralize dangerous content.
 */
class VCUL_Directory_Sanitization_Tests extends WP_UnitTestCase {

	private $post_id;

	public function setUp(): void {
		parent::setUp();

		$this->post_id = $this->factory->post->create( array(
			'post_type'   => 'directory',
			'post_status' => 'publish',
			'post_title'  => 'Test Staff',
		) );
	}

	public function tearDown(): void {
		wp_delete_post( $this->post_id, true );
		$this->clear_post_globals();
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function clear_post_globals(): void {
		$keys = array_merge(
			array( '_vcul_directory_meta_nonce' ),
			array_keys( \VCUL\Directory\Post_Type\post_meta_keys() )
		);
		foreach ( $keys as $key ) {
			unset( $_POST[ $key ] );
		}
	}

	/**
	 * Populate $_POST and trigger save_post() as if an admin form was submitted.
	 */
	private function simulate_save( array $fields ): void {
		$_POST['_vcul_directory_meta_nonce'] = wp_create_nonce( 'save-vcul-directory-meta' );
		foreach ( $fields as $key => $value ) {
			$_POST[ $key ] = $value;
		}
		\VCUL\Directory\Post_Type\save_post( $this->post_id, get_post( $this->post_id ) );
	}

	// -------------------------------------------------------------------------
	// Email field
	// -------------------------------------------------------------------------

	/**
	 * directory_email must be passed through sanitize_email() before storage.
	 *
	 * Fails because save_post() stores the raw $_POST value; the sanitize_callback
	 * is defined in post_meta_keys() but never invoked on save.
	 */
	public function test_email_field_is_sanitized_on_save() {
		$raw = 'not-an-email"><script>alert(1)</script>';

		$this->simulate_save( array( 'directory_email' => $raw ) );

		$stored   = get_post_meta( $this->post_id, 'directory_email', true );
		$expected = sanitize_email( $raw ); // '' — sanitize_email strips invalid input

		$this->assertNotEquals(
			$raw,
			$stored,
			'Raw unsanitized email value must not be stored'
		);
		$this->assertEquals(
			$expected,
			$stored,
			'Stored email must equal the output of sanitize_email()'
		);
	}

	// -------------------------------------------------------------------------
	// Text fields
	// -------------------------------------------------------------------------

	/**
	 * directory_title must be passed through sanitize_text_field() before storage,
	 * which strips HTML tags and extra whitespace.
	 *
	 * Fails because save_post() stores the raw $_POST value.
	 */
	public function test_title_field_strips_html_on_save() {
		$raw = '<b>Head of <em>Reference</em></b><script>alert(1)</script>';

		$this->simulate_save( array( 'directory_title' => $raw ) );

		$stored   = get_post_meta( $this->post_id, 'directory_title', true );
		$expected = sanitize_text_field( $raw );

		$this->assertNotEquals( $raw, $stored, 'Raw HTML must not be stored in a plain-text meta field' );
		$this->assertEquals( $expected, $stored, 'Stored title must equal sanitize_text_field() output' );
	}

	/**
	 * directory_phone must be passed through sanitize_text_field() before storage.
	 *
	 * Fails because save_post() stores the raw $_POST value.
	 */
	public function test_phone_field_strips_html_on_save() {
		$raw = '804-555-1234<img src=x onerror=alert(1)>';

		$this->simulate_save( array( 'directory_phone' => $raw ) );

		$stored   = get_post_meta( $this->post_id, 'directory_phone', true );
		$expected = sanitize_text_field( $raw );

		$this->assertNotEquals( $raw, $stored, 'Raw HTML must not be stored in the phone field' );
		$this->assertEquals( $expected, $stored, 'Stored phone must equal sanitize_text_field() output' );
	}

	// -------------------------------------------------------------------------
	// URL fields
	// -------------------------------------------------------------------------

	/**
	 * vcul-directory-cv is declared as a URL field with sanitize_callback esc_url_raw.
	 * It must not store arbitrary strings that are not valid URLs.
	 *
	 * Fails because save_post() stores the raw $_POST value.
	 */
	public function test_cv_url_field_is_sanitized_on_save() {
		$raw = 'javascript:alert(document.cookie)';

		$this->simulate_save( array( 'vcul-directory-cv' => $raw ) );

		$stored   = get_post_meta( $this->post_id, 'vcul-directory-cv', true );
		$expected = esc_url_raw( $raw ); // strips javascript: scheme

		$this->assertNotEquals( $raw, $stored, 'javascript: URL must not be stored in CV field' );
		$this->assertEquals( $expected, $stored, 'Stored CV URL must equal esc_url_raw() output' );
	}

	// -------------------------------------------------------------------------
	// sanitize_js / directory_guides
	// -------------------------------------------------------------------------

	/**
	 * sanitize_js() is registered as the sanitize_callback for directory_guides.
	 * It must actually sanitize (or validate as a URL), not merely encode.
	 *
	 * Fails because sanitize_js() calls base64_encode(), which is encoding, not
	 * sanitization. A valid URL passed through it comes back as a base64 string,
	 * not the original URL.
	 */
	public function test_sanitize_js_returns_sanitized_url_not_base64_string() {
		$url    = 'https://guides.library.vcu.edu/staff';
		$result = \VCUL\Directory\Post_Type\sanitize_js( $url );

		$this->assertEquals(
			$url,
			$result,
			'sanitize_js() must return a sanitized URL, not a base64-encoded string'
		);
	}

	/**
	 * sanitize_js() must neutralize script content, not just encode it.
	 *
	 * base64_encode('<script>...') does not contain the literal string "<script>"
	 * but the encoded payload is trivially reversible — it is not sanitized.
	 * A proper callback should strip or reject non-URL input entirely.
	 *
	 * Fails because sanitize_js() returns base64_encode($value).
	 */
	public function test_sanitize_js_rejects_non_url_input() {
		$script = '<script>alert("xss")</script>';
		$result = \VCUL\Directory\Post_Type\sanitize_js( $script );

		// A sanitize callback for a URL field should return an empty string
		// (or a safe value) when the input is not a valid URL.
		$this->assertEmpty(
			$result,
			'sanitize_js() must return empty string for non-URL input, not a base64-encoded payload'
		);
	}

	/**
	 * directory_guides must be stored as a sanitized URL, not as a base64 blob,
	 * when submitted via the admin form.
	 *
	 * Fails because: (1) save_post() does not call the sanitize_callback, and
	 * (2) even if it did, sanitize_js() would return base64 instead of a URL.
	 */
	public function test_guides_field_stores_url_not_base64_on_save() {
		$url = 'https://guides.library.vcu.edu/staff';

		$this->simulate_save( array( 'directory_guides' => $url ) );

		$stored = get_post_meta( $this->post_id, 'directory_guides', true );

		$this->assertEquals(
			$url,
			$stored,
			'directory_guides must store the URL as-is (or sanitized), not as a base64-encoded string'
		);
	}
}
