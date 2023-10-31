<?php
namespace VCUL\Directory\Shortcodes;

add_shortcode( 'vcul_directory', 'VCUL\Directory\Shortcodes\display_vcul_directory' );

/**
 * Inserts needed scripts, styles and a #app element
 *
 * @since 0.0.1
 */
function display_vcul_directory() {
	wp_enqueue_style( 'vcul-directory', plugins_url( 'css/directory.css', dirname( __FILE__ ) ), array(), \VCUL\Directory\plugin_version() );
	wp_enqueue_script( 'vcul-directory', plugins_url( 'js/directory.min.js', dirname( __FILE__ ) ), array( 'jquery' ), \VCUL\Directory\plugin_version(), true );
	wp_localize_script( 'vcul-directory', 'directory', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'vcul-directory' ),
	) );

  return "<div id='app'></div>";
}
