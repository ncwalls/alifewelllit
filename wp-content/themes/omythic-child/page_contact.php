<?php
/*
 * Template Name: Contact
 */
get_header(); ?>

<?php
	$primary_location = MakespaceChild::get_primary_location();

	$contact_address = '';
	$phone = '';
	$fax = '';
	$email = '';
	$contact_url = '';

	$map_location = '';
	$directions_link = '';

	if($primary_location){

		$address_1 = get_field('street_address_line_1', $primary_location->ID);
		$address_2 = get_field('street_address_line_2', $primary_location->ID);
		$address_city = get_field('city', $primary_location->ID);
		$address_state = get_field('state_region', $primary_location->ID);
		$address_zip = get_field('zip_postal_code', $primary_location->ID);
		$address_country = get_field('country', $primary_location->ID);


		if($address_1){
			$contact_address .= $address_1 . '<br>';
		}
		if($address_2){
			$contact_address .= $address_2 . '<br>';
		}
		if($address_city){
			$contact_address .= $address_city . ', ';
		}
		if($address_state){
			$contact_address .= $address_state;
		}
		if($address_zip){
			$contact_address .= ' ' . $address_zip . '<br>';
		}
		if($address_country){
			$contact_address .= $address_country;
		}
		
		$phone = get_field('phone', $primary_location->ID);
		$phone_display = get_field('phone_display', $primary_location->ID);
		$fax = get_field('fax', $primary_location->ID);
		$email = get_field('email', $primary_location->ID);
		$contact_url = get_field('url', $primary_location->ID);

		$map_location = get_field('google_map', $primary_location->ID);
		if($map_location){
			$directions_link = makespaceChild::get_google_directions_url( $map_location['address'] );
		}
	}
?>

	<?php while( have_posts() ): the_post(); ?>
		<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
			<div class="container">
				<h1><?php the_title(); ?></h1>
				<div class="wysiwyg">
					<?php the_content(); ?>
				</div>
				
				<div class="contact-page-content">
					<section class="contact-page-info">
						<?php if($phone): ?>
							<p class="phone"><a title="Phone number" href="tel:<?php echo MakespaceChild::format_number_string($phone); ?>"><?php echo $phone_display ? $phone_display : $phone; ?></a></p>
						<?php endif; ?>
						<?php if($fax): ?>
							<p class="fax">Fax: <a title="Fax Number" href="tel:<?php echo MakespaceChild::format_number_string($fax); ?>"><?php echo $fax; ?></a></p>
						<?php endif; ?>
						<?php if($email): ?>
							<p class="email"><a title="Email" href="mailto:<?php echo MakespaceChild::hide_email2($email); ?>"><?php echo MakespaceChild::hide_email($email); ?></a></p>
						<?php endif; ?>
						<?php if($contact_address): ?>
							<p class="address">
								<?php if($directions_link): ?>
									<a title="Get directions" href="<?php echo $directions_link; ?>" target="_blank"><?php echo $contact_address; ?><br>
									<span class="link">Directions</span></a>
								<?php else: ?>
									<?php echo $contact_address; ?>
								<?php endif; ?>
							</p>
						<?php endif; ?>
						<?php if($primary_location && have_rows('social_media_links', $primary_location->ID)): ?>
							<ul class="contact-social">
								<?php while(have_rows('social_media_links', $primary_location->ID)): the_row(); ?>
									<?php 
										$social_site_name = get_sub_field('site')['label'];
										$social_site_class = get_sub_field('site')['value'];
										$social_site_url = get_sub_field('url');
									?>
									<li>
										<a title="<?php echo $social_site_name ?>" href="<?php echo $social_site_url; ?>" target="_blank">
											<?php if(str_contains($social_site_class, 'twitter')): ?>
												<svg width="1200" height="1227" viewBox="0 0 1200 1227" fill="none" xmlns="http://www.w3.org/2000/svg">
													<path d="M714.163 519.284L1160.89 0H1055.03L667.137 450.887L357.328 0H0L468.492 681.821L0 1226.37H105.866L515.491 750.218L842.672 1226.37H1200L714.137 519.284H714.163ZM569.165 687.828L521.697 619.934L144.011 79.6944H306.615L611.412 515.685L658.88 583.579L1055.08 1150.3H892.476L569.165 687.854V687.828Z" />
												</svg>
											<?php else: ?>
												<span class="fab fa-<?php echo $social_site_class; ?>"></span>
											<?php endif; ?>
										</a>
									</li>
								<?php endwhile; ?>
							</ul>
						<?php endif; ?>
					</section>
					<section class="contact-page-form">
						<?php echo do_shortcode('[gravityform id="1" title="false" description="false" ajax="true"]'); ?>
					</section>
				</div>
				<div id="gmap" data-maxZoom="18" data-minZoom="1"></div>
			</div>
		</article>
	<?php endwhile; ?>

<?php get_footer();
