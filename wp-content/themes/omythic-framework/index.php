<?php get_header(); ?>

	<?php while( have_posts() ): the_post(); ?>
		<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
			<h1><?php the_title(); ?></h1>
			<?php the_content(); ?>
			<footer>
				<ul id="prev-next">
					<?php if( get_previous_post() ): $prev = get_previous_post(); ?>
						<li id="prev"><a href="<?php echo get_permalink( $prev->ID ); ?>"><?php echo $prev->post_title; ?></a></li>
					<?php endif; ?>
					<?php if( get_next_post() ): $next = get_next_post(); ?>
						<li id="next"><a href="<?php echo get_permalink( $next->ID ); ?>"><?php echo $next->post_title; ?></a></li>
					<?php endif; ?>
				</ul>
			</footer>
		</article>
	<?php endwhile; ?>

<?php get_footer();