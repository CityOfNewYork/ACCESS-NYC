<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;

class Attachments_Saver {

	/**
		 * @var Debug_Logger
		 */
	protected $logger;

	public function __construct( $logger ) {
		$this->logger = $logger;
	}

	public function save_attachments( $email_id, $attachments ) {
		$uploads_dir     = $this->get_uploads_dir_path( $email_id, $attachments );
		$new_attachments = array();

		if ( ! is_dir( $uploads_dir ) ) {
			/* translators: %1$s: directory path */
			$this->logger->log_debug( __METHOD__ . '(): ' . sprintf( __( 'Creating a new uploads directory at path: %1$s', 'gravitysmtp' ), $uploads_dir ) );
			mkdir( $uploads_dir, 0755, true );
		}

		foreach ( $attachments as $file_path ) {
			if ( ! file_exists( $file_path ) ) {
				/* translators: %1$s: file path */
				$this->logger->log_warning( __METHOD__ . '(): ' . sprintf( __( 'Could not locate file at path: %1$s', 'gravitysmtp' ), $file_path ) );
				continue;
			}

			$file_name = basename( $file_path );
			$contents  = file_get_contents( $file_path );
			$new_path  = sprintf( '%s%s', trailingslashit( $uploads_dir ), $file_name );

			/* translators: %1$s: file name, %2$s: new path */
			$this->logger->log_debug( __METHOD__ . '(): ' . sprintf( __( '%1$s being moved to new location: %2$s', 'gravitysmtp' ), $file_name, $new_path ) );

			file_put_contents( $new_path, $contents );

			$new_attachments[] = $new_path;
		}

		return $new_attachments;
	}

	public function get_saved_attachment( $email_id, $og_path ) {
		$file_basename = basename( $og_path );
		$uploads_dir   = $this->get_uploads_dir_path( $email_id, array( $og_path ) );

		return sprintf( '%s%s', trailingslashit( $uploads_dir ), $file_basename );
	}

	private function get_uploads_dir_path( $email_id, $attachments ) {
		$upload_base = wp_get_upload_dir()['basedir'];
		$uploads_dir = sprintf( '%s/gravitysmtp/attachments/%s/', untrailingslashit( $upload_base ), $email_id );

		/**
		 * Allows third-party code to modify where attachments are stored for a given upload.
		 *
		 * @param string $uploads_dir the current directory for uploading this file
		 * @param int    $email_id    the current email ID
		 * @param array  $attachments an array of attachment paths
		 */
		return apply_filters( 'gravitysmtp_attachment_uploads_dir_path', $uploads_dir, $email_id, $attachments );
	}
}
