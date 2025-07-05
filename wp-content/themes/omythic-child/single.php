<?php get_header(); ?>

	<div class="container">
		<?php while( have_posts() ): the_post(); ?>
			<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
				<h1><?php the_title(); ?></h1>
				<ul class="post-meta">
					<li datetime="<?php the_time( 'Y-m-d' ); ?>" itemprop="datePublished"><?php the_time( 'F j, Y' ); ?></li>
					<li><?php read_time(); ?></li>
					<li><?php the_author(); ?></li>
				</ul>
				<div class="wysiwyg">
					<?php the_content(); ?>
				</div>
				<div class="single-share">
					<div class="inner">
						<div class="share-title">Share This Article</div>
						<?php echo do_shortcode('[addtoany]'); ?>
					</div>
				</div>
				<footer class="single-pagination">
					<ul>
						<li class="item prev">
							<?php if( get_previous_post() ): $prev = get_previous_post(); ?>
								<a title="<?php echo $prev->post_title; ?>" href="<?php echo get_permalink( $prev->ID ); ?>">
									<i class="far fa-angle-left"></i> <span class="text">Previous</span>
								</a>
							<?php endif; ?>
						</li>
						<li class="item all">
							<a title="All posts" href="<?php echo get_permalink(get_option('page_for_posts')); ?>">Back to All</a>
						</li>
						<li class="item next">
							<?php if( get_next_post() ): $next = get_next_post(); ?>
								<a title="<?php echo $next->post_title; ?>" href="<?php echo get_permalink( $next->ID ); ?>">
									<span class="text">Next</span> <i class="far fa-angle-right"></i>
								</a>
							<?php endif; ?>
						</li>
					</ul>
				</footer>
			</article>
		<?php endwhile; ?>
	</div>

<?php get_footer();