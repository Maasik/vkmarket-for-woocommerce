<?php

function vkm_vkapi_resolve_screen_name( $params ) {
	$options = get_option( 'vkm_vk_api_site' );

	if ( ! empty( $options['site_access_token'] ) ) {
		$access_token = $options['site_access_token'];
	} else {
		vkm_add_log( 'vkm_vkapi_resolve_screen_name: No Access Token passed.' );

		return false;
	}

	//http://vk.com/dev/utils.resolveScreenName
	$default = array(
		'access_token' => $access_token,
		'v'            => '5.42'
	);
	$params  = wp_parse_args( $params, $default );

	$res = vkm_vkapi( array(
		'args'       => $params,
		'method'     => 'utils.resolveScreenName',
		'method_str' => 'resolve_screen_name'
	) );

	return $res;
}


function vkm_get_group_id_js() {

	if ( ! empty( $_POST ) ) {
		extract( $_POST );
	}

	if ( isset( $group_url ) && ! empty( $group_url ) ) {
		$vk_object = vkm_get_vk_object( $group_url );

		if ( empty( $vk_object['id'] ) ) {
			$out['error'] = 'Error';
		} else {
			$out['gid']         = $vk_object['id'];
			$out['group']       = $out['gid'];
			$out['screen_name'] = ! empty( $vk_object['screen_name'] ) ? $vk_object['screen_name'] : '';
		}
	} else {
		$out['error'] = 'Error';
	}

	print json_encode( $out );
	exit;
}

add_action( 'wp_ajax_vkm_get_group_id', 'vkm_get_group_id_js' );


function vkm_get_vk_object( $url ) {
	$out = array();

	$vk_objects = get_option( 'vkm_vk_objects' );
	if ( ! empty( $vk_objects ) && ! empty( $vk_objects[ $url ] ) ) {
		$out = $vk_objects[ $url ];
	} else {
		$urla = explode( '/', $url );
		if ( is_array( $urla ) && ! empty( $urla ) ) {
			$screen_name = array_pop( $urla );

			preg_match( '/^(id|public|club|event)([0-9]+)/', $screen_name, $matches );
			if ( ! empty( $matches[1] ) && ! empty( $matches[2] ) ) {
				$out['id'] = ( $matches[1] != 'id' ) ? ( - 1 * $matches[2] ) : $matches[2];
			} else {
				$out['screen_name'] = $screen_name;

				$res = vkm_vkapi_resolve_screen_name( array(
					'screen_name' => $screen_name
				) );

				if ( ! empty( $res ) && ! empty( $res['object_id'] ) ) {
					$out['type'] = $res['type'];
					$out['id']   = ( $res['type'] != 'user' ) ? - 1 * $res['object_id'] : $res['object_id'];
				}
			}
		}
		if ( ! empty( $out['id'] ) ) {
			$vk_objects[ $url ] = $out;
			update_option( 'vkm_vk_objects', $vk_objects );
		}
	}

	do_action( 'vkm_get_vk_object', $url, $out );

	return $out;
}


function vkm_get_lock( $transient ) {
	global $wpdb;

	$value = 0;
	if ( wp_using_ext_object_cache() ) {
		/*
		 * Skip local cache and force re-fetch of doing_cron transient
		 * in case another process updated the cache.
		 */
		$value = wp_cache_get( $transient, 'transient', true );
	} else {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", '_transient_' . $transient ) );
		if ( is_object( $row ) ) {
			$value = $row->option_value;
		}
	}

	return $value;
}


function vkm_vkapi_requests_limit() {
	$vkapi1 = microtime( true );
	//$vkapi = get_transient('vkapi');
	$vkapi = vkm_get_lock( 'vkapi' );

	if ( ! empty( $vkapi ) ) {
		$vkapi3 = ( $vkapi1 - $vkapi ) * 1000000;

		if ( $vkapi3 < 333333 ) {
			usleep( 333333 - $vkapi3 );
			vkm_vkapi_requests_limit();
		}
	}
}


function vkm_vkapi( $params ) {
	$options = get_option( 'vkm_options' );
	$timeout = empty( $options['timeout'] ) ? 5 : $options['timeout'];

	$params['args'] = apply_filters( 'vkm_vkapi_' . $params['method_str'], $params['args'] );

	vkm_vkapi_requests_limit();

	$args = array(
		'body'      => $params['args'],
		'sslverify' => false,
		'timeout'   => $timeout
	);
	//print__r( $args );//
	$data = wp_remote_post( VKM_API_URL . $params['method'], $args );
	//vkm_add_log( 'vkm_vkapi results: ' . '<pre>' . print_r( $data, 1 ) . '</pre>' );//

	set_transient( 'vkapi', microtime( true ), HOUR_IN_SECONDS );

	if ( is_wp_error( $data ) ) {
		vkm_add_log( $params['method_str'] . ': WP ERROR. ' . $data->get_error_code() . ' ' . $data->get_error_message() );

		return false;
	}

	if ( isset( $data['response'] ) && isset( $data['response']['code'] ) && $data['response']['code'] != 200 ) {
		vkm_add_log( $params['method_str'] . ': RESPONSE ERROR. ' . $data['response']['code'] . ' ' . $data['response']['message'] );

		return false;
	}

	$data['body'] = vkm_remove_emoji( $data['body'] );
	$resp         = json_decode( $data['body'], true );
	$vk_captcha   = get_transient( 'vk_captcha' );


	if ( isset( $resp['error'] ) ) {
		if ( isset( $resp['error']['error_code'] ) ) {
			vkm_add_log( $params['method_str'] . ': VK Error. ' . $resp['error']['error_code'] . ' ' . $resp['error']['error_msg'] );
		} else {
			vkm_add_log( $params['method_str'] . ': VK Error. ' . $resp['error'] );
		}

		if ( $resp['error']['error_code'] == 14 ) {


			if ( empty( $vk_captcha ) ) {
				$vk_captcha = array();
			}

			$vk_captcha[ $params['method_str'] ] = array(
				'captcha_sid' => $resp['error']['captcha_sid'],
				'captcha_img' => $resp['error']['captcha_img']
			);

			if ( ! empty( $params['item_id'] ) && $params['item_type'] ) {
				$vk_captcha[ $params['method_str'] ]['item_id']   = $params['item_id'];
				$vk_captcha[ $params['method_str'] ]['item_type'] = $params['item_type'];
			}

			set_transient( 'vk_captcha', $vk_captcha, MONTH_IN_SECONDS );

			vkm_add_log( $params['method_str'] . ': Captcha. Enter captcha in' . $params['item_type'] . ' with id=' . $params['item_id'] );
		}

		return false;
	}

	if ( ! empty( $vk_captcha[ $params['method_str'] ] ) ) {
		unset( $vk_captcha[ $params['method_str'] ] );
		if ( ! empty( $vk_captcha ) ) {
			set_transient( 'vk_captcha', $vk_captcha, MONTH_IN_SECONDS );
		} else {
			delete_transient( 'vk_captcha' );
		}
	}


	vkm_add_log( $params['method_str'] . ': VK API ' );

	//set_transient('vkapi', microtime( true ), HOUR_IN_SECONDS);

	return $resp['response'];
}


function vkm_vkapi_handler( $params, $data ) {

	if ( is_wp_error( $data ) ) {
		vkm_add_log( $params['method_str'] . ': WP ERROR. ' . $data->get_error_code() . ' ' . $data->get_error_message() );

		return false;
	}

	if ( isset( $data['response'] ) && isset( $data['response']['code'] ) && $data['response']['code'] != 200 ) {
		vkm_add_log( $params['method_str'] . ': RESPONSE ERROR. ' . $data['response']['code'] . ' ' . $data['response']['message'] );

		return false;
	}

	$data['body'] = vkm_remove_emoji( $data['body'] );
	$resp         = json_decode( $data['body'], true );

	if ( isset( $resp['error'] ) ) {
		if ( isset( $resp['error']['error_code'] ) ) {
			vkm_add_log( $params['method_str'] . ': VK Error. ' . $resp['error']['error_code'] . ' ' . $resp['error']['error_msg'] );
		} else {
			vkm_add_log( $params['method_str'] . ': VK Error. ' . $resp['error'] );
		}

		return false;
	}

	vkm_add_log( $params['method_str'] . ': VK API ' );

	return $resp;
}


function vkm_vkapi_upload( $params ) {

	vkm_vkapi_requests_limit();

	$params['args'] = apply_filters( 'vkm_vkapi_' . $params['method_str'], $params['args'] );
	//print $params['upload_url'];

	// Upload object to server
	$curl = new Wp_Http_Curl();
	$data = $curl->request( $params['upload_url'], array(
		'body'    => $params['args'],
		'method'  => 'POST',
		'headers' => array( 'Content-Type' => 'multipart/form-data' )
	) );

	//print__r($data);
	return vkm_vkapi_handler( $params, $data );
}


function vkm_vkapi_market_add( $params, $item_id = 0 ) {
	$options = get_option( 'vkm_vk_api_site' );

	// https://vk.com/dev/market.add
	$defaults = array(
		'owner_id'      => '', // !!!
		'name'          => '', // !!!; 4 - 100
		'description'   => '', // !!!; > 10
		'category_id'   => '', // !!!
		'price'         => '', // !!!; >= 0.01
		'deleted'       => 0,  // 0, 1
		'main_photo_id' => '', // !!!
		'photo_ids'     => '', // <= 4

		'access_token'  => $options['site_access_token'],
		'v'             => '5.42'
	);

	$args = wp_parse_args( $params, $defaults );

	$res = vkm_vkapi( array(
		'args'       => $args,
		'method'     => 'market.add',
		'method_str' => 'vkm_vkapi_market_add',
		'item_id'    => $item_id,
		'item_type'  => 'post'
	) );

	//vkm_add_log( 'vkm_vkapi_market_add: ' .print_r($res,1) );//

	return $res;
}

function vkm_vkapi_market_edit( $params, $item_id = 0 ) {
	$options = get_option( 'vkm_vk_api_site' );

	// https://vk.com/dev/market.add
	$defaults = array(
		//'owner_id'      => '', // !!!
		//'item_id'       => '', // !!!
		//'name'          => '', // !!!; 4 - 100
		//'description'   => '', // !!!; > 10
		//'category_id'   => '', // !!!
		//'price'         => '', // !!!; >= 0.01
		//'deleted'       => 0,  // 0, 1
		//'main_photo_id' => '', // !!!
		//'photo_ids'     => '', // <= 4

		'access_token' => $options['site_access_token'],
		'v'            => '5.42'
	);

	$args = wp_parse_args( $params, $defaults );
	//vkm_add_log( 'vkm_vkapi_market_edit: ' . print_r( $args, 1 ) );//
	$res = vkm_vkapi( array(
		'args'       => $args,
		'method'     => 'market.edit',
		'method_str' => 'vkm_vkapi_market_edit',
		'item_id'    => $item_id,
		'item_type'  => 'post'
	) );

	//vkm_add_log( 'vkm_vkapi_market_edit: ' . print_r( $res, 1 ) );//

	return $res;
}

function vkm_vkapi_photos_get_market_upload_server( $params ) {
	$options = get_option( 'vkm_vk_api_site' );

	$defaults = array(
		//'group_id'     => '', // !!!;  > 0
		//'main_photo'   => '', // !!!; 0, 1
		//'crop_x'       => 0, // > 0
		//'crop_y'       => 0, // > 0
		//'crop_width'   => '200', // >= 200

		'access_token' => $options['site_access_token'],
		'v'            => '5.42'
	);

	$args = wp_parse_args( $params, $defaults );

	$res = vkm_vkapi( array(
		'args'       => $args,
		'method'     => 'photos.getMarketUploadServer',
		'method_str' => 'vkm_vkapi_photos_get_market_upload_server'
	) );

	//vkm_add_log( 'vkm_vkapi_photos_get_market_upload_server: ' . print_r( $res, 1 ) );//

	return $res;
}

function vkm_vkapi_photos_save_market_photo( $params ) {
	$options = get_option( 'vkm_vk_api_site' );

	$defaults = array(
		//'group_id'     => '', // > 0
		//'photo'        => '', // !!!
		//'server'       => '', // !!!
		//'hash'         => '', // !!!
		//'crop_data'    => '', //
		//'crop_hash'    => '', //

		'access_token' => $options['site_access_token'],
		'v'            => '5.42'
	);

	$args = wp_parse_args( $params, $defaults );

	$res = vkm_vkapi( array(
		'args'       => $args,
		'method'     => 'photos.saveMarketPhoto',
		'method_str' => 'vkm_vkapi_photos_save_market_photo'
	) );

	return $res;
}

function vkm_vkapi_market_get_categories( $params ) {
	$options = get_option( 'vkm_vk_api_site' );

	// https://vk.com/dev/market.getCategories
	$defaults = array(
		'count'        => 1000, // 10; < 1000
		//'offset'          => '',

		'access_token' => $options['site_access_token'],
		'v'            => '5.44',
		'lang'         => 'ru'
	);

	$args = wp_parse_args( $params, $defaults );

	$res = vkm_vkapi( array(
		'args'       => $args,
		'method'     => 'market.getCategories',
		'method_str' => 'vkm_vkapi_market_get_categories'
	) );

	//vkm_add_log( 'vkm_vkapi_market_add: ' .print_r($res,1) );//

	return $res;
}


function vkm_vkapi_market_delete( $params ) {
	$options = get_option( 'vkm_vk_api_site' );

	// https://vk.com/dev/market.add
	$defaults = array(
		'owner_id'     => '', // !!!
		'item_id'      => '', // !!!

		'access_token' => $options['site_access_token'],
		'v'            => '5.42'
	);

	$args = wp_parse_args( $params, $defaults );

	$res = vkm_vkapi( array(
		'args'       => $args,
		'method'     => 'market.delete',
		'method_str' => 'vkm_vkapi_market_delete'
	) );

	//vkm_add_log( 'vkm_vkapi_market_add: ' .print_r($res,1) );//

	return $res;
}


