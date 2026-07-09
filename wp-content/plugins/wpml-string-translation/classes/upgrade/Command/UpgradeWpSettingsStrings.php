<?php
/**
 * Class WPML_ST_Upgrade_DB_Wp_Settings_Strings
 *
 * @package WPML\ST
 */

namespace WPML\ST\Upgrade\Command;

use WPML\ST\WpSettings\DateTimeFormatsDefaultLocaleValues;
use WPML\ST\WpSettings\DefaultMO;
use WPML\ST\MO\Generate\Process\ProcessFactory;
use function WPML\Container\make;


class UpgradeWpSettingsStrings implements \IWPML_St_Upgrade_Command {
	/**
	 * Create date and time related ST strings
	 *
	 * @return bool
	 */
	public function run() {
		global $sitepress_settings;
		if ( ! isset( $sitepress_settings['st']['db_ok_for_gettext_context'] ) ) {
			return false;
		}
		$formats = [
			DateTimeFormatsDefaultLocaleValues::STRING_NAME_DATE_FORMAT,
			DateTimeFormatsDefaultLocaleValues::STRING_NAME_TIME_FORMAT,
			DateTimeFormatsDefaultLocaleValues::STRING_NAME_LINKS_UPDATED_DATE_FORMAT,
		];

		$wpml_st_string_factory = make( \WPML_ST_String_Factory::class );
		$date_time_format       = new DateTimeFormatsDefaultLocaleValues( new DefaultMO() );

		wpml_collect( $formats )->each(
			function( $name ) use ( $wpml_st_string_factory, $date_time_format ) {
				$string = $wpml_st_string_factory->find_by_name( $name );
				if ( ! $string || ! $string->string_id() ) {
					wpml_st_load_admin_texts()->icl_register_admin_options( [ $name => 'on' ] );
					$date_time_format->update_format( $name );
				}
			}
		);
		return true;
	}

	public function run_ajax() {
		return $this->run();
	}

	public function run_frontend() {
	}

	/**
	 * Migration version
	 *
	 * @return string
	 */
	public static function get_command_id() {
		return __CLASS__;
	}
}
