<?php
namespace VCUL\Directory\Settings;

add_action( 'admin_init', 'VCUL\Directory\Settings\register_settings' );
/**
 * Registers settings for the Staff Directory Settings admin page.
 *
 * @since 0.0.1
 */
function register_settings() {
	register_setting(
		'settings',
		'directory_settings'
	);

	add_settings_section(
		'url',
		null,
		null,
		'settings'
	);

	add_settings_field(
		'org_chart',
		'Org Chart URL',
		'VCUL\Directory\Settings\org_chart_field',
		'settings',
		'url',
		array(
			'label_for' => 'org_chart',
		)
	);
}

/**
 * Outputs the Org Chart URL field.
 *
 * @since 0.0.1
 *
 * @param array $args Extra arguments used when outputting the field.
 */
function org_chart_field( $args ) {
	$options = get_option( 'directory_settings' );
	?>

  <label>Org Chart URL<br />
    <input type="text" class="widefat" name="directory_settings[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo esc_attr($options['org_chart']); ?>" />
  </label>
	<p class="description">Enter the URL for Staff Org Chart PDF.</p>
	<?php
}

add_action( 'admin_menu', 'VCUL\directory\Settings\add_settings_page' );
/**
 * Creates an admin page for Staff Org Chart settings.
 *
 * @since 0.0.1
 */
function add_settings_page() {
	add_submenu_page(
		'edit.php?post_type=' . \VCUL\Directory\Post_Type\post_type_slug(),
		'Staff Directory Settings',
		'Settings',
		'manage_options',
		'settings',
		'VCUL\Directory\Settings\settings_page'
	);
}

/**
 * Displays the directory Settings page.
 *
 * @since 0.0.1
 */
function settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_GET['settings-updated'] ) ) { //@codingStandardsIgnoreLine
		add_settings_error(
			'directory_settings_messages',
			'directory_settings_message',
			'Settings Saved',
			'updated'
		);
	}

	settings_errors( 'directory_settings_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php
				settings_fields( 'settings' );
				do_settings_sections( 'settings' );
				submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}
