<?php

namespace WPML\TM\Jobs\Utils;

use function WPML\Container\make;
use WPML_Post_Translation;

class ElementLinkFactory {

	public static function create() {
		/**
		 * @var WPML_Post_Translation $wpml_post_translations;
		 */
		global $wpml_post_translations;

		return make(
			ElementLink::class,
			[ ':postTranslation' => $wpml_post_translations ]
		);
	}
}
