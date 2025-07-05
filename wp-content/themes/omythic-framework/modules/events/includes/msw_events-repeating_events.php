<?php

class MakespaceFrameworkRepeatingEvents {
	
	private $repeating_settings_changed = false;

	function __construct(){

		add_filter('acf/prepare_field/name=repeating_event_series_id', array( $this, 'event_acf_prepare_field'));
		add_action( 'wp_ajax_nopriv_trash_type_function',  array( $this, 'trash_type_function') );
		add_action( 'wp_ajax_trash_type_function', array( $this, 'trash_type_function') );
		add_action( 'untrash_post', array( $this, 'msw_event_untrash' ));
		add_action( 'wp_trash_post', array( $this, 'msw_event_trash' ));
		add_action( 'acf/save_post', array( $this, 'msw_event_pre_save'), 1);
		add_action( 'acf/save_post', array( $this, 'msw_event_save'));
	}

	// read-only repeating series id
	function event_acf_prepare_field( $field ) {

		// Lock-in the value "Example".
		// if( $field['value'] === 'Example' ) {
			$field['readonly'] = true;
		// };
	    return $field;
	}


	function msw_create_event($args){

		$source_id = $args['source_id'];
		$title = $args['title'];
		$fields = $args['acf_fields'];
		// $content = $args['content'];
		$series_id = $args['repeating_event_series_id'];
		$event_exists = false;


		// $start_time_val = date('g:i a', strtotime($fields['start_time']));
		$start_time_val = date('H:i:s', strtotime($fields['start_time']));

		$existing_posts = get_posts(array(
			'post_type' => 'msw_events',
			'posts_per_page' => -1,
			'post_title' => $title,
			'post_status' => 'publish',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'start_date',
					'value'   => $fields['start_date'],
				),
				array(
					'key' => 'repeating_event_series_id',
					'value' => $series_id,
					'compare' => '='
				),
			),
			// 'meta_key' => 'start_date',
			// 'meta_value' => $fields['start_date'],
			// 'meta_key' => 'repeating_event_series_id',
			// 'meta_value' => $series_id,
			// 'orderby' => 'meta_value',
			// 'meta_key' => 'start_date',
			// 'order' => 'ASC',
		) );


		if($existing_posts){
			$event_exists = true;

			foreach($existing_posts as $ep){
				$existing_id = $ep->ID;

				wp_update_post(array(
					'ID'           => $ep->ID,
					'post_title'   => $ep->post_title,
					'post_content' => $args['content'],
				));

				set_post_thumbnail( $existing_id, $args['thumbnail'] );
				
				foreach($fields as $key => $val){
					// echo $key . '=>' . $val;
					// echo '<br>';
					// if(
						// $key == 'details'
						// || $key == 'cost'
						// || $key == 'cost_per'
						// || $key == 'cost_description'
						// || $key == 'tickets_product'
						// || $key == 'location_name'
						// || $key == 'location'
						// || $key == 'event_images'
						// || $key == 'header_image'
					// ){
					// 	update_field( $key, $val, $existing_id );
					// }
					if($key == 'start_time'){
						// $start_time = date('g:i a', strtotime($fields['start_time']));
						$start_time = date('H:i:s', strtotime($fields['start_time']));
						// $start_time = get_field('start_time', $source_id);
						// $start_time_date = date_create($start_time);
						// $start_time_display = date_format($start_time_date, 'H:i:s');
						update_field( $key, $start_time, $existing_id );
					}
					elseif($key == 'end_time'){
						// $end_time = date('g:i a', strtotime($fields['end_time']));
						$end_time = date('H:i:s', strtotime($fields['end_time']));
						update_field( $key, $end_time, $existing_id );
					}
					// elseif($key == 'repeating_event'){
					// 	update_field('repeating_event', 0, $existing_id);
					// }
					elseif($key !== 'start_date'){
						update_field( $key, $val, $existing_id );

					}
				}
			}

			return;
		}


		if($event_exists === false){

			$new_event_id = wp_insert_post( array(
				'post_title' => $title,
				'post_status' => 'publish',
				'post_type' => 'msw_events',
				'post_content' => $args['content'],
				'tax_input' => $args['terms']
			) );
			set_post_thumbnail( $new_event_id, $args['thumbnail'] );


			foreach($fields as $key => $val){
				if($key == 'start_time'){
					// $start_time = date('g:i a', strtotime($fields['start_time']));
					$start_time = date('H:i:s', strtotime($fields['start_time']));
					update_field( $key, $start_time, $new_event_id );
				}
				elseif($key == 'end_time'){
					// $end_time = date('g:i a', strtotime($fields['end_time']));
					$end_time = date('H:i:s', strtotime($fields['end_time']));
					update_field( $key, $end_time, $new_event_id );
				}
				// elseif($key == 'repeating_event'){
				// 	update_field('repeating_event', 0, $new_event_id);
				// }
				else{
					update_field( $key, $val, $new_event_id );
				}
			}
		}

	}


	function trash_type_function() {
		$post_id = $_POST['postid'];
		$input_val = $_POST['inputval'];
		update_post_meta( $post_id, 'trash_type', $input_val );

		$updatedtype = get_post_meta( $post_id, 'trash_type', true );
		// error_log($updatedtype);
	}

	function msw_event_untrash($post_id){
		$thepost = get_post($post_id);
		if( $thepost->post_type == 'msw_events'){
			update_post_meta( $post_id, 'trash_type', false );
			update_field( 'events_to_update', 'this', $post_id );
		}
	}


	/* repeating event trash */
	function msw_event_trash($post_id){

		$thepost = get_post($post_id);

		if( $thepost->post_type == 'msw_events' && get_field('repeating_event', $post_id)){

			$events_to_trash = get_post_meta( $post_id, 'trash_type', true );

			// error_log('events_to_trash: ' . $events_to_trash);

			/* trash only current event */ 
			if($events_to_trash == 'this' || $events_to_trash == false){
				return;
			}
			elseif($events_to_trash == 'future' || $events_to_trash == 'all'){

				if(get_field('repeating_event_series_id', $post_id)){
					$event_series_id = get_field('repeating_event_series_id', $post_id);
				}
				else{
					// set series id : field_62a0cee9277a7
					// update_field( 'repeating_event_series_id', $post_id, $post_id );
					$event_series_id = $post_id;
				}
				
				if($events_to_trash == 'future'){
				/* trash current and future events */

					$current_event_start_date = get_field('start_date', $post_id);

					$events_in_series_to_trash = get_posts(array(
						'post_type' => 'msw_events',
						'posts_per_page' => -1,
						// 'post_title' => $title,
						'post_status' => 'publish',
						'exclude' => $post_id,
						// 'orderby' => 'meta_value',
						// 'meta_key' => 'start_date',
						// 'order' => 'ASC',
						// 'meta_key' => 'repeating_event_series_id',
						// 'meta_value' => $event_series_id,
						'meta_query' => array(
							'relation' => 'AND',
							array(
								'key' => 'repeating_event_series_id',
								'value' => $event_series_id,
								'compare' => '='
							),
							array(
								'key' => 'start_date',
								'value' => $current_event_start_date,
								'compare' => '>='
							)
						),
					));
				}
				elseif($events_to_trash == 'all'){
					/* trash all events in series */

					$events_in_series_to_trash = get_posts(array(
						'post_type' => 'msw_events',
						'posts_per_page' => -1,
						// 'post_title' => $title,
						'post_status' => 'publish',
						'exclude' => $post_id,
						'meta_key' => 'repeating_event_series_id',
						'meta_value' => $event_series_id,
					));
				}

				// error_log(print_r($events_in_series_to_trash, true));

				foreach($events_in_series_to_trash as $ev){
					// error_log('trash: ' . $ev->ID);
					wp_trash_post($ev->ID);
				}
			}
		}
	}

	// 	global $repeating_settings_changed;
	// 	$repeating_settings_changed = false;


	/* repeating event save */
	function msw_event_pre_save($post_id){
		// error_log('acf save 1: ' . $post_id);

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if (wp_is_post_revision($post_id)) {
			return;
		}

		if (wp_is_post_autosave($post_id)) {
			return;
		}
		
		$thepost = get_post($post_id);

		if(!$thepost){
			return;
		}
		if($thepost->post_type !== 'msw_events'){
			return;
		}
		if ( 'trash' === $thepost->post_status ) {
			return;
		}


		// error_log('acf save 1b: ' . $post_id);

		if(get_field('repeating_event_series_id', $post_id)){
			$event_series_id = get_field('repeating_event_series_id', $post_id);
		}
		else{
			// set series id : field_62a0cee9277a7
			update_field( 'repeating_event_series_id', $post_id, $post_id );
			$event_series_id = $post_id;
		}

		// error_log('msw_event_pre_save');

		if($post_id == $event_series_id){
			/* first event */

			$repeating_settings_field_keys = array(
				'field_627d2e5a56085', // repeat_interval
				'field_627d2ef056086', // repeat_term
				'field_627d31cd56088', // repeat_days
				'field_627d3443f9ae4', // repeat_month_type
				'field_627d33305608a', // repeat_month_day_iteration
				'field_627d33b1f9ae3', // repeat_month_day
				'field_627d4642b83d9', // repeat_duration
				'field_627d46bcb83da', // repeat_duration_times
				'field_627d4705b83db', // repeat_duration_end_date
			);

			/* check if repeating settings changed */
			foreach($repeating_settings_field_keys as $field_key){

				if(isset($_POST['acf'][$field_key])){
					// error_log(get_field($field_key, $post_id));
					// error_log($_POST['acf'][$field_key]);
					if(get_field($field_key, $post_id) !== $_POST['acf'][$field_key]){
						// global $repeating_settings_changed;
						$this->repeating_settings_changed = true;

						break;
					}
				}
			}
		}
	}

	function msw_event_save($post_id){
		// error_log('acf save 2: ' . $post_id);

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if (wp_is_post_revision($post_id)) {
			return;
		}

		if (wp_is_post_autosave($post_id)) {
			return;
		}

		$thepost = get_post($post_id);

		if(!$thepost){
			return;
		}
		if($thepost->post_type !== 'msw_events'){
			return;
		}
		if ( 'trash' === $thepost->post_status ) {
			return;
		}

		// error_log('acf save 2b: ' . $post_id);


		if( $thepost->post_type == 'msw_events' && $thepost->post_status == 'publish' && get_field('repeating_event', $post_id)){
			
			if(get_field('repeating_event_series_id', $post_id)){
				$event_series_id = get_field('repeating_event_series_id', $post_id);
			}
			else{
				// set series id : field_62a0cee9277a7
				update_field( 'repeating_event_series_id', $post_id, $post_id );
				$event_series_id = $post_id;
			}

			$events_to_update = get_field('events_to_update', $post_id);
			// error_log('events_to_update 2: ' . $events_to_update);

			/* update only current event */ 
			if($events_to_update == 'this'){

				$events_in_series = get_posts(array(
					'post_type' => 'msw_events',
					'posts_per_page' => -1,
					// 'post_title' => $title,
					'post_status' => 'publish',
					'meta_key' => 'repeating_event_series_id',
					'meta_value' => $event_series_id,
				));

				// error_log(count($events_in_series) );
				
				if(count($events_in_series) <= 1){

					/* start new series */
					update_field( 'repeating_event_series_id', $post_id, $post_id );
					self::msw_repeating_events($post_id);

				}
				else{
					/* simply update post, reset repeating settings */

					$series_repeating_settings = array(
						// 'start_date' => get_field('start_date', $event_series_id),
						'repeat_interval' => get_field('repeat_interval', $event_series_id),
						'repeat_term' => get_field('repeat_term', $event_series_id),
						'repeat_days' => get_field('repeat_days', $event_series_id),
						'repeat_month_type' => get_field('repeat_month_type', $event_series_id),
						'repeat_month_day_iteration' => get_field('repeat_month_day_iteration', $event_series_id),
						'repeat_month_day' => get_field('repeat_month_day', $event_series_id),
						'repeat_duration' => get_field('repeat_duration', $event_series_id),
						'repeat_duration_times' => get_field('repeat_duration_times', $event_series_id),
						'repeat_duration_end_date' => get_field('repeat_duration_end_date', $event_series_id),
					);

					foreach($series_repeating_settings as $key => $val){
						update_field( $key, $val, $post_id );
					}

					return;
				}

			}
			elseif($events_to_update == 'future' || $events_to_update == 'all'){
				update_field( 'events_to_update', 'this', $post_id );
				update_post_meta( $post_id, 'trash_type', 'this' );
				// error_log('events_to_update this 2');
				
				if($events_to_update == 'future'){
				/* update current and future events */

					$current_event_start_date = get_field('start_date', $post_id);

					$events_in_series = get_posts(array(
						'post_type' => 'msw_events',
						'posts_per_page' => -1,
						// 'post_title' => $title,
						'post_status' => 'publish',
						// 'orderby' => 'meta_value',
						// 'meta_key' => 'start_date',
						// 'order' => 'ASC',
						// 'meta_key' => 'repeating_event_series_id',
						// 'meta_value' => $event_series_id,
						'meta_query' => array(
							'relation' => 'AND',
							array(
								'key' => 'repeating_event_series_id',
								'value' => $event_series_id,
								'compare' => '='
							),
							array(
								'key' => 'start_date',
								'value' => $current_event_start_date,
								'compare' => '>='
							)
						),
					));
				}
				elseif($events_to_update == 'all'){

					$events_in_series = get_posts(array(
						'post_type' => 'msw_events',
						'posts_per_page' => -1,
						// 'post_title' => $title,
						'post_status' => 'publish',
						'meta_key' => 'repeating_event_series_id',
						'meta_value' => $event_series_id,
					));

					// self::msw_repeating_events($post_id);
				}

				// $repeating_settings_changed = false;

				// $series_repeating_settings = array(
				// 	'repeat_interval' => get_field('repeat_interval', $event_series_id),
				// 	'repeat_term' => get_field('repeat_term', $event_series_id),
				// 	'repeat_days' => get_field('repeat_days', $event_series_id),
				// 	'repeat_month_type' => get_field('repeat_month_type', $event_series_id),
				// 	'repeat_month_day_iteration' => get_field('repeat_month_day_iteration', $event_series_id),
				// 	'repeat_month_day' => get_field('repeat_month_day', $event_series_id),
				// 	'repeat_duration' => get_field('repeat_duration', $event_series_id),
				// 	'repeat_duration_times' => get_field('repeat_duration_times', $event_series_id),
				// 	'repeat_duration_end_date' => get_field('repeat_duration_end_date', $event_series_id),
				// );

				$repeating_settings_fields = array(
					'repeat_interval',
					'repeat_term',
					'repeat_days',
					'repeat_month_type',
					'repeat_month_day_iteration',
					'repeat_month_day',
					'repeat_duration',
					'repeat_duration_times',
					'repeat_duration_end_date',
				);

				if($post_id !== $event_series_id){

					/* check if repeating settings changed */
					foreach($repeating_settings_fields as $field_key){

						if(get_field($field_key, $event_series_id) !== get_field($field_key, $post_id)){
							// global $repeating_settings_changed;
							$this->repeating_settings_changed = true;
							break;
						}
					}
				}

				// global $repeating_settings_changed;
				if( $this->repeating_settings_changed === true){
					/* trash all future events and start new series */
					// error_log('repeating_settings_changed');

					foreach($events_in_series as $ev){
						if($ev->ID !== $post_id){
							wp_trash_post($ev->ID);
						}
					}

					update_field( 'repeating_event_series_id', $post_id, $post_id );

					self::msw_repeating_events($post_id);

				}
				else{
					// error_log('repeating settings not changed');

					$post_fields = get_fields($post_id);

					foreach($events_in_series as $ev){

						$content = '';

						if($thepost->post_content){
							$content = $thepost->post_content;
						}

						wp_update_post(array(
							'ID'           => $ev->ID,
							'post_title'   => $thepost->post_title,
							'post_content' => $content,
						));

						set_post_thumbnail( $ev->ID, get_post_thumbnail_id($post_id) );

						foreach($post_fields as $key => $val){
							if($key == 'start_time'){
								// $start_time = date('g:i a', strtotime($post_fields['start_time']));
								$start_time = date('H:i:s', strtotime($post_fields['start_time']));
								update_field( $key, $start_time, $ev->ID );
							}
							elseif($key == 'end_time'){
								// $end_time = date('g:i a', strtotime($post_fields['end_time']));
								$end_time = date('H:i:s', strtotime($post_fields['end_time']));
								update_field( $key, $end_time, $ev->ID );
							}
							elseif($key !== 'start_date'){
								update_field( $key, $val, $ev->ID );
							}
						}
					}

					return;
				}

			}
		}
		else{
			return;
		}
	}

	function msw_repeating_events($post_id){

		//echo '<pre>';
		$thepost = get_post($post_id);
		// update_field( 'events_to_update', 'this', $post_id );

		$event_start_date = get_field('start_date', $post_id);
		$repeat_interval = get_field('repeat_interval', $post_id);
		$repeat_term = get_field('repeat_term', $post_id);
		$repeat_week_days = get_field('repeat_days', $post_id);
		$repeat_month_type = get_field('repeat_month_type', $post_id);
		$repeat_month_day_iteration = get_field('repeat_month_day_iteration', $post_id);
		$repeat_month_day = get_field('repeat_month_day', $post_id);
		$repeat_duration = get_field('repeat_duration', $post_id);
		$repeat_duration_times = get_field('repeat_duration_times', $post_id);
		$repeat_duration_end_date = get_field('repeat_duration_end_date', $post_id);

		// echo 'every ' . $repeat_interval . ' ' . $repeat_term . '<br>';
		// echo 'starting: ' . date('F j, Y', strtotime($event_start_date)) . '<br>';

		$new_event = array();
		$new_event['source_id'] = $post_id;
		$new_event['title'] = get_the_title($post_id);
		$new_event['acf_fields'] = get_fields($post_id);
		$new_event['end_date_interval'] = 0;
		$new_event['content'] = $thepost->post_content;
		$new_event['thumbnail'] = get_post_thumbnail_id($post_id);
		$new_event['repeating_event_series_id'] = $post_id;


		$terms = get_the_terms($post_id, 'event_category');
		$term_ids = array();

		if($terms){
			foreach($terms as $term){
				$term_ids[] = $term->term_id;
			}
		}

		$new_event['terms'] = array('event_category' => $term_ids);

		if($new_event['acf_fields']['end_date']){

			$start = new DateTime($new_event['acf_fields']['start_date']);
			$end = new DateTime($new_event['acf_fields']['end_date']);

			$new_event['end_date_interval'] = $start->diff($end)->days;
		}

		
		/********************************************************************************************/  
		/********************************************************************************************/  
		/*** DAYS ***/
		/********************************************************************************************/
		/********************************************************************************************/
		if($repeat_term == 'days'){
		}

		/********************************************************************************************/  
		/********************************************************************************************/  
		/*** WEEKS ***/
		/********************************************************************************************/
		/********************************************************************************************/
		elseif($repeat_term == 'weeks'){
			$startDay = date('w', strtotime($new_event['acf_fields']['start_date']));

			$firstWeekDays = array();

			foreach($repeat_week_days as $day){
				
				$nextDayInterval = $day - $startDay;

				if($nextDayInterval < 0){
					$nextDayInterval += (7 * $repeat_interval);
				}

				$firstWeekDays[] = date('F j, Y', strtotime($new_event['acf_fields']['start_date'] . ' + ' . $nextDayInterval . ' days'));
			}


			/********************************************************************************************/
			/* day(s) of week for x times */
			/********************************************************************************************/
			if($repeat_duration == 'times'){
				// $repeat_times = get_field('repeat_duration_times');

				for($i=1; $i<$repeat_duration_times; $i++){
					foreach($firstWeekDays as $day){
						$new_event['acf_fields']['start_date'] = date('Ymd', strtotime($day . ' + ' . ($i * 7 * $repeat_interval) . 'days'));

						if($new_event['acf_fields']['end_date']){
							$new_event['acf_fields']['end_date'] = date('Ymd', strtotime($new_event['acf_fields']['start_date'] . ' + ' . $new_event['end_date_interval'] . 'days'));
						}

						// echo date('F j, Y', strtotime($new_event['acf_fields']['start_date'])) . '<br>';
						self::msw_create_event($new_event);
					}
				}
			}
			/********************************************************************************************/
			/* day(s) of week indefinite */
			/********************************************************************************************/
			/*elseif($repeat_duration == 'forever'){
				$first_event_year = date('Y', strtotime($event_start_date));
				$new_event_year = $first_event_year;
				$forever_i = 0;

				echo 'Y: ' . $first_event_year . '<br>';

				while($new_event_year <= $first_event_year+1){

					foreach($firstWeekDays as $day){
						$new_event['acf_fields']['start_date'] = date('Ymd', strtotime($day . ' + ' . ($forever_i * 7 * $repeat_interval) . 'days'));

						if($new_event['acf_fields']['end_date']){
							$new_event['acf_fields']['end_date'] = date('Ymd', strtotime($new_event['acf_fields']['start_date'] . ' + ' . $new_event['end_date_interval'] . 'days'));
						}

						$new_event_year = date('Y', strtotime($new_event['acf_fields']['start_date']));

						if($new_event_year <= $first_event_year+1){
							// echo 'msw_create_event: ';
							self::msw_create_event($new_event);
						}
						echo date('F j, Y', strtotime($new_event['acf_fields']['start_date'])) . '<br>';
					}
					$forever_i++;
				}
			}*/

			/********************************************************************************************/
			/* day(s) of week with end date */
			/********************************************************************************************/
			elseif($repeat_duration == 'date'){
				$first_event_date = date('Ymd', strtotime($event_start_date));
				$new_event_date = $first_event_date;
				$end_i = 1;

				// echo 'end: ' . $repeat_duration_end_date . '<br>';

				while($new_event_date <= $repeat_duration_end_date){

					foreach($firstWeekDays as $day){
						$new_event['acf_fields']['start_date'] = date('Ymd', strtotime($day . ' + ' . ($end_i * 7 * $repeat_interval) . 'days'));

						if($new_event['acf_fields']['end_date']){
							$new_event['acf_fields']['end_date'] = date('Ymd', strtotime($new_event['acf_fields']['start_date'] . ' + ' . $new_event['end_date_interval'] . 'days'));
						}

						$new_event_date = date('Ymd', strtotime($new_event['acf_fields']['start_date']));

						if($new_event_date <= $repeat_duration_end_date){
							// echo 'msw_create_event: ';
							// echo date('F j, Y', strtotime($new_event['acf_fields']['start_date'])) . '<br>';
							self::msw_create_event($new_event);
						}

					}
					$end_i++;
				}
			}
		}
		/********************************************************************************************/  
		/********************************************************************************************/  
		/*** MONTHS ***/
		/********************************************************************************************/
		/********************************************************************************************/
		elseif($repeat_term == 'months'){

			$month_start_date = date('Ymd', strtotime($new_event['acf_fields']['start_date']));

			/********************************************************************************************/
			/* date of month */
			/********************************************************************************************/
			if($repeat_month_type == 'date'){

				/************************************/
				/* date of month for x times */
				/************************************/
				if($repeat_duration == 'times'){
					// $repeat_times = get_field('repeat_duration_times');

					for($i=1; $i<$repeat_duration_times; $i++){
						$startdateday = date('d', strtotime($month_start_date));
						
						// $i_next = $i+1;

						if($startdateday >= 28 ){
						
							$startyear = date('Y', strtotime($month_start_date));
							$startmonth = date('m', strtotime($month_start_date));

							$newdatemonth = date('Ymd', strtotime($startyear . $startmonth . '01 + ' . ($i * $repeat_interval) . 'month'));

							$newmonth = date('m', strtotime($newdatemonth));
							$newyear = date('Y', strtotime($newdatemonth));
							$newmonthdays = cal_days_in_month(CAL_GREGORIAN, $newmonth, $newyear);
							

							if($startdateday > $newmonthdays){
								$newdate = date('Ymd', strtotime($newyear . '-' . $newmonth . '-' . $newmonthdays));
							}
							else{
								$newdate = date('Ymd', strtotime($month_start_date . ' + ' . ($i * $repeat_interval) . 'month'));
							}
						}
						else{
							$newdate = date('Ymd', strtotime($month_start_date . ' + ' . ($i * $repeat_interval) . 'month'));
						}
						
						$new_event['acf_fields']['start_date'] = $newdate;

						if($new_event['acf_fields']['end_date']){
							$new_event['acf_fields']['end_date'] = date('Ymd', strtotime($new_event['acf_fields']['start_date'] . ' + ' . $new_event['end_date_interval'] . 'days'));
						}

						// echo date('F j, Y', strtotime($new_event['acf_fields']['start_date'])) . '<br>';

						self::msw_create_event($new_event);
						
					}
				}
				elseif($repeat_duration == 'forever'){
				}

				/************************************/
				/* date of month with end date */
				/************************************/
				elseif($repeat_duration == 'date'){
					$first_event_date = date('Ymd', strtotime($event_start_date));
					$new_event_date = $first_event_date;
					$end_i = 1;
					$startdateday = date('d', strtotime($month_start_date));

					// echo 'end: ' . $repeat_duration_end_date . '<br>';

					while($new_event_date < $repeat_duration_end_date){
							
						if($startdateday >= 28 ){
						
							$startyear = date('Y', strtotime($month_start_date));
							$startmonth = date('m', strtotime($month_start_date));

							$newdatemonth = date('Ymd', strtotime($startyear . $startmonth . '01 + ' . ($end_i * $repeat_interval) . 'month'));

							$newmonth = date('m', strtotime($newdatemonth));
							$newyear = date('Y', strtotime($newdatemonth));
							$newmonthdays = cal_days_in_month(CAL_GREGORIAN, $newmonth, $newyear);
							

							if($startdateday > $newmonthdays){
								$newdate = date('Ymd', strtotime($newyear . '-' . $newmonth . '-' . $newmonthdays));
							}
							else{
								$newdate = date('Ymd', strtotime($month_start_date . ' + ' . ($end_i * $repeat_interval) . 'month'));
							}
						}
						else{
							$newdate = date('Ymd', strtotime($month_start_date . ' + ' . ($end_i * $repeat_interval) . 'month'));
						}
						
						$new_event['acf_fields']['start_date'] = $newdate;

						if($new_event['acf_fields']['end_date']){
							$new_event['acf_fields']['end_date'] = date('Ymd', strtotime($new_event['acf_fields']['start_date'] . ' + ' . $new_event['end_date_interval'] . 'days'));
						}

						$new_event_date = date('Ymd', strtotime($new_event['acf_fields']['start_date']));

						// echo date('F j, Y', strtotime($new_event['acf_fields']['start_date'])) . '<br>';

						self::msw_create_event($new_event);

						$end_i++;
					}
				}
			}

			/********************************************************************************************  
			/* day of month */
			/********************************************************************************************/
			elseif($repeat_month_type == 'day'){

				// $repeat_month_day_iteration
				// 0 : First
				// 1 : Second
				// 2 : Third
				// 3 : Fourth
				// 4 : Fifth
				$month_day_iteration_map = array(
					'First',
					'Second',
					'Third',
					'Fourth',
					'Fifth'
				);
				
				// $repeat_month_day;
				// 0 : Sunday
				// 1 : Monday
				// 2 : Tuesday
				// 3 : Wednesday
				// 4 : Thursday
				// 5 : Friday
				// 6 : Saturday
				$month_dayofweek_map = array(
					'Sunday',
					'Monday',
					'Tuesday',
					'Wednesday',
					'Thursday',
					'Friday',
					'Saturday'
				);

				// echo $month_day_iteration_map[$repeat_month_day_iteration] . ' ' . $month_dayofweek_map[$repeat_month_day] . '<br>';

				$interation_str = $month_day_iteration_map[$repeat_month_day_iteration] . ' ' . $month_dayofweek_map[$repeat_month_day];

				$startyear = date('Y', strtotime($month_start_date));
				$startmonth = date('m', strtotime($month_start_date));


				/************************************/  
				/* day of month for x times */
				/************************************/
				if($repeat_duration == 'times'){

					for($i=1; $i<$repeat_duration_times; $i++){
						// $i_next = $i+1;

						$new_iteration_month = date('F', strtotime($startyear . $startmonth . '01 + ' . ($i * $repeat_interval) . 'month'));
						$new_iteration_year = date('Y', strtotime($startyear . $startmonth . '01 + ' . ($i * $repeat_interval) . 'month'));
						
						// echo $interation_str . ' of ' . $new_iteration_month . ' ' . $new_iteration_year . '<br>';
						
						$new_date = date('Ymd', strtotime($interation_str  . ' of ' . $new_iteration_month . ' ' . $new_iteration_year));
						$new_date_month = date('F', strtotime($interation_str  . ' of ' . $new_iteration_month . ' ' . $new_iteration_year));

						if($new_iteration_month == $new_date_month){
							// echo $new_date . '<br>';

							$new_event['acf_fields']['start_date'] = $new_date;

							if($new_event['acf_fields']['end_date']){
								$new_event['acf_fields']['end_date'] = date('Ymd', strtotime($new_event['acf_fields']['start_date'] . ' + ' . $new_event['end_date_interval'] . 'days'));
							}
							// echo date('F j, Y', strtotime($new_event['acf_fields']['start_date'])) . '<br>';
							self::msw_create_event($new_event);
						}
					}
				}
				/************************************/  
				/* day of month with end date */
				/************************************/
				elseif($repeat_duration == 'date'){
					$first_event_date = date('Ymd', strtotime($event_start_date));
					$new_event_date = $first_event_date;
					$end_i = 1;
					$startdateday = date('d', strtotime($month_start_date));

					while(date('Ym', strtotime($new_event_date)) < date('Ym', strtotime($repeat_duration_end_date))){

						$new_iteration_month = date('F', strtotime($startyear . $startmonth . '01 + ' . ($end_i * $repeat_interval) . 'month'));
						$new_iteration_year = date('Y', strtotime($startyear . $startmonth . '01 + ' . ($end_i * $repeat_interval) . 'month'));
						
						$new_date = date('Ymd', strtotime($interation_str  . ' of ' . $new_iteration_month . ' ' . $new_iteration_year));
						$new_date_month = date('F', strtotime($interation_str  . ' of ' . $new_iteration_month . ' ' . $new_iteration_year));

						if($new_iteration_month == $new_date_month){

							$new_event['acf_fields']['start_date'] = $new_date;

							if($new_event['acf_fields']['end_date']){
								$new_event['acf_fields']['end_date'] = date('Ymd', strtotime($new_event['acf_fields']['start_date'] . ' + ' . $new_event['end_date_interval'] . 'days'));
							}

							$new_event_date = date('Ymd', strtotime($new_event['acf_fields']['start_date']));

							// echo date('F j, Y', strtotime($new_event['acf_fields']['start_date'])) . '<br>';
							if($new_event_date <= date('Ymd', strtotime($repeat_duration_end_date))){
								self::msw_create_event($new_event);
							}
						}
						$end_i++;
					}
				}
			}
		}
		//echo '</pre>';
	}
}

new MakespaceFrameworkRepeatingEvents();