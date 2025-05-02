<?php

namespace WPML\StringTranslation\Infrastructure\Core\Command;

use WP_Filesystem_Direct;

class SaveFileCommand {

	/**
	 * @var WP_Filesystem_Direct
	 */
	protected $filesystem;

	public function __construct(
		$filesystem
	) {
		$this->filesystem = $filesystem;
	}

	public function run( string $filepath, string $data ) {
		$filepathParts = explode( '.', $filepath );
		$tmpFilepath = $filepath;
		if ( count( $filepathParts ) > 1 ) {
			$ext = array_pop( $filepathParts );
			$tmpFilepath = implode( '.', $filepathParts ) . '_tmp.' . $ext;
		}

		$chmod = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;

		if ( ! $this->filesystem->put_contents( $tmpFilepath, $data, $chmod ) ) {
			return;
		}

		$this->filesystem->move( $tmpFilepath, $filepath, true );
	}
}