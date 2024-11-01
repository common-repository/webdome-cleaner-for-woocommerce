<?php
/**
 * WDWC Deleted Products Logs
 *
 * Show a Log-Table for Product Deletes in Admin
 *
 * @link 
 * @package Webdome Importer for Woocommerce
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }


class WDWC_Deleted_Logs_Page {

    static function setup() {

		// Add settings page to admin menu.
		add_action( 'admin_menu', array( __CLASS__, 'wdwc_add_deleted_logs_page' ) );
        add_filter('set-screen-option', array( __CLASS__, 'wdwc_set_screen_option' ), 10, 3 );        

	}

    static function wdwc_add_deleted_logs_page() {
        
		$this_page = add_submenu_page('wdwc-settings', 'Deleted Logs', 'Deleted Logs', 'manage_options', 'wdwc-deleted-logs', array( __CLASS__, 'display_deleted_logs_page'));
        add_action("load-$this_page", array( __CLASS__ , 'wdwc_add_screen_option' ) );

	}

    static function wdwc_add_screen_option() {
        $args = array(
            'label' => 'Elemente pro Seite',
            'default' => 50,
            'option' => 'wdwc_elements_per_page'
        );
        add_screen_option( 'per_page', $args );
    }

    static function wdwc_set_screen_option($status, $option, $value) {
        if ( 'wdwc_elements_per_page' == $option ) return $value;
    }

    static function display_deleted_logs_page() {

		ob_start();
		?>
		<div id="wdwc-deleted-logs" class="wdwc-deleted-logs-wrap wrap">

			<h1>Deleted Logs</h1>

            <!-- <h2>Stati und Beschreibungen</h2>
            <ul>
                <li><strong>title-desc: </strong>   Titel und Kurzbeschreibung enthalten keines der Keywords</li>
                <li><strong>locked-merchant: </strong>   Das Merchant ist zwischenzeitlich gesperrt worden</li>
                <li><strong>sku: </strong>   Die SKU / EAN / GTIN existiert bereits als Woo-Produkt</li>
                <li><strong>woo-import-error: </strong>   Es trat ein Fehler beim Anlegen des Produktes in Woocommerce auf</li>
                <li><strong>new: </strong>   Das Produkt wurde erfolgreich importiert</li>
            </ul> -->

            <h2>Logs</h2>
            <!-- <p>Gesucht werden kann nach einem Status oder nach einem Hash-Code</p> -->
		
		<?php

		$exampleListTable = new WDWC_Deleted_Logs_Table();
        // $exampleListTable->search_box('Suchen', 'search_id');
		$exampleListTable->prepare_items();
		?>
		
			<div id="icon-users" class="icon32"></div>
					
				<?php $exampleListTable->display(); ?>
	
			</div>
		<?php

		echo ob_get_clean();
	}
}

// Run Setting Class.
WDWC_Deleted_Logs_Page::setup();

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WDWC_Deleted_Logs_Table extends WP_List_Table
{

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        if ( isset($_GET['cron_hash']) ) {
            $data = $this->table_data($_GET['cron_hash']);
        } else {
            $data = $this->table_data();
        }
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = $this->get_items_per_page('wdwc_elements_per_page', 20);
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    public function get_columns()
    {
        $columns = array(
            'dp_id'             => 'dp_id', 
            'ext_id' 		    => 'ext_id', 
            'ean'               => 'ean', 
            'woo_key'    	    => 'woo_key', 
            'cat'               => 'cat', 
            'name'      	    => 'name', 
            'short_desc'        => 'short_desc', 
            'price'             => 'price', 
            'merchant_key'      => 'merchant_key', 
            'deleted_date'      => 'deleted_date', 
            'url'      	        => 'url', 
            'reason'      	    => 'reason', 
            'cron_hash_key'     => 'cron_hash_key',
        );

        return $columns;
    }

    public function get_hidden_columns()
    {
        return array();
    }

    public function get_sortable_columns()
    {
        return array(
            'dp_id'             => array('dp_id', true),
            'ext_id' 		    => array('ext_id', true),
            'ean'               => array('ean', true),
            'woo_key'    	    => array('woo_key', true),
            'cat'               => array('cat', true),
            'name'      	    => array('name', true),
            'short_desc'        => array('short_desc', true),
            'price'             => array('price', true),
            'merchant_key'      => array('merchant_key', true),
            'deleted_date'      => array('deleted_date', true),
            'url'      	        => array('url', true),
            'reason'      	    => array('reason', true),
            'cron_hash_key'     => array('cron_hash_key', true),
		);
    }

    private function table_data( $cron_hash = '' )
    {
        $data = array();
        global $wpdb;
    	$table_logs = $wpdb->prefix . "wdwc_log_deleted_products"; 
    	if ( !empty($cron_hash) ) {
            return $wpdb->get_results("SELECT * FROM " . $table_logs . " WHERE cron_hash_key = '$cron_hash'", ARRAY_A);
        } else {
            return $wpdb->get_results("SELECT * FROM " . $table_logs, ARRAY_A);
        }
    }

    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
			case 'dp_id':
            case 'ext_id':
            case 'ean':
            case 'woo_key':
            case 'cat':
            case 'name':
            case 'short_desc':
            case 'price':
            case 'deleted_date':
            case 'url':
            case 'reason':
            case 'cron_hash_key':
                return $item[ $column_name ];
            case 'merchant_key':
                if( !is_null( get_term( $item[ $column_name ] ) ) ) { return get_term( $item[ $column_name ] )->name; } else { return ''; }
            default:
                return print_r( $item, true ) ;
        }
    }

    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'tp_id';
        $order = 'desc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = sanitize_text_field( $_GET['orderby'] );
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = sanitize_text_field( $_GET['order'] );
        }


        $result = strnatcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}
