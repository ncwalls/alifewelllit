<?php get_header(); ?>+

	<?php while( have_posts() ) : the_post(); ?>
		<article <?php post_class(); ?> id="case-study-<?php the_ID(); ?>">

			<div class="msw-case-content">
				<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
			</div>
			
			<?php if( get_field('case_study_gallery') ): ?>
				<ul class="msw-case-gallery" data-action="popup-gallery">
					<?php foreach( get_field('case_study_gallery') as $img ): ?>
						<li class="msw-case-gallery-item">
							<a href="<?php echo $img['url']; ?>" class="msw-case-gallery-img" style="background-image: url(<?php echo $img['sizes']['medium']; ?>);"></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			
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
						<a href="<?php echo get_post_type_archive_link( 'case_studies_module' ); ?>">Back to All</a>
					</li>
				</ul>
			</footer>
		</article>
	<?php endwhile; ?>

<?php get_footer();