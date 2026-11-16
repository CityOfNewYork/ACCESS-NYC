<?php
/**
 * Imported content importer metas.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models;

/**
 * Class Imported_Content_Importer_Metas.
 */
class Imported_Content_Importer_Metas {

	/**
	 * Importer connection id.
	 *
	 * @var int
	 */
	private $importer_id;

	/**
	 * Record id from the remote source.
	 *
	 * @var string
	 */
	private $record_id;

	/**
	 * Content updated date (date format GMT date Y-m-d H:i:s, @see gmdate())
	 *
	 * @var \DateTimeImmutable
	 */
	private $updated_at;


	/**
	 * Disconnect callback
	 *
	 * @var callable(mixed):bool
	 */
	private $disconnect;

	/**
	 * Constructor.
	 *
	 * @param int                  $importer_id Importer connection id.
	 * @param string               $record_id Record id from the remote source.
	 * @param string               $updated_at Content updated date (date format GMT date Y-m-d H:i:s, @see gmdate()).
	 * @param callable(mixed):bool $disconnect Disconnect callback.
	 */
	public function __construct( int $importer_id, string $record_id, string $updated_at, $disconnect ) {
		$this->importer_id = $importer_id;
		$this->record_id   = $record_id;
		$this->updated_at  = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $updated_at );
		$this->disconnect  = $disconnect;
	}

	/**
	 * Returns the importer connection id.
	 *
	 * @return int
	 */
	public function get_importer_id(): int {
		return $this->importer_id;
	}

	/**
	 * Returns the record id from the remote source.
	 *
	 * @return string
	 */
	public function get_record_id(): string {
		return $this->record_id;
	}

	/**
	 * Returns the content updated date.
	 *
	 * @return \DateTimeImmutable
	 */
	public function get_updated_at(): \DateTimeImmutable {
		return $this->updated_at;
	}

	/**
	 * Returns disconnect callback
	 *
	 * @var callable(mixed):bool
	 */
	public function get_disconnect(): callable {
		return $this->disconnect;
	}
}
