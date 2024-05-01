<?php

namespace WPML\TM\Settings;

use WPML\Collect\Support\Collection;
use WPML\Core\BackgroundTask\Command\UpdateBackgroundTask;
use WPML\Core\BackgroundTask\Model\BackgroundTask;
use WPML\Core\BackgroundTask\Repository\BackgroundTaskRepository;
use WPML\BackgroundTask\AbstractTaskEndpoint;
use WPML\Core\BackgroundTask\Service\BackgroundTaskService;
use WPML\Element\API\PostTranslations;
use WPML\FP\Obj;
use WPML\Setup\Option;
use WPML\TM\AutomaticTranslation\Actions\Actions as AutotranslateActions;

class ProcessNewTranslatableFields extends AbstractTaskEndpoint {
	const LOCK_TIME         = 5;
	const MAX_RETRIES       = 10;
	const DESCRIPTION       = 'Updating affected posts for changes in translatable fields %s.';
	const POSTS_PER_REQUEST = 10;

	/** @var \wpdb */
	private $wpdb;

	/** @var \WPML_TM_Post_Actions $postActions */
	private $postActions;

	/** @var AutotranslateActions $autotranslateActions */
	private $autotranslateActions;

	/**
	 * @param \wpdb                         $wpdb
	 * @param \WPML_TM_Post_Actions         $postActions
	 * @param AutotranslateActions          $autotranslateActions
	 * @param UpdateBackgroundTask          $updateBackgroundTask
	 * @param BackgroundTaskService      $backgroundTaskService
	 */
	public function __construct(
		\wpdb $wpdb,
		\WPML_TM_Post_Actions $postActions,
		AutotranslateActions $autotranslateActions,
		UpdateBackgroundTask $updateBackgroundTask,
		BackgroundTaskService $backgroundTaskService
	) {
		$this->wpdb                      = $wpdb;
		$this->postActions               = $postActions;
		$this->autotranslateActions      = $autotranslateActions;

		parent::__construct( $updateBackgroundTask, $backgroundTaskService );
	}

	public function runBackgroundTask( BackgroundTask $task ) {
		$payload = $task->getPayload();
		$fieldsToProcess = Obj::propOr( [], 'newFields', $payload );
		$page = Obj::propOr( 1, 'page', $payload );
		$postIds = $this->getPosts( $fieldsToProcess, $page );

		if ( count( $postIds ) > 0 ) {

			$this->updateNeedsUpdate( $postIds );
			$payload['page'] = $page + 1;
			$task->setPayload( $payload );
			$task->addCompletedCount( count( $postIds ) );
			$task->setRetryCount(0 );
			if ( $task->getCompletedCount() >= $task->getTotalCount() ) {
				$task->finish();
			}
		} else {
			$task->finish();
		}
		return $task;
	}

	public function getDescription( Collection $data ) {
		return sprintf(
			__( self::DESCRIPTION, 'sitepress' ),
			implode( ', ', $data->get( 'newFields', [] ) )
		);
	}

	public function getTotalRecords( Collection $data ) {
		return $this->getPostsCount( $data->all()['newFields'] );
	}

	/**
	 * @param array $fields
	 * @param int $page
	 *
	 * @return array
	 */
	private function getPosts( array $fields, $page ) {
		if ( empty( $fields ) ) {
			return [];
		}
		$fieldsIn = wpml_prepare_in( $fields, '%s' );

		return $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT DISTINCT post_id
						FROM {$this->wpdb->prefix}postmeta
						WHERE meta_key IN ({$fieldsIn})
						ORDER BY post_id ASC
						LIMIT %d OFFSET %d",
				self::POSTS_PER_REQUEST,
				($page-1)*self::POSTS_PER_REQUEST
			)
		);
	}

	/**
	 * @param array $fields
	 *
	 * @return int
	 */
	private function getPostsCount( array $fields ) {
		if ( empty( $fields ) ) {
			return 0;
		}
		$fields_in = wpml_prepare_in( $fields, '%s' );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $this->wpdb->get_var(
			"SELECT COUNT(DISTINCT(post_id))
					FROM {$this->wpdb->prefix}postmeta
					WHERE meta_key IN ({$fields_in}) AND meta_key <> ''"
		);
	}

	/**
	 * @param array $postIds
	 */
	private function updateNeedsUpdate( array $postIds ) {
		foreach ( $postIds as $postId ) {
			$translations = PostTranslations::getIfOriginal( $postId );
			$updater      = $this->postActions->get_translation_statuses_updater( $postId, $translations );
			$needsUpdate  = $updater();
			if (
				$needsUpdate
				&& \WPML_TM_ATE_Status::is_enabled_and_activated()
				&& Option::shouldTranslateEverything()
			) {
				$this->autotranslateActions->sendToTranslation( $postId );
			}
		}
	}
}
