<?php

namespace WPML\TM\ATE\Sync;

use function get_current_user_id;
use WPML\Collect\Support\Collection;
use WPML\WP\OptionManager;

class Trigger {

	const SYNC_TIMEOUT = 10 * MINUTE_IN_SECONDS;

	const OPTION_GROUP            = 'WPML\TM\ATE\Sync';
	const SYNC_LAST               = 'last';
	const SYNC_REQUIRED_FOR_USERS = 'required_for_users';

	/** @var OptionManager $optionManager */
	private $optionManager;

	public function __construct( OptionManager $optionManager ) {
		$this->optionManager = $optionManager;
	}

	/**
	 * @return bool
	 */
	public function isSyncRequired() {
		return $this->isUserSyncRequired() || $this->isPeriodicSyncRequired();
	}

	/**
	 * @return bool
	 */
	private function isPeriodicSyncRequired() {
		$lastSync = $this->optionManager->get( self::OPTION_GROUP, self::SYNC_LAST, 0 );
		return ( time() - self::SYNC_TIMEOUT ) > $lastSync;
	}

	/**
	 * @return bool
	 */
	private function isUserSyncRequired() {
		return $this->getUsersNeedSync()->contains( get_current_user_id() );
	}

	public function setSyncRequiredForCurrentUser() {
		$userId        = get_current_user_id();
		$usersNeedSync = $this->getUsersNeedSync();

		if ( ! $usersNeedSync->contains( $userId ) ) {
			$usersNeedSync->push( $userId );
			$this->setUsersNeedSync( $usersNeedSync );
		}
	}

	public function setLastSync() {
		$this->optionManager->set( self::OPTION_GROUP, self::SYNC_LAST, time(), false );

		$currentUserId = get_current_user_id();
		$usersNeedSync = $this->getUsersNeedSync();

		if ( $usersNeedSync->contains( $currentUserId ) ) {
			$isCurrentUser = function( $userId ) use ( $currentUserId ) {
				return $userId === $currentUserId;
			};
			$this->setUsersNeedSync( $usersNeedSync->reject( $isCurrentUser ) );
		}
	}

	/**
	 * @return Collection
	 */
	private function getUsersNeedSync() {
		return wpml_collect( $this->optionManager->get( self::OPTION_GROUP, self::SYNC_REQUIRED_FOR_USERS, [] ) );
	}

	private function setUsersNeedSync( Collection $usersNeedSync ) {
		$this->optionManager->set( self::OPTION_GROUP, self::SYNC_REQUIRED_FOR_USERS, $usersNeedSync->toArray(), false );
	}
}
