<?php

namespace WPML\Utilities;

use function WPML\Container\make;

class Lock implements ILock {
	private static $active_locks = [];

	/** @var \wpdb  */
	private $wpdb;

	/** @var string  */
	protected $name;

	/**
	 * Lock constructor.
	 *
	 * @param \wpdb  $wpdb
	 * @param string $name
	 */
	public function __construct( \wpdb $wpdb, $name ) {
		$this->wpdb = $wpdb;
		$this->name = 'wpml.' . $name . '.lock';
	}

	public static function whileLocked( $lockName, $releaseTimeout, callable $fn ) {
		$lock = make( Lock::class, [ ':name' => $lockName ] );
		if ( $lock->create( $releaseTimeout ) ) {
			$fn();
			$lock->release();
		}
	}

	/**
	 * Creates a lock using WordPress options ( Based on WP class WP_Upgrader ).
	 *
	 * @param int $release_timeout Optional. The duration in seconds to respect an existing lock.
	 *                             Default: 1 hour.
	 * @return bool False if a lock couldn't be created or if the lock is still valid. True otherwise.
	 */
	public function create( $release_timeout = null ) {
		if ( ! $release_timeout ) {
			$release_timeout = HOUR_IN_SECONDS;
		}

		if ( isset( self::$active_locks[ $this->name ] ) ) {
			// The lock for this type was already determinated.
			// No matter if this request has the valid lock or not,
			// only one task is allowed per type & request.
			// REPLACE THIS WITH: return self::$active_locks[ $this->name ];
			// as part of wpmldev-4141.
			return false;
		}

		// Try to lock.
		$lock_result = $this->wpdb->query( $this->wpdb->prepare( "INSERT IGNORE INTO {$this->wpdb->options} ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'no') /* LOCK */", $this->name, time() ) );

		if ( ! $lock_result ) {
			$lock_result = get_option( $this->name );

			// No lock could be created and found
			// OR the lock found is still valid (used by another request).
			// => No lock for this request.

			if ( ! $this->isValidLockTimeout($lock_result) ) {
				// avoid to be locked out if the lock is empty in case of key corruption (db error/corruption)
				// and set it as expired
				$lock_result = 1;
			}
			if ( ! $lock_result || $lock_result > ( time() - $release_timeout ) ) {
				self::$active_locks[ $this->name ] = false;
				return false;
			}

			// There must exist an expired lock, clear it and re-gain it.
			$this->release();
			return $this->create( $release_timeout );
		}

		// Update the lock, as by this point we've definitely got a lock, just need to fire the actions.
		update_option( $this->name, time(), false );

		self::$active_locks[ $this->name ] = true;

		return true;
	}

	/**
	 * Releases an upgrader lock.
	 *
	 * @return bool True if the lock was successfully released. False on failure.
	 */
	public function release() {
		unset( self::$active_locks[ $this->name ] );
		return delete_option( $this->name );
	}

	/**
	 * Check if the lock result is a valid timeout.
	 * @param mixed $lock_result
	 * @return bool
	 */
	private function isValidLockTimeout( $lock_result ) {

		return is_numeric( $lock_result ) && $lock_result > 0;

	}


}
