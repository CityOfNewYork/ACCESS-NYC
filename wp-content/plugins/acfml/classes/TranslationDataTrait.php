<?php

namespace ACFML;

use ACFML\Helper\ContentType;
use WPML\FP\Obj;

trait TranslationDataTrait {

	/** @var ContentType $contentTypeHelper */
	private $contentTypeHelper;

	/** @var bool */
	private $hasTranslationType;

	public function __construct( ContentType $contentTypeHelper ) {
		$this->contentTypeHelper  = $contentTypeHelper;
		$this->hasTranslationType = ( null !== $this->contentTypeHelper->getWpmlSyncOptionKey() );
	}

	/**
	 * @param  string|null $objectSlug
	 *
	 * @return int
	 */
	protected function getObjectTranslationType( $objectSlug ) {
		if ( $objectSlug ) {
			$settings = wpml_get_setting( $this->contentTypeHelper->getWpmlSyncOptionKey(), [] );
			return Obj::propOr( WPML_CONTENT_TYPE_DONT_TRANSLATE, $objectSlug, $settings );
		}

		return WPML_CONTENT_TYPE_DONT_TRANSLATE;
	}

	/**
	 * @param  string|null $objectSlug
	 *
	 * @return string
	 */
	protected function getObjectTranslationContent( $objectSlug ) {
		switch ( $this->getObjectTranslationType( $objectSlug ) ) {
			case WPML_CONTENT_TYPE_DONT_TRANSLATE:
				return __( 'Not translatable', 'acfml' );
			case WPML_CONTENT_TYPE_TRANSLATE:
			case WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED:
				return __( 'Translatable', 'acfml' );
		}
		return '';
	}

	/**
	 * @param  string|null $objectSlug
	 *
	 * @return string
	 */
	protected function getObjectTranslationContext( $objectSlug ) {
		switch ( $this->getObjectTranslationType( $objectSlug ) ) {
			case WPML_CONTENT_TYPE_TRANSLATE:
				return __( 'only show translated items', 'acfml' );
			case WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED:
				return __( 'use translation if available or fallback to default language', 'acfml' );
		}
		return '';
	}

	/**
	 * @param  string $objectSlug
	 * @param  string $separator
	 *
	 * @return string
	 */
	protected function getObjectTranslationInformation( $objectSlug, $separator = '' ) {
		$content = $this->getObjectTranslationContent( $objectSlug );
		$context = $this->getObjectTranslationContext( $objectSlug );

		return $this->getTranslationInformation( $content, $context, $separator );
	}

	/**
	 * @param  string $content
	 * @param  string $context
	 * @param  string $separator
	 *
	 * @return string
	 */
	protected function getTranslationInformation( $content, $context = '', $separator = '' ) {
		$information = '<span class="acfml-translation-info">' . esc_html( $content );
		if ( $context ) {
			$information .= $separator . '<span class="acfml-translation-info-context">' . esc_html( $context ) . '</span>';
		}
		$information .= '</span>';

		return $information;
	}

}
