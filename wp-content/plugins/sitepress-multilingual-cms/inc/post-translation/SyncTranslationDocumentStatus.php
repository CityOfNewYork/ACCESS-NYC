<?php

namespace WPML\Core\PostTranslation;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Lst;

class SyncTranslationDocumentStatus implements \IWPML_Action, \IWPML_DIC_Action, \IWPML_Backend_Action, \IWPML_REST_Action {
	private $sitepress;

	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_action( 'transition_post_status', [$this, 'onPostStatusChange'], 10, 3 );
	}

	/**
	 * @param string $newStatus
	 * @param string $oldStatus
	 * @param \WP_Post $post
	 */
	public function onPostStatusChange( $newStatus, $oldStatus, $post ) {
		if ( $newStatus === $oldStatus || 'publish' !== $newStatus ) {
			return;
		}

		if ( 'draft' !== $oldStatus ) {
			return;
		}

		$settings = $this->sitepress->get_settings();
		if ( ! $settings['translated_document_status_sync'] ) {
			return;
		}

		$allPosts         = \WPML\Element\API\PostTranslations::getIfOriginal( $post->ID );
		$originalPost     = Lst::nth( 0, Fns::filter( Obj::prop( 'original' ), $allPosts ) );
		$postTranslations = Fns::reject( Obj::prop( 'original' ), $allPosts );
		if ( ! $originalPost || empty( $postTranslations ) ) {
			return;
		}

		foreach ( $postTranslations as $sourceLangCode => $data ) {
			wp_update_post( ['ID' => $data->element_id, 'post_status' => $newStatus] );
		}
	}
}