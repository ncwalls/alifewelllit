<?php 

class MakespaceFrameworkEventsCalendar {

	/* draws a calendar */
	public static function draw_calendar($month,$year){
		/* based on https://davidwalsh.name/php-calendar */


		// if(isset($_GET['state'])){
		// 	$state_filter = filter_var($_GET['state'], FILTER_SANITIZE_STRING);
		// }
		// else{
		// 	$state_filter = false;
		// }

		if(isset($_GET['event_category'])){
			$cat_filtered_term = get_term_by( 'slug', $_GET['event_category'], 'event_category' );
		}
		else{
			$cat_filtered_term = false;
		}

		$month_total_events = 0;

		/* draw table */
		$calendar = '<table cellpadding="0" cellspacing="0" class="msw-calendar-table">';

		/* table headings */
		// $weekdays = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
		// $calendar.= '<tr class="calendar-row"><td class="calendar-day-head">'.implode('</td><td class="calendar-day-head">',$headings).'</td></tr>';
		$weekdays = array(
			'S<span class="abbr">un<span class="short">day</span></span>',
			'M<span class="abbr">on<span class="short">day</span></span>',
			'Tu<span class="abbr">e<span class="short">sday</span></span>',
			'W<span class="abbr">ed<span class="short">nesday</span></span>',
			'Th<span class="abbr">ur<span class="short">sday</span></span>',
			'F<span class="abbr">ri<span class="short">day</span></span>',
			'S<span class="abbr">at<span class="short">urday</span></span>',
		);
		/*$weekdays = array(
			array(
				'full' => 'Sunday',
				'short' => 'Sun',
				'abbr' => 'S'
			),
			array(
				'full' => 'Monday',
				'short' => 'Mon',
				'abbr' => 'M'
			),
			array(
				'full' => 'Tuesday',
				'short' => 'Tues',
				'abbr' => 'T'
			),
			array(
				'full' => 'Wednesday',
				'short' => 'Wed',
				'abbr' => 'W'
			),
			array(
				'full' => 'Thursday',
				'short' => 'Thur',
				'abbr' => 'Th'
			),
			array(
				'full' => 'Friday',
				'short' => 'Fri',
				'abbr' => 'F'
			),
			array(
				'full' => 'Saturday',
				'short' => 'Sat',
				'abbr' => 'S'
			)
		);*/
		$calendar .= '<thead>';
			$calendar .= '<tr class="calendar-row">';
			foreach($weekdays as $day){
				$calendar .= '<th class="calendar-weekday-heading">' . $day . '</th>';
			}
			$calendar .= '</tr>';
		$calendar .= '</thead>';

		/* days and weeks vars now ... */
		$running_day = date('w',mktime(0,0,0,$month,1,$year));
		$days_in_month = date('t',mktime(0,0,0,$month,1,$year));
		$days_in_this_week = 1;
		$day_counter = 0;
		$dates_array = array();

		/* row for week one */
		$calendar.= '<tr class="calendar-row">';

		/* print "blank" days until the first of the current week */
		for($x = 0; $x < $running_day; $x++):
			$calendar.= '<td class="calendar-day other-month"> </td>';
			$days_in_this_week++;
		endfor;

		/* keep going with days.... */
		for($list_day = 1; $list_day <= $days_in_month; $list_day++):

			$search_day = $year . $month . sprintf("%02d", $list_day);
			$end_day = $search_day;

			// if($state_filter !== false){
			// 	$event_args = array(
			// 		'post_type' => 'msw_events',
			// 		'posts_per_page' => -1,
			// 		'order' => 'ASC',
			// 		'orderby' => 'meta_value',
			// 		'meta_key' => 'start_time',
			// 		'meta_query' => array(
			// 			'AND',
			// 			array(
			// 				'key' => 'start_date',
			// 				'value' => $search_day
			// 			),
			// 			array(
			// 				'key' => 'location',
			// 				'value' => '"' . $state_filter . '"',
			// 				'compare' => 'LIKE'
			// 			)
			// 		)
			// 	);

			// 	$extended_event_args = array(
			// 		'post_type' => 'msw_events',
			// 		'posts_per_page' => -1,
			// 		'order' => 'ASC',
			// 		'orderby' => 'meta_value',
			// 		'meta_key' => 'start_time',
			// 		'meta_query' => array(
			// 			'AND',
			// 			array(
			// 				'key' => 'start_date',
			// 				'value' => $search_day,
			// 				'compare' => '<'
			// 			),
			// 			array(
			// 				'key' => 'end_date',
			// 				'value' => $end_day,
			// 				'compare' => '>='
			// 			),
			// 			array(
			// 				'key' => 'location',
			// 				'value' => '"' . $state_filter . '"',
			// 				'compare' => 'LIKE'
			// 			)
			// 		)
			// 	);
			// }
			if($cat_filtered_term !== false){
				$event_args = array(
					'post_type' => 'msw_events',
					'posts_per_page' => -1,
					'order' => 'ASC',
					'orderby' => 'meta_value_num',
					'meta_key' => 'start_time',
					'meta_query' => array(
						array(
							'key' => 'start_date',
							'value' => $search_day
						)
					),
					'tax_query' => array(
						array(
							'taxonomy' => 'event_category',
							'field' => 'term_id',
							'terms' => $cat_filtered_term->term_id,
							'operator' => 'IN',
						),
					),
				);

				$extended_event_args = array(
					'post_type' => 'msw_events',
					'posts_per_page' => -1,
					'order' => 'ASC',
					'orderby' => 'meta_value_num',
					'meta_key' => 'start_time',
					'meta_query' => array(
						'AND',
						array(
							'key' => 'start_date',
							'value' => $search_day,
							'compare' => '<'
						),
						array(
							'key' => 'end_date',
							'value' => $end_day,
							'compare' => '>='
						)
					),
					'tax_query' => array(
						array(
							'taxonomy' => 'event_category',
							'field' => 'term_id',
							'terms' => $cat_filtered_term->term_id,
							'operator' => 'IN',
						),
					)
				);
			}
			else{
				$event_args = array(
					'post_type' => 'msw_events',
					'posts_per_page' => -1,
					'order' => 'ASC',
					'orderby' => 'meta_value_num',
					'meta_key' => 'start_time',
					'meta_query' => array(
						array(
							'key' => 'start_date',
							'value' => $search_day,
						)
					)
				);

				$extended_event_args = array(
					'post_type' => 'msw_events',
					'posts_per_page' => -1,
					'order' => 'ASC',
					'orderby' => 'meta_value_num',
					'meta_key' => 'start_time',
					'meta_query' => array(
						'AND',
						array(
							'key' => 'start_date',
							'value' => $search_day,
							'compare' => '<'
						),
						array(
							'key' => 'end_date',
							'value' => $end_day,
							'compare' => '>='
						)
					)
				);
			}

			$day_events = get_posts($event_args);
			$extended_events = get_posts($extended_event_args);
			
			$day_class = '';
			if($day_events){
				$day_class = ' has-events ';
			}

			$calendar.= '<td class="calendar-day current-month ' . $day_class . '">';
				$calendar.= '<div class="day-inner">';
					
					$calendar.= '<div class="day-header">';
						/* add in the day number */
						$calendar.= '<div class="day-number">' . $list_day . '</div>';

						/* day of week for mobile */
						$day_datetime = new DateTime($search_day);
						$weekday = $day_datetime->format('l');
						$calendar.= '<div class="day-name">' . $weekday . '</div>';
					$calendar.= '</div>';

					$calendar.= '<div class="day-events">';

						if($day_events){

							foreach($day_events as $event){
								$calendar .= self::day_event_html($event->ID);
								$month_total_events++;
							}
						}
						if($extended_events){

							foreach($extended_events as $event){
								$calendar .= self::day_event_html($event->ID);
								$month_total_events++;
							}
						}
				
					$calendar.= '</div>';
				$calendar.= '</div>';
			$calendar.= '</td>';
			if($running_day == 6):
				$calendar.= '</tr>';
				if(($day_counter+1) != $days_in_month):
					$calendar.= '<tr class="calendar-row">';
				endif;
				$running_day = -1;
				$days_in_this_week = 0;
			endif;
			$days_in_this_week++; $running_day++; $day_counter++;
		endfor;

		/* finish the rest of the days in the week */
		if($days_in_this_week < 8 && $days_in_this_week > 1):
			for($x = 1; $x <= (8 - $days_in_this_week); $x++):
				$calendar.= '<td class="calendar-day other-month"> </td>';
			endfor;
		endif;

		/* final row */
		$calendar.= '</tr>';

		/* end the table */
		$calendar.= '</table>';

		if($month_total_events === 0 && get_field('no_events_message', 'option')){
			$calendar = '<p class="no-events-message">' . get_field('no_events_message', 'option') . '</p>' . $calendar;
		}
		
		/* all done, return result */
		return $calendar;

	/* sample usages */
	// echo '<h2>July 2009</h2>';
	// echo MakespaceFrameworkEventsCalendar::draw_calendar(7,2009);
	}


	/* Get Month Name */
	public static function get_month_name($m){
		$dateObj = DateTime::createFromFormat('!m', $m);
		return $dateObj->format('F');
	}

	/* Get Previous Month */
	public static function get_previous_month($date){

		$current_date = new DateTime(date($date));  
		$previous_month = $current_date->modify('-1 month'); // Modifying object to next month date
		// $next_month_num = $view_datetime->format('m');
		// $next_month_name = $view_datetime->format('F'); 
		// $next_year = $view_datetime->format('Y'); 

		return $previous_month;
	}
	/* Get Next Month */
	public static function get_next_month($date){

		$current_date = new DateTime(date($date));  
		$next_month = $current_date->modify('+1 month'); // Modifying object to next month date
		// $next_month_num = $view_datetime->format('m');
		// $next_month_name = $view_datetime->format('F'); 
		// $next_year = $view_datetime->format('Y'); 

		return $next_month;
	}

	public static function day_event_html($event_id){
		$event_link = get_permalink($event_id);
		$event_title = get_the_title($event_id);
		$event_html = '<p class="day-event">';
		$event_html .= '<a class="event-link" href="' . $event_link . '">' . $event_title;
		$event_html .= '<span class="event-info-popup">';
		$event_html .= '<span class="title">' . get_the_title($event_id) . '</span>';
		$event_html .= '<span class="time">';
		
		$start_time = get_field('start_time', $event_id);
		$start_time_date = date_create($start_time);
		$start_time_display = date_format($start_time_date, 'g:ia');

		$end_time = get_field('end_time', $event_id);
		$end_time_date = date_create($end_time);
		$end_time_display = date_format($end_time_date, 'g:ia');

		$event_html .= $start_time_display . ' - ' . $end_time_display;
							
		$event_html .= '</span>';
		$event_cost = get_field('cost', $event_id );
		if($event_cost){
			if($event_cost > 0){
				$event_html .= '<span class="cost">$' . get_field('cost', $event_id ) . '</span>';
			}
		}
		$event_html .= '<span class="link" href="' . $event_link . '">Learn More</span>';
		$event_html .= '</span>';
		$event_html .= '</a>';
		$event_html .= '</p>';

		return $event_html;
	}
}

new MakespaceFrameworkEventsCalendar();