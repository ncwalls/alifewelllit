<?php get_header(); ?>

	<?php 
		$events_archive_link = get_post_type_archive_link( 'msw_events' );

		$current_view_link = $events_archive_link;

		$list_view = false;
		if(isset($_GET['view']) && $_GET['view'] == 'list'){
			$list_view = true;
		}

		// $state_filtered = false;
		// if(isset($_GET['state'])){
		// 	$state_filtered = filter_var($_GET['state'], FILTER_SANITIZE_STRING);
		// }
	
		$cat_filtered_term = false;
		if(isset($_GET['event_category'])){
			$cat_filtered_term = get_term_by( 'slug', $_GET['event_category'], 'event_category' );
			// $cat_filtered_name = $cat_filtered_term->name;
			// $cat_filtered_slug = $cat_filtered_term->slug;
		}

		$is_date_query = false;
		if(isset($_GET['yr']) && isset($_GET['mo'])){
			$is_date_query = true;
			$y = intval($_GET['yr']);
			$m = sprintf("%02d", intval($_GET['mo']));
			$current_view_link .= '?yr=' . $y . '&mo=' . $m;
			
			// if(isset($_GET['state'])){
			// 	$current_view_link .= '&state=' . $state_filtered;
			// }
		}
		else{
			
			// if(isset($_GET['state'])){
			// 	$current_view_link .= '?state=' . $state_filtered;
			// }
		}

	?>
	<div class="msw-container-main <?php echo $list_view === true ? 'list-view' : 'calendar-view'; ?>">
		<h1><?php echo get_field('events_overview_title', 'option'); ?></h1>
		<div class="msw-container-main-intro-content"><?php echo get_field('events_overview', 'option'); ?></div>

		<div class="msw-calendar-controls">

			<?php if( $list_view === true ): ?>
				<div class="msw-calendar-views-nav">
					<a href="<?php echo $events_archive_link; ?>" class="month-view-link <?php echo $list_view === false ? 'active' : ''; ?>"><i class="far fa-calendar-alt"></i><span class="screen-reader-text">Month View</span></a>
					<a href="<?php echo $events_archive_link; ?>?view=list" class="list-view-link <?php echo $list_view === true ? 'active' : ''; ?>"><i class="fas fa-list-ul"></i><span class="screen-reader-text">List View</span></a>	
				</div>
			<?php endif; ?>

			<div class="filter-container">
				<div class="filter-label">Filter:</div>
				<div class="filter-dropdown">
					<div class="filter-display">
						<?php
							if( isset($_GET['event_category']) ){
								echo $cat_filtered_term->name;
							}
							else {
								echo 'All Events';
							}
						?>
					</div>

					<?php 

						// if(isset($_GET['view']) && $_GET['view'] == 'list'){
							// $all_events = get_posts(array(
							// 	'post_type' => 'msw_events',
							// 	'posts_per_page' => -1,
							// 	'meta_query' => array(
							// 		// 'relation' => 'AND',
							// 		array(
							// 			'key'     => 'location',
							// 			'compare' => 'EXISTS',
							// 		),
							// 		// array(
							// 		// 	'key' => 'start_date',
							// 		// 	'value' => date('Ymd'),
							// 		// 	'compare' => '>=',
							// 		// )
							// 	)
							// ));
						// }
						// else{
						// 	$all_events = get_posts(array(
						// 		'post_type' => 'msw_events',
						// 		'posts_per_page' => -1,
						// 		'meta_query' => array(
						// 			'relation' => 'AND',
						// 			array(
						// 				'key'     => 'location',
						// 				'compare' => 'EXISTS',
						// 			)
						// 		)
						// 	));
						// }
						
						// $all_states = array();


						// foreach($all_events as $event){
						// 	$event_location = get_field('location', $event->ID);

						// 	if($event_location){
						// 		$dup_key = searchForDuplicate($event_location['state_short'], 'short', $all_states);
								
						// 		// echo $event_location['state_short'] . ' - ' . $dup_key . '<br>';

						// 		if($dup_key === false){

						// 			$all_states[] = array(
						// 				'full' => $event_location['state'],
						// 				'short' => $event_location['state_short']
						// 			);
						// 		}
								
						// 	}
						// }
					?>
					<div class="dropdown-list">
						<ul>
							<?php
								// $post_type_name = get_post_type_object( get_post_type( get_the_ID() ) )->labels->name;
								$filter_all_link = $events_archive_link;
								if($list_view === true){
									$filter_all_link = $events_archive_link . '?view=list';
								}
							?>
							<li><a title="View All Events" href="<?php echo $filter_all_link; ?>">All Events</a></li>
							<?php
								$categories = get_terms( array(
									'orderby' => 'name',
									'order'   => 'ASC',
									'taxonomy' => 'event_category'
								) );
								foreach( $categories as $category ) {
								// 	$caturl = get_term_link( $category->term_id );
									// $catname = $category->name;
									// $catslug = $category->slug;
									
									$filter_link = $current_view_link;
									if($is_date_query === true){
										$filter_link .= '&';
									}
									else{
										$filter_link .= '?';
									}
									$filter_link .= 'event_category=' . $category->slug;

									if($list_view === true){
										$filter_link .= '&view=list';
									}
									
									echo '<li><a title="Events: ' . $category->name .  '" href="' . $filter_link .'">' . $category->name . '</a></li>';
								}
							?>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<?php
			if(isset($_GET['view']) && $_GET['view'] == 'list'){

				if( file_exists( get_stylesheet_directory() . '/msw-events/msw_events-list.php' ) ){
					include_once( get_stylesheet_directory() . '/msw-events/msw_events-list.php' );	
				}
				else{
					include_once( get_template_directory() . '/modules/events/includes/msw_events-list.php' );
				}
			}
			else{
				if( file_exists( get_stylesheet_directory() . '/msw-events/msw_events-month.php' ) ){
					include_once( get_stylesheet_directory() . '/msw-events/msw_events-month.php' );	
				}
				else{
					include_once( get_template_directory() . '/modules/events/includes/msw_events-month.php' );
				}
			}
		?>
	
	</div>

<?php get_footer();