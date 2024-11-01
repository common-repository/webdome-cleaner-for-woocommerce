<?php
/**
 * WDWC Helper
 *
 * Functions
 *
 * @link 
 * @package Webdome Cleaner for Woocommerce
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }


class WDWC_Helper {

    static function wdwc_helper_cronjob_result( $args ) {
        ?>
        <h3>Cronjob ausgeführt: <?php echo esc_html( $args["name"] ); ?></h3>
        <p>Time Start: <?php echo esc_html( $args["time_start"] ); ?></p>
        <p>Time End: <?php echo esc_html( $args["time_end"] ); ?></p>
        <p>Duration: <?php echo esc_html( $args["duration"] ); ?></p>
        <p>Useragent: <?php echo esc_html( $args["useragent"] ); ?></p>
        <p>IP-Adresse: <?php echo esc_html( $args["ip"] ); ?></p>
        <p>Bot-Cat: <?php echo esc_html( $args["bot_cat"] ); ?></p>
        <p>Comment: <?php echo esc_html( $args["comment"] ); ?></p>
        <?php
    }

    static function wdwc_delete_products_by_merchant( $merchant_key ) {

        global $wpdb;
        $wpdb->delete( $wpdb->prefix. 'wdwc_temp_products', array( 'merchant_key' => $merchant_key ) );

        $query = new WP_Query( $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish',
            'ignore_sticky_posts'   => 1,
            'posts_per_page'        => -1,
            'tax_query'             => array( array(
                'taxonomy'      => 'merchants',
                'field'         => 'term_id', // can be 'term_id', 'slug' or 'name'
                'terms'         => $merchant_key,
            ), ),
        ));
        if ( $query->have_posts() ):
            while( $query->have_posts() ): 
                $query->the_post();
                wp_delete_post( $query->post->ID );
            endwhile;
        endif;

    }

    static function wdwc_set_cronjob_log( $name, $start, $end, $duration, $user, $ip, $cat, $comment, $hash, $bot ) {
        $settings = get_option('wdwc_settings');
        $debug = isset( $settings["wdwc_delete_debugging"] ) ? ( $settings['wdwc_delete_debugging'] == 'on' ? true : false ) : false;
        global $wpdb;
        $table_logs = $wpdb->prefix . "wdwc_log_cronjobs"; 
        $state = $wpdb->query("INSERT INTO $table_logs (name, run_date, time_start, time_end, duration, useragent, ip, cat, comment, cron_hash) VALUES ('$name', CURRENT_TIMESTAMP, '$start', '$end', '$duration', '$user', '$ip', '$cat', '$comment', '$hash')");
        if( $debug && $bot == 'user' ) { $wpdb->show_errors(); }
    }

    static function wdwc_set_product_log( $ext_id, $ean, $woo_key, $cat, $name, $short_desc, $price, $merchant_key, $url, $reason, $cron_hash_key, $bot ) {
        $settings = get_option('wdwc_settings');
        $debug = isset( $settings["wdwc_delete_debugging"] ) ? ( $settings['wdwc_delete_debugging'] == 'on' ? true : false ) : false;
        global $wpdb;
        $table_logs = $wpdb->prefix . "wdwc_log_deleted_products"; 
        $name = esc_sql( $name );
        $short_desc = esc_sql( $short_desc );
        $state = $wpdb->query("INSERT INTO $table_logs (ext_id, ean, woo_key, cat, name, short_desc, price, merchant_key, deleted_date, url, reason, cron_hash_key) VALUES ('$ext_id', '$ean', '$woo_key', '$cat', '$name', '$short_desc', '$price', '$merchant_key', CURRENT_TIMESTAMP, '$url', '$reason', '$cron_hash_key')");
        if( $debug && $bot == 'user' ) { $wpdb->show_errors(); }
    }

    static function wdwc_delete_product( $product_id, $reason, $cron_hash_key, $bot ) {

        global $wpdb;
        
        $settings = get_option('wdwc_settings');
		$debug = isset( $settings["wdwc_delete_debugging"] ) ? ( $settings['wdwc_delete_debugging'] == 'on' ? true : false ) : false;
		$importer = isset( $settings["wdwc_importer_plugin_connectivity"] ) ? ( $settings['wdwc_importer_plugin_connectivity'] == 'on' ? true : false ) : false;

        if( $debug ) {
            $product = wc_get_product( $product_id );
            if( isset($product)) {
                $ext_id = get_post_meta(
                    $product_id,
                    'wdwi_product_ext_id',
                    true
                );
                $terms = json_encode( get_the_terms( $product_id, 'merchants' ) );
                $url = $product->get_product_url();
                WDWC_Helper::wdwc_set_product_log( $ext_id, $product->get_sku(), $product_id, '', $product->get_name(), $product->get_short_description(), $product->get_price(), $terms, $url, $reason, $cron_hash_key, $bot );
            }
        }

        if( $importer ) {
            $table_importer = $wpdb->prefix . "wdwi_temp_products";
            $wpdb->delete( $table_importer, array( 'woo_key' => $product_id ) );
        }
        
        wp_delete_post( $product_id, true );
        
    }

}
