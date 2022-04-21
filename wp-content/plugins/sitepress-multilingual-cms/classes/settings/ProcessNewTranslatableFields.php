<?php

namespace WPML\TM\Settings;

use WPML\Collect\Support\Collection;
use WPML\Element\API\PostTranslations;
use WPML\FP\Either;
use WPML\Setup\Option;
use WPML\TM\AutomaticTranslation\Actions\Actions;

class ProcessNewTranslatableFields {

	const MAX_POSTS = 10;

	public function run(
		Collection $data,
		\wpdb $wpdb,
		\WPML_TM_Post_Actions $postActions,
		Actions $autoTranslateActions
	) {

		$fields = $data->get( 'newFields', [] );
		$page   = (int) $data->get( 'page', 1 );
		if ( count( $fields ) ) {
			$postIds = self::getPosts( $wpdb, $fields, $page );

			$this->updateNeedsUpdate( $postIds, $postActions, $autoTranslateActions );

			if ( count( $postIds ) ) {
				return self::getFetchNextPageResponse( $fields, $page );
			} else {
				CustomFieldChangeDetector::remove( $fields );
			}
		}

		return Either::of( null );
	}

	private static function getPosts( \wpdb $wpdb, array $fields, $page ) {
		$fieldsIn = wpml_prepare_in( $fields, '%s' );
		$offset   = ( $page - 1 ) * self::MAX_POSTS;
		$limit    = self::MAX_POSTS;

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id
						FROM {$wpdb->prefix}postmeta
						WHERE meta_key IN ({$fieldsIn}) AND meta_key <> ''
						LIMIT {$limit} OFFSET {$offset}"
			)
		);
	}

	private static function getFetchNextPageResponse( $fields, $page ) {
		return Either::of( [
			'status' => 'continue',
			'data'   => [ 'newFields' => $fields, 'page' => $page + 1 ],
		] );
	}

	/**
	 * @param array                 $postIds
	 * @param \WPML_TM_Post_Actions $postActions
	 */
	private function updateNeedsUpdate(
		array $postIds,
		\WPML_TM_Post_Actions $postActions,
		Actions $autoTranslateActions
	) {
		foreach ( $postIds as $postId ) {
			$translations = PostTranslations::getIfOriginal( $postId );
			$updater      = $postActions->get_translation_statuses_updater( $postId, $translations );
			$needsUpdate  = $updater();
			if (
				$needsUpdate
				&& \WPML_TM_ATE_Status::is_enabled_and_activated()
				&& Option::shouldTranslateEverything()
			) {
				$autoTranslateActions->sendToTranslation( $postId );
			}
		}
	}
}
