<?php

namespace WPML\Utils;

use WPML\Collect\Support\Collection;

class Pager {
	/** @var string */
	protected $optionName;

	/** @var int */
	protected $pageSize;

	/**
	 * @param string $optionName
	 * @param int    $pageSize
	 */
	public function __construct( $optionName, $pageSize = 10 ) {
		$this->optionName = $optionName;
		$this->pageSize   = $pageSize;
	}

	/**
	 * @param Collection $collection
	 * @param callable   $callback
	 * @param int        $timeout
	 *
	 * @return int
	 */
	public function iterate( Collection $collection, callable $callback, $timeout = PHP_INT_MAX ) {
		$processedItems = $this->getProcessedCount();

		$this->getItemsToProcess( $collection, $processedItems )->eachWithTimeout( function ( $item ) use (
			&$processedItems,
			$callback
		) {
			return $callback( $item ) && ++ $processedItems;
		}, $timeout );

		$remainingPages = $this->getRemainingPages( $collection, $processedItems );

		if ( $remainingPages ) {
			\update_option( $this->optionName, $processedItems );
		} else {
			\delete_option( $this->optionName );
		}

		return $remainingPages;
	}

	private function getItemsToProcess( Collection $collection, $processedItems ) {
		return $collection->slice( $processedItems, $this->pageSize );
	}

	/**
	 * @param Collection $collection
	 *
	 * @return int
	 */
	public function getPagesCount( Collection $collection ) {
		return (int) ceil( $collection->count() / $this->pageSize );
	}

	/**
	 * @param \WPML\Collect\Support\Collection $collection
	 *
	 * @return int
	 */
	protected function getRemainingPages( Collection $collection, $processedItems ) {
		return (int) ceil( $collection->slice( $processedItems )->count() / $this->pageSize );
	}

	/**
	 * @return int
	 */
	public function getProcessedCount() {
		return get_option( $this->optionName, 0 );
	}
}
