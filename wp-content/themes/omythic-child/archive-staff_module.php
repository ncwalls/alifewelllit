<?php get_header(); ?>
	
	<div class="container main">
		<h1><?php
			if(get_field('staff_module_archive_title', 'option')){
				echo get_field('staff_module_archive_title', 'option');
			}
			else{
				echo get_post_type_object('staff_module')->labels->name;
			} 
		?></h1>

		<div class="wysiwyg">
			<?php echo get_field('staff_module_intro_copy', 'option'); ?>
		</div>

		<div class="msw-staff-filter">
			<?php
				$filter_args = array(
					'post_type' => 'staff_module',
					'tax_1_slug' => 'staff_module_department',
					'tax_1_title' => 'Department',
				);

				get_template_part( 'template', 'single_filter', array($filter_args));
			?>
		</div>

		
		<ol class="msw-staff-module-list">
			<?php while( have_posts() ) : the_post(); ?>
				<li class="msw-staff-module-item">
					<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>" class="msw-staff-module-permalink">
						<?php
							$staff_image = '';
							if( get_field('primary_photo') ):
								$staff_image = get_field('primary_photo')['sizes']['medium'];
								
								if(get_field('secondary_photo')){
									$staff_image_secondary = get_field('secondary_photo')['sizes']['medium'];
								}
						?>
						<figure class="msw-staff-module-thumbnail">
							<div class="img">
								<img src="<?php echo $staff_image; ?>" class="<?php echo get_field('secondary_photo') ? 'primary' : ''; ?>" alt="" loading="lazy">
								<?php if(get_field('secondary_photo')): ?>
									<img src="<?php echo $staff_image_secondary; ?>" class="secondary" alt="" loading="lazy">
								<?php endif; ?>
							</div>
						</figure>
					<?php endif; ?>
						<h2 class="msw-staff-module-name"><?php the_title(); ?></h2>
						<p class="msw-staff-position"><?php the_field('title'); ?></p>
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
	</div>

<?php get_footer();