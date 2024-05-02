<?php

namespace ACFML\Helper;

interface ContentType {

	/**
	 * @return string
	 */
	public function getInternalPostType();

	/**
	 * @return string
	 */
	public function getLabelTranslationsPackageSlug();

	/**
	 * @param  int $internalPostId ID of the internal post type defining the custom object.
	 *
	 * @return string|null
	 */
	public function getObjectSlug( $internalPostId );

	/**
	 * @return string|null
	 */
	public function getWpmlSyncOptionKey();

	/**
	 * @return string
	 */
	public function getEditorScreenSlug();

	/**
	 * @return bool
	 */
	public function isEditorScreen();

	/**
	 * @return bool
	 */
	public function isListingScreen();

	/**
	 * @return string
	 */
	public function getTranslationInfoLabel();

	/**
	 * @return string
	 */
	public function getLabelsTranslationInfoLabel();

	/**
	 * @return string
	 */
	public function getTranslationSettingsUrl();

}
