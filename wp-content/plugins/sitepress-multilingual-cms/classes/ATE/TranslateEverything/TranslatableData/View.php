<?php

namespace WPML\TM\ATE\TranslateEverything\TranslatableData;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Right;
use WPML\FP\Left;

class View implements IHandler {
	const ACTION_LIST_TRANSLATABLES = 'list-translatables';
	const ACTION_FETCH_DATA         = 'fetch-data';

	/** @var DataPreSetup $data_pre_setup */
	private $data;

	/**
	 * @param DataPreSetup $data
	 *
	 * @return void
	 */
	public function __construct( DataPreSetup $data ) {
		$this->data = $data;
	}

	public function run( Collection $data ) {
		$action = $data->get( 'action' );
		switch ( $action ) {
			case self::ACTION_LIST_TRANSLATABLES:
				return Right::of( $this->data->listTranslatableData() );
			case self::ACTION_FETCH_DATA:
				return $this->fetchData( $data );
		}

		return $this->unexpectedError();
	}

	private function fetchData( Collection $for ) {
		$fetchDataFor = $for->get( 'field' );

		if ( ! $fetchDataFor ) {
			$this->unexpectedError();
		}

		$fetchDataFor = array_merge(
			[
				'count' => 0,
				'words' => 0,
			],
			$fetchDataFor
		);

		try {
			$labels = 0 === (int) $fetchDataFor['count']
				? $this->fetchLabelFor(
					$fetchDataFor['type'],
					$fetchDataFor['name']
				)
				: $fetchDataFor['labels'];

			$stack = new Stack(
				$fetchDataFor['type'],
				$fetchDataFor['name'],
				$fetchDataFor['count'],
				$fetchDataFor['words'],
				$labels
			);
			$stack = $this->data->fetch( $stack );

			return Right::of( $stack->toArray() );
		} catch ( \Exception $e ) {
			$this->unexpectedError();
		}
	}

	private function fetchLabelFor( $type, $name ) {
		if ( DataPreSetup::KEY_POST_TYPES !== $type ) {
			return [];
		}

		$postTypeObject = get_post_type_object( $name );
		if (
			! is_object( $postTypeObject ) ||
			! property_exists( $postTypeObject, 'labels' )
		) {
			return [];
		}

		return [
			'singular' => $postTypeObject->labels->singular_name,
			'plural'   => $postTypeObject->labels->name,
		];
	}

	private function unexpectedError() {
		return Left::of(
			__( 'Server error. Please refresh and try again.', 'sitepress' )
		);
	}
}

