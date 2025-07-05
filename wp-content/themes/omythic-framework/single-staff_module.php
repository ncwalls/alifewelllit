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
						<div class="img" style="background-image: url(<?php echo $staff_image; ?>)"></div>
					</figure>
				<?php endif; ?>
				<?php the_content(); ?>
				<?php if(have_rows('social_media')): ?>
					<div class="msw-staff-social">
						<?php while(have_rows('social_media')): the_row(); ?>
						<a href="<?php the_sub_field('link'); ?>" target="_blank" title="<?php the_sub_field('site_name'); ?>"><i class="<?php the_sub_field('css_class'); ?>"></i></a>
						<?php endwhile; ?>
					</div>
				<?php endif; ?>
			</div>
			<footer>
				<ul>
					<li class="item prev">
						<?php if( get_previous_post() ): $prev = get_previous_post(); ?>
							<a href="<?php echo get_permalink( $prev->ID ); ?>"><?php echo $prev->post_title; ?></a>
						<?php endif; ?>
					</li>
					<li class="item next">
						<?php if( get_next_post() ): $next = get_next_post(); ?>
						<a href="<?php echo get_permalink( $next->ID ); ?>"><?php echo $next->post_title; ?></a>
						<?php endif; ?>
					</li>
					<li>
						<a href="<?php echo get_post_type_archive_link( 'staff_module' ); ?>">Back to All</a>
					</li>
				</ul>
			</footer>
		</article>
	<?php endwhile; ?>

<?php get_footer();