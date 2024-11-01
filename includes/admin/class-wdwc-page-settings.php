<?php
/**
 * WDWC Settings Page
 *
 * Registers all plugin settings with the WordPress Settings API.
 *
 * @link 
 * @package 
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }


class WDWC_Settings_Page {

    static function setup() {

		// Add settings page to admin menu.
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );

	}

    static function add_settings_page() {
        
		add_menu_page('WD Woo Cleaner', 'WD Woo Cleaner', 'manage_options', 'wdwc-settings', array( __CLASS__, 'display_settings_page' ), 'dashicons-trash', 3);
        add_submenu_page('wdwc-settings', 'Settings', 'Settings', 'manage_options', 'wdwc-settings', array( __CLASS__, 'display_settings_page'));

	}

    static function display_settings_page() {

		ob_start();
		?>

		<div id="wdwc-settings" class="wdwc-settings-wrap wrap">

			<?php

			global $wpdb;

			$mytables=$wpdb->get_results("SHOW TABLES", ARRAY_N);
			$tables = [];
			foreach( $mytables as $table ) {
				foreach( $table as $t ) {
					array_push( $tables, $t );	
				}
			}

			foreach( WDWC_Database_Tables::WDWC_TABLES as $key => $table ) {
				if ( !in_array( $wpdb->prefix . $table, $tables ) ) {
					echo '<h2 style="color: red;">Database Error: Folgende Tabelle fehlt: ' . $table . '</h2>';
				}
			}

			?>

			<h2>Cronjob WP-Cron-Status</h2>

			<p><strong>Cronjob Category Deleter</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_category_deleter_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>
			<p><strong>Cronjob Clean Products with 404 Link</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_clean_404_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>
			<p><strong>Cronjob Clean Short Desc (HTML Tags)</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_clean_short_desc_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>
			<p><strong>Cronjob Delete Products by Price</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_delete_products_by_price_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>
			<p><strong>Cronjob Copy Desc to Shortdesc</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_desc_to_shortdesc_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>
			<p><strong>Cronjob Products without image</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_products_without_iamge_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>

			<h1>Einstellungen</h1>

			<?php 
				$set = get_option('wdwc_settings');
				$license = isset( $set["wdwc_license_key"] ) ? sanitize_text_field( $set["wdwc_license_key"] ) : '';
				if( md5($license) !== 'd22e9dbe4767ceb90c842dca51998ab5' ) {
					echo '<h1 style="margin: 1 3em; text-align: center; background-color: red; font-weight: bold; font-size: 36px; color: white;">Bitte gültigen Lizenz-Key aktivieren!!!</h1>';
				}

			?>

			<form class="wdwc-settings-form" method="post" action="options.php">
				<?php
					settings_fields( 'wdwc_settings' );
					do_settings_sections( 'wdwc_settings' );
					submit_button();
				?>
			</form>

		</div>
		<style>.regular-text {width: 40em !important;}</style>

		<?php
		echo ob_get_clean();
	}
}

// Run Setting Class.
WDWC_Settings_Page::setup();
