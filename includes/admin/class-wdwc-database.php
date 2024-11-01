<?php
/**
 * WDWC Database
 *
 * Registers the database
 *
 * @link 
 * @package 
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }


class WDWC_Database_Tables {

    const WDWC_TABLES = array('wdwc_log_deleted_products', 'wdwc_log_cronjobs');

    static function setup() {

        register_activation_hook(WDWC_PLUGIN_FILE, array(__CLASS__, 'wdwc_create_database_tables'));
        register_uninstall_hook(WDWC_PLUGIN_FILE, array(__CLASS__, 'wdwc_delete_database_tables'));

        if (WDWC_VERSION !== get_option('wdwc_version')) {
            self::wdwc_create_database_tables();
        }

        update_option('wdwc_version', WDWC_VERSION);

    }

    static function wdwC_create_database_tables() {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name_1 = $wpdb->prefix . 'wdwc_log_cronjobs';

        $wpdb->query(
            "DELETE FROM " . $wpdb->prefix . "wdwc_log_cronjobs
            WHERE cron_hash = ''"
        );

        $sql = "CREATE TABLE $table_name_1 (
            cj_id INT NOT NULL AUTO_INCREMENT ,
            name VARCHAR(100) NOT NULL ,
            run_date DATE NOT NULL ,
            time_start DATETIME NOT NULL ,
            time_end DATETIME NOT NULL ,
            duration FLOAT NOT NULL ,
            useragent VARCHAR(255) NOT NULL ,
            ip VARCHAR(50) NOT NULL ,
            cat VARCHAR(10) NOT NULL ,
            comment TEXT NOT NULL ,
            cron_hash VARCHAR(200) DEFAULT NULL ,
            PRIMARY KEY (cj_id) ,
            UNIQUE KEY wdwc_log_cronjobs_cron_hash (cron_hash)
        ) $charset_collate;";
        dbDelta( $sql );

        $table_name_2 = $wpdb->prefix . 'wdwc_log_deleted_products';
        $sql = "CREATE TABLE $table_name_2 (
            dp_id INT NOT NULL AUTO_INCREMENT ,
            ext_id VARCHAR(200) DEFAULT NULL ,
            ean VARCHAR(25) DEFAULT NULL ,
            woo_key INT DEFAULT NULL ,
            cat VARCHAR(25) NOT NULL ,
            name VARCHAR(255) NOT NULL ,
            short_desc TEXT NOT NULL ,
            price FLOAT NOT NULL ,
            merchant_key INT NOT NULL ,
            deleted_date DATETIME NOT NULL ,
            url TEXT NOT NULL ,
            reason VARCHAR(100) NOT NULL ,
            cron_hash_key VARCHAR(200) DEFAULT NULL ,
            PRIMARY KEY (dp_id)
        ) $charset_collate;";
        dbDelta( $sql );

    }

    static function wdwc_delete_database_tables() {

        global $wpdb;
        foreach( self::WDWC_TABLES as $table ) {
            $wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . $table );
        }    

    }
}

// Run Setting Class.
WDWC_Database_Tables::setup();
