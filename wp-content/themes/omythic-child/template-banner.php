<?php
	$header_image = get_field( 'default_header_image', 'option' );

	if( is_singular() && get_field( 'header_image' ) ){
		$header_image = get_field( 'header_image' );
	}
	else {
		$slug = get_post_type();
		if( 'post' != $slug && get_field( $slug . '_header_image', 'option' ) ){
			$header_image = get_field( $slug . '_header_image', 'option' );
		}

		if( is_singular('post') && get_the_post_thumbnail_url() ){
			// $header_image = get_the_post_thumbnail_url( );
			
			$header_image = array(
				'url' => get_the_post_thumbnail_url(),
				'sizes' => array(
					'medium' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
					'large' => get_the_post_thumbnail_url(get_the_ID(), 'large')
				)
			);
		}
		elseif( is_singular('post') && get_field( 'header_image', get_option( 'page_for_posts' ) ) ){
			$header_image = get_field( 'header_image', get_option( 'page_for_posts' ) );
		}
	}

	if(is_home() || is_category()){
		if( get_field( 'header_image', get_option( 'page_for_posts' ) ) ){
			$header_image = get_field( 'header_image', get_option( 'page_for_posts' ) );
		}
		else{
			$header_image = get_field( 'default_header_image', 'option' );
		}
	}

	// if(is_array( $header_image )){
	// 	$header_image = $header_image['url'];
	// }
?>

<?php if($header_image): ?>
	<style type="text/css">
		.msw-page-banner{
			background-image: url(<?php echo $header_image['url']; ?>);
		}
		@media (max-width: 1400px) {
			.msw-page-banner{
				background-image: url(<?php echo $header_image['sizes']['large']; ?>);
			}
		}
		@media (max-width: 768px) {
			.msw-page-banner{
				background-image: url(<?php echo $header_image['sizes']['medium']; ?>);
			}
		}
	</style>
<?php endif; ?>
<aside class="msw-page-banner">
</aside>

<div id="breadcrumbs">
	<div class="container">
		<?php
			if( function_exists( 'yoast_breadcrumb' )) { 
				// if(( is_singular() && !is_page()) || (is_page() && $post->post_parent > 0 )){
					yoast_breadcrumb( '', '' );
				// }
			}
		?>
	</div>
</div>