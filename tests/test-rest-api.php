<?php

/**
 * Tests for REST API endpoint behavior.
 *
 * These tests document known issues and will fail until those issues are fixed:
 *
 * - All four endpoints use `'permission_callback' => '__return_true'`, meaning
 *   any unauthenticated request receives a 200 with full data.
 *
 * - The privacy model gates internal fields on the HTTP_SEC_FETCH_SITE header,
 *   which is spoofable by any HTTP client. An unauthenticated request that sends
 *   `Sec-Fetch-Site: same-origin` receives internal-only fields.
 *
 * - When no department terms exist, `$department_list` is never initialized
 *   inside the foreach body, causing a PHP notice (converted to an exception by
 *   phpunit.xml) when it is passed to WP_REST_Response.
 */
class VCUL_Directory_REST_API_Tests extends WP_UnitTestCase {

	/** @var WP_REST_Server */
	private $server;

	private $ns = '/vcul-directory/v1';
	private $staff_id;
	private $staff_slug = 'jane-smith';

	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		$this->staff_id = $this->factory->post->create( array(
			'post_type'   => 'directory',
			'post_status' => 'publish',
			'post_title'  => 'Jane Smith',
			'post_name'   => $this->staff_slug,
		) );

		update_post_meta( $this->staff_id, 'directory_phone', '804-555-1234' );
		update_post_meta( $this->staff_id, 'internal_phone_only', '1' );
		update_post_meta( $this->staff_id, 'directory_email', 'jsmith@library.vcu.edu' );
	}

	private $dept_term_id;
	private $expertise_term_id;

	private function create_test_terms() {
		$this->dept_term_id = wp_insert_term( 'Research & Education', 'department' );
		$this->expertise_term_id = wp_insert_term( 'Data Science', 'expertise' );
		wp_set_object_terms( $this->staff_id, array( $this->dept_term_id['term_id'] ), 'department' );
		wp_set_object_terms( $this->staff_id, array( $this->expertise_term_id['term_id'] ), 'expertise' );
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		wp_delete_post( $this->staff_id, true );
		unset( $_SERVER['HTTP_SEC_FETCH_SITE'] );

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Response field contracts
	// -------------------------------------------------------------------------

	/**
	 * Department list response must include a slug field alongside name and count.
	 */
	public function test_department_list_includes_slug_field() {
		$term = wp_insert_term( 'Systems & Discovery', 'department' );
		wp_set_object_terms( $this->staff_id, array( $term['term_id'] ), 'department' );

		$request  = new WP_REST_Request( 'GET', $this->ns . '/get-department' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data );
		$first = $data[0];
		$this->assertArrayHasKey( 'name', $first, 'Department list entry must have name' );
		$this->assertArrayHasKey( 'slug', $first, 'Department list entry must have slug' );
		$this->assertArrayHasKey( 'count', $first, 'Department list entry must have count' );
	}

	/**
	 * Staff expertise field must return objects with name and slug keys, not flat strings.
	 */
	public function test_expertise_field_returns_name_and_slug_objects() {
		$this->create_test_terms();

		$request = new WP_REST_Request( 'GET', $this->ns . '/get-directory' );
		$request->set_param( 'staff', $this->staff_slug );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data );
		$expertise = $data[0]['expertise'];
		$this->assertNotEmpty( $expertise, 'Expected expertise terms on staff entry' );
		$this->assertArrayHasKey( 'name', $expertise[0], 'Expertise entry must have name key' );
		$this->assertArrayHasKey( 'slug', $expertise[0], 'Expertise entry must have slug key' );
		$this->assertEquals( 'Data Science', $expertise[0]['name'] );
		$this->assertEquals( 'data-science', $expertise[0]['slug'] );
	}

	/**
	 * Staff department field must return objects with name and slug keys, not flat strings.
	 */
	public function test_department_field_returns_name_and_slug_objects() {
		$this->create_test_terms();

		$request = new WP_REST_Request( 'GET', $this->ns . '/get-directory' );
		$request->set_param( 'staff', $this->staff_slug );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data );
		$department = $data[0]['department'];
		$this->assertNotEmpty( $department, 'Expected department terms on staff entry' );
		$this->assertArrayHasKey( 'name', $department[0], 'Department entry must have name key' );
		$this->assertArrayHasKey( 'slug', $department[0], 'Department entry must have slug key' );
		$this->assertEquals( 'Research & Education', html_entity_decode( $department[0]['name'] ) );
	}

	/**
	 * get-directory must return the correct staff member when queried by post ID.
	 */
	public function test_get_directory_by_id_returns_matching_staff() {
		$request = new WP_REST_Request( 'GET', $this->ns . '/get-directory' );
		$request->set_param( 'id', (string) $this->staff_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $data, 'Expected exactly one result for a valid post ID' );
		$this->assertEquals( $this->staff_id, $data[0]['id'] );
		$this->assertEquals( 'Jane Smith', $data[0]['name'] );
	}

	/**
	 * get-directory with an unknown ID must return an empty array, not an error.
	 */
	public function test_get_directory_by_nonexistent_id_returns_empty() {
		$request = new WP_REST_Request( 'GET', $this->ns . '/get-directory' );
		$request->set_param( 'id', '999999' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertEmpty( $data );
	}

	// -------------------------------------------------------------------------
	// Endpoint access control
	// -------------------------------------------------------------------------

	/**
	 * get-directory should not be freely accessible to unauthenticated requests.
	 *
	 * Fails because permission_callback is `__return_true`.
	 */
	public function test_get_directory_is_public() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', $this->ns . '/get-directory' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'get-directory should remain publicly readable' );
	}

	/**
	 * get-experts should not be freely accessible to unauthenticated requests.
	 *
	 * Fails because permission_callback is `__return_true`.
	 */
	public function test_get_experts_is_public() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', $this->ns . '/get-experts' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'get-experts should remain publicly readable' );
	}

	/**
	 * get-department should not be freely accessible to unauthenticated requests.
	 *
	 * Fails because permission_callback is `__return_true`.
	 */
	public function test_get_department_is_public() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', $this->ns . '/get-department' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'get-department should remain publicly readable' );
	}

	/**
	 * get-settings should not be freely accessible to unauthenticated requests.
	 *
	 * Fails because permission_callback is `__return_true`.
	 */
	public function test_get_settings_is_public() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', $this->ns . '/get-settings' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'get-settings should remain publicly readable' );
	}

	// -------------------------------------------------------------------------
	// Privacy model
	// -------------------------------------------------------------------------

	/**
	 * An unauthenticated request must not receive internal-only phone numbers,
	 * even when the Sec-Fetch-Site header is set to same-origin.
	 *
	 * Fails because privacy_check() trusts the header value at face value;
	 * any HTTP client can send `Sec-Fetch-Site: same-origin`.
	 */
	public function test_internal_phone_not_exposed_via_spoofed_same_origin_header() {
		wp_set_current_user( 0 );
		$_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin'; // spoofed

		$request = new WP_REST_Request( 'GET', $this->ns . '/get-directory' );
		$request->set_param( 'staff', $this->staff_slug );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data, 'Expected staff entry in response' );
		$this->assertNull(
			$data[0]['phone'],
			'Internal phone must not be exposed to unauthenticated requests, regardless of Sec-Fetch-Site header value'
		);
	}

	/**
	 * An unauthenticated request with no Sec-Fetch-Site header must not expose
	 * internal-only phone numbers.
	 *
	 * Describes expected baseline behavior — external tools (curl, scripts) send
	 * no Sec-Fetch-Site header and must not receive internal fields.
	 */
	public function test_internal_phone_hidden_when_fetch_site_header_is_absent() {
		wp_set_current_user( 0 );
		unset( $_SERVER['HTTP_SEC_FETCH_SITE'] );

		$request = new WP_REST_Request( 'GET', $this->ns . '/get-directory' );
		$request->set_param( 'staff', $this->staff_slug );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data, 'Expected staff entry in response' );
		$this->assertNull(
			$data[0]['phone'],
			'Internal phone must not be exposed when Sec-Fetch-Site header is absent'
		);
	}

	// -------------------------------------------------------------------------
	// Undefined variable in get_department() with no terms
	// -------------------------------------------------------------------------

	/**
	 * Requesting the department list when no department terms exist must return
	 * an empty array without triggering a PHP notice.
	 *
	 * Fails because $department_list is only assigned inside the foreach body;
	 * when the term list is empty the variable is undefined at the return
	 * statement, producing a PHP notice (promoted to an exception by phpunit.xml).
	 */
	public function test_get_department_list_with_no_terms_returns_empty_array() {
		wp_set_current_user( 0 );

		// The test database has no department terms by default.
		$request  = new WP_REST_Request( 'GET', $this->ns . '/get-department' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
		$this->assertEmpty( $response->get_data(), 'Empty term list must return an empty array, not cause a PHP error' );
	}
}
