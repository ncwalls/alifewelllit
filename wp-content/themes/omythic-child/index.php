<?php get_header(); ?>

	<div class="container main">
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
		
		<div class="filter-container">
			<div class="filter-label">Filter By</div>
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

		<div class="archive-list">
			<?php while( have_posts() ): the_post(); ?>
				<article <?php post_class(); ?> id="post-<?php the_ID(); ?>" itemscope itemtype="http://schema.org/BlogPosting">
					<a title="<?php the_title(); ?>" href="<?php the_permalink(); ?>">
						<?php
							$thumb_image = '';
							if( get_the_post_thumbnail_url() ){
								$thumb_image = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
							}
							else{
								$thumb_image = get_field( 'default_placeholder_image', 'option' )['sizes']['medium'];
							}
						?>
						<figure class="post-thumbnail">
							<div class="image">
								<img src="<?php echo $thumb_image; ?>" alt="" loading="lazy">
							</div>
						</figure>
						<div class="content">
							<h2 class="post-title" itemprop="name"><?php the_title(); ?></h2>
							<?php if( is_search() ): ?>
								<?php
									$post_type_label = false;

									$post_type_obj = get_post_type_object(get_post_type());

									if($post_type_obj->name == 'post' ){
										$post_type_label = 'Blog Post';
									}
									elseif($post_type_obj){
										if($post_type_obj->name == 'page'){
											$post_type_label = false;
										}
										else{
											$post_type_label = $post_type_obj->labels->singular_name;
										}
									}

									if($post_type_label != false):
								?>
									<ul class="post-meta">
										<li>
											<?php echo $post_type_label; ?>
										</li>
									</ul>
								<?php endif; ?>
							<?php else: ?>
								<ul class="post-meta">
									<li><i class="far fa-clock"></i><?php read_time(); ?></li>
									<li datetime="<?php the_time( 'Y-m-d' ); ?>" itemprop="datePublished"><i class="far fa-calendar"></i><?php the_time( 'F j, Y' ); ?></li>
									<li><i class="fas fa-pencil"></i><?php the_author(); ?></li>
								</ul>
							<?php endif; ?>
							<div class="post-excerpt">
								<?php the_excerpt(); ?>
							</div>
							<span class="button">Read This</span>
						</div>
					</a>
				</article>
			<?php endwhile; ?>
		</div>
		
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