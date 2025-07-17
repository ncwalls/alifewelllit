<?php get_header(); ?>

	<div class="container main">
		<div class="blog-header">
			<div>
				<h1><?php echo get_the_title(get_option('page_for_posts')); ?></h1>
				<?php if( single_term_title( '', false ) ): ?>
					<h2 class="category-title"><?php single_term_title(); ?></h2>
				<?php endif;?>
				<?php if(is_search()): ?>
					<h2>Search: "<?php echo htmlentities(get_search_query()); ?>"</h2>
				<?php else: ?>
					<div class="wysiwyg">
						<?php echo apply_filters('the_content', get_post_field('post_content', get_option('page_for_posts'))); ?>
					</div>
				<?php endif; ?>
			</div>
			
			<div class="filter-container">
				<div class="filter-dropdown">
					<div class="filter-display">
						<?php
							if( single_term_title( '', false ) ){
								single_term_title();
							} else {
								echo 'Filter By';
							}
						?>
					</div>
					<nav class="dropdown-list">
						<ul>
							<?php $post_type_name = get_post_type_object( get_post_type( get_the_ID() ) )->labels->name;  ?>
							<li><a title="View All <?php echo $post_type_name; ?>" href="<?php echo get_post_type_archive_link( get_post_type( get_the_ID() ) ); ?>">All</a></li>
							<?php
								$categories = get_categories( array(
									'orderby' => 'name',
									'order'   => 'ASC'
								) );

								foreach( $categories as $category ) {
									$caturl = get_category_link( $category->term_id );
									$catname = $category->name;
									$accessibility_title = $catname . ' ' . $post_type_name;
									echo '<li><a title="' . $accessibility_title . '" href="' . $caturl .'">' . $catname. '</a></li>';
								}
							?>
						</ul>
					</nav>
				</div>
			</div>
		</div>
	</div>

	<div class="blog-list">
		<?php while( have_posts() ): the_post(); ?>
			<article <?php post_class(); ?> id="post-<?php the_ID(); ?>" itemscope itemtype="http://schema.org/BlogPosting">
				<a href="<?php the_permalink(); ?>" class="wrap">
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
					<div class="content">
						<div>
							<h2 class="post-title" itemprop="name"><?php the_title(); ?></h2>
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
							<div class="excerpt">
								<?php the_excerpt(); ?>
							</div>
						</div>
						<div>
							<span class="button hover-dark">Read This</span>
						</div>
					</div>
				</a>
			</article>
		<?php endwhile; ?>
	</div>
	
	<div class="container">
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
	</div>

<?php get_footer();