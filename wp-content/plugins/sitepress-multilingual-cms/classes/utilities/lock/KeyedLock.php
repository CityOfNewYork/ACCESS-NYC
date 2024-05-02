<?php

namespace WPML\Utilities;

class KeyedLock extends Lock {

	/** @var string $keyName */
	private $keyName;

	/**
	 * Lock constructor.
	 *
	 * @param \wpdb  $wpdb
	 * @param string $name
	 */
	public function __construct( \wpdb $wpdb, $name ) {
		$this->keyName = 'wpml.' . $name . '.lock.key';
		parent::__construct( $wpdb, $name );
	}

	/**
	 * @param string $key
	 * @param int    $release_timeout
	 *
	 * @return string|false The key or false if could not acquire the lock
	 */
	public function create( $key = null, $release_timeout = null ) {
		$acquired = parent::create( $release_timeout );

		if ( $acquired ) {

			if ( ! $key ) {
				$key = wp_generate_uuid4();
			}

			update_option( $this->keyName, $key );
			return $key;
		} elseif ( $key === get_option( $this->keyName ) ) {
			$this->extendTimeout();
			return $key;
		}

		return false;
	}

	public function release() {
		delete_option( $this->keyName );
		// When running concurrent calls to delete_option, the cache might not be updated properly.
		// And WP will skip its own cache invalidation.
		wp_cache_delete( $this->keyName, 'options' );

		return parent::release();
	}

	private function extendTimeout() {
		update_option( $this->name, time() );
	}
}
