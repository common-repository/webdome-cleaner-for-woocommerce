<?php
/**
 * WDWC Cronjob Handler
 *
 * Shows all Cronjob details
 *
 * @link 
 * @package Webdome WC Cronjobs
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }


class WDWC_Cronjob_Handler {

    static function setup() {

        $settings = get_option('wdwc_settings');

        foreach( WDWC_Settings::WDWC_Cornjobs as $job ) {
            add_filter( 'cron_schedules', array( __CLASS__, 'wdwc_cj_' . $job . '_scheduler' ) );
            add_action( 'wdwc_cj_' . $job . '_hook', array( __CLASS__, 'wdwc_cj_' . $job . '_run' ) );
        }
        
        // Uninstall Hook
        register_uninstall_hook(WDWC_PLUGIN_FILE, array(__CLASS__, 'wdwc_delete_scheduled_cronjobs'));

	}

    static function wdwc_cj_category_deleter_run() {

        WDWC_CJ_Uncategorized_Products::setup( 'cron' );

    }

    static function wdwc_cj_clean_404_run() {

        WDWC_CJ_Clean_404_Products::setup( 'cron' );

    }

    static function wdwc_cj_clean_short_desc_run() {

        WDWC_CJ_Clean_Short_Desc::setup( 'cron' );

    }

    static function wdwc_cj_delete_products_by_price_run() {

        WDWC_CJ_Delete_Product_By_Price::setup( 'cron' );

    }

    static function wdwc_cj_desc_to_shortdesc_run() {

        WDWC_CJ_Copy_Desc_To_ShortDesc::setup( 'cron' );

    }

    static function wdwc_cj_products_without_image_run() {

        WDWC_CJ_Products_Without_Image::setup( 'cron' );

    }

    static function wdwc_cj_category_deleter_scheduler( $schedules ) {

        $settings = get_option('wdwc_settings');
        $interval = isset( $settings["wdwc_cj_interval_category_deleter"] ) ? sanitize_text_field ( $settings["wdwc_cj_interval_category_deleter"] ) : '';

        if( $interval > 0 ) {
            $schedules['wdwc_cj_category_deleter_interval'] = array(
                'interval' => $interval,
                'display'  => esc_html__( 'Intervall für CJ: Category Deleter' ), );
            return $schedules;
        }

    }

    static function wdwc_cj_clean_404_scheduler( $schedules ) {

        $settings = get_option('wdwc_settings');
        $interval = isset( $settings["wdwc_cj_interval_clean_404"] ) ? sanitize_text_field ( $settings["wdwc_cj_interval_clean_404"] ) : '';

        if( $interval > 0 ) {
            $schedules['wdwc_cj_clean_404_interval'] = array(
                'interval' => $interval,
                'display'  => esc_html__( 'Intervall für CJ: clean_404' ), );
            return $schedules;
        }

    }

    static function wdwc_cj_clean_short_desc_scheduler( $schedules ) {

        $settings = get_option('wdwc_settings');
        $interval = isset( $settings["wdwc_cj_interval_clean_short_desc"] ) ? sanitize_text_field ( $settings["wdwc_cj_interval_clean_short_desc"] ) : '';

        if( $interval > 0 ) {
            $schedules['wdwc_cj_clean_short_desc_interval'] = array(
                'interval' => $interval,
                'display'  => esc_html__( 'Intervall für CJ: clean_short_desc' ), );
            return $schedules;
        }

    }

    static function wdwc_cj_delete_products_by_price_scheduler( $schedules ) {

        $settings = get_option('wdwc_settings');
        $interval = isset( $settings["wdwc_cj_interval_delete_products_by_price"] ) ? sanitize_text_field ( $settings["wdwc_cj_interval_delete_products_by_price"] ) : '';

        if( $interval > 0 ) {
            $schedules['wdwc_cj_delete_products_by_price_interval'] = array(
                'interval' => $interval,
                'display'  => esc_html__( 'Intervall für CJ: delete_products_by_price' ), );
            return $schedules;
        }

    }

    static function wdwc_cj_desc_to_shortdesc_scheduler( $schedules ) {

        $settings = get_option('wdwc_settings');
        $interval = isset( $settings["wdwc_cj_interval_desc_to_shortdesc"] ) ? sanitize_text_field ( $settings["wdwc_cj_interval_desc_to_shortdesc"] ) : '';

        if( $interval > 0 ) {
            $schedules['wdwc_cj_desc_to_shortdesc_interval'] = array(
                'interval' => $interval,
                'display'  => esc_html__( 'Intervall für CJ: desc_to_shortdesc' ), );
            return $schedules;
        }

    }

    static function wdwc_cj_products_without_image_scheduler( $schedules ) {

        $settings = get_option('wdwc_settings');
        $interval = isset( $settings["wdwc_cj_interval_products_without_image"] ) ? sanitize_text_field ( $settings["wdwc_cj_interval_products_without_image"] ) : '';

        if( $interval > 0 ) {
            $schedules['wdwc_cj_products_without_image_interval'] = array(
                'interval' => $interval,
                'display'  => esc_html__( 'Intervall für CJ: products_without_image' ), );
            return $schedules;
        }

    }

    static function wdwc_delete_scheduled_cronjobs() {

        foreach( WDWC_Settings::WDWC_Cornjobs as $job ) {
            $timestamp = wp_next_scheduled( 'wdwc_cj_' . $job . '_hook' );
		    wp_unschedule_event( $timestamp, 'wdwc_cj_' . $job . '_hook' );
        }

    }

}

// Run Setting Class.
WDWC_Cronjob_Handler::setup();
