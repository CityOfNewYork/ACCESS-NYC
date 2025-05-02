<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\Compatibility\BaseTranslationGuiLabels;

class TranslationGuiLabels extends BaseTranslationGuiLabels {

	const POST_TYPE_LANDING_PAGE      = 'e-landing-page';
	const POST_TYPE_FLOATING_ELEMENTS = 'e-floating-buttons';
	const POST_TYPE_TEMPLATE          = 'elementor_library';

	/**
	 * @return string[]
	 */
	protected function getPostTypes() {
		return [
			self::POST_TYPE_LANDING_PAGE,
			self::POST_TYPE_FLOATING_ELEMENTS,
			self::POST_TYPE_TEMPLATE,
		];
	}

	/**
	 * @return string
	 */
	protected function getFormat() {
		// Translators: %s: Post type label. For example, Elementor Landing Pages.
		return __( 'Elementor %s', 'sitepress' );
	}

	/**
	 * @param string $label
	 * @param string $name
	 * @param bool   $isPlural
	 *
	 * @return string
	 */
	protected function formatLabel( $label, $name, $isPlural ) {
		if ( $name === self::POST_TYPE_TEMPLATE && $isPlural ) {
			return __( 'Elementor Templates');
		}
		return sprintf( $this->getFormat(), $label );
	}

}
