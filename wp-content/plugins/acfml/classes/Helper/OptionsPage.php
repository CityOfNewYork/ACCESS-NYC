<?php

namespace ACFML\Helper;

use WPML\API\Sanitize;
use WPML\FP\Obj;

class OptionsPage implements ContentType {

	const CPT         = 'acf-ui-options-page';
	const SCREEN_SLUG = 'acf-ui-options-page';
	const SYNC_OPTION = null;

	/**
	 * @return string
	 */
	public function getInternalPostType() {
		return self::CPT;
	}

	/**
	 * @param  int $internalPostId ID of the internal post type defining the custom object.
	 *
	 * @return string|null
	 */
	public function getObjectSlug( $internalPostId ) {
		$internalPostContent = get_post_field( 'post_content', $internalPostId, 'raw' );
		$objectSettings      = maybe_unserialize( $internalPostContent );
		return Obj::propOr( null, 'menu_slug', $objectSettings );
	}

	/**
	 * @return string
	 */
	public function getLabelTranslationsPackageSlug() {
		return \ACFML\Strings\Package::OPTION_PAGE_PACKAGE_KIND_SLUG;
	}

	/**
	 * @return null
	 */
	public function getWpmlSyncOptionKey() {
		return self::SYNC_OPTION;
	}

	/**
	 * @return string
	 */
	public function getEditorScreenSlug() {
		return self::SCREEN_SLUG;
	}

	/**
	 * @return bool
	 */
	public function isEditorScreen() {
		return function_exists( 'acf_is_screen' ) && acf_is_screen( self::SCREEN_SLUG );
	}

	/**
	 * @return bool
	 */
	public function isListingScreen() {
		global $pagenow;

		return 'edit.php' === $pagenow
			&& self::CPT === Sanitize::stringProp( 'post_type', $_GET ); // phpcs:ignore
	}

	/**
	 * @return string
	 */
	public function getTranslationInfoLabel() {
		return '';
	}

	/**
	 * @return string
	 */
	public function getLabelsTranslationInfoLabel() {
		return __( 'Labels Translation', 'acfml' );
	}

	/**
	 * @return string
	 */
	public function getTranslationSettingsUrl() {
		return '';
	}

}
