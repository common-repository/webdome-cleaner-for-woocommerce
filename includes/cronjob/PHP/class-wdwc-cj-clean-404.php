<?php
/**
 * WDWC Cronjob Clean Products with 404 Link
 *
 * 
 *
 * @link 
 * @package Webdome Firm Management
 */

class WDWC_CJ_Clean_404_Products {

    public static function setup( $bot ) {
        $args = self::wdwc_cj_main( $bot );
        if( $bot == 'user' ) {
            return $args;
        }
    }

    private static function wdwc_cj_head( $bot_cat ) {
        $name = "Woocommerce Products Clean with 404 Link";
        date_default_timezone_set('Europe/Berlin');
        $timestamp_start = time();
        $time_cronjob_start = date("Y-m-d H:i:s",$timestamp_start);

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $cip = sanitize_text_field ( $_SERVER['HTTP_CLIENT_IP'] );
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $cip = sanitize_text_field ( $_SERVER['HTTP_X_FORWARDED_FOR'] );
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $cip = sanitize_text_field ( $_SERVER['REMOTE_ADDR'] );
        } else {
            $cip = "";
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $useragent = sanitize_text_field ( $_SERVER['HTTP_USER_AGENT'] );
        } else {
            $useragent = "none";
        }

        return array(
            "name" => $name,
            "time_start" => $time_cronjob_start,
            "timestamp_start" => $timestamp_start,
            "useragent" => $useragent,
            "ip" => $cip,
            "bot_cat" => $bot_cat,
            "hash" => md5(time()),
        );
    }

    private static function wdwc_cj_main( $bot ) {		
        $v_head = self::wdwc_cj_head( $bot );
        $v_main = self::wdwc_cj_main_task( $v_head["hash"], $bot );

        $v_foot = self::wdwc_cj_foot($v_head["timestamp_start"]);

        global $wpdb;
        $table_logs = $wpdb->prefix . "wdwc_log_cronjobs"; 
        WDWC_Helper::wdwc_set_cronjob_log( $v_head["name"], $v_head["time_start"], $v_foot["time_end"], $v_foot["duration"], $v_head["useragent"], $v_head["ip"], $v_head["bot_cat"], $v_main["comment"], $v_head["hash"], $bot );
        
        if( $bot == 'user' ) {
            return array(
                'name'       => $v_head["name"],
                'time_start' => $v_head["time_start"],
                'time_end'   => $v_foot["time_end"],
                'duration'   => $v_foot["duration"],
                'useragent'  => $v_head["useragent"],
                'ip'         => $v_head["ip"],
                'bot_cat'    => $v_head["bot_cat"],
                'comment'    => $v_main["comment"]
            );
        }
    }

    private static function wdwc_cj_foot($timestamp_start) {
        $timestamp_end = time();
        $time_cronjob_end = date("Y-m-d H:i:s",$timestamp_end);
        $duration = $timestamp_end - $timestamp_start;
        return array(
            "duration" => $duration,
            "timestamp_end" => $timestamp_end,
            "time_end" => $time_cronjob_end,
        );
    }

    private static function wdwc_cj_main_task( $hash, $bot ) {
        $count = 0;
        $count_change = 0;
        $bodynew = "";
        $comment = "";

        $args = array(
            'limit' => -1,
            'return' => 'ids',
        );
        $products = wc_get_products( $args );

        foreach( $products as $p ):
            $count++;
            $product = wc_get_product( $p );
            if (!is_null($product)) {
                $url = apply_filters('woocommerce_product_add_to_cart_url', $product->get_product_url(), $product);
                $handle = curl_init($url);
                curl_setopt_array($handle, array(
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_NOBODY => true,
                ));
                $response = curl_exec($handle);
                $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            
                if ($httpCode == 404) {
                    $count_change++;
                    WDWC_Helper::wdwc_delete_product( $product->get_id(), '404-Link', $hash, $bot );
                } 
            
                curl_close($handle);
            }
        endforeach;
		
		$comment = "Es wurden " . strval($count_change) . " von " . strval($count) . " Produkten gelöscht;";

        return array(
            "count" => $count_change,
            "body" => $bodynew,
            "comment" => $comment,
        );
    }

}