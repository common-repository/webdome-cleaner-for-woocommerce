<?php
/*
Plugin Name: Webdome Cleaner for WooCommerce
Description: Cleaner for Products in Woocommerce
Author: Webdome Webentwicklung
Author URI: https://www.web-dome.de
Version: 1.2.0
Text Domain: wd-woo-cle
Domain Path: /languages/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Webdome Cleaner for WooCommerce
Copyright(C) since 2022, Webdome - Fabian.Heidger@web-dome.de

*/

namespace Webdome_Cleaner_For_Woocommerce;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Webdome Firm Management
 */
class Webdome_Cleaner_For_Woocommerce {

	/**
	 * Call all Functions to setup the Plugin
	 *
	 * @return void
	 */

	static function setup() {

		// Setup Constants.
		self::constants();

		// Setup Translation.
		add_action( 'plugins_loaded', array( __CLASS__, 'translation' ) );

		// Include Files.
		self::includes();

	}

	/**
	 * Setup plugin constants
	 *
	 * @return void
	 */
	static function constants() {

		// Define Plugin Name.
		define( 'WDWC_NAME', 'Webdome_Cleaner_for_WooCommerce' );

		// Define Version Number.
		define( 'WDWC_VERSION', '1.2.0' );

		// Plugin Folder Path.
		define( 'WDWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

		// Plugin Folder URL.
		define( 'WDWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

		// Plugin Root File.
		define( 'WDWC_PLUGIN_FILE', __FILE__ );

	}

	/**
	 * Load Translation File
	 *
	 * @return void
	 */
	static function translation() {

		//load_plugin_textdomain( 'wp-theme-changelogs', false, dirname( plugin_basename( WDWC_PLUGIN_FILE ) ) . '/languages/' );

	}

	/**
	 * Include required files
	 *
	 * @return void
	 */
	static function includes() {

		// Check License-Key
		$settings = get_option('wdwc_settings');
        $license = isset( $settings["wdwc_license_key"] ) ? sanitize_text_field( $settings["wdwc_license_key"] ) : '';

		// Include Settings.
		require_once WDWC_PLUGIN_DIR . 'includes/admin/class-wdwc-database.php';
		require_once WDWC_PLUGIN_DIR . 'includes/admin/class-wdwc-settings.php';
		require_once WDWC_PLUGIN_DIR . 'includes/admin/class-wdwc-page-settings.php';
		
		// Include all other Files
		if( md5($license) === 'd22e9dbe4767ceb90c842dca51998ab5' ) {
			
			if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				add_action( 'woocommerce_loaded', array( __CLASS__, 'includes_cronjobs' ), 10, 1 ); 			
			}
		}		
		
	}

	static function includes_cronjobs() {

		// Load Cronjobs
		require_once WDWC_PLUGIN_DIR . 'includes/cronjob/PHP/class-wdwc-cj-category-deleter.php';
		require_once WDWC_PLUGIN_DIR . 'includes/cronjob/PHP/class-wdwc-cj-clean-404.php';
		require_once WDWC_PLUGIN_DIR . 'includes/cronjob/PHP/class-wdwc-cj-clean-short-desc.php';
		require_once WDWC_PLUGIN_DIR . 'includes/cronjob/PHP/class-wdwc-cj-delete-product-by-price.php';
		require_once WDWC_PLUGIN_DIR . 'includes/cronjob/PHP/class-wdwc-cj-desc-to-shortdesc.php';
		require_once WDWC_PLUGIN_DIR . 'includes/cronjob/PHP/class-wdwc-cj-products-without-image.php';

		// Load Cronjob Handler
		require_once WDWC_PLUGIN_DIR . 'includes/cronjob/class-wdwc-cronjob-handler.php';

		// Load Admin Includes
		require_once WDWC_PLUGIN_DIR . 'includes/admin/class-wdwc-helper.php';
		require_once WDWC_PLUGIN_DIR . 'includes/admin/class-wdwc-page-cronjob.php';
		require_once WDWC_PLUGIN_DIR . 'includes/admin/class-wdwc-page-cronjob-logs.php';
		require_once WDWC_PLUGIN_DIR . 'includes/admin/class-wdwc-page-deleted-logs.php';

	}

}

// Run Plugin.
Webdome_Cleaner_For_Woocommerce::setup();
