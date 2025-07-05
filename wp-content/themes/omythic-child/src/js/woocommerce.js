(function($){

	var ajaxAddToCart = function(){
		var atcButton = $('.ms-ajax-add-to-cart');

		var productInfo = {
			id: null,
			type: null,
			slug: null, 
			sku: null,
			quantity: 1,
			variations: null,
			groupedProducts: null,
			productBundles: null,
		};

		var addToCart = function(productInfo) {
			var query = $.ajax({
				url: MSWObject.ajax_url,
				method: 'POST',
				data: {
					action: 'ms_ajax_atc',
					woo_ajax_object: productInfo,
				},
				fail: function(textStatus) { console.log(textStatus); },
				success: function() { 
					console.log('Successully added to cart.');
					// Do stuff
				},
			});
		};

		atcButton.on('click', function(e) {
			e.preventDefault();

			productInfo.variations = null;
			productInfo.groupedProducts = null;
			productInfo.productBundles = null;

			var target = $(e.currentTarget);
			var productOptions = [];

			productInfo.type = target.attr('data-product_type');
			productInfo.id = target.attr('data-product_id');
			productInfo.slug = target.attr('data-product_slug');
			productInfo.sku = target.attr('data-product_sku');
			productOptionSelector = $('.' + productInfo.id + '-' + productInfo.type + '-' + productInfo.slug);
			productInputSelector = $('.' + productInfo.id + '-' + productInfo.type + '-' + productInfo.slug + '-quantity');
			productInfo.quantity = productInfo.quantity = productInputSelector.val();
			if(productInfo.quantity == '') { 
				productInfo.quantity = 1;
			}

			switch(productInfo.type) {
				case 'simple':
					break;
				case 'variable':
					$.each(productOptionSelector, function() {
						var name = $(this).attr('data-variable_name');
						var selected = this.options[this.selectedIndex].text;
						var variation = {
							name: name,
							selected: selected,
						};
						productOptions.push(variation);
					});
					productInfo.variations = productOptions;
					break;
				case 'grouped':
					$.each(productOptionSelector, function() {
						var id = $(this).attr('data-product_id');
						var quantity = $(this).find('input').val();
						var childProduct = {
							id: id,
							quantity: quantity
						};
						productOptions.push(childProduct);
					});
					productInfo.groupedProducts = productOptions;
					break;
				default:
					break;
			}
			addToCart(productInfo);
		});
	};

	$(document).ready(function(){
		ajaxAddToCart();
	});

})(jQuery);