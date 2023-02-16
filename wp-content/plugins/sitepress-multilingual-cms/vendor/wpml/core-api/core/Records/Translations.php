<?php

namespace WPML\Records;

use WPML\Collect\Support\Traits\Macroable;
use WPML\Collect\Support\Collection;
use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\curryN;

/**
 * Class Translations
 * @package WPML\Records
 *
 * @method static callable|array getByTrid( ...$trid )
 *
 * Returns array of records from wp_icl_translations matching given $trid
 */
class Translations {

	const OLDEST_FIRST = 'ASC';
	const NEWEST_FIRST = 'DESC';

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'getByTrid', curryN( 1, function ( $trid ) {
			global $wpdb;

			$sql = "SELECT * FROM {$wpdb->prefix}icl_translations WHERE trid = %d";

			return $wpdb->get_results( $wpdb->prepare( $sql, $trid ) );
		} ) );
	}

	/**
	 * @param array|null  $order
	 * @param string|null $postType
	 *
	 * @return callable|Collection
	 */
	public static function getForPostType( array $order = null, $postType = null ) {
		$get = function ( array $order, $postType ) {
			global $wpdb;

			$orderBy = Obj::propOr( self::NEWEST_FIRST, $postType, $order );
			$sql = "SELECT translations.element_id, translations.language_code, translations.source_language_code, translations.trid, translation_status.status, translation_status.needs_update
					FROM {$wpdb->prefix}icl_translations translations
					LEFT JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.translation_id = translations.translation_id
					WHERE translations.element_type = %s
					ORDER BY translations.element_id $orderBy
					";

			return wpml_collect( $wpdb->get_results( $wpdb->prepare( $sql, 'post_' . $postType ) ) );
		};

		return call_user_func_array( curryN( 2, $get ), func_get_args() );
	}

	/**
	 * @param string|null     $lang
	 * @param Collection|null $translations
	 *
	 * @return callable|Collection
	 */
	public static function getSourceInLanguage( $lang = null, Collection $translations = null ) {
		$getSource = function ( $defaultLang, Collection $translations ) {
			return self::getSource( $translations )->filter( Relation::propEq( 'language_code', $defaultLang ) )->values();
		};

		return call_user_func_array( curryN( 2, $getSource ), func_get_args() );
	}

	public static function getSource( Collection $translations = null) {
		$getSource = function ( Collection $translations ) {
			return $translations->filter( Relation::propEq( 'source_language_code', null ) )->values();
		};

		return call_user_func_array( curryN( 1, $getSource ), func_get_args() );
	}

	public static function getSourceByTrid( $trid = null ) {
		$getSourceByTrid = function ( $trid ) {
			return self::getSource( \wpml_collect( self::getByTrid( $trid ) ) )->first();
		};

		return call_user_func_array( curryN( 1, $getSourceByTrid ), func_get_args() );
	}

}

Translations::init();