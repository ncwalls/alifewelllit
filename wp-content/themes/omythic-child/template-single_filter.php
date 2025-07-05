<?php 

/*
	archive page code:
	
	<?php
		$filter_args = array(
			'post_type' => 'post',
			'tax_1_slug' => 'research_category',
			'tax_1_title' => 'Category',
			'tax_1_all_text' => 'All',
		);

		get_template_part( 'template', 'single_filter', array($filter_args));
	?>
*/

	$template_args = $args[0];

	$post_type_slug = $template_args['post_type'];
	$post_type_url = get_post_type_archive_link($template_args['post_type']);
	$post_type_name = get_post_type_object( $template_args['post_type'] )->labels->name;
	$tax_1_slug = $template_args['tax_1_slug'];
	$tax_1_title = $template_args['tax_1_title'];
	$tax_1_all_text = $template_args['tax_1_all_text'];

?>

	<div class="filter-container">
		<div class="filter-label">Filter By <?php echo $tax_1_title; ?></div>
		<div class="filter-dropdown">
			<div class="filter-display">
				<?php
					if( single_term_title( '', false ) ){
						single_term_title();
					} else {
						echo $tax_1_all_text;
					}
				?>
			</div>
			<nav class="dropdown-list">
				<ul>
					<li><a title="View All <?php echo $post_type_name; ?>" href="<?php echo $post_type_url; ?>">All</a></li>
					<?php
						$categories = get_terms( array(
							'orderby' => 'name',
							'order'   => 'ASC',
							'taxonomy' => $tax_1_slug
						) );
						foreach( $categories as $category ) {
							$caturl = get_term_link( $category->term_id );
							$catname = $category->name;
							$accessibility_title = $catname . ' ' . $post_type_name;
							echo '<li><a title="' . $accessibility_title . '" href="' . $caturl .'">' . $catname. '</a></li>';
						}
					?>
				</ul>
			</nav>
		</div>
	</div>