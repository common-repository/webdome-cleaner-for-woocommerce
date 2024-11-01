<?php
/**
 * WDWC Cronjob Page
 *
 * Shows all Cronjob details
 *
 * @link 
 * @package Webdome Cleaner for Woocommerce
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }


class WDWC_Cronjob_Page {

    static function setup() {

		add_action( 'admin_menu', array( __CLASS__, 'wdwc_add_cronjob_page' ) );

	}

    static function wdwc_add_cronjob_page() {

        add_submenu_page('wdwc-settings', 'Cronjob', 'Cronjob', 'manage_options', 'wdwc-cronjob', array( __CLASS__, 'display_cronjob_page'));
        
	}

    static function display_cronjob_page() {

		// $mydir = WDWC_PLUGIN_DIR . 'includes/cronjob/';
		// foreach($cronjobs as $c) {
		// 	if( strtolower(pathinfo($c, PATHINFO_EXTENSION)) == "sh" ) {
		// 		chmod($mydir . $c, 0774);
		// 	}
		// }

		ob_start();
		?>
		<div id="wdwc-cronjobs" class="wdwc-cronjobs-wrap wrap">

		<h1>Cronjobs</h1>

		<h2>Cronjob WP-Cron-Status</h2>

		<p><strong>Cronjob Category Deleter</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_category_deleter_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>
		<p><strong>Cronjob Clean Products with 404 Link</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_clean_404_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>
		<p><strong>Cronjob Clean Short Desc (HTML Tags)</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_clean_short_desc_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>
		<p><strong>Cronjob Delete Products by Price</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_delete_products_by_price_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>
		<p><strong>Cronjob Copy Desc to Shortdesc</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_desc_to_shortdesc_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>
		<p><strong>Cronjob Products without image</strong><span style="margin-left: 0.5em;"><?php echo isset( wp_get_scheduled_event('wdwc_cj_products_without_iamge_hook')->timestamp ) ? '&#9989;' : '&#10060;'; ?></span></p>

		<?php
		if( isset( $_GET['do_cron'] ) ):

			?>
			<h2 class="cron-title">Folgende Cronjobs wurden ausgeführt</h2>
			<?php
			switch( sanitize_text_field( $_GET['do_cron'] ) ) {
				case 'wdwc_cj_category_deleter':
					$args = WDWC_CJ_Uncategorized_Products::setup( 'user' );
					break;
				case 'wdwc_cj_clean_404':
					$args = WDWC_CJ_Clean_404_Products::setup( 'user' );
					break;
				case 'wdwc_cj_clean_short_desc':
					$args = WDWC_CJ_Clean_Short_Desc::setup( 'user' );
					break;
				case 'wdwc_cj_delete_prodcuts_by_price':
					$args = WDWC_CJ_Delete_Product_By_Price::setup( 'user' );
					break;
				case 'wdwc_cj_desc_to_shortdesc':
					$args = WDWC_CJ_Copy_Desc_To_ShortDesc::setup( 'user' );
					break;
				case 'wdwc_cj_products_without_image':
					$args = WDWC_CJ_Products_Without_Image::setup( 'user' );
					break;
			}
			WDWC_Helper::wdwc_helper_cronjob_result( $args );

		endif;

		chmod($mydir = ABSPATH . 'wp-cron.php', 0774);

		?>

		<h2 class="cron-title">Infos</h2>

		<p>TODO: In der wp-config.php muss folgender Eintrag eingefügt werden: >>> define('DISABLE_WP_CRON', true); <<<</p>
		<br />
		<p>TODO: Einen Server Cronjob einrichten, der die Datei wp-cron.php aufruft: Beispielsweise: >>> /usr/bin/php80 /usr/www/users/dachps/wp-cron.php; <<<</p>
		<br />
		<h2 class="cron-title">Cronjobs ausführen</h2>

		<a href="/wp-admin/admin.php?page=wdwc-cronjob&do_cron=wdwc_cj_category_deleter" class="button button-primary">Cronjob Category Deleter ausführen</a><br /><br />
		<a href="/wp-admin/admin.php?page=wdwc-cronjob&do_cron=wdwc_cj_clean_404" class="button button-primary">Cronjob Clean Products with 404 Link ausführen</a><br /><br />
		<a href="/wp-admin/admin.php?page=wdwc-cronjob&do_cron=wdwc_cj_clean_short_desc" class="button button-primary">Cronjob Clean Short Desc (HTML Tags) ausführen</a><br /><br />
		<a href="/wp-admin/admin.php?page=wdwc-cronjob&do_cron=wdwc_cj_delete_prodcuts_by_price" class="button button-primary">Cronjob Delete Products by Price ausführen</a><br /><br />
		<a href="/wp-admin/admin.php?page=wdwc-cronjob&do_cron=wdwc_cj_desc_to_shortdesc" class="button button-primary">Cronjob Copy Desc to Shortdesc ausführen</a><br /><br />
		<a href="/wp-admin/admin.php?page=wdwc-cronjob&do_cron=wdwc_cj_products_without_image" class="button button-primary">Cronjob Products without image ausführen</a><br /><br />

		<?php
		
		?>

		<?php
		echo ob_get_clean();
	}
}

// Run Setting Class.
WDWC_Cronjob_Page::setup();
