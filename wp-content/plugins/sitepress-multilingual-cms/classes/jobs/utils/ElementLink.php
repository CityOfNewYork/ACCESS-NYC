<?php

namespace WPML\TM\Jobs\Utils;

use stdClass;
use WPML_Post_Translation;
use WPML_TM_Post_Link_Factory;

class ElementLink {

	/** @var WPML_TM_Post_Link_Factory $postLinkFactory */
	private $postLinkFactory;

	/** @var WPML_Post_Translation $postTranslation */
	private $postTranslation;

	public function __construct( WPML_TM_Post_Link_Factory $postLinkFactory, WPML_Post_Translation $postTranslation ) {
		$this->postLinkFactory = $postLinkFactory;
		$this->postTranslation = $postTranslation;
	}

	/**
	 * @param stdClass $job
	 *
	 * @return string
	 */
	public function getOriginal( $job ) {
		return $this->get( $job, __( 'View original', 'wpml-translation-management' ) );
	}

	/**
	 * @param stdClass $job
	 *
	 * @return string
	 */
	public function getTranslation( $job ) {
		if ( $this->isExternalType( $job->element_type_prefix ) ) {
			return '';
		}

		$translatedId = $this->postTranslation->element_id_in( $job->original_doc_id, $job->language_code );

		if ( $translatedId ) {
			return $this->get( $job, __( 'View', 'wpml-translation-management' ), $translatedId );
		}

		return '';
	}

	/**
	 * @param stdClass    $job
	 * @param string      $viewText
	 * @param string|null $elementId
	 *
	 * @return mixed|string|void
	 */
	private function get( $job, $viewText, $elementId = null ) {
		$elementId   = $elementId ?: $job->original_doc_id;
		$elementType = preg_replace( '/^' . $job->element_type_prefix . '_/', '', $job->original_post_type );

		if ( $this->isExternalType( $job->element_type_prefix ) ) {
			$url        = apply_filters( 'wpml_external_item_url', '', $elementId );
			$tmPostLink = '<a href="' . $url . '">' . $viewText . '</a>';
		} else {
			$tmPostLink = $this->postLinkFactory->view_link_anchor( $elementId, $viewText, '_blank' );
		}

		$tmPostLink = apply_filters(
			'wpml_document_view_item_link',
			$tmPostLink,
			$viewText,
			$job,
			$job->element_type_prefix,
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
