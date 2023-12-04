<?php

namespace VCUL\Directory\Post_Type;

/**
 * Provides the Directory post type slug.
 *
 * @since 0.0.1
 *
 * @return string
 */
function post_type_slug() {
	return 'directory';
}

/**
 * Provides the Expertise taxonomy slug.
 *
 * @since 0.0.1
 *
 * @return string
 */
function taxonomy_slug_expertise() {
	return 'expertise';
}


/**
 * Provides the Department taxonomy slug.
 *
 * @since 0.0.1
 *
 * @return string
 */
function taxonomy_slug_department() {
	return 'department';
}


/**
 * Provides an array of post meta keys associated with the directory.
 *
 * @since 0.0.1
 *
 * @return array
 */
function post_meta_keys() {
	return array(
		'directory_title' => array(
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'directory_site' => array(
			'type' => 'string',
			'sanitize_callback' => 'esc_url_raw',
		),
		'directory_email' => array(
			'type' => 'string',
			'sanitize_callback' => 'sanitize_email',
		),
		'directory_phone' => array(
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'directory_address' => array(
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'directory_pronouns' => array(
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
	);
}


/**
 * Sanitizes the value of checkbox meta fields.
 *
 * @param string $value The unsanitized value.
 *
 * @since 0.0.1
 *
 * @return string|boolean
*/
function sanitize_checkbox( $value ) {
	if ( '1' === $value ) {
		$value = '1';
	} else {
		$value = false;
	}

	return $value;
}

add_action( 'init', 'VCUL\Directory\Post_Type\register_post_type', 12 );

/**
 * Registers a post type for tracking information about Directory.
 *
 * @since 0.0.1
 */
function register_post_type() {
	$labels = array(
		'name' => 'Staff Directory',
		'singular_name' => 'Staff Member',
		'all_items' => 'All Entries',
		'view_item' => 'View Staff Member',
		'add_new_item' => 'Add New Staff Member',
		'edit_item' => 'Edit Staff Member',
		'update_item' => 'Update Staff Member',
		'search_items' => 'Search Directory',
		'not_found' => 'No entries found',
		'not_found_in_trash' => 'No entries found in trash',
		'featured_image' => 'Profile Photo',
	);

	$args = array(
		'labels' => $labels,
		'description' => 'Supporting internal and public staff directory.',
		'public' => true,
		'menu_position' => 5,
		'menu_icon' => 'dashicons-id-alt',
		'supports' => array(
			'title',
			'editor',
			'revisions',
			'thumbnail',
		),
		'taxonomies' => array(
		),
		'show_in_rest' => true,
	);

	\register_post_type( post_type_slug(), $args );
}

add_action( 'init', 'VCUL\Directory\Post_Type\register_taxonomies', 12 );
/**
 * Registers taxonomies that will be attached to the Directory post type.
 *
 * @since 0.0.1
 */
function register_taxonomies() {
	$labels = array(
		'name' => 'Expertise',
		'singular_name' => 'Expertise',
		'all_items' => 'All Expertise',
		'edit_item' => 'Edit Expertise',
		'view_item' => 'View Expertise',
		'update_item' => 'Update Expertise',
		'add_new_item' => 'Add New Expertise',
		'new_item_name' => 'New Expertise Name',
		'search_items' => 'Search Expertise',
		'popular_items' => 'Popular Expertise',
		'separate_items_with_commas' => 'Separate expertise with commas',
		'add_or_remove_items' => 'Add or remove expertise',
		'choose_from_most_used' => 'Choose from the most used expertise',
		'not_found' => 'No expertise found',
	);

	$args = array(
		'labels' => $labels,
		'description' => 'Directory expertise criteria.',
		'public' => true,
		'hierarchical' => false,
		'show_admin_column' => true,
		'show_in_rest' => true,
	);

	register_taxonomy( taxonomy_slug_expertise(), post_type_slug(), $args );

	$labels = array(
		'name' => 'Department',
		'singular_name' => 'Department',
		'all_items' => 'All Departments',
		'edit_item' => 'Edit Department',
		'view_item' => 'View Department',
		'update_item' => 'Update Department',
		'add_new_item' => 'Add New Department',
		'new_item_name' => 'New Department Name',
		'search_items' => 'Search Departments',
		'popular_items' => 'Popular Departments',
		'separate_items_with_commas' => 'Separate departments with commas',
		'add_or_remove_items' => 'Add or remove departments',
		'choose_from_most_used' => 'Choose from the most used departments',
		'not_found' => 'No departments found',
	);

	$args = array(
		'labels' => $labels,
		'description' => 'Directory department criteria.',
		'public' => true,
		'hierarchical' => false,
		'show_admin_column' => true,
		'show_in_rest' => true,
	);

	register_taxonomy( taxonomy_slug_department(), post_type_slug(), $args );

}

add_action( 'init', 'VCUL\Directory\Post_Type\register_meta' );
/**
 * Registers the Directory meta.
 *
 * @since 0.0.1
 */
function register_meta() {
	foreach ( post_meta_keys() as $key => $args ) {
		$args['single'] = true;
		\register_meta( 'post', $key, $args );
	}
}

add_action( 'admin_enqueue_scripts', 'VCUL\Directory\Post_Type\admin_enqueue_scripts', 10 );
/**
 * Enqueues the styles for the Directory information metabox.
 *
 * @since 0.0.1
 *
 * @param string $hook
 */
function admin_enqueue_scripts( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && get_current_screen()->id !== post_type_slug() ) {
		return;
	}

	wp_enqueue_style( 'vcul-directory-admin', plugins_url( 'css/directory-admin.css', dirname( __FILE__ ) ), array(), \VCUL\Directory\plugin_version() );
}


add_action( 'add_meta_boxes_' . post_type_slug(), 'VCUL\Directory\Post_Type\add_meta_boxes', 10 );
/**
 * Adds the metaboxes used to capture Directory information.
 *
 * @since 0.0.1
 */
function add_meta_boxes() {
	add_meta_box(
		'vcul-directory-meta',
		'Directory Information',
		'VCUL\Directory\Post_Type\display_directory_meta_box',
		post_type_slug(),
		'normal',
		'high'
	);
}

/**
 * Displays the metabox used to capture Directory information.
 *
 * @since 0.0.1
 *
 * @param WP_Post $post Object for the post currently being edited.
 */
function display_directory_meta_box( $post ) {
	$title = get_post_meta( $post->ID, 'directory_title', true );
	$pronouns = get_post_meta( $post->ID, 'directory_pronouns', true );
	$site = get_post_meta( $post->ID, 'directory_site', true );
	$email = get_post_meta( $post->ID, 'directory_email', true );
	$phone = get_post_meta( $post->ID, 'directory_phone', true );
	$address = get_post_meta( $post->ID, 'directory_address', true );

	wp_nonce_field( 'save-vcul-directory-meta', '_vcul_directory_meta_nonce' );
	?>
	<div class="vcul-directory-fieldset">
		<label>Title<br />
			<input type="text" class="widefat" name="directory_title" value="<?php echo esc_attr( $title ); ?>" />
		</label>
		<label>Pronouns<br />
			<input type="text" class="widefat" name="directory_pronouns" value="<?php echo esc_attr( $pronouns ); ?>" />
		</label>
	</div>

	<p><strong>Contact</strong></p>

	<div class="vcul-directory-fieldset">

		<label>Website<br />
			<input type="url" class="widefat" name="directory_site" pattern="https?://.+" value="<?php echo esc_attr( $site ); ?>" />
		</label>

		<label>Email<br />
			<input type="email" class="widefat" name="directory_email" value="<?php echo esc_attr( $email ); ?>" />
		</label>

		<label>Phone (ex: (804) 555-5555)<br />
			<input type="tel" class="widefat" name="directory_phone" pattern="\(\d{3}\) \d{3}-\d{4}" value="<?php echo esc_attr( $phone ); ?>" />
		</label>

		<label>Address<br />
			<input type="text" class="widefat" name="directory_address" value="<?php echo esc_attr( $address ); ?>" />
		</label>

	</div>
	<?php
}


add_action( 'save_post', 'VCUL\Directory\Post_Type\save_post', 10, 2 );
/**
 * Saves the information assigned to the Directory.
 *
 * @since 0.0.1
 *
 * @param int     $post_id ID of the post being saved.
 * @param WP_Post $post    Post object of the post being saved.
 */
function save_post( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( post_type_slug() !== $post->post_type ) {
		return;
	}

	if ( 'auto-draft' === $post->post_status ) {
		return;
	}

	if ( ! isset( $_POST['_vcul_directory_meta_nonce'] ) || ! wp_verify_nonce( $_POST['_vcul_directory_meta_nonce'], 'save-vcul-directory-meta' ) ) {
		return;
	}

	$keys = get_registered_meta_keys( 'post' );

	foreach ( post_meta_keys() as $key => $args ) {
		if ( isset( $_POST[ $key ] ) && '' !== $_POST[ $key ] && isset( $args['sanitize_callback'] ) ) {
			update_post_meta( $post_id, $key, $_POST[ $key ] );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}
}

add_action( 'wp_enqueue_scripts', 'VCUL\Directory\Post_Type\wp_enqueue_scripts' );
/**
 * Enqueue the scripts and styles used on the front end.
 *
 * @since 0.0.1
 */
function wp_enqueue_scripts() {
	if ( is_singular( post_type_slug() ) ) {
		wp_enqueue_style( 'vcul-directory', plugins_url( 'css/directory.css', dirname( __FILE__ ) ), \VCUL\Directory\plugin_version() );
	}
}

add_filter( 'body_class', 'VCUL\Directory\Post_Type\body_class' );
/**
 * Add 'section-Directory' as a body class when individual Directory are being displayed.
 *
 * @since 0.0.1
 *
 * @param array $classes Current body classes.
 *
 * @return array Modified body classes.
 */
function body_class( $classes ) {
	if ( is_singular( post_type_slug() ) ) {
		$classes[] = 'section-directory';
	}

	return $classes;
}

// add_filter( 'sfs_theme_header_elements', 'VCUL\Directory\Post_Type\header_elements' );
/**
 * Output custom page headers when viewing an individual Directory.
 *
 * @since 0.0.1
 *
 * @param array $headers Current header element values.
 *
 * @return array Modified header element values.
 */
function header_elements( $headers ) {
	if ( is_singular( post_type_slug() ) ) {
		$headers['page_sup'] = 'Directory';
		$headers['page_sub'] = 'Details';
	}

	return $headers;
}

// add_filter( 'nav_menu_css_class', 'VCUL\Directory\Post_Type\menu_class', 11, 3 );

/**
 * Add the 'active' class to a menu item when the search results page or an individual directory entry is viewed.
 *
 * @since 0.0.1
 *
 * @param array    $classes Current list of nav menu classes.
 * @param WP_Post  $item    Post object representing the menu item.
 * @param stdClass $args    Arguments used to create the menu.
 *
 * @return array Modified list of nav menu classes.
 */
function menu_class( $classes, $item, $args ) {
	$spine_menu = in_array( $args->menu, array( 'site', 'offsite' ), true );
	$options = get_option( 'directory_settings' );

	if ( $spine_menu && $options && isset( $options['active_menu_item'] ) ) {
		$directory = is_singular( post_type_slug() );
		$search_results = ( isset( $options['search_page'] ) && is_page( $options['search_page'] ) );
		$active_item = ( get_permalink( $options['active_menu_item'] ) === $item->url );

		if ( $active_item && ( $directory || $search_results ) ) {
			$classes[] = 'active';
		}
	}

	return $classes;
}

add_filter( 'wp_revisions_to_keep', 'VCUL\Directory\Post_Type\revisions_to_keep', 10, 2 );
/**
 * Limit Directory revisions to 1.
 *
 * Revision support has been added to the 'Directory' post type so that
 * 'Last Updated' data is provided, so only one revision needs to be kept.
 * The revisions link in the publish block is hidden via css.
 *
 * @since 0.0.4
 *
 * @param int     $num  Number of revisions to keep.
 * @param WP_Post $post Current post object.
 *
 * @return int $num Number of revisions to keep.
 */
function revisions_to_keep( $num, $post ) {
	if ( post_type_slug() === $post->post_type ) {
		$num = 1;
	}

	return $num;
}

add_action( 'rest_api_init', 'VCUL\Directory\Post_Type\register_api_fields' );
/**
 * Register the custom meta fields attached to a REST API response containing Directory data.
 *
 * @since 0.1.0
 */
function register_api_fields() {
	$args = array(
		'get_callback' => 'VCUL\Directory\Post_Type\get_api_meta_data',
		'update_callback' => null,
		'schema' => null,
	);

	foreach ( post_meta_keys() as $key => $_args ) {
		register_rest_field( post_type_slug(), $key, $args );
	}
}

/**
 * Return the value of a post meta field sanitized against a whitelist with the provided method.
 *
 * @since 0.1.0
 *
 * @param array           $object  The current post being processed.
 * @param string          $key     Name of the field being retrieved.
 * @param WP_Rest_Request $request The full current REST request.
 *
 * @return mixed Meta data associated with the post and field name.
 */
function get_api_meta_data( $object, $key, $request ) {
	if ( ! array_key_exists( $key, post_meta_keys() ) ) {
		return '';
	}

	$sanitize_callback = post_meta_keys()[ $key ]['sanitize_callback'];
	$meta_value = get_post_meta( $object['id'], $key, true );

	if ( 'sanitize_text_field' === $sanitize_callback || 'VCUL\Directory\Post_Type\sanitize_checkbox' === $sanitize_callback || 'VCUL\Directory\Post_Type\sanitize_state' === $sanitize_callback ) {
		return esc_html( $meta_value );
	}

	if ( 'absint' === $sanitize_callback ) {
		return absint( $meta_value );
	}

	if ( 'esc_url_raw' === $sanitize_callback ) {
		return esc_url( $meta_value );
	}

	if ( 'sanitize_email' === $sanitize_callback ) {
		return sanitize_email( $meta_value );
	}

	if ( 'wp_kses_post' === $sanitize_callback ) {
		return wp_kses_post( apply_filters( 'the_content', $meta_value ) );
	}

	return '';
}

add_filter( 'pll_get_post_types', 'VCUL\Directory\Post_Type\disable_post_type_translation_support' );
/**
 * Disables translation support for the Directory post type.
 *
 * @since 0.1.1
 *
 * @param array $post_types Post types with Polylang support.
 *
 * @return array
 */
function disable_post_type_translation_support( $post_types ) {
	unset( $post_types[ post_type_slug() ] );

	return $post_types;
}

add_filter( 'pll_get_taxonomies', 'VCUL\Directory\Post_Type\disable_taxonomy_translation_support' );
/**
 * Disables translation support for taxonomies associated with the Directory post type.
 *
 * @since 0.1.1
 *
 * @param array $post_types Post types with Polylang support.
 *
 * @return array
 */
function disable_taxonomy_translation_support( $taxonomies ) {
	$unset_taxonomies = array(
		taxonomy_slug_expertise(),
		taxonomy_slug_department(),
	);

	$taxonomies = array_diff( $taxonomies, $unset_taxonomies );

	return $taxonomies;
}