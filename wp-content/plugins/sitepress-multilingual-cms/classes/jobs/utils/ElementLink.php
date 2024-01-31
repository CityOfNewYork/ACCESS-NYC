<?php

namespace WPML\TM\Jobs\Utils;

use WPML\TM\Menu\PostLinkUrl;
use WPML_Post_Translation;

class ElementLink {

	/** @var PostLinkUrl $postLinkUrl */
	private $postLinkUrl;

	/** @var WPML_Post_Translation $postTranslation */
	private $postTranslation;

	public function __construct( PostLinkUrl $postLinkUrl, WPML_Post_Translation $postTranslation ) {
		$this->postLinkUrl     = $postLinkUrl;
		$this->postTranslation = $postTranslation;
	}

	public function getOriginal( \WPML_TM_Post_Job_Entity $job ) {
		return $this->get( $job, $job->get_original_element_id() );
	}

	/**
	 * @param \WPML_TM_Post_Job_Entity $job
	 *
	 * @return string
	 */
	public function getTranslation( \WPML_TM_Post_Job_Entity $job ) {
		if ( $this->isExternalType( $job->get_element_type_prefix() ) ) {
			return '';
		}

		$translatedId = $this->postTranslation->element_id_in( $job->get_original_element_id(), $job->get_target_language() );

		if ( $translatedId ) {
			return $this->get( $job, $translatedId );
		}

		return '';
	}

	/**
	 * @param \WPML_TM_Post_Job_Entity $job
	 * @param string|int|null $elementId
	 *
	 * @return mixed|string|void
	 */
	private function get( \WPML_TM_Post_Job_Entity $job, $elementId = null ) {
		$elementId   = $elementId ?: $job->get_target_language();
		$elementType = preg_replace( '/^' . $job->get_element_type_prefix() . '_/', '', $job->get_element_type() );

		if ( $this->isExternalType( $job->get_element_type_prefix() ) ) {
			$tmPostLink = apply_filters( 'wpml_external_item_url', '', $elementId );
		} else {
			$tmPostLink = $this->postLinkUrl->viewLinkUrl( $elementId );
		}
		$tmPostLink = apply_filters(
			'wpml_document_view_item_link',
			$tmPostLink,
			'',
			$job,
			$job->get_element_type_prefix(),
			$elementType
		);

		return $tmPostLink;
	}

	/**
	 * @param string $elementTypePrefix
	 *
	 * @return bool
	 */
	private function isExternalType( $elementTypePrefix ) {
		return apply_filters( 'wpml_is_external', false, $elementTypePrefix );
	}
}
