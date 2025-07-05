(function($){


	var mswEventsAdmin = function() {
		// console.log('admin');

		$('input[name="acf[field_62a1082a24f1e]"]').on('change', function(e) {

			var inputVal = $(this).val();
			// console.log(inputVal);
			var post_ID = $('[name="post_ID"]').val()*1;
			var ajaxurl = MSWeventsObjectAdmin.ajax_url;
			
			$.ajax({
				url: ajaxurl + "?action=trash_type_function",
				method: 'POST',
				data: {'postid': post_ID, 'inputval': inputVal},
				success: function(data) {
					// console.log(data);
					// console.log("SUCCESS!");
				},
				error: function(data) {
					console.log("FAILURE");
				}
			});
		});

	};

	$(document).ready(function(){
		mswEventsAdmin();
	});
})(jQuery);