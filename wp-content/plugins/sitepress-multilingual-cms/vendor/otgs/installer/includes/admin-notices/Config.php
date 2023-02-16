<?php

namespace OTGS\Installer\AdminNotices;

class Config {

	/**
	 * @var array
	 */
	protected $config;

	public function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * @param array $messages
	 * @param string $item
	 * @param string $type
	 *
	 * @return bool
	 */
	protected function hasItem( array $messages, $item, $type ) {
		foreach ( $messages['repo'] as $repo => $ids ) {
			foreach ( $ids as $id => $noticeType ) {
				$index = is_array( $noticeType ) ? $id : $noticeType;

				if ( isset( $this->config['repo'][ $repo ][ $index ][ $type ] )
				     && in_array( $item, $this->config['repo'][ $repo ][ $index ][ $type ], true ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
