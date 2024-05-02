<?php

namespace WPML\TM\ATE\Log;

use WPML\Collect\Support\Collection;
use WPML\WP\OptionManager;

class Storage {

	const OPTION_GROUP = 'TM\ATE\Log';
	const OPTION_NAME  = 'logs';
	const MAX_ENTRIES  = 50;

	public static function add( Entry $entry, $avoidDuplication = false ) {
		$entry->timestamp = $entry->timestamp ?: time();

		$entries = self::getAll();

		if ( $avoidDuplication ) {
			$entries = $entries->reject(
				function( $iteratedEntry ) use ( $entry ) {
					return (
					$iteratedEntry->wpmlJobId === $entry->wpmlJobId
					&& $entry->ateJobId === $iteratedEntry->ateJobId
					&& $entry->description === $iteratedEntry->description
					&& $entry->eventType === $iteratedEntry->eventType
					);
				}
			);
		}

		$entries->prepend( $entry );

		$newOptionValue = $entries->forPage( 1, self::MAX_ENTRIES )
								->map(
									function( Entry $entry ) {
										return (array) $entry; }
								)
								  ->toArray();
		OptionManager::updateWithoutAutoLoad( self::OPTION_NAME, self::OPTION_GROUP, $newOptionValue );
	}

	/**
	 * @param Entry $entry
	 */
	public static function remove( Entry $entry ) {
		$entries        = self::getAll();
		$entries        = $entries->reject(
			function( $iteratedEntry ) use ( $entry ) {
				return $iteratedEntry->timestamp === $entry->timestamp && $entry->ateJobId === $iteratedEntry->ateJobId;
			}
		);
		$newOptionValue = $entries->forPage( 1, self::MAX_ENTRIES )
				->map(
					function( Entry $entry ) {
						return (array) $entry; }
				)
				->toArray();
		OptionManager::updateWithoutAutoLoad( self::OPTION_NAME, self::OPTION_GROUP, $newOptionValue );
	}

	/**
	 * @return Collection Collection of Entry objects.
	 */
	public static function getAll() {
		return wpml_collect( OptionManager::getOr( [], self::OPTION_NAME, self::OPTION_GROUP ) )
			->map(
				function( array $item ) {
					return new Entry( $item );
				}
			);
	}

	public function getCount(): int {
		return count( OptionManager::getOr( [], self::OPTION_NAME, self::OPTION_GROUP ) );
	}
}
