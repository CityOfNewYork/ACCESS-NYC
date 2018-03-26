<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer;
use DateTime;
use DateTimeZone;

/**
 * GatherContent Plugin Utilities
 *
 * @since 3.0.0
 */
class Utils extends Base {

	/**
	 * A flag to check if SCRIPT_DEBUG is enabled.
	 *
	 * @var boolean
	 */
	protected static $script_debug = false;

	/**
	 * A flag to check if this is an ajax request.
	 *
	 * @var boolean
	 */
	protected static $doing_ajax = false;

	/**
	 * Constructor. Sets the static vars.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		self::$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		self::$doing_ajax   = defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * Check if SCRIPT_DEBUG is enabled.
	 *
	 * @return string
	 */
	public static function script_debug() {
		return self::$script_debug || Debug::debug_mode();
	}

	/**
	 * Get the suffix for script/style assets.
	 *
	 * @return string
	 */
	public static function asset_suffix() {
		return self::script_debug() ? '' : '.min';
	}

	/**
	 * Check if ajax request.
	 *
	 * @return bool
	 */
	public static function doing_ajax() {
		return self::$doing_ajax;
	}

	/**
	 * Determines if $date_check is within allowance of $date_compare.
	 *
	 * @since  3.0.0
	 *
	 * @param  mixed   $date_check   Date to check.
	 * @param  mixed   $date_compare Date to compare with.
	 * @param  integer $allowance    Allowed tolerance.
	 *
	 * @return bool                  Whether $date_check is current with $date_compare.
	 */
	public static function date_current_with( $date_check, $date_compare, $allowance = 0 ) {
		$date_compare = strtotime( $date_compare );
		$date_check   = strtotime( $date_check );
		$difference   = $date_compare - $date_check;

		return $difference < $allowance;
	}

	/**
	 * Utility function for doing array_map recursively.
	 *
	 * @since  3.0.0
	 *
	 * @param  callable $callback Callable function.
	 * @param  array    $array    Array to recurse.
	 *
	 * @return array              Updated array.
	 */
	static function array_map_recursive( $callback, $array ) {
		foreach ( $array as $key => $value ) {
			if ( is_array( $array[ $key ] ) ) {
				$array[ $key ] = self::array_map_recursive( $callback, $array[ $key ] );
			} else {
				$array[ $key ] = call_user_func( $callback, $array[ $key ] );
			}
		}
		return $array;
	}

	/**
	 * Convert a UTC date to human readable date using the WP timezone.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $utc_date UTC date.
	 *
	 * @return string           Human readable relative date.
	 */
	public static function relative_date( $utc_date ) {
		static $tzstring = null;

		// Get the WP timezone string.
		if ( null === $tzstring ) {
			$current_offset = get_option( 'gmt_offset' );
			$tzstring       = get_option( 'timezone_string' );
			$allowed_zones  = timezone_identifiers_list();

			// Remove old Etc mappings. Fallback to gmt_offset.
			if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
				$tzstring = '';
			}

			if ( ! in_array( $tzstring, $allowed_zones, true ) ) {
				$tzstring = '';
			}

			if ( empty( $tzstring ) ) {
				$tzstring = timezone_name_from_abbr( '', $current_offset, 0 );
				$tzstring = false !== $tzstring ? $tzstring : timezone_name_from_abbr( '', 0, 0 );
			}
		}

		try {
			$date = new DateTime( $utc_date, new DateTimeZone( $tzstring ) );
		} catch ( \Exception $e ) {
			$date = new DateTime( $utc_date );
		}

		$time = $date->getTimestamp();
		$currtime = time();
		$time_diff = $currtime - $time;

		if ( $time_diff >= 0 && $time_diff < DAY_IN_SECONDS ) {
			$date = sprintf( __( '%s ago' ), human_time_diff( $time ) );
		} else {
			$date = date_i18n( __( 'Y/m/d' ), $time );
		}

		return $date;
	}

	/**
	 * Get the GatherContent item field type nice-name.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $type The type to get the name for. 'all' to get the entire array.
	 *
	 * @return mixed        The type nice-name, or the entire types array.
	 */
	public static function gc_field_type_name( $type ) {
		static $types = null;

		if ( null === $types ) {
			$types = apply_filters( 'gc_field_type_names', array(
				'text'            => __( 'Text', 'gathercontent-import' ),
				'text_rich'       => __( 'Rich Text', 'gathercontent-import' ),
				'text_plain'      => __( 'Plain Text', 'gathercontent-import' ),
				'choice_radio'    => __( 'Muliple Choice', 'gathercontent-import' ),
				'choice_checkbox' => __( 'Checkboxes', 'gathercontent-import' ),
				'files'           => __( 'Attachment', 'gathercontent-import' ),
			) );
		}

		if ( 'all' === $type ) {
			return $types;
		}

		return isset( $types[ $type ] ) ? $types[ $type ] : $type;
	}

	/**
	 * Get the GatherContent wizard step label.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $step The step to get the name for. 'all' to get the entire array.
	 *
	 * @return mixed        The step nice-name, or the entire types array.
	 */
	public static function get_step_label( $step ) {
		static $labels = null;

		if ( null === $labels ) {
			$labels = array(
				'accounts' => esc_html__( 'Select an account:', 'gathercontent-importer' ),
				'projects' => esc_html__( 'Select a project:', 'gathercontent-importer' ),
				'templates' => esc_html__( 'Select a template:', 'gathercontent-importer' ),
				'mappings' => sprintf( esc_html_x( 'Select a %s:', 'Select a template mapping', 'gathercontent-importer' ), General::get_instance()->admin->mapping_wizard->mappings->args->labels->singular_name ),
				'import' => esc_html__( 'Import Items:', 'gathercontent-importer' ),
			);
		}

		if ( 'all' === $step ) {
			return $labels;
		}

		return isset( $labels[ $step ] ) ? $labels[ $step ] : $step;
	}

	/**
	 * Check if enqueued version of script is at least $version.
	 *
	 * @since  3.0.0.8
	 *
	 * @param  string $handle   The script's registered handle.
	 * @param  string  $version Version string to compare.
	 *
	 * @return bool             Result of comparison check.
	 */
	public static function enqueued_at_least( $handle, $version ) {
		$wpjs = wp_scripts();
		return isset( $wpjs->registered[ $handle ] )
			&& version_compare( $wpjs->registered[ $handle ]->ver, $version, '>=' );
	}
}
