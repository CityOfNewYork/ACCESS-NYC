<?php

namespace ACFML;

use ACFML\Strings\Package;
use ACFML\TranslationDataTrait;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class TranslationDataMetaboxHooks implements \IWPML_Backend_Action {
	use TranslationDataTrait;

	const METABOX_ID    = 'acfml-multilingual-data';
	const STATI_TO_SKIP = [ 'auto-draft' ];

	public function add_hooks() {
		Hooks::onAction(
			sprintf( 'add_meta_boxes_%s', $this->contentTypeHelper->getInternalPostType() )
		)->then( spreadArgs( [ $this, 'addMetaBox' ] ) );
	}

	/**
	 * @param \WP_Post $post
	 * @todo  acfml-830 Pending proper GUI
	 */
	public function addMetaBox( $post ) {
		// phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
		if ( in_array( $post->post_status, self::STATI_TO_SKIP, true ) ) {
			return;
		}
		$status = Package::create(
			$this->contentTypeHelper->getObjectSlug( $post->ID ),
			$this->contentTypeHelper->getLabelTranslationsPackageSlug()
		)->getStatus();
		add_meta_box(
			self::METABOX_ID,
			'<i class="otgs-ico-translation"></i>&nbsp;' . esc_html__( 'Multilingual Setup', 'acfml' ),
			function() use ( $post, $status ) {
				$this->translationModeInfo( $post->ID );
				$this->labelsTranslationsStatusInfo( $status );
			},
			$this->contentTypeHelper->getEditorScreenSlug(),
			'normal',
			'high'
		);
		// phpcs:enable
	}

	/**
	 * @param int $postId
	 */
	private function translationModeInfo( $postId ) {
		if ( ! $this->hasTranslationType ) {
			return;
		}

		$objectSlug = $this->contentTypeHelper->getObjectSlug( $postId );
		if ( null === $objectSlug ) {
			return;
		}

		echo '<div class="acfml-translation-status-metabox acfml-translation-mode">'
			. '<span class="acfml-translation-info-title">' . esc_html( $this->contentTypeHelper->getTranslationInfoLabel() ) . '</span>'
			. $this->getObjectTranslationInformation( $objectSlug, ' - ' ) // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
			. sprintf(
				/* translators: %1$s and %2$s are placeholders for bold tags. */
				esc_html__( '%1$sChange translation settings%2$s', 'acfml' ),
				'<a href="' . esc_url( $this->contentTypeHelper->getTranslationSettingsUrl() ) . '" title="" class="wpml-external-link" target="_blank">',
				'</a>'
			)
			. '</div>';
	}

	/**
	 * @param string $status
	 */
	private function labelsTranslationsStatusInfo( $status ) {
		echo '<div class="acfml-translation-status-metabox acfml-labels-translation-status">'
			. '<span class="acfml-translation-info-title">' . esc_html( $this->contentTypeHelper->getLabelsTranslationInfoLabel() ) . '</span>'
			. $this->getTranslationInformation( Package::status2text( $status ) ) // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
			. sprintf(
				/* translators: %1$s and %2$s are placeholders for bold tags. */
				esc_html__( '%1$sTranslate labels%2$s', 'acfml' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=tm/menu/main.php' ) ) . '" title="" class="wpml-external-link" target="_blank">',
				'</a>'
			)
			. '</div>';
	}

}
