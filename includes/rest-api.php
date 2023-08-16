<?php namespace VCUL\Plugin\Directory;

class Rest_API {

	public static function get_filters( \WP_REST_Request $request ) {

		$params = $request->get_query_params();
		$is_search_block = $params['is-search-block'] ?? 'false';

		$data = array();

		$experts = get_terms(
			array(
				'taxonomy' => \VCUL\Directory\Post_Type\taxonomy_slug_expertise(),
				'hide_empty' => 0,
				'orderby' => 'term_id',
			)
		);

		try {
			foreach($experts as $expert) {

				error_log(print_r($expert, true));
				$expert = array(
					'name' => $expert->name,
					'count' => $expert->count,
				);

				$expertise_list[] = $expert;
			}


		$data['expertise'] = $expertise_list;


		$data['departments'] = get_terms(
			array(
				'taxonomy' => \VCUL\Directory\Post_Type\taxonomy_slug_department(),
				'hide_empty' => 0,
			)
		);

		return new \WP_REST_Response(
			$data,
			200
		);
		} catch ( Exception $e ) {
			return new \WP_Error( 'error', 'Sorry, something went wrong.', array( 'status' => 500 ) );
		}
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


		try {
			while ( $directory_query->have_posts() ) {

				$directory_query->the_post();
				$directory_title = get_post_meta( get_the_ID(), 'directory_title', true );

				$directory_entry = array(
					'id' => get_the_ID(),
					'name' => get_the_title(),
					'permalink' => get_the_permalink(),
					'position' => esc_attr( $directory_title ),
				);

				$the_directory[] = $directory_entry;
			}

			wp_reset_postdata();
		} catch ( Exception $e ) {
			return new \WP_Error( 'error', 'Sorry, something went wrong.', array( 'status' => 500 ) );
		}

		$data['directory'] = $the_directory;

		// $data['query'] = $directory_query->request; // for debugging
		$data['showingCount'] = $directory_query->post_count;
		$data['totalCount'] = $directory_query->found_posts;
		$data['numberOfPages'] = $directory_query->max_num_pages;


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
