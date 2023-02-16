<?php

namespace WPML\AI;
/**
 * Class Attachments
 * @package WPML\AI
 */
class Attachments {

	/**
	 * @var null|string Location of the file to unlink.
	 */
	static $URL;

	/**
	 * Things to do before single post is going to be imported.
	 */
	public function beforePostImport() {
		$this->allowToDeleteAttachments();
	}

	/**
	 * Things to do after single post has been imported.
	 */
	public function afterPostImport() {
		$this->removeFilter();
	}

	/**
	 * Set location of the file to unlink (after WPMl core resets it to null).
	 */
	private function allowToDeleteAttachments() {
		add_filter( 'wp_delete_file', [ $this, 'grabUrlInDeleteFileFilter'], 1, 1 );
		add_filter( 'wp_delete_file', [ $this, 'restoreUrlInDeleteFileFilter'], 20, 1 );
	}

	/**
	 * Removes filters registered with \WPML\AI\attachments::allowToDeleteAttachments
	 */
	private function removeFilter() {
		remove_filter( 'wp_delete_file', [ $this, 'grabUrlInDeleteFileFilter'], 1, 1 );
		remove_filter( 'wp_delete_file', [ $this, 'restoreUrlInDeleteFileFilter'], 20, 1 );
		self::$URL = null;
	}

	/**
	 * Save file location.
	 *
	 * @param $url File location
	 */
	public function grabUrlInDeleteFileFilter( $url ) {
		self::$URL = $url;
	}

	/**
	 * Restore file location.
	 *
	 * @param $url null|string File location.
	 *
	 * @return string|null File location
	 */
	public function restoreUrlInDeleteFileFilter( $url ) {
		if ( self::$URL ) {
			$url = self::$URL;
		}
		return $url;
	}
}
