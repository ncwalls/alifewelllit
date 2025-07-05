<?php 

/*
	
	archive page code:
	
	<?php
		$search_term = false;
		if(get_query_var('s')){
			$search_term = get_query_var('s');
		}
		$filter_args = array(
			'post_type' => 'journal',
			'tax_1_slug' => 'journal_category',
			'tax_1_title' => 'Category',
			'tax_1_all_text' => 'Categories',
			'tax_2_slug' => 'journal_state',
			'tax_2_title' => 'State',
			'tax_2_all_text' => 'Search by State',
			'search' => true,
			'search_term' => $search_term
		);

		get_template_part( 'template', 'dual_filters', array($filter_args));
	? >
*/

	$template_args = $args[0];

	$post_type_slug = $template_args['post_type'];
	if(isset($template_args['post_type_url'])){
		$post_type_url = $template_args['post_type_url'];
	}
	else{
		$post_type_url = get_post_type_archive_link($template_args['post_type']);
	}
	$post_type_name = get_post_type_object( $template_args['post_type'] )->labels->name;
	$tax_1_slug = $template_args['tax_1_slug'];
	$tax_1_title = $template_args['tax_1_title'];
	$tax_1_all_text = $template_args['tax_1_all_text'];
	$tax_2_slug = $template_args['tax_2_slug'];
	$tax_2_title = $template_args['tax_2_title'];
	$tax_2_all_text = $template_args['tax_2_all_text'];
	$search_field = $template_args['search'];
	$search_term = $template_args['search_term'];
	if(isset($template_args['search_key'])){
		$search_key = $template_args['search_key'];
	}
	else{
		$search_key = 's';
	}
	// $tax_1_query = false;
	// if(isset($_GET[$tax_1_slug])){
	// 	$tax_1_query = $tax_1_slug . '=' . htmlentities($_GET[$tax_1_slug]);
	// }
	// $tax_2_query = false;
	// if(isset($_GET[$tax_2_slug])){
	// 	$tax_2_query = $tax_2_slug . '=' . htmlentities($_GET[$tax_2_slug]);
	// }

	$tax_1_query = false;
	if(get_query_var($tax_1_slug)){
		$tax_1_query = $tax_1_slug . '=' . htmlentities(get_query_var($tax_1_slug));
	}
	$tax_2_query = false;
	if(get_query_var($tax_2_slug)){
		$tax_2_query = $tax_2_slug . '=' . htmlentities(get_query_var($tax_2_slug));
	}
?>
		
		<?php if($search_field == true): ?>
			<form action="<?php echo $post_type_url; ?>" method="get" role="search" id="<?php echo $post_type_slug; ?>-search-form" class="search-form">
				<label for="blog-search-input">Keyword Search</label>
				<div class="search-field">
					<input type="text" name="<?php echo $search_key; ?>" id="<?php echo $post_type_slug; ?>-search-input" placeholder="Search" value="<?php echo isset($_GET[$search_key]) ? $_GET[$search_key] : get_search_query(); ?>">
					<?php if($tax_1_query): ?>
						<input type="hidden" name="<?php echo $tax_1_slug; ?>" value="<?php echo htmlentities(get_query_var($tax_1_slug)) ?>" />
					<?php endif; ?>
					<?php if($tax_2_query): ?>
						<input type="hidden" name="<?php echo $tax_2_slug; ?>" value="<?php echo htmlentities(get_query_var($tax_2_slug)) ?>" />
					<?php endif; ?>
					<button type="submit" id="searchsubmit" form="<?php echo $post_type_slug; ?>-search-form" value="Search">
						<i class="fa fa-search" aria-hidden="true"></i>
					</button>

					<?php if(isset($_GET[$search_key])): ?>
						<?php
							$clear_search_url = $post_type_url;

							if($tax_1_query && $tax_2_query){
								$clear_search_url .= '?' . $tax_1_query . '&' . $tax_2_query;
							}
							else{
								if($tax_1_query){
									$clear_search_url .= '?' . $tax_1_query;
								}
								if($tax_2_query){
									$clear_search_url .= '?' . $tax_2_query;
								}
							}
						?>
						<a href="<?php echo $clear_search_url; ?>" class="search-clear"><i class="fal fa-times"></i> Clear Search</a>
					<?php endif; ?>

				</div>
			</form>
		<?php endif; ?>

		<div class="filter-container <?php echo $tax_1_slug; ?>">
			<div class="filter-label">Filter By <?php echo $tax_1_title; ?></div>
			<div class="filter-dropdown">
				<div class="filter-display">
					<?php
						if( single_term_title( '', false ) && get_query_var('taxonomy') == $tax_1_slug){
							single_term_title();
						}
						elseif(get_query_var($tax_1_slug)){
							// echo htmlentities($_GET[$tax_1_slug]);

							$term_1_slug = get_query_var($tax_1_slug);
							$term_1 = get_term_by('slug', $term_1_slug, $tax_1_slug);

							echo $term_1->name;

						}
						else {
							echo $tax_1_all_text;
						}
					?>
				</div>
				<div class="dropdown-list">
					<ul>
						<?php
							$tax_1_all_url = $post_type_url;
							$tax_1_all_query = '';

							if($tax_2_query){
								// $tax_1_all_url .= '?'.$tax_2_slug.'=' . htmlentities($_GET[$tax_2_slug]);
								$tax_1_all_query = '?' . $tax_2_query;
							
								if($search_term){
									$tax_1_all_query .= '&'.$search_key.'=' . $search_term;
								}
							}
							elseif($search_term){
								$tax_1_all_query = '?'.$search_key.'=' . $search_term;
							}
							$tax_1_all_url .= $tax_1_all_query;
						?>
						<li><a title="View All <?php echo $post_type_name; ?>" href="<?php echo $tax_1_all_url; ?>" data-action="filter-link" data-query="<?php echo str_replace('?','',$tax_1_all_query); ?>">All</a></li>
						<?php
							$tax_1_terms = get_terms( array(
								'orderby' => 'name',
								'order'   => 'ASC',
								'taxonomy' => $tax_1_slug
							) );
							foreach( $tax_1_terms as $category ) {
								$catslug = $category->slug;
								$catname = $category->name;
								$accessibility_title = $catname . ' ' . $post_type_name;
								$cat_query = $tax_1_slug . '=' . $catslug;
								// $caturl = $post_type_url . '?' . $cat_query;

								if(get_query_var($tax_2_slug)){
									$cat_query .= '&'.$tax_2_slug.'=' . htmlentities(get_query_var($tax_2_slug));
								}
								if($search_term){
									$cat_query .= '&'.$search_key.'=' . $search_term;
								}
								$caturl = $post_type_url . '?' . $cat_query;

								echo '<li><a title="' . $accessibility_title . '" href="' . $caturl .'"  data-action="filter-link" data-query="' . $cat_query . '">' . $catname . '</a></li>';
							}
						?>
					</ul>
				</div>
			</div>
		</div>
		<div class="filter-container <?php echo $tax_2_slug; ?>">
			<div class="filter-label">Filter By <?php echo $tax_2_title; ?></div>
			<div class="filter-dropdown">
				<div class="filter-display">
					<?php
						if( single_term_title( '', false ) && get_query_var('taxonomy') == $tax_2_slug){
							single_term_title();
						}
						elseif(get_query_var($tax_2_slug)){
							// echo htmlentities($_GET[$tax_2_slug]);
							$term_2_slug = get_query_var($tax_2_slug);
							$term_2 = get_term_by('slug', $term_2_slug, $tax_2_slug);

							echo $term_2->name;
						}
						else {
							echo $tax_2_all_text;
						}
					?>
				</div>
				<div class="dropdown-list">
					<ul>
						<?php
							$tax_2_all_url = $post_type_url;
							$tax_2_all_query = '';
							
							if($tax_1_query){
								// $tax_2_all_url .= '?'.$tax_1_slug.'=' . htmlentities($_GET[$tax_1_slug]);
								$tax_2_all_query = '?' . $tax_1_query;
							
								if($search_term){
									$tax_2_all_query .= '&' . $search_key . '=' . $search_term;
								}
							}
							elseif($search_term){
								$tax_2_all_query = '?' . $search_key . '=' . $search_term;
							}
							$tax_2_all_url .= $tax_2_all_query;
						?>
						<li><a title="View All <?php echo $post_type_name; ?>" href="<?php echo $tax_2_all_url; ?>" data-action="filter-link" data-query="<?php echo str_replace('?','',$tax_2_all_query); ?>">All</a></li>
						<?php
							$tax_2_terms = get_terms( array(
								'orderby' => 'name',
								'order'   => 'ASC',
								'taxonomy' => $tax_2_slug
							) );

							foreach( $tax_2_terms as $category ) {
								$catslug = $category->slug;
								$catname = $category->name;
								$accessibility_title = $catname . ' ' . $post_type_name;
								$cat_query = $tax_2_slug . '=' . $catslug;

								if($tax_1_query){
									$cat_query .= '&' . $tax_1_query;
								}
								if($search_term){
									$cat_query .= '&' . $search_key . '=' . $search_term;
								}
								$caturl = $post_type_url . '?' . $cat_query;

								echo '<li><a title="' . $accessibility_title . '" href="' . $caturl .'" data-action="filter-link" data-query="' . $cat_query . '">' . $catname . '</a></li>';
							}
						?>
					</ul>
				</div>
			</div>
		</div>
