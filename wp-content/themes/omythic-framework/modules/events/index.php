<?php

class MakespaceFrameworkEventsModule {

	// The field(s) listed here are unique to this module
	// and will be enabled/disabled when the module is
	private $acf_fields = array(
		'group_614a1d0a5b576',
		'group_614b3497af0c8'
	);

	function __construct(){
		add_action( 'after_setup_theme', array( $this, 'check_module_theme_support' ), 15 );
		add_action( 'after_setup_theme', array( $this, 'toggle_acf_fields' ), 20 );
		// add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
	}

	function admin_menu(){
		// Adds an ACF options menu under the new post type's menu
		// $post_type_single_name = apply_filters( 'events_module_single_name', 'Event' );
		acf_add_options_sub_page( array(
			'page_title' => 'Events Archive &amp; Global Settings',
			'menu_title' => 'Events Settings',
			'menu_slug' => 'msw-events-settings',
			'parent_slug' => 'edit.php?post_type=msw_events'
		) );
	}

	function check_module_theme_support(){
		// If the theme doesn't support this module, do nothing. Otherwise...
		if( current_theme_supports( 'events-module' ) ){
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'pre_get_posts', array( $this, 'msw_events_pre_get_posts') );
			add_action( 'wp_enqueue_scripts',  array( $this, 'msw_events_js') );
			add_action( 'admin_enqueue_scripts', array( $this, 'msw_events_admin_js') );
			add_filter( 'manage_msw_events_posts_columns', array( $this, 'msw_events_remove_columns') );
			add_filter( 'manage_msw_events_posts_columns', array( $this, 'msw_events_add_columns') );
			add_action( 'manage_msw_events_posts_custom_column', array( $this, 'msw_events_columns_content'), 10, 2 );
		}
	}

	function enter_title_here(){
		// Make a friendlier placeholder for new posts
		return 'Enter event title here';
	}

	function init(){
		// Register post types and taxonomies for this module
		$post_type_slug = apply_filters( 'msw_events_slug', 'msw-events' );
		$post_type_single_name = apply_filters( 'msw_events_single_name', 'Event' );
		$post_type_plural_name = apply_filters( 'msw_events_plural_name', 'Events' );
		$post_type_menu_icon = apply_filters( 'msw_events_menu_icon', 'dashicons-calendar-alt' );
		register_post_type( 'msw_events', array(
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
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes' ),
			'show_in_rest' => true,
			'rewrite' => array(
				'slug' =>  $post_type_slug
			)
		) );

		$taxonomy_slug = apply_filters( 'msw_events_taxonomy_slug', 'event_category' );
		$taxonomy_single_name = apply_filters( 'msw_events_taxonomy_single_name', 'Category' );
		$taxonomy_plural_name = apply_filters( 'msw_events_taxonomy_plural_name', 'Categories' );

		register_taxonomy( 'event_category', 'msw_events', array(
			'label' => $taxonomy_plural_name,
			'labels' => array(
				'name' => $taxonomy_plural_name,
				'singular_name' => $taxonomy_single_name,
				'menu_name' => $taxonomy_plural_name,
				'all_items' => 'All ' . $taxonomy_plural_name,
				'edit_item' => 'Edit ' . $taxonomy_single_name,
				'view_item' => 'View ' . $taxonomy_single_name,
				'update_item' => 'Update ' . $taxonomy_single_name,
				'add_new_item' => 'Add New ' . $taxonomy_single_name,
				'new_item_name' => 'New ' . $taxonomy_single_name . ' Name',
				'parent_item' => 'Parent ' . $taxonomy_single_name,
				'search_items' => 'Search ' . $taxonomy_plural_name,
				'popular_items' => 'Popular ' . $taxonomy_plural_name,
				'separate_items_with_commas' => 'Separate with commas',
				'add_or_remove_items' => 'Add or remove ' . strtolower( $taxonomy_plural_name ),
				'choose_from_most_used' => 'Choose from most used ' . strtolower( $taxonomy_plural_name ),
				'not_found' => 'No ' . strtolower( $taxonomy_plural_name ) . ' found',
			),
			'hierarchical' => true,
			'rewrite' => array(
				'slug' => $taxonomy_slug
			),
			'show_admin_column' => true,
			'show_in_rest' => true,
		) );
	}

	function toggle_acf_fields(){
		// Enable/disable acf fields for this module
		if( $this->acf_fields ){
			$enabled = current_theme_supports( 'events-module' );
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


	function msw_events_pre_get_posts( $query ){
		if( $query->is_main_query() && !is_admin() ){

			if ( is_post_type_archive( 'msw_events' )  ){

				if(isset($_GET['view']) && $_GET['view'] == 'list'){
					$query->set( 'orderby', 'meta_value' );
					$query->set( 'meta_key', 'start_date' );
					$query->set( 'order', 'ASC' );
					$query->set( 'posts_per_page', 1 );
					// 'meta_key' => 'custom-meta-key',
					
					if(isset($_GET['past_events']) && $_GET['past_events'] == 'true'){
						$query->set( 'meta_query', array(
							array(
								'key' => 'start_date',
								'value' => date('Ymd'),
								'compare' => '<=',
							)
						));
					}
					else{
						$query->set( 'meta_query', array(
							array(
								'key' => 'start_date',
								'value' => date('Ymd'),
								'compare' => '>=',
							)
						));
					}
				}
			}
		}
		else{

			global $pagenow;
			if( is_admin()
				&& 'edit.php' == $pagenow
				&& !isset( $_GET['orderby'] )
				&& isset( $_GET['post_type'] )
				&& $_GET['post_type'] == 'msw_events' ){
					$query->set( 'meta_key', 'start_date' );
					$query->set( 'orderby', 'meta_value' );
					$query->set( 'order', 'DESC' );
					// $query->set( 'posts_per_page', 2 );
			}
		}
	}


	/* JS  **********************************************************************/
	// add_action( 'wp_enqueue_scripts', 'msw_events_js' );
	function msw_events_js() {

		$msw_events_object = array(
			'ajax_url' => admin_url( 'admin-ajax.php' )
		);

		wp_register_script( 'msw_events_js', get_template_directory_uri() . '/modules/events/js/msw_events.js', array( 'jquery' ), null, true );
		wp_enqueue_script( 'msw_events_js' );
		wp_localize_script( 'msw_events_js', 'MSWeventsObject', $msw_events_object );
	}

	/* Admin JS  **********************************************************************/
	// add_action( 'admin_enqueue_scripts', 'msw_events_admin_js' );
	function msw_events_admin_js() {

		$msw_events_object_admin = array(
			'ajax_url' => admin_url( 'admin-ajax.php' )
		);

		wp_register_script( 'msw_events_admin_js', get_template_directory_uri() . '/modules/events/js/msw_events_admin.js', array( 'jquery' ), null, true );
		wp_enqueue_script( 'msw_events_admin_js' );
		wp_localize_script( 'msw_events_admin_js', 'MSWeventsObjectAdmin', $msw_events_object_admin );
	}




	/* Admin Columns **********************************************************************/
	//remove publish date on events in admin
	// add_filter( 'manage_msw_events_posts_columns', 'msw_events_remove_columns' );
	function msw_events_remove_columns( $columns ) {
	    unset($columns['date']);
	    return $columns;
	}
	// Add Event Date/Time to posts archive in admin 
	// add_filter( 'manage_msw_events_posts_columns', 'msw_events_add_columns' );
	function msw_events_add_columns( $columns ) {
	    $columns['datetime'] = 'Date/Time';
	    return $columns;
	}

	// add_action( 'manage_msw_events_posts_custom_column', 'msw_events_columns_content', 10, 2 );
	function msw_events_columns_content( $column_name, $post_id ) {
	    if ( 'datetime' != $column_name ) {
	        return;
	    }

		$start_date = get_post_meta( $post_id, 'start_date', true );
		// $end_date = get_post_meta( $post_id, 'end_date', true );

		// if(get_field('multi_day_event') == true && get_field('end_date')){
		// 	$end_date = get_field('end_date');
		// }
		// else{
		// 	$end_date = $start_date;
		// }

		$start_date_obj = date_create($start_date);
		$start_date_display = date_format($start_date_obj, 'F j, Y');
		
		
		// if($end_date !== $start_date){
		
		// 	$end_date_obj = date_create($end_date);
		// 	$end_date_display = date_format($end_date_obj,"l, F j, Y");

		// 	echo '- <br>' . $end_date_display;
		// }

		$start_time = get_post_meta($post_id, 'start_time', true);
		$start_time_date = date_create($start_time);
		$start_time_display = date_format($start_time_date, 'g:ia');

		// $end_time = get_field('end_time');
		// $end_time_date = date_create($end_time);
		// $end_time_display = date_format($end_time_date, 'g:ia');

		// echo $start_time_display . ' - ' . $end_time_display;			
	 
	    $datetime = $start_date_display . ' @ ' . $start_time_display;

	    echo $datetime;
	}


	/* calendar links *********************************************************************************
	https://docs.theeventscalendar.com/reference/classes/tribe__events__main/googlecalendarlink/
	*/

	static function msw_calendar_links( $post_ID ) {

		$event_post = get_post($post_ID);

		$event_url_title = urlencode(get_the_title($post_ID));
		$event_url_description = 'View event details here: ' . urlencode(get_permalink($post_ID));

		/* datetimes */
		$event_url_dates = '';
		$tz = 'America/Chicago';
		$event_tz = new DateTimeZone($tz);
		
		$start_date = get_field('start_date', $post_ID);
		$start_date_obj = date_create($start_date, $event_tz);
		$start_date_url = date_format($start_date_obj, 'Ymd');
		
		$start_time = get_field('start_time', $post_ID);
		$start_time_obj = date_create($start_time, $event_tz);
		$start_time_url = date_format($start_time_obj, 'His');
		
		// $start_datetime_url = $start_date_url . 'T' . $start_time_url;

		$local_start_datetime = new DateTime($start_date_url . $start_time_url, $event_tz);
		$local_start_datetime_gmt = new DateTime($start_date_url . $start_time_url, $event_tz);
		$local_start_datetime_gmt->setTimezone(new DateTimeZone('GMT'));

		$start_datetime_url = $local_start_datetime_gmt->format('Ymd\THis') . 'Z';

		$tz_offset = $local_start_datetime->format('P');

		$end_time = get_field('end_time', $post_ID);
		$end_time_obj = date_create($end_time, $event_tz);
		$end_time_url = date_format($end_time_obj, 'His');

		if($end_date = get_field('end_date', $post_ID)){
			// $event_url_dates = YYYYMMDDTHHmmSSZ
			$end_date_obj = date_create($end_date, $event_tz);
			$end_date_url = date_format($end_date_obj, 'Ymd');
			
			$local_end_datetime = new DateTime($end_date_url . $end_time_url, $event_tz);
			// $local_end_datetime_gmt = $local_end_datetime->setTimezone(new DateTimeZone('GMT'));
			$local_end_datetime_gmt = new DateTime($end_date_url . $end_time_url, $event_tz);
			$local_end_datetime_gmt->setTimezone(new DateTimeZone('GMT'));
		
			$end_datetime_url = $local_end_datetime_gmt->format('Ymd\THis') . 'Z';
		}
		else{
			$local_end_datetime = new DateTime($start_date_url . $end_time_url, $event_tz);
			$local_end_datetime_gmt = new DateTime($start_date_url . $end_time_url, $event_tz);
			$local_end_datetime_gmt->setTimezone(new DateTimeZone('GMT'));
		
			$end_datetime_url = $local_end_datetime_gmt->format('Ymd\THis') . 'Z';
		}
		

		$event_url_dates =  $start_datetime_url . '/'. $end_datetime_url;


		$start_datetime_ms = urlencode($local_start_datetime_gmt->format('Y-m-d\TH:i:s'.$tz_offset));
		$end_datetime_ms = urlencode($local_end_datetime_gmt->format('Y-m-d\TH:i:s'.$tz_offset));

		/* location */
		$event_url_location = '';
		$event_address = '';
		$event_location_name = get_field('location_name',$post_ID);
		$event_location = get_field('location',$post_ID);

		if($event_location){
			// print_r($event_location);
			$event_address = $event_location['address'];
		}

		if($event_location_name && $event_address){
			$event_url_location = urlencode($event_location_name . ' ' . $event_address);
		}
		elseif($event_location_name){
			$event_url_location = urlencode($event_location_name);
		}
		elseif($event_location){
			$event_url_location = urlencode($event_address);
		}
		
		/* urls */
		$calendar_links = array();

		//google
		$calendar_links['google'] = 'https://calendar.google.com/calendar/render?action=TEMPLATE&dates='.$event_url_dates.'&text='.$event_url_title.'&location='.$event_url_location.'&details='.$event_url_description.'&ctz=America/Chicago';

		//outlook
		$calendar_links['outlook'] = 'https://outlook.live.com/calendar/0/deeplink/compose?allday=false&location='.$event_url_location.'&path=%2Fcalendar%2Faction%2Fcompose&rru=addevent&startdt='.$start_datetime_ms.'&enddt='.$end_datetime_ms.'&subject='.$event_url_title;

		//office365
		$calendar_links['office365'] = 'https://outlook.office.com/calendar/0/deeplink/compose?allday=false&location='.$event_url_location.'&path=%2Fcalendar%2Faction%2Fcompose&rru=addevent&startdt='.$start_datetime_ms.'&enddt='.$end_datetime_ms.'&subject=evtitle';

		//aol
		$calendar_links['aol'] = 'https://calendar.aol.com/?desc='.$event_url_description.'&dur=&in_loc='.$event_url_location.'&st='.$start_datetime_url.'&et='.$end_datetime_url.'&title='.$event_url_title.'&v=60';

		//yahoo
		$calendar_links['yahoo'] = 'https://calendar.yahoo.com/?desc='.$event_url_description.'&dur=&in_loc='.$event_url_location.'&st='.$start_datetime_url.'&et='.$end_datetime_url.'&title='.$event_url_title.'&v=60';

		//ics
		$ics_url = get_template_directory_uri() . '/modules/events/includes/ics.php';
		$ics_url .= '?title='.urlencode(get_the_title($post_ID));
		$ics_url .= '&dtstart='.$tz.':'.$local_start_datetime->format('Ymd\THis');
		$ics_url .= '&dtend='.$tz.':'.$local_end_datetime->format('Ymd\THis');
		$ics_url .= '&dtstamp='.gmdate('Ymd').'T'.gmdate('His') . 'Z';
		if($event_location_name || $event_address){
			$ics_url .= '&location='.urlencode($event_location_name . ' ' . $event_address);
		}
		if($event_location){
			$ics_url .= '&geo='.$event_location['lat'].';'.$event_location['lng'];
		}
		$ics_url .= '&description=' . urlencode($event_url_description);
		$ics_url .= '&url=' . urlencode(get_permalink($post_ID));

		$calendar_links['ics'] = $ics_url;

		return $calendar_links;
	}

}

new MakespaceFrameworkEventsModule();

/* Repeating Events **********************************************************************/
// if( file_exists( get_stylesheet_directory() . '/msw-events/msw_events-repeating_events.php' ) ){
// 	include_once( get_stylesheet_directory() . '/msw-events/msw_events-repeating_events.php' );	
// }
// else{
	require_once( 'includes/msw_events-repeating_events.php');
// }

/* Month Calendar **********************************************************************/
if( file_exists( get_stylesheet_directory() . '/msw-events/msw_events-calendar.php' ) ){
	include_once( get_stylesheet_directory() . '/msw-events/msw_events-calendar.php' );	
}
else{
	require_once( 'includes/msw-calendar.php' );
}

