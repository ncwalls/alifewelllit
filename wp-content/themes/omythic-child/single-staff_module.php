<?php get_header(); ?>

	<?php while( have_posts() ) : the_post(); ?>
		<article <?php post_class(); ?> id="staff-<?php the_ID(); ?>">
			<div class="msw-staff-content">
				<h1><?php the_title(); ?></h1>
				<h3 class="msw-staff-position"><?php the_field('title'); ?></h3>
				<?php
					$staff_image = '';
					if( get_field('primary_photo') ):
						$staff_image = get_field('primary_photo')['sizes']['medium'];

				?>
					<figure class="msw-staff-featured-image">
						<img src="<?php echo $staff_image; ?>" alt="">
					</figure>
				<?php endif; ?>
				<div class="wysiwyg">
					<?php the_content(); ?>
				</div>
				<?php if(have_rows('social_media')): ?>
					<div class="msw-staff-social">
						<ul>
							<?php while(have_rows('social_media')): the_row(); ?>
								<?php 
									$social_site_name = get_sub_field('site')['label'];
									$social_site_class = get_sub_field('site')['value'];
									$social_site_url = get_sub_field('url');
								?>
								<li>
									<a title="<?php echo $social_site_name ?>" href="<?php echo $social_site_url; ?>" target="_blank">
										<span class="fab fa-<?php echo $social_site_class; ?>"></span>
									</a>
								</li>
							<?php endwhile; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
			<footer class="single-pagination">
				<ul>
					<li class="item prev">
						<?php if( get_previous_post() ): $prev = get_previous_post(); ?>
							<a title="Previous" href="<?php echo get_permalink( $prev->ID ); ?>">
								<i class="far fa-angle-left"></i> <span class="text">Previous <span>Team Member</span></span>
							</a>
						<?php endif; ?>
					</li>
					<li class="item all">
						<a title="All" href="<?php echo get_post_type_archive_link( 'staff_module' ); ?>">Back To Team</a>
					</li>
					<li class="item next">
						<?php if( get_next_post() ): $next = get_next_post(); ?>
							<a title="Next" href="<?php echo get_permalink( $next->ID ); ?>">
								<span class="text">Next <span>Team Member</span></span> <i class="far fa-angle-right"></i>
							</a>
						<?php endif; ?>
					</li>
				</ul>
			</footer>
		</article>
	<?php endwhile; ?>

<?php get_footer();