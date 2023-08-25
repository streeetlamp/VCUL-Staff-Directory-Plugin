<?php

namespace VCUL\Directory\Contributor_Role;

/**
 * Provides the name of the Directory Contributor role.
 *
 * @since 0.1.0
 *
 * @return string
 */
function directory_contributor() {
	return 'vcul_directory_contributor';
}

add_action( 'init', 'VCUL\Directory\Contributor_Role\add_directory_contributor_role' );
/**
 * Adds the Directory Contributor role.
 *
 * @since 0.1.0
 */
function add_directory_contributor_role() {
	if ( array_key_exists( directory_contributor(), \WP_Roles()->get_names() ) ) {
		return;
	}

	add_role(
		directory_contributor(),
		'Directory Contributor',
		array(
			'create_directory' => true,
			'edit_directory' => true,
			'read' => true,
			'upload_files' => true,
		)
	);
}

add_action( 'init', 'VCUL\Directory\Contributor_Role\map_directory_contributor_capabilities', 13 );
/**
 * Maps the Directory Contributor role capabilities to the directory post type.
 *
 * @since 0.1.0
 */
function map_directory_contributor_capabilities() {
	$user = wp_get_current_user();

	if ( ! in_array( directory_contributor(), (array) $user->roles, true ) ) {
		return;
	}

	$directory = get_post_type_object( \VCUL\Directory\Post_Type\post_type_slug() );

	if ( $directory ) {
		$directory->cap->create_posts = 'create_directory';
		$directory->cap->edit_posts = 'edit_directory';
	}

	$taxonomies = get_taxonomies( array(), 'objects' );

	if ( $taxonomies ) {
		foreach ( $taxonomies as $taxonomy ) {
			$taxonomy->cap->assign_terms = 'edit_directory';
		}
	}
}

add_action( 'pre_get_posts', 'VCUL\Directory\Contributor_Role\filter_list_tables' );
/**
 * Filters the media library view for users with the Directory Contributor role.
 *
 * @since 0.1.0
 *
 * @param WP_Query $query
 */
function filter_list_tables( $query ) {
	if ( ! is_admin() ) {
		return;
	}

	$user = wp_get_current_user();

	if ( ! in_array( directory_contributor(), (array) $user->roles, true ) ) {
		return;
	}

	if ( 'attachment' === $query->query['post_type'] ) {
		$query->set( 'author', $user->ID );
	}
}
