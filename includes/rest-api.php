<?php namespace VCUL\Plugin\Directory;

class Rest_API {

	public static function get_filters( \WP_REST_Request $request ) {

		$params = $request->get_query_params();
		$is_search_block = $params['is-search-block'] ?? 'false';

		$data = array();

		$data['expertise'] = get_terms(
			array(
				'taxonomy' => \VCUL\Directory\Post_Type\taxonomy_slug_expertise(),
				'hide_empty' => 0,
				'orderby' => 'term_id',
			)
		);

		$data['department'] = get_terms(
			array(
				'taxonomy' => \VCUL\Directory\Post_Type\taxonomy_slug_department(),
				'hide_empty' => 0,
			)
		);

		return new \WP_REST_Response(
			$data,
			200
		);

	}


	public static function get_directory( \WP_REST_Request $request ) {
		
		$params = $request->get_query_params();
		error_log( print_r($params, true));


		$posts_per_page = $params['postsPerPage'] ?? 20;
		$orderby = $params['orderBy'] ?? 'title';
		$order = $params['order'] ?? 'ASC';
		$page = $params['page'] ?? 1;

		// $data = array( $params, $orderby, $order );
		$data = array();

		$directory_query_args = array(
			'post_status' => 'publish',
			'posts_per_page' => $posts_per_page,
			'paged' => intval( $page ),
			'post_type' => \VCUL\Directory\Post_Type\post_type_slug(),
			'orderby' => array(
				'title' => $order,
			));


		$directory = array();

		$directory_query = new \WP_Query( $directory_query_args );

		// $data['query'] = $directory_query->request; // for debugging
		$data['showingCount'] = $directory_query->post_count;
		$data['totalCount'] = $directory_query->found_posts;
		$data['numberOfPages'] = $directory_query->max_num_pages;

		$data['directory'] = $directory;

		return new \WP_REST_Response(
			$data,
			200
		);

	}


	public static function register_endpoints() {

		// wp-json/vcul-directory/v1/get-directory
		register_rest_route(
			'vcul-directory/v1',
			'get-directory',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_directory' ),
				'permission_callback' => '__return_true'
			)
		);

		// /wp-json/vcul-directory/v1/get-filters
		register_rest_route(
			'vcul-directory/v1',
			'get-filters',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_filters' ),
				'permission_callback' => '__return_true'
			)
		);

	}


	public static function init() {

		add_action( 'rest_api_init', __CLASS__ . '::register_endpoints' );

	}
}

Rest_API::init();
