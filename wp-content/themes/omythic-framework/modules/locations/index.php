<?php

class MakespaceFrameworkLocationsModule {

	// The field(s) listed here are unique to this module
	// and will be enabled/disabled when the module is
	private $acf_fields = array(
		'group_5a933daa5fcc9',
		'group_5a934238edd6c'
	);

	function __construct(){
		add_action( 'acf/save_post_locations_module', array( $this, 'maybe_set_primary_location' ), 20 );
		add_action( 'after_setup_theme', array( $this, 'check_module_theme_support' ), 15 );
		add_action( 'after_setup_theme', array( $this, 'toggle_acf_fields' ), 20 );
		add_action( 'manage_locations_module_posts_custom_column' , array( $this, 'manage_locations_module_posts_custom_column' ), 10, 2 );
		add_action( 'save_post_locations_module', array( $this, 'maybe_set_primary_location' ) );

		// add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
		add_filter( 'manage_locations_module_posts_columns', array( $this, 'manage_locations_module_posts_columns' ) );
	}

	function admin_menu(){
		// Adds an ACF options menu under the new post type's menu
		$post_type_single_name = apply_filters( 'locations_module_single_name', 'Location' );
		acf_add_options_sub_page( array(
			'page_title' => $post_type_single_name . ' Archive &amp; Global Settings',
			'menu_title' => $post_type_single_name . ' Settings',
			'menu_slug' => 'makespace-locations_module-archive-settings',
			'parent_slug' => 'edit.php?post_type=locations_module'
		) );
	}

	function check_module_theme_support(){
		// If the theme doesn't support this module, do nothing. Otherwise...
		if( current_theme_supports( 'locations-module' ) ){
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'init', array( $this, 'init' ) );
		}
	}

	function enter_title_here(){
		// Make a friendlier placeholder for new posts
		return 'Enter location name here';
	}

	function init(){
		// Register post types and taxonomies for this module
		$post_type_slug = apply_filters( 'locations_module_slug', 'locations' );
		$post_type_single_name = apply_filters( 'locations_module_single_name', 'Location' );
		$post_type_plural_name = apply_filters( 'locations_module_plural_name', 'Locations' );
		$post_type_menu_icon = apply_filters( 'locations_module_menu_icon', 'dashicons-location-alt' );
		register_post_type( 'locations_module', array(
			'label' => $post_type_plural_name,
			'labels' => array(
				'name' => $post_type_plural_name,
				'singular_name' => $post_type_single_name,
				'add_new' => 'Add New',
				'add_new_item' => 'Add New ' . $post_type_single_name,
				'edit_item' => 'Edit ' . $post_type_single_name,
				'new_item' => 'New ' . $post_type_single_name,
				'view_item' => 'View '  .$post_type_single_name,
				'view_items' => 'View ' . $post_type_plural_name,
				'search_items' => 'Search ' . $post_type_plural_name,
				'not_found' => 'No ' . strtolower( $post_type_plural_name ) . ' found',
				'not_found_in_trash' => 'No ' . strtolower( $post_type_plural_name ) . ' found in trash',
				'parent_item_colon' => 'Parent ' . $post_type_single_name,
				'all_items' => 'All ' . $post_type_plural_name,
				'archives' => $post_type_single_name . ' Archives',
				'attributes' => $post_type_single_name . ' Attributes',
				'insert_into_item' => 'Insert into ' . $post_type_single_name . ' page',
				'uploaded_to_this_item' => 'Uploaded to ' . $post_type_single_name . ' page'
			),
			'has_archive' => true,
			'hierarchical' => true,
			'public' => true,
			'menu_icon' => $post_type_menu_icon,
			'supports' => array( 'title', 'editor', 'revisions', 'page-attributes' ),
			'show_in_rest' => true,
			'rewrite' => array(
				'slug' =>  $post_type_slug
			)
		) );
	}

	function manage_locations_module_posts_columns( $columns ){
		$new_columns = array();
		foreach( $columns as $key => $val ){
			$new_columns[ $key ] = $val;
			if( 'title' == $key ){
				$new_columns[ 'is_primary' ] = 'Primary?';
			}
		}
		return $new_columns;
	}

	function manage_locations_module_posts_custom_column( $column, $post_id ){
		if( 'is_primary' == $column ){
			if( get_field( 'primary_location', $post_id ) ){
				echo '<span class="dashicons dashicons-star-filled"></span>';
			} else {
				echo '<span class="dashicons dashicons-star-empty"></span>';
			}
		}
	}

	function maybe_set_primary_location( $post_id ){
		$primary_location = get_field( 'primary_location' );
		$old_primary_locations = get_posts( array(
			'exclude' => array( $post_id ),
			'meta_key' => 'primary_location',
			'meta_value' => 'true',
			'post_type' => 'locations_module'
		) );
		if( $old_primary_locations ){
			foreach( $old_primary_locations as $old_primary_location ){
				update_field( 'primary_location', false, $old_primary_location->ID );
			}
		}
	}

	function toggle_acf_fields(){
		// Enable/disable acf fields for this module
		$enabled = current_theme_supports( 'locations-module' );
		foreach( $this->acf_fields as $field_group ){
			$field_group_json_file = $field_group . '.json';
			if( true == $enabled && !file_exists( get_stylesheet_directory() . '/acf-json/' . $field_group_json_file ) ){
				copy( dirname( __FILE__ ) . '/' . $field_group_json_file, get_stylesheet_directory() . '/acf-json/' . $field_group_json_file );
				update_option( 'msw_admin_notice_acf_sync', true, 'no' );
			}
			if( false == $enabled && file_exists( get_stylesheet_directory() . '/acf-json/' . $field_group_json_file ) ){
				unlink( get_stylesheet_directory() . '/acf-json/' . $field_group_json_file );
				update_option( 'msw_admin_notice_acf_delete', true, 'no' );
			}
		}
	}
}

new MakespaceFrameworkLocationsModule();
