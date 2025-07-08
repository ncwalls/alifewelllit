<?php

if( !function_exists( 'get_google_map_data' ) ){
	function get_google_map_data(){
		$map = array();

		/*
		 * Get All Locations, sorted to Primary Location first
		 */ 
		$location_args = array(
			'meta_key' => 'primary_location',
			'numberposts' => -1,
			'orderby' => 'meta_value',
			'post_type' => 'locations_module',
		);
		$locations = get_posts( $location_args );
		$primary_location_map_marker;

		foreach ($locations as $location) {
			$location_name = esc_html( get_the_title( $location ) );
			$location_id = $location->ID;
			$google_map_details = get_field( 'google_map', $location );
			if($google_map_details){
				$location_lat = $google_map_details['lat'];
				$location_lng = $google_map_details['lng'];

				/*
				 * Set primary location marker as default
				 * If a location has a marker, use that
				 */
				if ( get_field( 'primary_location', $location ) == '1' && get_field( 'map_marker', $location )) {
					$primary_location_map_marker = get_field( 'map_marker', $location )['url'];
					$location_marker = $primary_location_map_marker;
				}
				if ( get_field( 'map_marker', $location ) ) {
					$location_marker = get_field( 'map_marker', $location )['url'];
				}
				elseif ( get_field( 'map_marker', 'option' ) ) {
					$location_marker = get_field( 'map_marker', 'option' )['url'];
				}
			}

			/*
			 * Build array of all locations' info and send it as json
			 */
			$location_url = '';
			if(get_field( 'url' , $location )){
				$location_url = esc_url( get_field( 'url' , $location) );
			}
			$location_array = array(
				'location_name' => $location_name,
				'location_id' => $location_id,
				'location_url' => $location_url,
				'lat' => isset($location_lat) ? $location_lat : 0,
				'lng' => isset($location_lng) ? $location_lng : 0,
				'street_address_line_1' => esc_html( get_field( 'street_address_line_1', $location) ),
				'street_address_line_2' => esc_html( get_field( 'street_address_line_2', $location) ),
				'city' => esc_html( get_field( 'city', $location) ),
				'state_region' => esc_html( get_field( 'state_region', $location) ),
				'zip_postal_code' => esc_html( get_field( 'zip_postal_code', $location) ),
				'country' => esc_html( get_field( 'country', $location) ),
			);
			if ( isset( $location_marker ) ) {
				$location_array['marker'] = $location_marker;
			}
			$location_array['infowindow_content'] = '<div class="infowindow-row">';

				if(get_the_post_thumbnail_url($location_id)){
					$location_array['infowindow_content'] .= '<div class="infowindow-image"><img src="' . get_the_post_thumbnail_url($location_id) . '" alt="' . $location_name .'"></div>';
				}
				
				$location_array['infowindow_content'] .= '<div class="infowindow-content">';
					$location_array['infowindow_content'] .= '<p class="name">' . $location_name . '</p>';

					$location_array['infowindow_content'] .= '<p class="address">';
					$location_infowindow_address = esc_html( get_field( 'street_address_line_1', $location) ) . '<br>';
					if(get_field( 'street_address_line_2', $location)){
						$location_infowindow_address .= esc_html( get_field( 'street_address_line_2', $location) ) . '<br>';
					}
					$location_infowindow_address .= esc_html( get_field( 'city', $location) ) . ', ';
					$location_infowindow_address .= esc_html( get_field( 'state_region', $location) ) . ' ';
					$location_infowindow_address .= esc_html( get_field( 'zip_postal_code', $location) ) . '</p>';
					
					$map_location = get_field('google_map', $location);
					if(isset($map_location['address'])){
						$directions_link = makespaceChild::get_google_directions_url( $map_location['address'] );
						$location_array['infowindow_content'] .= '<a href="' . $directions_link . '" target="_blank">' . $location_infowindow_address . '</a></p>';
					}
					else{
						$location_array['infowindow_content'] .= $location_infowindow_address;
					}
					$location_array['infowindow_content'] .= '</p>';
					
					if($location_phone = get_field( 'phone', $location)){
						$location_array['infowindow_content'] .= '<p class="phone"><a href="tel:' . MakespaceChild::format_number_string($location_phone) . '">' . $location_phone . '</p>';
					}
				$location_array['infowindow_content'] .= '</div>';
			$location_array['infowindow_content'] .= '</div>';

			$map[] = $location_array;
		}		

		return json_encode( $map );
	}
}

if( !function_exists( 'get_read_time' ) ) {
	function get_read_time( $post_id = 0 ){
		if( is_single() && 0 == $post_id ){
			global $post;
			$post_id = $post->ID;
			$text = get_the_content();
		} else {
			$content_post = get_post( $post_id );
			$text = $content_post->post_content;
		}
		$words = str_word_count( strip_tags( $text ) );
		$words_per_minute = apply_filters( 'read_time_words_per_minute', 200 );
		$min = (int)ceil( $words / $words_per_minute );
		$min = max( 1, $min );

		return apply_filters( 'read_time_minutes', $min );
	}
}

if( !function_exists( 'read_time' ) ) {
	function read_time( $post_id = 0 ){
		$read_time_in_minutes = get_read_time( $post_id );
		$time_string = apply_filters( 'read_time_units', 'min read' );

		echo trim( $read_time_in_minutes . ' ' . $time_string );
	}
}