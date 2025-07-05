<?php get_header(); ?>

	<h1><?php
		if(get_field('locations_module_archive_title', 'option')){
			echo get_field('locations_module_archive_title', 'option');
		}
		else{
			echo get_post_type_object('locations_module')->labels->name;
		} 
	?></h1>

	<ol class="msw-locations-module-list">
		<?php while( have_posts() ) : the_post(); ?>
			<li class="msw-locations-module-item">
				<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>" class="msw-locations-module-permalink">
					<h2 class="msw-locations-module-name"><?php the_title(); ?></h2>
					<div class="msw-locations-module-hentry"><?php the_content(); ?></div>
				</a>
			</li>
		<?php endwhile; ?>
	</ol>

	<?php if(paginate_links()): ?>
		<footer class="archive-pagination">
			<div class="pagination-links">
				<?php
					$translated = __( 'Page ', 'mytextdomain' );
					echo paginate_links( array(
						'prev_text' => '<i class="fal fa-chevron-left"></i><span class="screen-reader-text">Previous Page</span>',
						'next_text' => '<i class="fal fa-chevron-right"></i><span class="screen-reader-text">Next Page</span>',
						'type' => 'plain',
						'before_page_number' => '<span class="screen-reader-text">' . $translated . '</span>'
					) );
				?>
			</div>
		</footer>
	<?php endif; ?>

<?php get_footer();