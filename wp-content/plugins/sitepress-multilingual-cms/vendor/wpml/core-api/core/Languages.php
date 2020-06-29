<?php

namespace WPML\Element\API;

use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static array getActive()
 *
 * It returns an array of the active languages.
 *
 * The returned array is indexed by language code and every element has the following structure:
 * ```
 *  'fr' => [
 *      'code'           => 'fr',
 *      'id'             => 3,
 *      'english_name'   => 'French',
 *      'native_name'    => 'Français',
 *      'major'          => 1,
 *      'default_locale' => 'fr_FR',
 *      'encode_url'     => 0,
 *      'tag'            => 'fr ,
 *      'display_name'   => 'French
 *  ]
 * ```
 *
 */
class Languages {
	use Macroable;

	/**
	 * @ignore
	 */
	public static function init() {
		global $sitepress;

		self::macro( 'getActive', function () use ( $sitepress ) {
			return $sitepress->get_active_languages();
		} );

	}
}

Languages::init();