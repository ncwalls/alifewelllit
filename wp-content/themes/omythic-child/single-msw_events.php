<?php get_header(); ?>

	<?php 
		$terms = get_the_terms(get_the_ID(), 'event_category');
		$term_slugs = array();
		// $term_ids = array();
		if($terms){
			foreach($terms as $term){
				$term_slugs[] = $term->slug;
				// $term_ids[] = $term->term_id;
			}
		}
	?>
	
	<div class="container">
		<?php while( have_posts() ): the_post(); ?>
			<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
				<h1><?php the_title(); ?></h1>

				<div class="wysiwyg">
					<?php the_content(); ?>
				</div>
				
				<div class="single-event-main-row">

					<?php if(get_field('event_images')): $gallery_imgs = get_field('event_images'); ?>
						<div class="single-event-images-container">
							<div class="single-event-image-slider" id="single-event-image-slider">
								<?php if( get_the_post_thumbnail_url() ): ?>
									<div class="slide">
										<a href="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?>"><img src="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?>" alt=""></a>
									</div>
								<?php endif; ?>
								<?php foreach($gallery_imgs as $img): ?>
									<div class="slide">
										<div>
											<a href="<?php echo $img['sizes']['large']; ?>"><img src="<?php echo $img['sizes']['large']; ?>" alt=""></a>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
							<div class="single-event-image-gallery">
								<?php /*if( get_the_post_thumbnail_url() ): ?>
									<div class="main-img">
										<img src="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?>" alt="">
									</div>
								<?php endif;*/ ?>
								<div class="gallery-thumbs">
									<div class="thumb-img active" data-action="event-gallery-thumb" data-target="0">
										<a href="#single-event-image-slider"><img src="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' ); ?>" alt=""></a>
									</div>
									<?php $img_count = 0; foreach($gallery_imgs as $img): $img_count++; ?>
										<div class="thumb-img" data-action="event-gallery-thumb" data-target="<?php echo $img_count; ?>">
											<a href="#single-event-image-slider"><img src="<?php echo $img['sizes']['thumbnail']; ?>" alt=""></a>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					<?php elseif( get_the_post_thumbnail_url() ): ?>
						<div class="single-event-image">
							<img src="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'large' ); ?>" alt="">
						</div>
					<?php endif; ?>

					<div class="single-event-content">
						<section class="event-date">
							<h2>Date:</h2>
							<p><?php
								$start_date = get_field('start_date');

								if(get_field('end_date')){
									$end_date = get_field('end_date');
								}
								else{
									$end_date = $start_date;
								}

								$start_date_obj = date_create($start_date);
								$start_date_display = date_format($start_date_obj,"l, F j, Y");
								
								if($end_date == $start_date){
									echo $start_date_display;
								}
								else{
									$start_year = date_format($start_date_obj,"Y");
									$start_month = date_format($start_date_obj,"m");
								
									$end_date_obj = date_create($end_date);
									$end_date_display = date_format($end_date_obj,"l, F j, Y");
									
									$end_year = date_format($end_date_obj,"Y");
									$end_month = date_format($end_date_obj,"m");

									if($start_year == $end_year){
										$start_date_display = date_format($start_date_obj,"l, F j");
									}


									echo $start_date_display;
									echo ' - <span>' . $end_date_display . '</span>';
								}
							?></p>
						</section>

						<section class="event-time">
							<h2>Time:</h2>
							<p><?php
								$start_time = get_field('start_time');
								$start_time_date = date_create($start_time);
								$start_time_display = date_format($start_time_date, 'g:ia');

								$end_time = get_field('end_time');
								$end_time_date = date_create($end_time);
								$end_time_display = date_format($end_time_date, 'g:ia');

								echo $start_time_display . ' - ' . $end_time_display;
							?></p>
						</section>

						<?php if(get_field('location') || get_field('location_name')): ?>
							<section class="event-location">
								<h2>Location:</h2>
								<p>
									<strong><?php the_field('location_name'); ?></strong><br>
									<?php $event_location = get_field('location'); ?>

									<?php if($event_location): 
										$event_address = $event_location['address'];

										//echo $event_address . '<br>';
										$loc_num = '';
										$loc_street = '';
										$loc_city = '';
										$loc_state = '';
										$loc_zip = '';

										if(isset($event_location['street_number'])){
											$loc_num = $event_location['street_number'];
										}
										if(isset($event_location['street_name'])){
											$loc_street = $event_location['street_name'];
										}
										if(isset($event_location['city'])){
											$loc_city = $event_location['city'];
										}
										if(isset($event_location['state_short'])){
											$loc_state = $event_location['state_short'];
										}
										if(isset($event_location['post_code'])){
											$loc_zip  = $event_location['post_code'];
										}

										echo $loc_num . ' ' . $loc_street . '<br>';
										echo $loc_city . ', ' . $loc_state . ' ' . $loc_zip . '<br>';
								
										$directions_url_google = 'https://www.google.com/maps/dir//' . $event_address;

										$directions_icon_waze = '<i class="fab fa-waze"></i>';
										$directions_url_waze = 'https://waze.com/ul?q=' . $event_address . '&ll=' . $event_location['lat'] . ',' . $event_location['lng'] . '&navigate=yes';

										$directions_icon_apple = '<i class="fab fa-apple"></i>';
										$directions_url_apple = 'http://maps.apple.com/?daddr=' . $event_address;
									?>
										<a href="<?php echo $directions_url_google; ?>" target="_blank">Get Directions</a><?php /*<br>
											
										<a href="<?php echo $directions_url_waze; ?>" target="_blank">Get Directions (Waze)</a><br>
										
										<a href="<?php echo $directions_url_apple; ?>" target="_blank">Get Directions (Apple Maps)</a> */?>

									<?php endif; ?>
								</p>
							</section>
						<?php endif; ?>

						<?php if(get_field('details')): ?>
							<section class="event-details">
								<h2>Details:</h2>
								<div class="wysiwyg">
									<?php the_field('details'); ?>
								</div>
							</section>
						<?php endif; ?>

						<?php 
							$event_cost = get_field('cost');
							if($event_cost && $event_cost > 0):
						?>
							<section class="event-cost">
								<h2>Cost:</h2>
								<?php if(get_field('cost_description')): ?>
								<span class="description"><?php echo get_field('cost_description'); ?></span>
								<?php endif; ?>
								<span class="price"><?php echo '$' . $event_cost; ?></span>
								<?php if(get_field('cost_per')): ?>
									<span class="per">per <?php echo get_field('cost_per'); ?></span>
								<?php endif; ?>
							</section>
						<?php endif; ?>

						<?php if(get_field('tickets_product')): ?>
							<section class="event-details">
								<?php 
								$timestamp = $start_date . $start_time;
								// echo date('F j, Y @ g:i a', strtotime( $timestamp ));
								?>
								<a href="<?php echo get_permalink(get_field('tickets_product')) . '?event=' . get_the_ID() . '&datetime=' . $timestamp; ?>" class="button">Buy Tickets</a>
							</section>
						<?php endif; ?>

						<?php $calendar_links = MakespaceFrameworkEventsModule::msw_calendar_links(get_the_ID()); ?>
						<div class="add-to-calendar">
							<p><i class="fal fa-calendar-alt"></i> Add To Calendar</p>
							<ul class="links">
								<li><a href="<?php echo $calendar_links['google']; ?>" target="_blank">Google</a></li>
								<li><a href="<?php echo $calendar_links['outlook']; ?>" target="_blank">Oulook</a></li>
								<li><a href="<?php echo $calendar_links['office365']; ?>" target="_blank">Office 365</a></li>
								<li><a href="<?php echo $calendar_links['ics']; ?>" target="_blank">Download ICS</a></li>
							</ul>
						</div>
					</div>
				</div>
				
				<div class="single-share">
					<div class="inner">
						<div class="share-title">Share</div>
					</div>
					<?php echo do_shortcode('[addtoany]'); ?>
				</div>
		
				<footer class="single-pagination">
					<?php
						$all_events = new WP_Query(array(
							'orderby' => 'meta_value_num',
							'meta_key' => 'start_date',
							'order' => 'ASC',
							'posts_per_page' => -1,
							'post_type' => 'msw_events'
						));

						foreach($all_events->posts as $key => $value) {
							if($value->ID == $post->ID){
								$nextID = false;
								$prevID = false;

								if(isset($all_events->posts[$key + 1])){
									$nextID = $all_events->posts[$key + 1]->ID;
								}

								if(isset($all_events->posts[$key - 1])){
									$prevID = $all_events->posts[$key - 1]->ID;
								}
								break;
							}
						}
					?>
					<ul>
						<li class="item prev">
							<?php if( $prevID ): ?>
								<a title="Previous" href="<?php echo get_permalink( $prevID ); ?>">
									<i class="far fa-angle-left"></i> <span class="text">Previous</span>
								</a>
							<?php endif; ?>
						</li>
						<li class="item all">
							<a href="<?php echo get_post_type_archive_link( 'msw_events' ); ?>">Back to Events Calendar</a>
						</li>
						<li class="item next">
							<?php if( $nextID ): ?>
								<a title="Next" href="<?php echo get_permalink( $nextID ); ?>">
									<span class="text">Next Event</span> <i class="far fa-angle-right"></i>
								</a>
							<?php endif; ?>
						</li>
					</ul>
				</footer>
			</article>
		<?php endwhile; ?>
	</div>

<?php get_footer();