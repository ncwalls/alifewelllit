<?php get_header(); ?>

	<div class="container">
		<?php while( have_posts() ): the_post(); ?>
			<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
				<h1><?php the_title(); ?></h1>
				<ul class="post-meta">
					<li class="readtime">
						<span class="label">Read time:</span>
						<span><?php read_time(); ?></span>
					</li>
					<?php if(get_the_category()): ?>
						<li>
							<span class="label">Category:</span>
							<ul class="category">
								<?php foreach(get_the_category(get_the_ID()) as $cat): ?>
									<li><?php echo $cat->name; ?></li>
								<?php endforeach; ?>
							</ul>
						</li>
					<?php endif; ?>
					<li class="author">
						<span class="label">Author:</span>
						<span><?php the_author(); ?></span>
					</li>
				</ul>
				<div class="wysiwyg">
					
					<figure class="post-thumbnail">
						<div class="image">
							<?php 
								$thumb_image = '';
								if( get_the_post_thumbnail_url() ){
									$thumb_image = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
								}
								else{
									$thumb_image = get_field( 'default_placeholder_image', 'option' )['sizes']['medium'];
								}
							?>
							<img src="<?php echo $thumb_image; ?>" alt="" loading="lazy">
						</div>
					</figure>
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