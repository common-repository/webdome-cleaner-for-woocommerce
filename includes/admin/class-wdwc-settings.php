<?php
/**
 * WDWC Settings
 *
 * Registers all plugin settings with the WordPress Settings API.
 *
 * @link 
 * @package Webdome Firm Management
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }

class WDWC_Settings {

    private static $instance;

    private $options;

	const WDWC_Cornjobs = array(
		'category_deleter',
		'clean_404',
		'clean_short_desc',
		'delete_products_by_price',
		'desc_to_shortdesc',
		'products_without_image',
	);

    public static function instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

    public function __construct() {

		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Merge Plugin Options Array from Database with Default Settings Array.
		$this->options = wp_parse_args( get_option( 'wdwc_settings' , array() ), $this->default_settings() );
	}

    public function get( $key, $default = false ) {
		$value = ! empty( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
		return $value;
	}

    public function get_all() {
		return $this->options;
	}

    public function default_settings() {

		$default_settings = array(
			'wdwc_importer_plugin_connectivity' => __( 'on' ),
			'wdwc_delete_debugging' => __( 'off' ),
			'wdwc_delete_debugging' => __( 'off' ),
			'wdwc_license_key' => __( '' ),
			'wdwc_price_lower' => __( '' ),
			'wdwc_price_greater' => __( '' ),
			'wdwc_cronjobs_batch_size' => __( '' ),
			'wdwc_category_id' => __( '' ),
			// 'wdwc_cj_unix_category_deleter' => __( '' ),
			// 'wdwc_cj_interval_category_deleter' => __( 0 ),
		);

		foreach( self::WDWC_Cornjobs as $job ) {
			$default_settings['wdwc_cj_unix_' . $job] = __( '' );
			$default_settings['wdwc_cj_interval_' . $job] = __( 0 );
		}

		return $default_settings;
	}

    function register_settings() {

		// Make sure that options exist in database.
		if ( false === get_option( 'wdwc_settings' ) ) {
			add_option( 'wdwc_settings' );
		}

		// Add Sections.
		add_settings_section( 'wdwc_settings_general', 'WDWC Settings', '__return_false', 'wdwc_settings' );

		// Add Settings.
		foreach ( $this->get_registered_settings() as $key => $option ) :

			$name = isset( $option['name'] ) ? $option['name'] : '';
			$section = isset( $option['section'] ) ? $option['section'] : 'widgets';

			add_settings_field(
				'wdwc_settings[' . $key . ']',
				$name,
				is_callable( array( $this, $option['type'] . '_callback' ) ) ? array( $this, $option['type'] . '_callback' ) : array( $this, 'missing_callback' ),
				'wdwc_settings',
				'wdwc_settings_' . $section,
				array(
					'id'      => $key,
					'name'    => isset( $option['name'] ) ? $option['name'] : null,
					'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
					'size'    => isset( $option['size'] ) ? $option['size'] : null,
					'max'     => isset( $option['max'] ) ? $option['max'] : null,
					'min'     => isset( $option['min'] ) ? $option['min'] : null,
					'step'    => isset( $option['step'] ) ? $option['step'] : null,
					'options' => isset( $option['options'] ) ? $option['options'] : '',
					'default'     => isset( $option['default'] ) ? $option['default'] : '',
				)
			);

		endforeach;

		// Creates our settings in the options table.
		register_setting( 'wdwc_settings', 'wdwc_settings', array( $this, 'sanitize_settings' ) );
	}

    function sanitize_settings( $input = array() ) {

		if ( empty( $_POST['_wp_http_referer'] ) ) {
			return $input;
		}

		$saved = get_option( 'wdwc_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$settings = $this->get_registered_settings();
		$input = $input ? $input : array();

		// Loop through each setting being saved and pass it through a sanitization filter.
		foreach ( $input as $key => $value ) :

			// Get the setting type (checkbox, select, etc).
			$type = isset( $settings[ $key ]['type'] ) ? $settings[ $key ]['type'] : false;

			// Sanitize user input based on setting type.
			if ( 'text' === $type ) :

				$input[ $key ] = sanitize_text_field( $value );

			elseif ( 'radio' === $type or 'select' === $type ) :

				$available_options = array_keys( $settings[ $key ]['options'] );
				$input[ $key ] = in_array( $value, $available_options, true ) ? $value : $settings[ $key ]['default'];

			elseif ( 'checkbox' === $type ) :

				$input[ $key ] = $value; // Validate Checkboxes later.

			elseif ( 'textbox' === $type ) :

				$input[ $key ] = $text = sanitize_text_field( str_replace( "\r\n", ';', trim( $value ) ) );

			else :

				// Default Sanitization.
				$input[ $key ] =  $value ;

			endif;

		endforeach;

		// Ensure a value is always passed for every checkbox.
		if ( ! empty( $settings ) ) :
			foreach ( $settings as $key => $setting ) :

				// Single checkbox.
				if ( isset( $settings[ $key ]['type'] ) && 'checkbox' == $settings[ $key ]['type'] ) :
					$input[ $key ] = ! empty( $input[ $key ] );
				endif;

			endforeach;
		endif;

		// Reset to default settings.
		if ( isset( $_POST['wdwc_reset_defaults'] ) ) {
			$input = $this->default_settings();
		}

		foreach( self::WDWC_Cornjobs as $job ) {

			$this->wdwc_update_cj( $job, $input['wdwc_cj_unix_' . $job], $input['wdwc_cj_interval_' . $job], $saved['wdwc_cj_unix_' . $job], $saved['wdwc_cj_interval_' . $job] );

		}

		// $this->wdwc_update_cj( 'category-deleter', $input['wdwc_cj_unix_category_deleter'], $input['wdwc_cj_interval_category_deleter'], $saved['wdwc_cj_unix_category_deleter'], $saved['wdwc_cj_interval_category_deleter'] );

		return array_merge( $saved, $input );
	}

	static function wdwc_update_cj( $cj, $unix, $interval, $unix_old, $interval_old ) {

		if( $interval <> '' && $unix <> '' ) {
			if( $unix <> $unix_old || $interval <> $interval_old || wp_get_scheduled_event('wdwc_cj_' . $cj . '_hook') == False ) {
				if( $interval > 0 ) {
					$unix = strtotime( $unix );
					$timestamp = wp_next_scheduled( 'wdwc_cj_' . $cj . '_hook' );
					wp_unschedule_event( $timestamp, 'wdwc_cj_' . $cj . '_hook' );	
	
					$script_tz = date_default_timezone_get();
					date_default_timezone_set('UTC');
	
					if( $unix <= time() ) {
						$diff = floor( ( time() - $unix ) / $interval );
						$unix = $unix + ( $diff * $interval );
					}
			
					// wp_mail('Fabian.Heidger@web-dome.de', 'Infos', "Unix: $unix - Diff = $diff - unix-new: $unix_new - intervall: $interval - time: " . time());
			
					if( has_filter( 'cron_schedules', 'WDWC_Cronjob_Handler::wdwc_cj_' . $cj . '_scheduler' ) ) {
						$stat = wp_schedule_event( $unix, 'wdwc_cj_' . $cj . '_interval', 'wdwc_cj_' . $cj . '_hook' );
						sleep(1);
					}
			
					date_default_timezone_set($script_tz);
				} else {
					$timestamp = wp_next_scheduled( 'wdwc_cj_' . $cj . '_hook' );
					wp_unschedule_event( $timestamp, 'wdwc_cj_' . $cj . '_hook' );
				}
			}
		}

	}

    function get_registered_settings() {

		// Get default settings.
		$default_settings = $this->default_settings();

		// Create Settings array.
		$settings = array(
			'wdwc_importer_plugin_connectivity' => array(
				'name' => 'Konnektivität zum Webdome Importer for Woocommerce Plugin',
				'desc' => 'Konnektivität zum Webdome Importer for Woocommerce Plugin',
				'section' => 'general',
				'type' => 'radio',
				'size' => 'regular',
				'options' => [
					'on' => 'Importer-Konnektivität AN',
					'off' => 'Importer-Konnektivität AUS'
				],
				'default' => $default_settings['wdwc_importer_plugin_connectivity'],
			),
			'wdwc_delete_debugging' => array(
				'name' => 'Debugging-Modus für Produkt-Löschen',
				'desc' => 'Debugging-Modus für Produkt-Löschen',
				'section' => 'general',
				'type' => 'radio',
				'size' => 'regular',
				'options' => [
					'on' => 'Debug-Modus AN',
					'off' => 'Debug-Modus AUS'
				],
				'default' => $default_settings['wdwc_delete_debugging'],
			),
			'wdwc_license_key' => array(
				'name' => 'Plugin-Lizenz-Key',
				'desc' => 'Plugin-Lizenz-Key',
				'section' => 'general',
				'type' => 'password',
				'size' => 'regular',
				'default' => $default_settings['wdwc_license_key'],
			),
			'wdwc_price_lower' => array(
				'name' => 'Preisgrenze kleiner',
				'desc' => 'Preisgrenze zum Löschen der Produkte kleiner diesem Preis per Cronjob',
				'section' => 'general',
				'type' => 'number',
				'size' => 'regular',
				'default' => $default_settings['wdwc_price_lower'],
			),
            'wdwc_price_greater' => array(
				'name' => 'Preisgrenze größer',
				'desc' => 'Preisgrenze zum Löschen der Produkte größer diesem Preis per Cronjob',
				'section' => 'general',
				'type' => 'number',
				'size' => 'regular',
				'default' => $default_settings['wdwc_price_greater'],
			),
            'wdwc_cronjobs_batch_size' => array(
				'name' => 'Anzahl Produkten pro Batch in Cronjobs',
				'desc' => '',
				'section' => 'general',
				'type' => 'number',
				'size' => 'regular',
				'step' => '1',
				'default' => $default_settings['wdwc_cronjobs_batch_size'],
			),
            'wdwc_category_id' => array(
				'name' => 'SLUG der Uncategorized Kategorie zum Löschen',
				'desc' => '',
				'section' => 'general',
				'type' => 'text',
				'size' => 'regular',
				'default' => $default_settings['wdwc_category_id'],
			),
            'wdwc_cj_unix_category_deleter' => array(
				'name' => 'Unix-Timestamp (GMT) erster Lauf für Cronjob: Category Deleter',
				'desc' => '',
				'section' => 'general',
				'type' => 'datetime',
				'size' => 'regular',
				'default' => $default_settings['wdwc_cj_unix_category_deleter'],
			),
            'wdwc_cj_interval_category_deleter' => array(
				'name' => 'Intervall zwischen Cronjobs: Category Deleter in Sekunden (Minimum: 1)',
				'desc' => 'Soll der Cronjob nicht aktiv sein, bitte 0 eintragen',
				'section' => 'general',
				'type' => 'number',
				'size' => 'regular',
				'step' => '1',
				'min' => 0,
				'default' => $default_settings['wdwc_cj_interval_category_deleter'],
			)
		);

		foreach( self::WDWC_Cornjobs as $job ) {

			$settings['wdwc_cj_unix_' . $job] = array(
			   'name' => 'Unix-Timestamp (GMT) erster Lauf für Cronjob: ' . $job,
			   'desc' => '',
			   'section' => 'general',
			   'type' => 'datetime',
			   'size' => 'regular',
			   'default' => $default_settings['wdwc_cj_unix_' . $job],
		   );

		   $settings['wdwc_cj_interval_' . $job] = array(
			   'name' => 'Intervall zwischen Cronjobs: ' . $job . ' in Sekunden (Minimum: 1)',
			   'desc' => 'Soll der Cronjob nicht aktiv sein, bitte 0 eintragen',
			   'section' => 'general',
			   'type' => 'number',
			   'size' => 'regular',
			   'step' => '1',
			   'min' => 0,
			   'default' => $default_settings['wdwc_cj_interval_' . $job],
		   );

	   }

		return apply_filters( 'wdwc_settings', $settings );
	}

	function checkbox_callback( $args ) {

		$checked = isset( $this->options[ $args['id'] ] ) ? checked( 1, $this->options[ $args['id'] ], false ) : '';
		echo '<input type="checkbox" id="wdwc_settings[' . esc_html ( $args['id'] ) . ']" name="wdwc_settings[' . esc_html ( $args['id'] ) . ']" value="1" ' . esc_html ( $checked ) . '/>';
		echo '<label for="wdwc_settings[' . esc_html ( $args['id'] ) . ']"> ' . esc_html ( $args['desc'] ) . '</label>';

	}

    function text_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) ) {
			$value = $this->options[ $args['id'] ];
		} else {
			$value = isset( $args['default'] ) ? $args['default'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		echo '<input type="text" class="' . esc_html ( $size ) . '-text" id="wdwc_settings[' . esc_html ( $args['id'] ) . ']" name="wdwc_settings[' . esc_html ( $args['id'] ) . ']" value="' . esc_html ( $value ) . '"/>';
		echo '<p class="description">' . esc_html ( $args['desc'] ) . '</p>';

	}

	function datetime_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) ) {
			$value = $this->options[ $args['id'] ];
		} else {
			$value = isset( $args['default'] ) ? $args['default'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		echo '<input type="datetime-local" class="' . esc_html ( $size ) . '-text" id="wdwc_settings[' . esc_html ( $args['id'] ) . ']" name="wdwc_settings[' . esc_html ( $args['id'] ) . ']" value="' . esc_html ( $value ) . '"/>';
		echo '<p class="description">' . esc_html ( $args['desc'] ) . '</p>';

	}

	function textbox_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) ) {
			$value = $this->options[ $args['id'] ];
		} else {
			$value = isset( $args['default'] ) ? $args['default'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		echo '<textarea type="text" rows="15" class="' . esc_html ( $size ) . '-text" id="wdwc_settings[' . esc_html ( $args['id'] ) . ']" name="wdwc_settings[' . esc_html ( $args['id'] ) . ']" />' . esc_html ( str_replace( ";", "\r\n", trim( $value ) ) ) . '</textarea>';
		echo '<p class="description">' . esc_html ( $args['desc'] ) . '</p>';

	}
	
	function number_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) ) {
			$value = $this->options[ $args['id'] ];
		} else {
			$value = isset( $args['default'] ) ? $args['default'] : '';
		}
		$step = ( isset( $args['step'] ) && ! is_null( $args['step'] ) ) ? $args['step'] : '0.01';
		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		echo '<input type="number" step="' . esc_html ( $step ) . '" min="' . esc_html ( $args['min'] ) . '" max="' . esc_html ( $args['max'] ) . '" class="' . esc_html ( $size ) . '-text" id="wdwc_settings[' . esc_html ( $args['id'] ) . ']" name="wdwc_settings[' . esc_html ( $args['id'] ) . ']" value="' . esc_html ( $value ) . '"/>';
		echo '<p class="description">' . esc_html ( $args['desc'] ) . '</p>';

	}

	function password_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) ) {
			$value = $this->options[ $args['id'] ];
		} else {
			$value = isset( $args['default'] ) ? $args['default'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		echo '<input type="password" class="' . esc_html ( $size ) . '-text" id="wdwc_settings[' . esc_html ( $args['id'] ) . ']" name="wdwc_settings[' . esc_html ( $args['id'] ) . ']" value="' . esc_html ( $value ) . '"/>';
		echo '<p class="description">' . esc_html ( $args['desc'] ) . '</p>';

	}

    function radio_callback( $args ) {

		if ( ! empty( $args['options'] ) ) :
			foreach ( $args['options'] as $key => $option ) :
				$checked = false;

				if ( isset( $this->options[ $args['id'] ] ) && $this->options[ $args['id'] ] == $key ) {
					$checked = true;
				} elseif ( isset( $args['default'] ) && $args['default'] == $key && ! isset( $this->options[ $args['id'] ] ) ) {
					$checked = true;
				}

				echo '<input name="wdwc_settings[' . esc_html ( $args['id'] ) . ']"" id="wdwc_settings[' . esc_html ( $args['id'] ) . '][' . esc_html ( $key ) . ']" type="radio" value="' . esc_html ( $key ) . '" ' . checked( true, $checked, false ) . '/>&nbsp;';
				echo '<label for="wdwc_settings[' . esc_html ( $args['id'] ) . '][' . esc_html ( $key ) . ']">' . esc_html ( $option ) . '</label><br/>';

			endforeach;
		endif;
		echo '<p class="description">' . esc_html ( $args['desc'] ) . '</p>';
	}

    function select_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) ) {
			$value = $this->options[ $args['id'] ];
		} else {
			$value = isset( $args['default'] ) ? $args['default'] : '';
		}

		echo '<select id="wdwc_settings[' . esc_html ( $args['id'] ) . ']" name="wdwc_settings[' . esc_html ( $args['id'] ) . ']"/>';

		foreach ( $args['options'] as $option => $name ) :
			$selected = selected( $option, $value, false );
			echo '<option value="' . esc_html ( $option ) . '" ' . esc_html ( $selected ) . '>' . esc_html ( $name ) . '</option>';
		endforeach;

		echo '</select>';
		echo '<p class="description">' . esc_html ( $args['desc'] ) . '</p>';

	}

    function reset_callback( $args ) {

		echo '<input type="submit" class="button" name="wdwc_reset_defaults" value="RESET"/>';
		echo '<p class="description">' . esc_html ( $args['desc'] ) . '</p>';

	}

    function missing_callback( $args ) {
		printf( 'The callback function used for the <strong>%s</strong> setting is missing.', $args['id'] );
	}
}

// Run Setting Class.
WDWC_Settings::instance();
