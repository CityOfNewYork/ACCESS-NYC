<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Command;

abstract class BulkActionBaseCommand {

	/** @var \wpdb */
	protected $wpdb;

	/** @var int<1,max> */
	protected $chunk_size = 1000;

	protected function runBulkQuery( string $query ) {
		$this->wpdb->suppress_errors = true;
		$this->wpdb->query($query);
		$this->wpdb->suppress_errors = false;
	}
}