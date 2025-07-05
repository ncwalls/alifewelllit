<?php get_header(); ?>
	
	<h1><?php
		if(get_field('case_studies_module_archive_title', 'option')){
			echo get_field('case_studies_module_archive_title', 'option');
		}
		else{
			echo get_post_type_object('case_studies_module')->labels->name;
		} 
	?></h1>

	<div class="wysiwyg">
		<?php echo get_field('case_studies_module_intro_copy', 'option'); ?>
	</div>

	<div class="msw-case-studies-filter">
		<?php
			$filter_args = array(
				'post_type' => 'case_studies_module',
				'tax_1_slug' => 'case_studies_module_industry',
				'tax_1_title' => 'Industry',
			);

			get_template_part( 'template', 'single_filter', array($filter_args));
		?>
	</div>

	<ol class="msw-case-studies-module-list">
		<?php while( have_posts() ) : the_post(); ?>
			<li class="msw-case-studies-module-item">
				<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>" class="msw-case-studies-module-permalink">
					<?php
						$case_image = '';
						if( get_field('header_image') ):
							$case_image = get_field('header_image')['sizes']['medium'];
					?>
						<figure class="msw-case-thumbnail">
							<div class="img" style="background-image: url(<?php echo $case_image; ?>)"></div>
						</figure>
					<?php endif; ?>
					<h2 class="msw-case-studies-module-name"><?php the_title(); ?></h2>
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