(function($){

	var mswEvents = function() {
		$('.single-event-image-slider').slick({
			prevArrow: '<button class="slick-prev"><svg width="12" height="40" viewBox="0 0 12 40" xmlns="http://www.w3.org/2000/svg"><path d="M11.872 0 4.658 19.997 11.962 40H7.444L0 20 7.444 0z" fill="#CC1D62" fill-rule="evenodd"/></svg></button>',
			nextArrow: '<button class="slick-next"><svg width="12" height="40" viewBox="0 0 12 40" xmlns="http://www.w3.org/2000/svg"><path d="m.09 0 7.214 19.997L0 40h4.518l7.444-20L4.518 0z" fill="#CC1D62" fill-rule="evenodd"/></svg></button>',
			dots: false,
			adaptiveHeight: true
		});

		$('.single-event-image-slider').magnificPopup({
			type: 'image',
			delegate: 'a',
			gallery: {
				enabled: true,
			}
		});

		// $('.single-event-image-gallery')

		$('[data-action="event-gallery-thumb"]').on('click', function(e){
			var targetSlide = $(this).attr('data-target');
			$('.single-event-image-slider').slick('slickGoTo', targetSlide);
			$(this).addClass('active');
			$('[data-action="event-gallery-thumb"]').not(this).removeClass('active');
		});
	};

	$(document).ready(function(){
		mswEvents();
	});
})(jQuery);