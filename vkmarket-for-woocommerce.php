<?php
/*
Plugin Name: VKMarket for WooCommerce
Description: Автоматическая синхронизация магазина на WooCommerce c разделом Товары ВКонтакте.
Version: 0.8
Plugin URI: http://ukraya.ru/vkmarket-for-woocommerce/
Author: Aleksej Solovjov
Author URI: http://ukraya.ru
Text Domain: vkmarket-for-woocommerce
Domain Path: /languages/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*  Copyright 2016 Aleksej Solovjov

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

*/

function vkm_version() {
	return '0.8';
}


function vkm_requirements( $status = false ) {
	if ( $status ) {
		if ( ! class_exists( 'WP_Settings_API_Class2' ) ) {
			include_once( 'inc/wp-settings-api-class.php' );
		}

		if ( ! class_exists( 'WP_Help_Pointer' ) ) {
			include_once( 'inc/wp-help-pointer-class.php' );
		}

		include 'inc/vkwp-api.php';
		include 'vkm-functions.php';
		include 'vkm-export.php';

		include 'vkm-admin.php';
	} else {
		add_action( 'admin_notices', 'vkm_admin_notice_deactivation' );
		add_action( 'admin_init', 'vkm_deactivation' );
	}
}

global $wp_version;

if ( version_compare( PHP_VERSION, '5', '>' ) &&
     version_compare( $wp_version, '4.4', '>=' )
) {

	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$vkm_requirements = true;
	} else {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
			$vkm_requirements = true;
		} else {
			$vkm_requirements = false;
		}
	}

} else {
	$vkm_requirements = false;
}


vkm_requirements( $vkm_requirements );


function vkm_deactivation() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}

function vkm_admin_notice_deactivation() {
	$url = site_url( 'wp-admin/plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=772&height=507' );

	printf( '<div class="error"><p>' .
	        __( 'For VKMarket & WooCommerce plugin is required PHP 5, WordPress 4.4 and <a class="thickbox" href="%s">WooCommerce</a> plugin. Please install and activate the necessary programs.', 'vkmarket-for-woocommerce' ) .
	        '</p></div>', $url, $url
	);
}


define( 'VKM_TOKEN_URL', 'https://oauth.vk.com/access_token' );
define( 'VKM_AUTHORIZATION_URL', 'https://oauth.vk.com/authorize' );
define( 'VKM_API_URL', 'https://api.vk.com/method/' );


function vkm_init() {
	global $wp_version;


	if ( version_compare( PHP_VERSION, '5', '>' ) &&
	     version_compare( $wp_version, '4.4', '>=' )
	) {

		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$vkm_requirements = true;
		} else {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}

			if ( is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
				$vkm_requirements = true;
			} else {
				$vkm_requirements = false;
			}
		}

	} else {
		$vkm_requirements = false;
	}

	if ( $vkm_requirements ) {

		load_plugin_textdomain( 'vkmarket-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// set $vk_market_categories global.
		$lang = get_bloginfo( 'language' );
		vkm_get_vk_categories( array( 'lang' => substr( $lang, 0, 2 ) ) );


	}
}

add_action( 'admin_init', 'vkm_init' );


function vkm_admin_head() {

	if ( isset( $_GET['page'] ) && in_array( $_GET['page'], array(
			'vkmarket',
			'vkmarket-settings',
			'vkmarket-bulk',
			'vkmarket-log',
			'vkmarket-help',
		) )
	) {
		?>
		<style type="text/css">
			#col-right.vkm {
				width: 35%;
			}

			#col-left.vkm {
				width: 64%;
			}

			.vkm-box {
				padding: 0 20px 0 40px;
			}

			@media only screen and (max-width: 960px) {
				#col-right.vkm {
					width: 100%;
				}

				#col-left.vkm {
					width: 100%;
				}

				.vkm-box {
					padding: 0;
				}
			}

			.vkm-boxx {
				background: none repeat scroll 0 0 #FFFFFF;
				border-left: 4px solid #2EA2CC;
				box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);
				margin: 5px 0 15px;
				padding: 1px 12px;
			}

			.vkm-boxx h3 {
				line-height: 1.5;
			}

			.vkm-boxx p {
				margin: 0.5em 0;
				padding: 2px;
			}
		</style>

		<?php
	}

	if ( in_array( $GLOBALS['pagenow'], array( 'post.php' ) ) ) {
		?>
		<style type="text/css">
			#vkm-product-link {
				color: #666;
				line-height: 24px;
				min-height: 25px;
				padding: 0 10px;
			}
		</style>
		<?php
	}
}

add_action( 'admin_head', 'vkm_admin_head', 90 );


function vkm_plugin_action_links( $links ) {

	$links[] = '<a href="' . admin_url( 'admin.php?page=vkmarket-help' ) . '">Быстрый старт</a>';

	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'vkm_plugin_action_links' );


function vkm_admin_help_pointers() {

	$pointers = array(
		array(
			'id'       => 'vkm_help_page_pointer',
			'screen'   => array('dashboard', 'plugins', 'edit-product', 'toplevel_page_vkmarket', 'tovary-vk_page_vkmarket-settings' ),
			'target'   => '#toplevel_page_vkmarket',
			'title'    => 'Товары ВК: Быстрый старт',
			'content'  => '<a href="' . admin_url( 'admin.php?page=vkmarket-help' ) . '">Документация</a>: от настроек до публикации первого товара в группе ВКонтакте.',
			'position' => array(
				'edge'  => 'left', //top, bottom, left, right
				'align' => 'right' //top, bottom, left, right, middle
			)
		)
	);

	new WP_Help_Pointer( $pointers );
}

add_action( 'admin_enqueue_scripts', 'vkm_admin_help_pointers' );