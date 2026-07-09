<?php

namespace ACFML;

use ACFML\Strings\Package;
use ACFML\TranslationDataTrait;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class TranslationDataColumnHooks implements \IWPML_Backend_Action {
	use TranslationDataTrait;

	const COLUMN_KEY        = 'acfml-translation-mode';
	const LABELS_COLUMN_KEY = 'acfml-labels-translation-status';

	/**
	 * We need to put a higher priority, because ACF will overwrite
	 * the columns on current_screen hook
	 */
	const COLUMN_HOOK_PRIORITY = 11;

	public function add_hooks() {
		if ( $this->contentTypeHelper->isListingScreen() ) {
			Hooks::onFilter(
				sprintf( 'manage_%s_posts_columns', $this->contentTypeHelper->getInternalPostType() ),
				self::COLUMN_HOOK_PRIORITY
			)->then( spreadArgs( [ $this, 'setColumnsTitle' ] ) );

			Hooks::onAction(
				sprintf( 'manage_%s_posts_custom_column', $this->contentTypeHelper->getInternalPostType() ),
				10,
				2
			)->then( spreadArgs( [ $this, 'setColumnsContent' ] ) );
		}
	}

	/**
	 * @param array $columns
	 *
	 * @return array
	 */
	public function setColumnsTitle( $columns ) {
		if ( $this->hasTranslationType ) {
			$columns[ self::COLUMN_KEY ] = $this->contentTypeHelper->getTranslationInfoLabel();
		}
		$columns[ self::LABELS_COLUMN_KEY ] = $this->contentTypeHelper->getLabelsTranslationInfoLabel();

		return $columns;
	}

	/**
	 * @param string $column
	 * @param int    $postId
	 */
	public function setColumnsContent( $column, $postId ) {
		if ( self::COLUMN_KEY === $column && $this->hasTranslationType ) {
			$objectSlug = $this->contentTypeHelper->getObjectSlug( $postId );
			if ( null !== $objectSlug ) {
				echo $this->getObjectTranslationInformation( $objectSlug ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
			}
		}

		if ( self::LABELS_COLUMN_KEY === $column ) {
			$status = Package::create( $this->contentTypeHelper->getObjectSlug( $postId ), $this->contentTypeHelper->getLabelTranslationsPackageSlug() )->getStatus();
			echo $this->getTranslationInformation( Package::status2text( $status ) ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
		}
	}

}
