<?php
/**
 * Update date time formats
 *
 * @package WPML\ST
 */

namespace WPML\ST\WpSettings;

use WPML_Admin_Texts;
use function WPML\Container\make;
use WPML\FP\Obj;
use WPML\FP\Fns;
use function WPML\FP\partialRight;

class DateTimeFormatsDefaultLocaleValues implements \IWPML_Action {

	const STRING_NAME_DATE_FORMAT               = 'date_format';
	const STRING_NAME_TIME_FORMAT               = 'time_format';
	const STRING_NAME_LINKS_UPDATED_DATE_FORMAT = 'links_updated_date_format';

	/**
	 * DefaultMO to find default translation of strings
	 *
	 * @var DefaultMO $default_mo
	 */
	private $default_mo;

	/**
	 * Translated strings
	 *
	 * @var string[] $cached_strings
	 */
	private $cached_strings = [];

	public function __construct( DefaultMO $default_mo ) {
		$this->default_mo = $default_mo;
	}
	/**
	 * Add WP Hooks
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'wpml_update_active_languages', [ $this, 'update_action' ] );
		add_filter( 'pre_update_option_' . WPML_Admin_Texts::TRANSLATABLE_NAMES_SETTING, [ $this, 'update_admin_option_action' ] );
	}

	/**
	 * Update default values for date time formats action hook
	 *
	 * @param array $old_languages - list of old languages.
	 *
	 * @return void
	 */
	public function update_action( $old_languages = array() ) {
		$formats      = [
			self::STRING_NAME_DATE_FORMAT,
			self::STRING_NAME_TIME_FORMAT,
			self::STRING_NAME_LINKS_UPDATED_DATE_FORMAT,
		];
		$updateFormat = partialRight( [ $this, 'update_format' ], $old_languages );

		wpml_collect( $formats )
			->each( Fns::unary( $updateFormat ) );
	}

	/**
	 * Update default values for date time formats action hook if admin texts added
	 *
	 * @param mixed $new_value - new value of WPML_Admin_Texts::TRANSLATABLE_NAMES_SETTING option.
	 *
	 * @return mixed
	 */
	public function update_admin_option_action( $new_value ) {

		$formats = [
			self::STRING_NAME_DATE_FORMAT,
			self::STRING_NAME_TIME_FORMAT,
			self::STRING_NAME_LINKS_UPDATED_DATE_FORMAT,
		];

		$isValidFormat = function( $key ) use ( $formats ) {
			return in_array( $key, $formats, true );
		};

		$isEnabled = function( $value ) {
			return 1 === (int) $value;
		};

		wpml_collect( $new_value )
			->filter(
				function( $value, $key ) use ( $isValidFormat, $isEnabled ) {
					return $isValidFormat( $key ) && $isEnabled( $value );
				}
			)
			->keys()
			->diff( $this->cached_strings )
			->each( Fns::unary( [ $this, 'update_format' ] ) );

		return $new_value;
	}

	/**
	 * Undocumented function
	 *
	 * @param string $name - name of the datetime format wp option.
	 * @param array  $old_languages - list of old languages.
	 *
	 * @return void
	 */
	public function update_format( $name, $old_languages = array() ) {
		$wpml_st_string_factory = make( \WPML_ST_String_Factory::class );

		$string = $wpml_st_string_factory->find_admin_by_name( $name );
		if ( $string && $string->string_id() ) {
			$this->set_default_translations( $string, $old_languages );
			$this->cached_strings[] = $name;
		}
	}

	/**
	 * Use translation string to add default translation for existing languages from mo files
	 *
	 * @param \WPML_ST_String $string - string object.
	 * @param array           $old_languages - list of old languages.
	 *
	 * @return void
	 */
	private function set_default_translations( \WPML_ST_String $string, $old_languages = array() ) {
		global $sitepress, $wpdb;

		$existing = wpml_collect( $string->get_translation_statuses() )
			->pluck( 'language' )
			->merge( wpml_collect( $old_languages )->keys() )
			->unique()
			->all();

		$languages = wpml_collect( $sitepress->get_active_languages() )
			->keyBy( 'code' )
			->except( $existing )
			->values();

		foreach ( $languages as $lang ) {
			$translation = $this->default_mo->translate( $string->get_name(), 'default', $lang['default_locale'] );

			if ( ! empty( $translation ) ) {
				$string->set_translation( $lang['code'], $translation, ICL_TM_COMPLETE );
				$wpdb->update(
					$wpdb->prefix . 'icl_string_translations',
					array(
						'mo_string' => $translation,
					),
					array(
						'string_id' => $string->string_id(),
						'language'  => $lang['code'],
					)
				);
			}
		}
	}
}
