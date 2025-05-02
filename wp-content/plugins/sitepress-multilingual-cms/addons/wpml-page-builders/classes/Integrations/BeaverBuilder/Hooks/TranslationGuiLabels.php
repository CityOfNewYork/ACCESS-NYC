<?php

namespace WPML\PB\BeaverBuilder\Hooks;

use WPML\Compatibility\BaseTranslationGuiLabels;

class TranslationGuiLabels extends BaseTranslationGuiLabels {

	const POST_TYPE_TEMPLATE = 'fl-builder-template';

	/**
	 * @return string[]
	 */
	protected function getPostTypes() {
		return [ self::POST_TYPE_TEMPLATE ];
	}

	/**
	 * @return string
	 */
	protected function getFormat() {
		// Translators: %s: Post type label. For example, Beaver Builder Templates.
		return __( 'Beaver Builder %s', 'sitepress' );
	}

}
