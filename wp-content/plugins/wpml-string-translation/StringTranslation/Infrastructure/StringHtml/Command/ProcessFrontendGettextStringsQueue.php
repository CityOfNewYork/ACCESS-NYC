<?php

namespace WPML\StringTranslation\Infrastructure\StringHtml\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringHtml\Command\ProcessFrontendGettextStringsQueueInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\FrontendQueueRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Command\ProcessFrontendStringsObserverInterface;

class ProcessFrontendGettextStringsQueue implements ProcessFrontendGettextStringsQueueInterface {

	/** @var \wpdb */
	private $wpdb;

	/** @var FrontendQueueRepositoryInterface */
	private $frontendQueueRepository;

	/**
	 * @var ProcessFrontendStringsObserverInterface[]
	 */
	private $observers;

	public function __construct(
		$wpdb,
		FrontendQueueRepositoryInterface $frontendQueueRepository,
		array $observers = []
	) {
		$this->wpdb                    = $wpdb;
		$this->frontendQueueRepository = $frontendQueueRepository;
		$this->observers               = $observers;
	}

	public function run() {
		$allGettextStringsByUrl = $this->frontendQueueRepository->get();
		foreach ( $allGettextStringsByUrl as $gettextStringsByUrl ) {
			$this->runBatchByRequestUrl( $gettextStringsByUrl->getStrings(), $gettextStringsByUrl->getRequestUrl() );
		}

		$this->frontendQueueRepository->remove();
	}

	private function runBatchByRequestUrl( array $gettextStrings, string $requestUrl ) {
		if ( count( $gettextStrings ) === 0 ) {
			return;
		}

		$buildRowSql = function( StringItem $string ) {
			$args = [
				$string->getValue(),
				$string->getDomain(),
			];

			$contextSql = '';
			if ( is_string( $string->getContext() ) && strlen( $string->getContext() ) > 0 ) {
				$contextSql = ' AND gettext_context="%s"';
				$args[]     = $string->getContext();
			}

			return $this->wpdb->prepare(
				'(value=%s AND context="%s"' . $contextSql . ')',
				$args
			);
		};

		$query = "SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE ";
		$searchRows = [];
		foreach ( $gettextStrings as $string ) {
			$searchRows[] = $buildRowSql( $string );
		}
		$query .= implode( ' OR ', $searchRows );
		$stringIds = $this->wpdb->get_col( $query );

		if ( count( $stringIds ) === 0 ) {
			return;
		}

		$table = $this->wpdb->prefix . 'icl_string_positions';
		$kind  = ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND;

		$stringIdsToUpdate = $this->wpdb->get_col(
			"SELECT DISTINCT(string_id) AS string_id FROM " . $table . " WHERE string_id IN (" . wpml_prepare_in( $stringIds, '%d' ) . ")"
		);
		$stringIdsToInsert = array_filter(
			$stringIds,
			function( $stringId ) use ( $stringIdsToUpdate ) {
				return ! in_array( $stringId, $stringIdsToUpdate );
			}
		);

		if ( count( $stringIdsToUpdate ) > 0 ) {
			$this->wpdb->query(
				"UPDATE " . $table . " SET kind = " . $kind . " WHERE string_id IN (" . wpml_prepare_in( $stringIdsToUpdate, '%d' ) . ")"
			);
		}
		if ( count( $stringIdsToInsert ) > 0 ) {
			$rowsSql = [];
			foreach ( $stringIdsToInsert as $stringId ) {
				$rowsSql[] = $this->wpdb->prepare(
					'(%d, %d, %s)',
					$stringId,
					$kind,
					$requestUrl
				);
			}
			$this->wpdb->query(
				"INSERT INTO " . $table . " (string_id, kind, position_in_page) VALUES " . implode( ',', $rowsSql )
			);
		}

		foreach ( $this->observers as $observer ) {
			$observer->newFrontendStringsRegistered( $stringIds );
		}
	}
}