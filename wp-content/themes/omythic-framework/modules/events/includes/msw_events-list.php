<div class="msw-calendar-list">	
	<?php while( have_posts() ): the_post(); ?>
		<?php 

			// if(isset($_GET['state'])){
			// 	$state_filtered = filter_var($_GET['state'], FILTER_SANITIZE_STRING);
			// }
		?>
		<article <?php post_class('event'); ?> id="event-<?php the_ID(); ?>">
			<a title="<?php the_title(); ?>" href="<?php the_permalink(); ?>" class="inner">
				<?php 
					$thumb_image = '';
					if( get_the_post_thumbnail_url() ){
						$thumb_image = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
					}
					else{
						$thumb_image = get_field( 'default_placeholder_image', 'option' )['sizes']['medium'];
					}
				?>
				<figure class="event-thumbnail">
					<div class="img" style="background-image: url(<?php echo $thumb_image; ?>)"></div>
				</figure>
				<div class="content">
					<!-- <div class="post-label">Event</div> -->
					<h2 class="event-title"><?php the_title(); ?></h2>
					<p class="event-date">
						<?php
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
						?>
					</p>
					<p class="event-time">
						<?php
							$start_time = get_field('start_time');
							$start_time_date = date_create($start_time);
							$start_time_display = date_format($start_time_date, 'g:ia');

							$end_time = get_field('end_time');
							$end_time_date = date_create($end_time);
							$end_time_display = date_format($end_time_date, 'g:ia');

							echo $start_time_display . ' - ' . $end_time_display;
						?>
					</p>
					<?php 
						$event_cost = get_field('cost' );
						if($event_cost && $event_cost > 0):
					?>
						<p class="event-cost">
							<?php echo '$' . $event_cost; ?>
						</p>
					<?php endif; ?>
					<?php
						$event_location = get_field('location'); 
						if($event_location):
					?>
						<p class="event-location">
							<?php echo $event_location['address']; ?>
						</p>
					<?php endif; ?>
					<!-- <div class="post-excerpt">
						<?php //the_excerpt(); ?>
					</div> -->
					<span class="button">View Details</span>
				</div>
			</a>
		</article>
	<?php endwhile; ?>
</div>
