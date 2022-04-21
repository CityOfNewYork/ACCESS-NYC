<?php


namespace ACFML;

class FieldReferenceAdjuster {

	/**
	 * @var \SitePress
	 */
	private $sitepress;
	/**
	 * @var string
	 */
	private $originalReference;
	/**
	 * @var int|string
	 */
	private $displayedPostId;
	const GROUP_POST_TYPE = 'acf-field-group';
	const FIELD_POST_TYPE = 'acf-field';
	/**
	 * @var string
	 */
	private $fieldName;

	/**
	 * FieldReferenceAdjuster constructor.
	 *
	 * @param \SitePress $sitepress
	 */
	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		if ( $this->hooksShouldBeRegistered() ) {
			add_filter( 'acf/load_reference', [ $this, 'translatedFieldReference' ], 10, 3 );
		}
	}

	/**
	 * Filter for "acf/load_reference" to get field_reference from translated version of the field.
	 *
	 * @param string $originalReference Field reference being filtered (field_5b3...).
	 * @param string $fieldName         ACF field name.
	 * @param int    $displayedPostId   ID of the post where field belongs to.
	 *
	 * @return string
	 */
	public function translatedFieldReference( $originalReference, $fieldName, $displayedPostId ) {
		$this->originalReference = $originalReference;
		$this->displayedPostId   = $displayedPostId;
		$this->fieldName         = $fieldName;

		if ( $this->referenceLanguageDifferentThanPostLanguage() ) {
			return $this->getTranslatedFieldReference();
		}

		return $originalReference;
	}

	/**
	 * Checks conditions if hooked logic should be loaded.
	 *
	 * Checks if this is front-end request and field group is set as translatable.
	 *
	 * @return bool
	 */
	private function hooksShouldBeRegistered() {
		return $this->isFrontEndRequest()
				&& $this->fieldGroupsAreTranslatable();
	}

	/**
	 * Checks if this is front-end request.
	 *
	 * @return bool
	 */
	private function isFrontEndRequest() {
		return ! \is_admin();
	}

	/**
	 * Checks if acf-field-group post type is set as translatable.
	 *
	 * @return bool
	 */
	private function fieldGroupsAreTranslatable() {
		return $this->sitepress->is_translated_post_type( self::GROUP_POST_TYPE );
	}

	/**
	 * Compares field language with post language.
	 *
	 * @return bool
	 */
	private function referenceLanguageDifferentThanPostLanguage() {
		return $this->getFieldLanguage() !== $this->getPostLanguage();
	}

	/**
	 * Get language of the post where field belongs to.
	 *
	 * @return string Language code or empty string.
	 */
	private function getPostLanguage() {
		return $this->getLanguageCode( $this->displayedPostId );
	}

	/**
	 * Get language of field group, where field belongs to.
	 *
	 * We have field reference here. ACf fields are always untranslatable post types,
	 * so the language for this is never set. But fields belongs to field groups which
	 * has language set.
	 *
	 * This method gets the id of field group (from field's post_parent) and checks
	 * its language.
	 *
	 * @return string Language code or empty string.
	 */
	private function getFieldLanguage() {
		$fieldsParent = $this->getFieldsParentByReference();
		if ( $fieldsParent ) {
			return $this->getLanguageCode( $fieldsParent, sprintf( 'post_%s', self::GROUP_POST_TYPE ) );
		}
		return '';
	}

	/**
	 * Get language code of given post.
	 *
	 * @param int    $postId   Post ID.
	 * @param string $postType Post type.
	 *
	 * @return string Language code or empty string.
	 */
	private function getLanguageCode( $postId, $postType = 'post_post' ) {
		$languageDetails = $this->sitepress->get_element_language_details( $postId, $postType );
		return isset( $languageDetails->language_code ) ? (string) $languageDetails->language_code : '';
	}

	/**
	 * Gets reference string for translated version of the field.
	 *
	 * The final boss.
	 * - Checks what is field parent (field group id).
	 * - Gets translations of this field group.
	 * - Gets translation of field group in current language.
	 * - Gets children of this translation (translated field).
	 * - Finally gets this field's reference string.
	 *
	 * @return string The reference string (field_5b5....).
	 */
	private function getTranslatedFieldReference() {
		$fieldsParent = $this->getFieldsParentByReference();
		if ( $fieldsParent ) {
			$trid                  = $this->sitepress->get_element_trid( $fieldsParent, sprintf( 'post_%s', self::GROUP_POST_TYPE ) );
			$translatedFieldGroups = $this->sitepress->get_element_translations( $trid, sprintf( 'post_%s', self::GROUP_POST_TYPE ) );
			if ( isset( $translatedFieldGroups[ $this->sitepress->get_current_language() ]->element_id ) ) {
				return $this->getReferenceFromChildrenOfFieldGroup( $translatedFieldGroups[ $this->sitepress->get_current_language() ]->element_id );
			}
		}
		return $this->originalReference;
	}

	/**
	 * Find field object with a processed original reference.
	 *
	 * @return int|null
	 */
	private function getFieldsParentByReference() {
		$posts = get_posts(
			[
				'name'        => $this->originalReference,
				'post_type'   => self::FIELD_POST_TYPE,
				'numberposts' => 1,
			]
		);
		return ( isset( $posts[0] ) && is_a( $posts[0], 'WP_Post' ) ) ? $posts[0]->post_parent : null;
	}

	/**
	 * Find field which is children of the field group and return reference key saved in post_name.
	 *
	 * @param int $groupId The field group ID.
	 *
	 * @return string|null
	 */
	private function getReferenceFromChildrenOfFieldGroup( $groupId ) {
		$posts = get_posts(
			[
				'numberposts'  => -1,
				'post_type'    => self::FIELD_POST_TYPE,
				'post_parent'  => $groupId,
				'post_status'  => 'publish',
			]
		);
		foreach( $posts as $post ) {
			if ( $post->post_excerpt === $this->fieldName ) {
				return $post->post_name;
			}
		}
		return null;
	}
}
