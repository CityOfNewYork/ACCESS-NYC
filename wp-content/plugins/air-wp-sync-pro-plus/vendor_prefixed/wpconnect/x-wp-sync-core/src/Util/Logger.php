<?php
/**
 * Logger.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util;

/**
 * Class Logger
 */
class Logger {

	const LOG = 'log';

	const WARNING = 'warning';

	const ERROR = 'error';

	/**
	 * Log directory.
	 *
	 * @var string
	 */
	private $log_dir;

	/**
	 * Constructor.
	 *
	 * @param string $log_dir Log directory.
	 */
	public function __construct( $log_dir ) {
		$this->log_dir = $log_dir;
	}

	/**
	 * Log message to file and WPCLI output.
	 *
	 * @param string|\WP_Error|\Throwable $message Message to log.
	 * @param string                      $filename Log filename.
	 * @param string                      $level Log level ("log", "error", "warning").
	 * @param string[]                    $flags List of flags to display before the message.
	 */
	public function log( $message, $filename, $level = self::LOG, $flags = array() ) {
		if ( ! is_dir( $this->log_dir ) ) {
			wp_mkdir_p( $this->log_dir );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$file = fopen( $this->log_dir . '/' . sanitize_file_name( $filename ) . '.log', 'a' );
		if ( ! is_string( $message ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
				$message = var_export( $message, true );
			} else {
				$message_str = 'Logger::log, the $message parameter is not a string, to debug the object turn on WP_DEBUG.';
				if ( is_wp_error( $message ) ) {
					$message_str .= "\n" . $message->get_error_message();
				} elseif ( $message instanceof \Throwable ) {
					$message_str .= "\n" . $message->getMessage();
				}
				$message = $message_str;
			}
		}

		$flags_strings = '';
		if ( ! empty( $flags ) ) {
			$flags_strings = array_map(
				function ( $flag ) {
					return '[' . $flag . ']';
				},
				$flags
			);
			$flags_strings = implode( '', $flags_strings ) . ' ';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $file, "\n" . gmdate( 'Y-m-d H:i:s' ) . ' ' . $level . ' :: ' . $flags_strings . $message );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $file );

		if ( class_exists( 'WP_CLI' ) ) {
			$method = method_exists( 'WP_CLI', $level ) ? $level : 'log';
			\WP_CLI::$method( $message );
		}
	}

	/**
	 * Returns log dir (without trailing slash).
	 *
	 * @return string
	 */
	public function get_log_dir() {
		return untrailingslashit( $this->log_dir );
	}

	/**
	 * Returns the log files in ante-chronological order filter by a prefix if defined.
	 *
	 * @param string|false $prefix The prefix to filter the files to return.
	 *
	 * @return string[]
	 */
	public function get_log_files( $prefix = false ) {
		$files = glob( $this->get_log_dir() . '/*.log' );
		$files = array_combine( $files, array_map( 'filemtime', $files ) );
		arsort( $files );
		$files = array_keys( $files );
		if ( false !== $prefix ) {
			$prefix = sanitize_file_name( (string) $prefix );
			$files  = array_filter(
				$files,
				function ( $file ) use ( $prefix ) {
					return strpos( $file, $this->get_log_dir() . '/' . $prefix ) === 0;
				}
			);
			$files  = array_values( $files );
		}
		return $files;
	}

	/**
	 * Returns log dir URL (without trailing slash).
	 *
	 * @return string
	 */
	public function get_log_dir_url() {
		$upload_dir_info = wp_upload_dir( null, false );
		return untrailingslashit( str_replace( $upload_dir_info['basedir'], $upload_dir_info['baseurl'], $this->get_log_dir() ) );
	}
}
