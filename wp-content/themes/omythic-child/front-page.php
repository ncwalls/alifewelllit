<?php get_header(); ?>

	<?php while( have_posts() ): the_post(); ?>
		<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
			
			<?php if($hero = get_field('hero')): ?>
				<div class="hero">
					<?php
						$hero_type = $hero['type'];
					?>
					
					<?php if($hero_type == 'image' && $hero['image']): ?>
						<div class="hero-bg" style="background-image:url(<?php echo $hero['image']['url']; ?>)"></div>

					<?php elseif($hero_type == 'slider' && $hero['slider']): ?>
						<div class="hero-slider" style="background-image:url(<?php echo $hero['slider'][0]['image']['sizes']['medium']; ?>);">
							<?php foreach($hero['slider'] as $slide): ?>
								<div class="slide">
									<img src="" alt="" data-lazy="<?php echo $slide['image']['url']; ?>">
								</div>
							<?php endforeach; ?>
						</div>

					<?php elseif($hero_type == 'video_file' && $hero['video_file']): ?>
						<div class="hero-video">
							<?php $video_url = $hero['video_file']['url']; ?>
							<video src="<?php echo $video_url; ?>" poster="<?php //echo $hero_bg; ?>" autoplay muted loop playsinline ></video>
						</div>

					<?php elseif($hero_type == 'video_embed' && $hero['video_embed']): ?>
						<?php 
							$video = $hero['video_embed'];

							// Add autoplay functionality to the video code
							if ( preg_match('/src="(.+?)"/', $video, $matches) ) {
								// Video source URL
								$src = $matches[1];

								// get youtube video id
								preg_match('/embed\/(.*?)\?/', $src, $vid_id_arr);
								
								if(is_array($vid_id_arr) && count($vid_id_arr) > 0){
									if(isset($vid_id_arr[1])){
										$playlist_id = $vid_id_arr[1];
									}
									else{
										$playlist_id = $vid_id_arr[0];
									}
								}
								else{
									$playlist_id = '';
								}

								// Add option to hide controls, enable HD, and do autoplay -- depending on provider
								$params = array(
									'controls'    => 0,
					                'muted' => 1,
					                'mute' => 1,
					                'playsinline' => 1,
									'hd'  => 1,
									'background' => 1,
									'loop' => 1,
									'title' => 0,
									'byline' => 0,
									'autoplay' => 1,
					                'playlist' => $playlist_id // required to loop youtube
								);

								
								$new_src = add_query_arg($params, $src);
								
								$video = str_replace($src, $new_src, $video);
								
								// add extra attributes to iframe html
								$attributes = 'frameborder="0" autoplay muted loop playsinline webkit-playsinline allow="autoplay; fullscreen"';
								 
								$video = str_replace('></iframe>', ' ' . $attributes . '></iframe>', $video);
							}
						?>
						<div class="hero-video"><?php echo $video ?></div>

					<?php endif; ?>

					<div class="hero-content">
						<div class="container">
							<img src="<?php the_field( 'default_logo', 'option' ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="logo">
							<?php if($hero['title']): ?>
								<h1 class="hero-title"><?php echo text_replace_brackets($hero['title']); ?></h1>
							<?php endif; ?>
							<?php if($hero['content']): ?>
								<p class="content"><?php echo $hero['content']; ?></p>
							<?php endif; ?>
							<?php if($hero['form']): ?>
								<div class="form"><?php echo do_shortcode($hero['form']); ?></div>
							<?php endif; ?>
						</div>
					</div>
				</div>

			<?php endif; ?>


			<?php if($intro = get_field('intro')): ?>
				<section class="home-section home-intro">
					<?php if($intro['background_image']): ?>
						<div class="bg scroll-animate-item">
							<img src="<?php echo $intro['background_image']['url']; ?>" alt="" loading="lazy">
						</div>
					<?php endif; ?>
					<div class="container">
						<div class="content scroll-animate-item">
							<?php if($intro['title']): ?>
								<h2 class="section-title"><?php echo $intro['title']; ?></h2>
							<?php endif; ?>
							<?php if($intro['subtitle']): ?>
								<h3 class="section-subtitle"><?php echo $intro['subtitle']; ?></h3>
							<?php endif; ?>
							<?php if($intro['content']): ?>
								<div class="wysiwyg"><?php echo $intro['content']; ?></div>
							<?php endif; ?>
							<?php if($intro_button = $intro['button']): ?>
								<a href="<?php echo $intro_button['url']; ?>" target="<?php echo $intro_button['target']; ?>" data-target="<?php echo $intro['button_form_target'] ?: ''; ?>" class="button"><?php echo $intro_button['title']; ?></a>
							<?php endif; ?>
						</div>

						<div class="topics scroll-animate-item">
							<?php if($intro['topic_title']): ?>
								<h2 class="section-title"><?php echo $intro['topic_title']; ?></h2>
							<?php endif; ?>
							<?php if($intro['topics_content']): ?>
								<div class="wysiwyg"><?php echo $intro['topics_content']; ?></div>
							<?php endif; ?>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php if($host = get_field('host')): ?>
				<section class="home-section home-host">
					<?php if($host['background_image']): ?>
						<img src="<?php echo $host['background_image']['url']; ?>" alt="" class="bg scroll-animate-item" loading="lazy">
					<?php endif; ?>
					<div class="container scroll-animate-item">
						<?php if($host['title']): ?>
							<h2 class="section-title"><?php echo $host['title']; ?></h2>
						<?php endif; ?>
						<div class="row">
							<?php if($host['image']): ?>
								<figure class="image">
									<img src="<?php echo $host['image']['sizes']['medium']; ?>" alt="" loading="lazy">
								</figure>
							<?php endif; ?>
							<div class="content">
								<?php if($host['subtitle']): ?>
									<h3 class="section-subtitle"><?php echo $host['subtitle']; ?></h3>
								<?php endif; ?>
								<?php if($host['content']): ?>
									<div class="wysiwyg"><?php echo $host['content']; ?></div>
								<?php endif; ?>
								<?php if($host['signature']): ?>
									<img src="<?php echo $host['signature']['sizes']['small']; ?>" alt="" loading="lazy" class="signature">
								<?php endif; ?>
							</div>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php if($table = get_field('table')): ?>
				<section class="home-section home-table">
					<?php if($table['background_image']): ?>
						<div class="bg scroll-animate-item">
							<img src="<?php echo $table['background_image']['url']; ?>" alt="" loading="lazy">
						</div>
					<?php endif; ?>
					<div class="container">
						<div class="content scroll-animate-item">
							<?php if($table['title']): ?>
								<h2 class="section-title"><?php echo $table['title']; ?></h2>
							<?php endif; ?>
							<?php if($table['subtitle']): ?>
								<h3 class="section-subtitle"><?php echo $table['subtitle']; ?></h3>
							<?php endif; ?>
							<?php if($table['content']): ?>
								<div class="wysiwyg"><?php echo $table['content']; ?></div>
							<?php endif; ?>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php if($guest = get_field('guest')): ?>
				<section class="home-section home-guest">
					<div class="bg scroll-animate-item"></div>
					<div class="container scroll-animate-item">
						<?php if($guest['image']): ?>
							<figure class="image">
								<img src="<?php echo $guest['image']['sizes']['medium']; ?>" alt="" loading="lazy">
							</figure>
						<?php endif; ?>
						<div class="content">
							<?php if($guest['title']): ?>
								<h2 class="section-title"><?php echo $guest['title']; ?></h2>
							<?php endif; ?>
							<?php if($guest['content']): ?>
								<div class="wysiwyg"><?php echo $guest['content']; ?></div>
							<?php endif; ?>
							<?php if($guest_button = $guest['button']): ?>
								<a href="<?php echo $guest_button['url']; ?>" target="<?php echo $guest_button['target']; ?>" data-target="<?php echo $guest['button_form_target'] ?: ''; ?>" class="button dark hover-light"><?php echo $guest_button['title']; ?></a>
							<?php endif; ?>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php if($sponsorship = get_field('sponsorship')): ?>
				<section class="home-section home-sponsorship">
					<?php if($sponsorship['background_image']): ?>
						<img src="<?php echo $sponsorship['background_image']['url']; ?>" alt="" class="bg scroll-animate-item" loading="lazy">
					<?php endif; ?>
					<div class="container">
						<div class="content scroll-animate-item">
							<?php if($sponsorship['title']): ?>
								<h2 class="section-title"><?php echo $sponsorship['title']; ?></h2>
							<?php endif; ?>
							<?php if($sponsorship['subtitle']): ?>
								<h3 class="section-subtitle"><?php echo $sponsorship['subtitle']; ?></h3>
							<?php endif; ?>
							<?php if($sponsorship['content']): ?>
								<div class="wysiwyg"><?php echo $sponsorship['content']; ?></div>
							<?php endif; ?>
						</div>
						<?php if($download = $sponsorship['download']): ?>
							<div class="download scroll-animate-item">
								<?php if($download['image']): ?>
									<figure class="dl-image">
										<img src="<?php echo $download['image']['sizes']['small']; ?>" alt="" loading="lazy">
									</figure>
								<?php endif; ?>
								<div class="dl-content">
									<?php if($download['title']): ?>
										<h3 class="section-subtitle"><?php echo $download['title']; ?></h3>
									<?php endif; ?>
									<?php if($download['description']): ?>
										<div class="wysiwyg"><?php echo $download['description']; ?></div>
									<?php endif; ?>
									<?php if($download_button = $download['button']): ?>
										<a href="<?php echo $download_button['url']; ?>" target="<?php echo $download_button['target']; ?>" data-target="<?php echo $download['button_form_target'] ?: ''; ?>" class="button"><?php echo $download_button['title']; ?></a>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
					<?php if($sponsors = $sponsorship['sponsors']): ?>
						<div class="sponsors scroll-animate-item">
							<div class="container sponsors-content">
								<?php if($sponsors['title']): ?>
									<h3 class="title"><?php echo $sponsors['title']; ?></h3>
								<?php endif; ?>
								<?php if($sponsors['content']): ?>
									<div class="wysiwyg"><?php echo $sponsors['content']; ?></div>
								<?php endif; ?>
							</div>
							<div class="sponsors-marquee">
								<div class="sponsors-list">
									<?php foreach($sponsors['sponsors'] as $sponsor): ?>
										<div class="slide">
											<?php
												$sponsor_name = '';
												if($sponsor['link']){
													$sponsor_name = $sponsor['link']['title'];
													echo '<a href="' . $sponsor['link']['url'] . '" target="' . $sponsor['link']['target'] . '" class="button">';
												}
												if($sponsor['logo']){
													echo '<img src="' . $sponsor['logo']['sizes']['small'] . '" alt="' . $sponsor_name . '">';
												}
												else{
													echo $sponsor_name;
												}
												if($sponsor['link']){
													echo '</a>';
												}
											?>
										</div>
									<?php endforeach; ?>
								</div>
								<div class="sponsors-list">
									<?php foreach($sponsors['sponsors'] as $sponsor): ?>
										<div class="slide">
											<?php
												$sponsor_name = '';
												if($sponsor['link']){
													$sponsor_name = $sponsor['link']['title'];
													echo '<a href="' . $sponsor['link']['url'] . '" target="' . $sponsor['link']['target'] . '" class="button">';
												}
												if($sponsor['logo']){
													echo '<img src="' . $sponsor['logo']['sizes']['small'] . '" alt="' . $sponsor_name . '">';
												}
												else{
													echo $sponsor_name;
												}
												if($sponsor['link']){
													echo '</a>';
												}
											?>
										</div>
									<?php endforeach; ?>
								</div>
								<div class="sponsors-list">
									<?php foreach($sponsors['sponsors'] as $sponsor): ?>
										<div class="slide">
											<?php
												$sponsor_name = '';
												if($sponsor['link']){
													$sponsor_name = $sponsor['link']['title'];
													echo '<a href="' . $sponsor['link']['url'] . '" target="' . $sponsor['link']['target'] . '" class="button">';
												}
												if($sponsor['logo']){
													echo '<img src="' . $sponsor['logo']['sizes']['small'] . '" alt="' . $sponsor_name . '">';
												}
												else{
													echo $sponsor_name;
												}
												if($sponsor['link']){
													echo '</a>';
												}
											?>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if($locations = get_field('locations')): ?>
				<section class="home-section home-locations">
					<div class="content scroll-animate-item">
						<?php if($locations['title']): ?>
							<h2 class="section-title"><?php echo $locations['title']; ?></h2>
						<?php endif; ?>
						<?php if($locations['content']): ?>
							<div class="wysiwyg"><?php echo $locations['content']; ?></div>
						<?php endif; ?>
						<?php if($locations_button = $locations['button']): ?>
							<a href="<?php echo $locations_button['url']; ?>" target="<?php echo $locations_button['target']; ?>" data-target="<?php echo $locations['button_form_target'] ?: ''; ?>" class="button hover-dark"><?php echo $locations_button['title']; ?></a>
						<?php endif; ?>
					</div>
					<div class="map">
						<div id="gmap" data-maxZoom="18" data-minZoom="1"></div>
					</div>
				</section>
			<?php endif; ?>

			<?php if($blog = get_field('blog')): ?>
				<section class="home-section home-blog">
					<div class="container">
						<div class="content scroll-animate-item">
							<?php if($blog['title']): ?>
								<h2 class="section-title"><?php echo $blog['title']; ?></h2>
							<?php endif; ?>
							<?php if($blog['content']): ?>
								<div class="wysiwyg"><?php echo $blog['content']; ?></div>
							<?php endif; ?>
						</div>
					</div>
					<div class="blog-list scroll-animate-item">
						<?php
							$latest_posts = get_posts(array(
								'posts_per_page' => 3,
								'fields' => 'ids'
							));
						?>
						<?php
							if($latest_posts): 
								foreach($latest_posts as $post_id):
									if($post_info = MakespaceChild::get_post_info($post_id)):
						?>
						<article class="post">
							<a href="<?php echo $post_info['permalink']; ?>" class="wrap">
								<figure class="post-thumbnail">
									<div class="image">
										<img src="<?php echo $post_info['image']; ?>" alt="" loading="lazy">
									</div>
								</figure>
								<div class="content">
									<div>
										<h4 class="post-title section-subtitle"><?php echo $post_info['title']; ?></h4>
										<ul class="post-meta">
											<li class="readtime">
												<span class="label">Read time:</span>
												<span><?php echo $post_info['read_time']; ?> min</span>
											</li>
											<?php if($post_info['category']): ?>
												<li>
													<span class="label">Category:</span>
													<ul class="category">
														<?php foreach($post_info['category'] as $cat): ?>
															<li><?php echo $cat->name; ?></li>
														<?php endforeach; ?>
													</ul>
												</li>
											<?php endif; ?>
											<li class="author">
												<span class="label">Author:</span>
												<span><?php echo $post_info['author']; ?></span>
											</li>
										</ul>
										<div class="excerpt">
											<?php echo $post_info['excerpt']; ?>
										</div>
									</div>
									<div>
										<span class="button hover-dark">Read This</span>
									</div>
								</div>
							</a>
						</article>
						<?php
									endif;
								endforeach;
							endif;
						?>
					</div>
					<div class="container">
						<?php if($blog_button = $blog['button']): ?>
							<a href="<?php echo $blog_button['url']; ?>" target="<?php echo $blog_button['target']; ?>" class="button"><?php echo $blog_button['title']; ?></a>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if($contact = get_field('contact')): ?>
				<section id="home-section-contact" class="home-section home-contact">
					<?php if($contact['background_image']): ?>
						<img src="<?php echo $contact['background_image']['url']; ?>" alt="" class="bg scroll-animate-item" loading="lazy">
					<?php endif; ?>
					<div class="container">
						<div class="content scroll-animate-item">
							<?php if($contact['title']): ?>
								<h2 class="section-title"><?php echo $contact['title']; ?></h2>
							<?php endif; ?>
							<?php if($contact['content']): ?>
								<div class="wysiwyg"><?php echo $contact['content']; ?></div>
							<?php endif; ?>
							<?php if($contact['form']): ?>
								<div class="form"><?php echo do_shortcode($contact['form']); ?></div>
							<?php endif; ?>
							<?php if($contact['cta']): ?>
								<div class="wysiwyg cta"><?php echo $contact['cta']; ?></div>
							<?php endif; ?>
						</div>
					</div>
				</section>
			<?php endif; ?>

		</article>
	<?php endwhile; ?>
<?php get_footer();