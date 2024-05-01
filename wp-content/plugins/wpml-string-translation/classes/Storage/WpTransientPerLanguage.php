<?php

namespace WPML\ST\Storage;

class WpTransientPerLanguage implements StoragePerLanguageInterface {

	/** @var string $id */
	private $id;

	/** @var int $lifetime Lifetime of storage in seconds.*/
	private $lifetime = 86400; // 1 day.

	/**
	 * @param string $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}


	/**
	 * @param string $lang
	 * @return mixed Returns StorageInterface::NOTHING if there is no cache.
	 */
	public function get( $lang ) {
		$value = get_transient( $this->getName( $lang ) );

		return false === $value
			? StoragePerLanguageInterface::NOTHING
			: $value;
	}


	/**
	 * @param string $lang
	 * @param mixed  $value
	 */
	public function save( $lang, $value ) {
		return set_transient( $this->getName( $lang ), $value, $this->lifetime );
	}


	public function delete( $lang ) {
		return delete_transient( $this->getName( $lang ) );
	}



	/**
	 * Set the lifetime.
	 *
	 * @param int $lifetime
	 */
	public function setLifetime( $lifetime ) {
		$lifetime = (int) $lifetime;

		if ( $lifetime <= 0 ) {
			// Don't allow no expiration for lifetime.
			// Because WordPress sets 'autoload' to 'yes' for transients with
			// no expiration, which would be very bad for language based data.
			$lifetime = 86400 * 365; // 1 year.
		}

		$this->lifetime = $lifetime;
	}

	private function getName( $lang ) {
		return $this->id . '-' . $lang;
	}

}

