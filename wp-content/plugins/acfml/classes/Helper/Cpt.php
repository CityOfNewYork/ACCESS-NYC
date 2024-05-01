<?php

namespace ACFML\Helper;

use WPML\API\Sanitize;
use WPML\FP\Obj;

class Cpt implements ContentType {

	const CPT         = 'acf-post-type';
	const SCREEN_SLUG = 'acf-post-type';
	const SYNC_OPTION = 'custom_posts_sync_option';

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
		return Obj::propOr( null, 'post_type', $objectSettings );
	}

	/**
	 * @return string
	 */
	public function getLabelTranslationsPackageSlug() {
		return \ACFML\Strings\Package::CPT_PACKAGE_KIND_SLUG;
	}

	/**
	 * @return string
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
		return __( 'Post Type Translation', 'acfml' );
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
		return admin_url( 'admin.php?page=tm%2Fmenu%2Fsettings#ml-content-setup-sec-7' );
	}

}
