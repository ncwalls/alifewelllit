<?php 

if( !function_exists( 'prepare_ajax_atc' ) ) {
	function prepare_ajax_atc() {
		global $product;
		$id = $product->get_ID();
		$shop_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
		$type = $product->get_type();
		$slug = $product->get_slug();
		$name = $product->get_name();
		$sku = $product->get_sku();
		$attributes = $product->get_attributes();
		$input_class = $id . '-' . $type . '-' . $slug . '-quantity';
		$iterator_class = $id . '-' . $type . '-' . $slug;
		$atc = '<div class="add-to-cart-container">';
		switch($type) {
			case 'variable':
				foreach ($attributes as $name => $object) {
					$atc .= '<label for="' . $iterator_class . '">' . $name . '</label>';
					$atc .= '<select id="' . $iterator_class . '" class="variable-options ' . $iterator_class . '" data-variable_name="attribute_' . $name . '">';
					foreach ($object['options'] as $option) {
						$atc .= '<option value="' . $option . '">' . $option . '</option>';
					}
					$atc .= '</select>';
				}
				$atc .= '<input type="number" class="quantity ' . $input_class . '" data-product_id="' . $id . '" value="1">';
				break;
			case 'grouped':
				$children = $product->get_children();
				foreach ($children as $child) {
					$c_product = wc_get_product($child);
					$c_id = $c_product->get_ID();
					$c_name = $c_product->get_name();
					$atc .= '<div class="product-child ' . $iterator_class . '" data-product_id="' . $c_id . '" data-quantity="1">';
					$atc .= '<span>' . $c_name . '</span>';
					$atc .= '<input type="number" class="quantity" value="1">';
					$atc .= '</div>';
				}
				break;
			default:
				$atc .= '<input type="number" class="quantity ' . $input_class . '" data-product_id="' . $id . '" value="1">';
		}

		$atc .= '<a href="' . $shop_url . 'add-to-cart=' . $id . '" data-quantity="1" data-product_id="' . $id . '" data-product_type="' . $type . '" data-product_slug="' . $slug . '" data-product_sku="' . $sku . '" class="button ms-ajax-add-to-cart" aria-label="' . $name . '" rel="nofollow">Add to cart</a>'; 
		$atc .= '</div>';

		return $atc;
	}
}

if( !function_exists( 'do_ajax_atc' ) ) {
	function do_ajax_atc( $woo_ajax_object ) {
		global $woocommerce;
		$id = $woo_ajax_object['id'];
		$type = $woo_ajax_object['type'];
		$quantity = $woo_ajax_object['quantity'];
		$product_variations = $woo_ajax_object['variations'];
		$grouped_products = $woo_ajax_object['groupedProducts'];
		$product_bundles = $woo_ajax_object['productBundles'];
		$product = wc_get_product( $id );

		switch($type) {
			case 'simple':
				if( $product->is_purchasable() && $product->is_in_stock() ) {
					$woocommerce->cart->add_to_cart($id, $quantity);
				}
				break; 
			case 'variable':
				$variations = [];
				$attributes = [];
				$available_variations = $product->get_available_variations();
				foreach ($product_variations as $variation) {
					$values = array_values($variation);
					$variations[$values[0]] = $values[1];
				}
				foreach ( $available_variations as $key => $val ) {
					$attributes[$val['variation_id']] = $val['attributes'];
				}
				foreach ($attributes as $variation_id => $attribute) {
					if( $attribute === $variations && $product->is_purchasable() && $product->is_in_stock() ) {
						$woocommerce->cart->add_to_cart($id, $quantity, $val['variation_id'], $attribute);
					}
				}
				break;
			case 'grouped':
				foreach ($grouped_products as $grouped_product) {
					$sub_product = wc_get_product( $grouped_product['id'] );
					if( $sub_product->is_purchasable() && $sub_product->is_in_stock() ) {
						$woocommerce->cart->add_to_cart($grouped_product['id'], $grouped_product['quantity']);
					}
				}
				break;
			default:
				if( $product->is_purchasable() && $product->is_in_stock() ) {
					$woocommerce->cart->add_to_cart($id, $quantity);
				}
		}
	}
}
