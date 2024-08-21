<?php
/*
Plugin Name: VCUL Staff Directory
Version: 1.0.5
Description: A WordPress plugin for managing a staff directory.
Author: VCUL Web Team
Author URI: https://library.vcu.edu
Plugin URI: https://github.com/
Text Domain: vcul-directory

GitHub Plugin URI: https://github.com/streeetlamp/VCUL-Staff-Directory-Plugin
*/

namespace VCUL\Directory;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// This plugin uses namespaces and requires PHP 5.3 or greater.
if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
	add_action(
		'admin_notices',
		function() {
			echo '<div class="error"><p>' . esc_html__( 'VCUL Staff Directory requires PHP 5.3 to function properly. Please upgrade PHP or deactivate the plugin.', 'vcul-directory' ) . '</p></div>';
		}
	);
	return;
} else {
	add_action( 'plugins_loaded', 'VCUL\Directory\bootstrap' );

	/**
	 * Provide the plugin version for enqueued scripts and styles.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function plugin_version() {
		return '0.0.10';
	}

	/* 
	* Filter to orderby directory with last name
	*/
	function orderby_lastname ($orderby_statement) 
	{
		$orderby_statement = "RIGHT(post_title, LOCATE(' ', REVERSE(post_title)) - 1) ASC";
		return $orderby_statement;
	}

	/* 
	* Checking if origin is same AND if the field is marked private or not. If origin is the same (meaning the request is coming from the same server) and the field is NOT marked private then we will display private fields.
	*/
	function privacy_check ($origin, $field = false) 
	{
		if ($origin == 'same-origin' || $field == false) {
			return true;
		}
		return false;
	}

	/**
	 * Register the /wp-json/vcul-directory/v1/get-directory endpoint so it will be cached.
	 */
	// function wprc_add_directory_endpoint($allowed_endpoints)
	// {
	// 	if (
	// 		!isset($allowed_endpoints['vcul-directory/v1'])
	// 	) {
	// 		$allowed_endpoints['vcul-directory/v1'][] = 'get-directory';
	// 		$allowed_endpoints['vcul-directory/v1'][] = 'get-experts';
	// 		$allowed_endpoints['vcul-directory/v1'][] = 'get-department';
	// 		$allowed_endpoints['vcul-directory/v1'][] = 'get-settings';
	// 	}
	// 	return $allowed_endpoints;
	// }
	// add_filter('wp_rest_cache/allowed_endpoints', 'VCUL\Directory\wprc_add_directory_endpoint', 10, 1);

	// function wprc_hide_clear_cache_button( $show ) {
  //   return true;
	// }
	// add_filter('wp_rest_cache/display_clear_cache_button', 'VCUL\Directory\wprc_hide_clear_cache_button', 10, 1);

	/**
	 * Starts things up.
	 *
	 * @since 0.1.0
	 */
	function bootstrap() {
		include_once __DIR__ . '/includes/directory-post-type.php';
		include_once __DIR__ . '/includes/directory-settings.php';
		include_once __DIR__ . '/includes/directory-shortcodes.php';
		include_once __DIR__ . '/includes/directory-contributor-role.php';
		include_once __DIR__ . '/includes/rest-api.php';
	}

}
