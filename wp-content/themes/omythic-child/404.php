<?php get_header(); ?>
	
	<article>
		<div class="container">
			<h1><?php echo get_field('404_page_title', 'option') ?: 'Error 404 Page Not Found'; ?></h1>

			<div class="wysiwyg">
				<?php echo get_field('404_page_content', 'option') ?: 'Page Not Found'; ?>
			</div>
		</div>

	</article>

<?php get_footer();