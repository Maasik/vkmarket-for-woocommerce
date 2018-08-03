<?php

function vkm_delete_product( $post_id ) {

	$vk_item_id = get_post_meta( $post_id, 'vk_item_id', true );

	if ( ! empty( $vk_item_id ) ) {

		$vk_item_id = explode( '_', $vk_item_id );

		if ( is_array( $vk_item_id ) && count( $vk_item_id ) == 2 ) {

			$params  = array(
				'owner_id' => $vk_item_id[0],
				'item_id'  => $vk_item_id[1]
			);
			$deleted = vkm_vkapi_market_delete( $params );

			//if ( ! empty( $deleted ) && $deleted ) {
			// WARNING! we do not know whether the product deleted

			$ids = array( $post_id );

			if ( has_post_thumbnail( $post_id ) ) {
				$ids[] = get_post_thumbnail_id( $post_id );
			}

			$p       = new WC_Product_Factory();
			$product = $p->get_product( $post_id );

			if ( ! empty( $product ) ) {
				$gallery_attachment_ids = $product->get_gallery_attachment_ids();

				if ( ! empty( $gallery_attachment_ids ) && is_array( $gallery_attachment_ids ) ) {
					$ids = array_merge( $ids, $gallery_attachment_ids );
				}
			}

			foreach ( $ids as $id ) {
				delete_post_meta( $id, 'vk_item_id' );
			}

			do_action( 'vkm_product_deleted', $post_id );
			//}
		}
	}

	/*
	if (  $vk_item_id  == '' ) {
		delete_post_meta( $post_id, 'vk_item_id' );
	}
	*/
}

function vkm_get_vk_categories( $params = array(), $undefined = false ) {
	global $vk_market_categories;
	$out = array();

	if ( ! empty( $params['lang'] ) ) {
		$lang = $params['lang'];
	} else {
		$site_lang      = get_bloginfo( 'language' );
		$lang           = substr( $site_lang, 0, 2 );
		$params['lang'] = $lang;
	}

	$lang = ! empty( $params['lang'] ) ? $params['lang'] : 'ru';

	if ( false === ( $out = get_transient( 'vk_market_categories_' . $lang ) ) ) {

		$cats = vkm_vkapi_market_get_categories( $params );

		if ( ! empty( $cats ) && ! empty( $cats['items'] ) ) {

			foreach ( $cats['items'] as $cat ) {
				$out[ $cat['section']['name'] ][ $cat['id'] ] = $cat['name'];
			}

			set_transient( 'vk_market_categories_' . $lang, $out, WEEK_IN_SECONDS );
		}
	}

	if ( $undefined && is_array( $out ) ) {
		array_unshift( $out, __( 'Undefined', 'vkmarket-for-woocommerce' ) );
	}

	if ( ! empty( $out ) ) {
		foreach ( $out as $val ) {
			if ( is_array( $val ) ) {
				foreach ( $val as $k => $v ) {
					$vk_market_categories[ $k ] = $v;
				}
			}
		}
	}

	return $out;
}

function vkm_vk_categories_select_helper( $value = null ) {
	//global $vk_market_categories;

	$cats = vkm_get_vk_categories( null, true );
	$html = '';

	foreach ( $cats as $key => $label ) {

		if ( is_array( $label ) ) {
			$_html = array();

			foreach ( $label as $k => $l ) {
				$_html[] = sprintf( '<option value="%s"%s>%s</option>', $k, selected( $value, $k, false ), $l );
			}
			if ( ! empty( $_html ) ) {
				$html .= '<optgroup label = "' . $key . '">' . implode( $_html ) . '</optgroup>';
			}

		} else {
			$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
		}
	}

	return $html;
}

function vkm_upload_photo( $att_id, $main_photo = 0, $reupload = false ) {
	$options = get_option( 'vkm_options' );

	$vk_item_id = get_post_meta( $att_id, 'vk_item_id', true );
	//if( !$main_photo ) {
	//if(!empty($vk_item_id) && 1!=1 ) {
	if ( ! empty( $vk_item_id ) && ! $reupload ) {
		$vk_item_id_arr = explode( '_', $vk_item_id );
		if ( ! empty( $vk_item_id_arr[1] ) ) {
			return $vk_item_id_arr[1];
		}

	} /*
	else {
		$vk_item_id_arr = explode( '_', $vk_item_id );
		if ( ! empty( $vk_item_id_arr[1] ) ) {
			return $vk_item_id_arr[1];
		}
	} */

	$params1 = array(
		'group_id'   => abs( $options['page_id'] ), // !!!;  > 0
		'main_photo' => $main_photo, // !!!; 0, 1
	);


	$att_meta = apply_filters( 'vkm_upload_photo_attachment_meta', wp_get_attachment_metadata( $att_id ), $att_id, 'product' );

	if ( empty( $att_meta ) ) {
		vkm_add_log( 'vkm_upload_photo: Cant get attachment metadata <a href="' . get_edit_post_link( $att_id ) . '">#' . $att_id . '</a>' );

		return false;
	}

	if ( ( ! empty( $att_meta['width'] ) && $att_meta['width'] < 400 ) || ( ! empty( $att_meta['height'] ) && $att_meta['height'] < 400 ) ) {
		vkm_add_log( 'vkm_upload_photo: Photo width and height must be at least 400px <a href="' . get_edit_post_link( $att_id ) . '">#' . $att_id . '</a>' );

		return false;
	}

	if ( $main_photo ) {
		$params1['crop_x']     = 0;
		$params1['crop_y']     = 0;
		$params1['crop_width'] = min( $att_meta['width'], $att_meta['height'] );
	}
	//vkm_add_log( 'vkm_upload_photo: ' . print_r($params1, 1) );
	$res1 = vkm_vkapi_photos_get_market_upload_server( $params1 );

	if ( empty( $res1['upload_url'] ) ) {
		return false;
	}

	$params2 = array(
		'upload_url' => $res1['upload_url'],
		'method'     => 'vkm_upload_photo',
		'method_str' => 'vkm_upload_photo'
	);

	$att_path = empty( $att_meta['path'] ) ? get_attached_file( $att_id ) : $att_meta['path'];

	if ( version_compare( PHP_VERSION, '5.5', '>=' ) ) {
		$params2['args']['photo'] = new CURLFile( $att_path );
	} else {
		$params2['args']['photo'] = '@' . $att_path;
	}

	$res2 = vkm_vkapi_upload( $params2 );

	if ( empty( $res2['photo'] ) || $res2['photo'] == '[]' ) {
		return false;
	}

	$params3 = array(
		'group_id' => abs( $options['page_id'] ), //  > 0
		'photo'    => $res2['photo'],
		'server'   => $res2['server'],
		'hash'     => $res2['hash']
	);

	if ( ! empty( $res2['crop_data'] ) ) {
		$params3['crop_data'] = $res2['crop_data'];
		$params3['crop_hash'] = $res2['crop_hash'];
	}


	$res3 = vkm_vkapi_photos_save_market_photo( $params3 );

	if ( empty( $res3 ) ) {
		return false;
	}

	if ( ! empty( $res3[0]['id'] ) ) {
		$vk_item_id = $options['page_id'] . '_' . $res3[0]['id'];
		if ( ! update_post_meta( $att_id, 'vk_item_id', $vk_item_id ) ) {
			add_post_meta( $att_id, 'vk_item_id', $vk_item_id, true );
		}
	}

	return $res3[0]['id'];

}

function vkm_upload_photos_id( $post_id, $reupload = false ) {
	$options = get_option( 'vkm_options' );

	$images_ids = array();
	if ( has_post_thumbnail( $post_id ) ) {
		$images_ids[] = get_post_thumbnail_id( $post_id );
	}

	//if ( !empty($options['upload_photo_count']) && $options['upload_photo_count'] > 4 ) {
	//	$options['upload_photo_count'] = 4;
	//}

	$p       = new WC_Product_Factory();
	$product = $p->get_product( $post_id );

	if ( ! empty( $product ) ) {
		$gallery_attachment_ids = $product->get_gallery_attachment_ids();

		if ( ! empty( $gallery_attachment_ids ) && is_array( $gallery_attachment_ids ) ) {

			$images_ids = array_merge( $images_ids, $gallery_attachment_ids );
		}
	}

	/*
	$post_images = get_children( array(
		'post_parent'    => $post_id,
		'post_status'    => 'inherit',
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'orderby'        => 'menu_order id',
		'order'          => 'ASC',
		'numberposts'    => $options['upload_photo_count']
	) );

	if ( ! empty( $post_images ) ) {
		foreach ( $post_images as $image ) {
			$image_id = is_object( $image ) ? $image->ID : $image;
			if ( ! in_array( $image_id, $images_ids ) ) {
				$images_ids[] = $image_id;
			}
		}
	}
	*/

	$images_ids = array_unique( $images_ids );
	$images_ids = apply_filters( 'vkm_upload_photos_id', $images_ids, $post_id );

	if ( empty( $images_ids ) ) {
		return false;
	}

	$i             = 0;
	$photo_ids     = array();
	$main_photo_id = '';
	foreach ( (array) $images_ids as $att_id ) {
		$main_photo = ! $i ? 1 : 0;
		$photo_id   = vkm_upload_photo( $att_id, $main_photo, $reupload );
		if ( ! empty( $photo_id ) ) {
			$photo_ids[] = $photo_id;

		}
		$i ++;

		if ( $i > 4 ) {
			break;
		}
	}

	return $photo_ids;
}

function vkm_add_product( $post ) {
	$options = get_option( 'vkm_options' );

	$p       = new WC_Product_Factory();
	$product = $p->get_product( $post->ID );

	$category_id = vkm_get_post_vk_category( $post->ID );
	if ( empty( $category_id ) ) {
		vkm_add_log( 'vkm_add_product: Empty vkm_get_post_vk_category <a href="' . get_edit_post_link( $post->ID ) . '">#' . $post->ID . '</a>.' );

		return false;
	}

	$params = array(
		'owner_id'    => $options['page_id'], // !!!
		'category_id' => $category_id // !!!
	);

	if ( $post->post_title ) {
		// !!!; 4 - 100

		$post_title = vkm_text_clean( $post->post_title );
		$post_title = vkm_strlen( $post_title, 100, 'cp1251' );

		if ( mb_strlen( $post_title ) < 4 ) {
			vkm_add_log( 'vkm_add_product: Title should be greater then 4 letters <a href="' . get_edit_post_link( $post->ID ) . '">#' . $post->ID . '</a>.' );
			add_post_meta( $post->ID, 'vkm_error', 'Title should be greater then 4 letters.' );

			return false;
		} else {
			$params['name'] = $post_title;
		}
	} else {
		vkm_add_log( 'vkm_add_product: Empty title <a href="' . get_edit_post_link( $post->ID ) . '">#' . $post->ID . '</a>.' );
		add_post_meta( $post->ID, 'vkm_error', 'Empty title.' );

		return false;
	}

	if ( ! empty( $_POST['_sale_price'] ) ) {
		$params['price'] = $_POST['_sale_price'];
	} else if ( ! empty( $_POST['_regular_price'] ) ) {
		$params['price'] = $_POST['_regular_price']; // !!!;
	} else if ( ! empty( $product ) && $product->get_price() ) {
		$params['price'] = $product->get_price();
	} else if ( ! empty( $product ) && $product->get_sale_price() ) {
		$params['price'] = $product->get_sale_price();
	} else if ( ! empty( $product ) && $product->get_regular_price() ) {
		$params['price'] = $product->get_regular_price();
	} else {
		vkm_add_log( 'vkm_add_product: Empty price <a href="' . get_edit_post_link( $post->ID ) . '">#' . $post->ID . '</a>.' );
		add_post_meta( $post->ID, 'vkm_error', 'Empty price.' );

		return false;
	}

	// If price added to WooCommerce incorrected.
	if ( strpos( ',', $params['price'] ) !== 0 ) {
		$params['price'] = str_replace( ',', '.', $params['price'] );
		settype( $params['price'], 'float' );
	}


	$params['deleted'] = 1;

	if ( ! empty( $_POST['product-type'] ) && $_POST['product-type'] == 'variable' && ! empty( $_POST['variable_stock_status'] ) ) {

		$variable_stock_status = $_POST['variable_stock_status'];
		foreach ( $_POST['variable_stock_status'] as $variable_stock_status ) {

			if ( $variable_stock_status == 'instock' ) {
				$params['deleted'] = 0;
			}
		}
	} else if ( ! empty( $_POST['_stock_status'] ) && $_POST['_stock_status'] == 'instock' ) {
		$params['deleted'] = 0;
	} else if ( ! empty( $_POST['_stock_status'] ) && $_POST['_stock_status'] == 'outofstock' ) {
		$params['deleted'] = 1;
	} else if ( ! empty( $product ) && $product->is_type( 'variable' ) ) {

		$available_variations = $product->get_available_variations();
		if ( ! empty( $available_variations ) ) {

			foreach ( $available_variations as $variation ) {
				if ( $variation['is_in_stock'] ) {
					$params['deleted'] = 0;
				}
			}
		}
	} else if ( ! empty( $product ) && ! $product->is_type( 'variable' ) && $product->is_in_stock() ) {
		$params['deleted'] = 0;
	} else {
		$params['deleted'] = 1;
	}


	$reupload   = false;
	$vk_item_id = get_post_meta( $post->ID, 'vk_item_id', true );
	if ( ! empty( $vk_item_id ) ) {

		$vk_item_id_arr = explode( '_', $vk_item_id );

		if ( ! empty( $vk_item_id_arr[1] ) ) {
			$item_id           = $vk_item_id_arr[1];
			$params['item_id'] = $item_id;

			//unset( $params['photo_ids'] );
			//$params['photo_ids'] = '';
		}
	} else {
		$reupload = true;
	}

	$photos = vkm_upload_photos_id( $post->ID, $reupload );
	if ( ! empty( $photos ) ) {
		$params['main_photo_id'] = array_shift( $photos );

		if ( ! empty( $photos ) && is_array( $photos ) ) {
			$params['photo_ids'] = implode( ',', $photos );
		}
	} else {
		vkm_add_log( 'vkm_add_product: Need at least 1 photo for product <a href="' . get_edit_post_link( $post->ID ) . '">#' . $post->ID . '</a> or photo error.' );
		add_post_meta( $post->ID, 'vkm_error', 'Need at least 1 photo for product.' );

		return false;
	}


	$params = apply_filters( 'vkm_add_product', $params, $post );
	if ( empty( $params ) ) {
		vkm_add_log( 'vkm_add_product_filters: Empty params <a href="' . get_edit_post_link( $post->ID ) . '">#' . $post->ID . '</a>.' );//
		add_post_meta( $post->ID, 'vkm_error', ' Empty params.' );

		return false;
	}

	if ( empty( $params['description'] ) || mb_strlen( $params['description'] ) < 10 ) {
		vkm_add_log( 'vkm_add_product: Need description for post <a href="' . get_edit_post_link( $post->ID ) . '">#' . $post->ID . '</a>.' );//
		add_post_meta( $post->ID, 'vkm_error', ' Need description for post.' );

		return false;
	}

	if ( ! empty( $_POST['captcha_sid'] ) && ! empty( $_POST['captcha_key'] ) ) {
		$params['captcha_sid'] = $_POST['captcha_sid'];
		$params['captcha_key'] = $_POST['captcha_key'];
	}


	// $params['item_id']    = ''; //
	if ( ! empty( $params['item_id'] ) ) {
		$res = vkm_vkapi_market_edit( $params, $post->ID );

		if ( ! empty( $res ) && $res === 1 ) {
			//vkm_add_log( 'vkm_add_product:' . print_r( $params, 1 ) );//
			vkm_add_log( 'vkm_add_product: Product <a href="' . get_edit_post_link( $post->ID ) . '">#' . $post->ID . '</a> updated in VK.' );//

			$vkm_updated = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
			if ( ! update_post_meta( $post->ID, 'vkm_updated', $vkm_updated ) ) {
				add_post_meta( $post->ID, 'vkm_updated', $vkm_updated, true );
			}

		}
		do_action( 'vkm_after_edit_product', $vk_item_id, $post );
	} else {

		$res = vkm_vkapi_market_add( $params, $post->ID );

		if ( ! empty( $res['market_item_id'] ) ) {
			$vk_item_id = $params['owner_id'] . '_' . $res['market_item_id'];

			if ( ! update_post_meta( $post->ID, 'vk_item_id', $vk_item_id ) ) {
				add_post_meta( $post->ID, 'vk_item_id', $vk_item_id, true );
			}

			vkm_add_log( 'vkm_add_product: Product <a href="' . get_edit_post_link( $post->ID ) . '">#' . $post->ID . '</a> added to VK.' );//
		}

		do_action( 'vkm_after_add_product', $vk_item_id, $post );
	}

	delete_post_meta( $post->ID, 'vkm_error' );

	return $res;
}


function vkm_add_product_description( $params, $post ) {
	$options = get_option( 'vkm_options' );
	$m       = array();

	if ( empty( $options['message'] ) ) {
		$options['message'] = '%content%';
	}

	preg_match_all( '/%([\w-]*)%/m', $options['message'], $mt, PREG_PATTERN_ORDER );

	if ( in_array( 'link', $mt[1] ) ) {
		$m['%link%'] = get_permalink( $post->ID );
	}

	if ( in_array( 'content', $mt[1] ) ) {
		if ( ! empty( $post->post_content ) ) {
			$m['%content%'] = $post->post_content;
		} else {
			$m['%content%'] = '';
		}
	}

	if ( in_array( 'excerpt', $mt[1] ) ) {
		if ( ! empty( $post->post_excerpt ) ) {
			$m['%excerpt%'] = $post->post_excerpt;
		} else if ( preg_match( '/<!--more(.*?)?-->/', $post->post_content, $matches ) ) {
			$_excerpt       = explode( $matches[0], $post->post_content, 2 );
			$m['%excerpt%'] = $_excerpt[0];
		} else {
			$m['%excerpt%'] = '';
		}
	}

	$m = apply_filters( 'vkm_add_product_description', $m, $mt[1], $post );

	$params['description'] = str_replace( array_keys( $m ), array_values( $m ), $options['message'] );
	$params['description'] = vkm_text_clean( $params['description'] );

	return apply_filters( 'vkm_add_product_description_result', $params, $post );
}

add_filter( 'vkm_add_product', 'vkm_add_product_description', 10, 2 );


function vkm_get_post_vk_category( $post_id, $meta_key = 'vkm_category', $single = 1 ) {
	$options = get_option( 'vkm_options' );
	if ( $meta_key == 'vkm_category' ) {
		$out = $options['vkm_category'];
	} else {
		$out = '';
	}

	$terms = wp_get_post_terms( $post_id, 'product_cat' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return $out;
	}

	$vkm_category = array();
	foreach ( $terms as $term ) {
		$_vkm_category = vkm_get_post_vk_category_handler( $term, $meta_key );
		if ( ! empty( $_vkm_category ) ) {
			if ( $single ) {
				return $_vkm_category;
			} else {
				if ( ! in_array( $_vkm_category, $vkm_category ) ) {
					$vkm_category[] = $_vkm_category;
				}
			}
		}
	}

	if ( ! $single && ! empty( $vkm_category ) ) {
		return $vkm_category;
	}

	return $out;
}

function vkm_get_post_vk_category_handler( $term, $meta_key ) {
	if ( function_exists( 'get_term_meta' ) ) {
		$vkm_category = get_term_meta( $term->term_id, $meta_key, true );
	} else {
		$vkm_category = get_woocommerce_term_meta( $term->term_id, $meta_key, true );
	}
	if ( empty( $vkm_category ) ) {
		if ( ! empty( $term->parent ) ) {
			$_term = get_term( $term->parent );
			if ( ! empty( $_term ) && ! is_wp_error( $_term ) ) {
				return vkm_get_post_vk_category_handler( $_term, $meta_key );
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return $vkm_category;
	}
}

function vkm_transition_post_status( $new, $old, $post ) {

	if ( apply_filters( 'vkm_transition_post_status_add', false, $new, $old, $post ) ) {
		vkm_add_product( $post );
	}

	if ( apply_filters( 'vkm_transition_post_status_delete', false, $new, $old, $post ) ) {
		vkm_delete_product( $post->ID );
	}
}

add_action( 'transition_post_status', 'vkm_transition_post_status', 10, 3 );


function vkm_transition_post_status_filter_add( $filter, $new, $old, $post ) {
	$options = get_option( 'vkm_options' );

	if ( $post->post_type == 'product' &&
	     $new == 'publish' &&
	     $options['sync'] &&
	     ( stripos( wp_get_referer(), 'post.php' ) !== false || stripos( wp_get_referer(), 'post-new.php' ) !== false ) &&
	     empty( $_POST['vkm_delete_product'] )
	) {
		return true;
	}

	return $filter;
}

add_filter( 'vkm_transition_post_status_add', 'vkm_transition_post_status_filter_add', 10, 4 );


function vkm_transition_post_status_filter_delete( $filter, $new, $old, $post ) {
	$options = get_option( 'vkm_options' );

	if ( (
		     ( $post->post_type == 'product' && $new == 'trash' ) ||
		     ! empty( $_POST['vkm_delete_product'] )
	     ) &&
	     $options['sync'] &&
	     stripos( wp_get_referer(), 'post.php' ) !== false
	) {
		return true;
	}

	return $filter;
}

add_filter( 'vkm_transition_post_status_delete', 'vkm_transition_post_status_filter_delete', 10, 4 );
