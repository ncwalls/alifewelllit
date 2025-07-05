<?php get_header(); ?>

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

	<?php
		echo paginate_links( array(
			'prev_text' => '<i class="far fa-angle-left"></i>',
			'next_text' => '<i class="far fa-angle-right"></i>',
			'type' => 'list'
		) );
	?>

<?php get_footer();