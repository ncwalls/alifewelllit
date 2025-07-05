<?php get_header(); ?>
	
	<?php while( have_posts() ): the_post(); ?>

		<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

			<div class="container">

				<h1><?php the_title(); ?></h1>

				<div class="wysiwyg">
					<?php the_content(); ?>
				</div>

			</div>

		</article>

	<?php endwhile; ?>

<?php get_footer();