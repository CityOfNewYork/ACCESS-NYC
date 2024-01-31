<?php

namespace WPML\TM\Settings\Flags;

use WPML\FP\Obj;

class FlagsRepository {
	/** @var \wpdb */
	private $wpdb;

	/**
	 * @param \wpdb $wpdb
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function getItems( $data = array() ) {
		$whereSqlParts = [];
		$whereArgs     = [];

		if ( Obj::has( 'onlyInstalledByDefault', $data ) ) {
			$whereSqlParts[] = 'flags.from_template = 0';
		}

		$whereSqlParts[] = '1=%d';
		$whereArgs[]     = 1;

		$whereSql = 'WHERE ' . implode( ' AND ', $whereSqlParts );

		$flags = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT
					flags.id,
					flags.lang_code,
					flags.flag,
					flags.from_template
				FROM {$this->wpdb->prefix}icl_flags flags {$whereSql}",
				$whereArgs
			)
		);

		return $flags;
	}

	public function getItemsInstalledByDefault( $data = array() ) {
		return $this->getItems( array_merge(
			$data,
			[
				'onlyInstalledByDefault' => true,
			]
		) );
	}

	private function getFlagsCountByExt( $ext ) {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(flags.id)
				FROM {$this->wpdb->prefix}icl_flags flags
				WHERE flags.from_template = 0 AND flags.flag LIKE %s",
				'%.' . $ext
			)
		);
	}

	public function hasSvgFlags() {
		return $this->getFlagsCountByExt( 'svg' ) > 0;
	}

	public function hasPngFlags() {
		return $this->getFlagsCountByExt( 'png' ) > 0;
	}
}