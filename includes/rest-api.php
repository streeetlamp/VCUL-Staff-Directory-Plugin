<?php

namespace VCUL\Plugin\Directory;

class Rest_API
{
	// Debug ?_envelope&_wpnonce=5xti%20DfaS%20Y1hC%20GUmQ%20lKLZ%20Fe53
	public static function get_experts(\WP_REST_Request $request)
	{

		$params = $request->get_query_params();
		$is_filtered = $params['expertise'] ?? false;

		$expert_check = term_exists($is_filtered,  \VCUL\Directory\Post_Type\taxonomy_slug_expertise());
		$expert_exists = $expert_check !== 0 && $expert_check !== null ? true : false;

		if (!$expert_exists) {
			$experts = get_terms(
				array(
					'taxonomy' => \VCUL\Directory\Post_Type\taxonomy_slug_expertise(),
					'hide_empty' => 1,
					'orderby' => 'term_id',
				)
			);

			try {
				foreach ($experts as $expert) {

					$expert = array(
						'name' => $expert->name,
						'count' => $expert->count,
					);

					$expertise_list[] = $expert;
				}


				return new \WP_REST_Response(
					$expertise_list,
					200
				);
			} catch (Exception $e) {
				return new \WP_Error('error', 'Sorry, something went wrong.', array('status' => 500));
			}
		} else {

			$expert_id = get_term_by('name', $is_filtered, \VCUL\Directory\Post_Type\taxonomy_slug_expertise());

			$expert_query_args = array(
				'posts_per_page' => -1,
				'post_type' => \VCUL\Directory\Post_Type\post_type_slug(),
				'tax_query' => array(
					array(
						'taxonomy' => \VCUL\Directory\Post_Type\taxonomy_slug_expertise(),
						'field' => 'term_id',
						'terms' => $expert_id->term_id,
					)
				)
			);

			$expert_query = new \WP_Query($expert_query_args);
			$expert_list = array();

			try {
				while ($expert_query->have_posts()) {
					$expert_query->the_post();

					$expertise = wp_get_object_terms(get_the_ID(), 'expertise', array('fields' => 'names'));
					$department = wp_get_object_terms(get_the_ID(), 'department', array('fields' => 'names'));
					$directory_title = get_post_meta(get_the_ID(), 'directory_title', true);

					$expert = array(
						'id' => get_the_ID(),
						'name' => get_the_title(),
						'permalink' => get_the_permalink(),
						'position' => esc_attr($directory_title),
						'bio' => get_the_content(),
						'expertise' => $expertise,
						'department' => $department,
						'headshot' => wp_get_attachment_url(get_post_thumbnail_id()),
					);

					$expert_list[] = $expert;
				}
				wp_reset_postdata();
			} catch (Exception $e) {
				return new \WP_Error('error', 'Sorry, something went wrong.', array('status' => 500));
			}

			return new \WP_REST_Response(
				$expert_list,
				200
			);
		}
	}

	public static function get_department(\WP_REST_Request $request)
	{
		$params = $request->get_query_params();
		$is_filtered = $params['dept'] ?? false;

		$dept_id = get_term_by('name', $is_filtered, \VCUL\Directory\Post_Type\taxonomy_slug_department());

		$dept_check = term_exists($is_filtered, \VCUL\Directory\Post_Type\taxonomy_slug_department());
		$dept_exists = $dept_check !== 0 && $dept_check !== null ? true : false;

		if (!$dept_exists) {
			$departments = get_terms(
				array(
					'taxonomy' => \VCUL\Directory\Post_Type\taxonomy_slug_department(),
					'hide_empty' => 1,
					'orderby' => 'term_id',
				)
			);

			try {
				foreach ($departments as $department) {

					$department = array(
						'name' => $department->name,
						'count' => $department->count,
					);

					$department_list[] = $department;
				}


				return new \WP_REST_Response(
					$department_list,
					200
				);
			} catch (Exception $e) {
				return new \WP_Error('error', 'Sorry, something went wrong.', array('status' => 500));
			}
		} else {

			$dept_query_args = array(
				'posts_per_page' => -1,
				'post_type' => \VCUL\Directory\Post_Type\post_type_slug(),
				'tax_query' => array(
					array(
						'taxonomy' => \VCUL\Directory\Post_Type\taxonomy_slug_department(),
						'field' => 'term_id',
						'terms' => $dept_id->term_id,
					)
				)
			);
			$department_query = new \WP_Query($dept_query_args);

			try {
				while ($department_query->have_posts()) {
					$department_query->the_post();

					$directory_title = get_post_meta(get_the_ID(), 'directory_title', true);
					$expertise = wp_get_object_terms(get_the_ID(), 'expertise', array('fields' => 'names'));
					$department = wp_get_object_terms(get_the_ID(), 'department', array('fields' => 'names'));

					$dept_entry = array(
						'id' => get_the_ID(),
						'name' => get_the_title(),
						'permalink' => get_the_permalink(),
						'position' => esc_attr($directory_title),
						'expertise' => $expertise,
						'department' => $department,
						'bio' => get_the_content(),
						'headshot' => wp_get_attachment_url(get_post_thumbnail_id()),
					);

					$the_department[] = $dept_entry;
				}
				wp_reset_postdata();
			} catch (Exception $e) {
				return new \WP_Error('error', 'Sorry, something went wrong.', array('status' => 500));
			}

			return new \WP_REST_Response(
				$the_department,
				200
			);
		}
	}


	public static function get_directory(\WP_REST_Request $request)
	{

		$params = $request->get_query_params();
		// error_log(\VCUL\Directory\privacy_check($_SERVER['HTTP_SEC_FETCH_SITE']));

		$posts_per_page = $params['postsPerPage'] ?? -1;
		$orderby = $params['orderBy'] ?? 'title';
		$order = $params['order'] ?? 'ASC';
		$page = $params['page'] ?? 1;
		$staff = $params['staff'] ?? '';
		add_filter('posts_orderby', 'VCUL\Directory\orderby_lastname');


		// $data = array( $params, $orderby, $order );
		$data = array();

		$directory_query_args = array(
			'post_status' => 'publish',
			'posts_per_page' => $posts_per_page,
			'paged' => intval($page),
			'post_type' => \VCUL\Directory\Post_Type\post_type_slug(),
			'name' => $staff,
		);


		$directory = array();

		$directory_query = new \WP_Query($directory_query_args);

		try {
			while ($directory_query->have_posts()) {

				$directory_query->the_post();

				$expertise = wp_get_object_terms(get_the_ID(), 'expertise', array('fields' => 'names'));
				$department = wp_get_object_terms(get_the_ID(), 'department', array('fields' => 'names'));
				$directory_title = get_post_meta(get_the_ID(), 'directory_title', true);
				$directory_cv = get_post_meta( get_the_ID(), 'vcul-directory-cv', true);
				$directory_cv = $directory_cv['url'] ?? null;
				$faculty_rank = get_post_meta( get_the_ID(), 'directory_rank', true);
				$libcal_link = get_post_meta( get_the_ID(), 'directory_libcal', true);
				$pronouns = get_post_meta( get_the_ID(), 'directory_pronouns', true);
				$internal_phone = get_post_meta( get_the_ID(), 'internal_phone_only', true);
				$phone = \VCUL\Directory\privacy_check($_SERVER['HTTP_SEC_FETCH_SITE'], $internal_phone) ? get_post_meta( get_the_ID(), 'directory_phone', true) : null;
				$directory_address = \VCUL\Directory\privacy_check($_SERVER['HTTP_SEC_FETCH_SITE'], true) ? get_post_meta( get_the_ID(), 'directory_address', true) : null;
				$email = get_post_meta( get_the_ID(), 'directory_email', true);
				$guides = get_post_meta( get_the_ID(), 'directory_guides', true);
				$protitle = get_post_meta( get_the_ID(), 'directory_pro_title', true);
				$headshot_privacy = get_post_meta( get_the_ID(), 'internal_pic_only', true);
				$headshot = \VCUL\Directory\privacy_check($_SERVER['HTTP_SEC_FETCH_SITE'], $headshot_privacy) ? wp_get_attachment_url(get_post_thumbnail_id()) : plugins_url('img/anon_headshot.jpg', dirname( __FILE__ ) );
				
				if ($headshot == false) {
					$headshot = plugins_url('img/anon_headshot.jpg', dirname( __FILE__ ) );
				}
				
				$directory_entry = array(
					'id' => get_the_ID(),
					'slug' => get_post_field('post_name', get_post()),
					'name' => get_the_title(),
					'permalink' => get_the_permalink(),
					'position' => esc_attr($directory_title),
					'expertise' => $expertise,
					'department' => $department,
					'bio' => get_the_content(),
					'headshot' => $headshot,
					'cv' => $directory_cv,
					'rank' => $faculty_rank,
					'libcal_link' => $libcal_link,
					'pronouns' => $pronouns,
					'phone' => $phone,
					'email' => $email,
					'location' => $directory_address,
					'guides' => $guides,
					'protitle' => $protitle
				);

				$the_directory[] = $directory_entry;
			}

			wp_reset_postdata();
		} catch (Exception $e) {
			return new \WP_Error('error', 'Sorry, something went wrong.', array('status' => 500));
		}
		remove_filter('posts_orderby', 'VCUL\Directory\orderby_lastname');

		$data = $the_directory;

		// $data['query'] = $directory_query->request; // for debugging
		// $data['showingCount'] = $directory_query->post_count;
		// $data['totalCount'] = $directory_query->found_posts;
		// $data['numberOfPages'] = $directory_query->max_num_pages;


		return new \WP_REST_Response(
			$data,
			200
		);
	}


	public static function register_endpoints()
	{

		// wp-json/vcul-directory/v1/get-directory
		// get-directory?staff=first-lastname

		register_rest_route(
			'vcul-directory/v1',
			'get-directory',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_directory'),
				'permission_callback' => '__return_true'
			)
		);

		// /wp-json/vcul-directory/v1/get-experts
		register_rest_route(
			'vcul-directory/v1',
			'get-experts',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_experts'),
				'permission_callback' => '__return_true'
			)
		);

		// /wp-json/vcul-directory/v1/get-department?dept=DEPTNAME
		// this gives you all members of a department via ?dept param
		register_rest_route(
			'vcul-directory/v1',
			'get-department',
			array(
				'methods'             => 'GET',
				'callback'            => array(__CLASS__, 'get_department'),
				'permission_callback' => '__return_true'
			)
		);
	}

	public static function init()
	{
		add_action('rest_api_init', __CLASS__ . '::register_endpoints');
	}

}

Rest_API::init();
