<?php
/**
 * WDWC Cronjob Logs
 *
 * Show a Log-Table in Admin
 *
 * @link 
 * @package Webdome Firm Management
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }


class WDWC_Cronjob_Logs_Page {

    static function setup() {

		// Add settings page to admin menu.
		add_action( 'admin_menu', array( __CLASS__, 'wdwc_add_cronjob_page' ) );
        add_filter('set-screen-option', array( __CLASS__, 'wdwc_set_screen_option' ), 10, 3 );        

	}

    static function wdwc_add_cronjob_page() {
        
		$this_page = add_submenu_page('wdwc-settings', 'Cronjob Logs', 'Cronjob Logs', 'manage_options', 'wdwc-cronjob-logs', array( __CLASS__, 'display_cronjob_logs_page'));
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

    static function display_cronjob_logs_page() {

		ob_start();
		?>
		<div id="wdwc-cronjobs-logs" class="wdwc-cronjobs-logs-wrap wrap">

			<h1>Cronjob Logs</h1>
		
		<?php

		$exampleListTable = new WDWC_Cronjob_Logs_Table();
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
WDWC_Cronjob_Logs_Page::setup();

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WDWC_Cronjob_Logs_Table extends WP_List_Table
{

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
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
            'cj_id'         => 'ID',
            'name'       	=> 'Name',
            'run_date' 		=> 'Ausführungsdatum',
            'time_start'    => 'Startzeit',
            'time_end'    	=> 'Endzeit',
            'duration'      => 'Dauer',
            'useragent'     => 'Useragent',
            'ip'      		=> 'IP',
            'cat'      		=> 'Kategorie',
            'comment'      	=> 'Kommentar',
            'cron_hash'     => 'Unique Hash',
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
			'cj_id'         => array('cj_id', true),
            'name'       	=> array('Name', false),
            'run_date' 		=> array('Ausführungsdatum', false),
            'time_start'    => array('Startzeit', false),
            'time_end'    	=> array('Endzeit', false),
            'duration'      => array('Dauer', false),
            'useragent'     => array('Useragent', false),
            'ip'      		=> array('IP', false),
            'cat'      		=> array('Kategorie', false),
            'comment'      	=> array('Kommentar', false),
            'cron_hash'     => array('cron_hash', true),
		);
    }

    private function table_data()
    {
        $data = array();

        global $wpdb;
    	$table_logs = $wpdb->prefix . "wdwc_log_cronjobs"; 
    	return $wpdb->get_results("SELECT * FROM " . $table_logs, ARRAY_A);

        // return $data;
    }

    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
			case 'cj_id':
            case 'name':
            case 'run_date':
            case 'time_start':
            case 'time_end':
            case 'duration':
            case 'useragent':
            case 'ip':
            case 'cat':
            case 'comment':
                return $item[ $column_name ];
            case 'cron_hash';
                return '<a href="/wp-admin/admin.php?page=wdwc-deleted-logs&cron_hash=' . $item[ $column_name ] . '">' . $item[ $column_name ] . '</a>';
            default:
                return print_r( $item, true ) ;
        }
    }

    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'time_start';
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
