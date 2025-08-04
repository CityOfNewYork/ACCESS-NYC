<?php

namespace Gravity_Forms\Gravity_Tools\Logging;

use Gravity_Forms\Gravity_Tools\Logging\Parsers\File_Log_Parser;

/**
 * File Logging Provider
 *
 * A logging provider which writes directly to a log file.
 */
class File_Logging_Provider implements Logging_Provider {
	const DEBUG = 1;
	const INFO  = 2;
	const WARN  = 3;
	const ERROR = 4;
	const FATAL = 5;
	const OFF   = 6;

	const LOG_OPEN    = 1;
	const OPEN_FAILED = 2;
	const LOG_CLOSED  = 3;

	public  $log_status  = self::LOG_CLOSED;
	public  $date_format = "Y-m-d G:i:s";
	public  $message_queue;
	private $offset;
	private $log_file;
	private $priority    = self::INFO;

	private $file_handle;

	private $timestamp_map = array(
		self::INFO  => "- INFO  -->",
		self::WARN  => "- WARN  -->",
		self::DEBUG => "- DEBUG -->",
		self::ERROR => "- ERROR -->",
		self::FATAL => "- FATAL -->",
	);

	/**
	 * @var File_Log_Parser
	 */
	private $line_parser;

	/**
	 * Constructor
	 *
	 * @since 1.0
	 *
	 * @param $filepath
	 * @param $priority
	 * @param $offset
	 */
	public function __construct( $filepath, $priority, $offset = 0, File_Log_Parser $line_parser ) {
		if ( $priority == self::OFF ) {
			return;
		}

		$this->offset        = $offset;
		$this->log_file      = $filepath;
		$this->message_queue = array();
		$this->priority      = $priority;
		$this->line_parser   = $line_parser;

		if ( file_exists( $this->log_file ) ) {
			if ( ! is_writable( $this->log_file ) ) {
				$this->log_status      = self::OPEN_FAILED;
				$this->message_queue[] = __( "The file exists, but could not be opened for writing. Check that appropriate permissions have been set.", 'gravitytools' );

				return;
			}
		}

		if ( $this->file_handle = fopen( $this->log_file, "a" ) ) {
			$this->log_status      = self::LOG_OPEN;
			$this->message_queue[] = __( "The log file was opened successfully.", 'gravitytools' );
		} else {
			$this->log_status      = self::OPEN_FAILED;
			$this->message_queue[] = __( "The file could not be opened. Check permissions.", 'gravitytools' );
		}

		return;
	}

	/**
	 * Close file on destruct.
	 *
	 * @since 1.0
	 */
	public function __destruct() {
		if ( $this->file_handle ) {
			fclose( $this->file_handle );
		}
	}

	public function get_log_file_path() {
		return $this->log_file;
	}

	/**
	 * Add info line.
	 *
	 * @since 1.0
	 *
	 * @param $line
	 *
	 * @return void
	 */
	public function log_info( $line ) {
		$this->log( $line, self::INFO );
	}

	/**
	 * Add debug line.
	 *
	 * @since 1.0
	 *
	 * @param $line
	 *
	 * @return void
	 */
	public function log_debug( $line ) {
		$this->log( $line, self::DEBUG );
	}

	/**
	 * Add warning line.
	 *
	 * @since 1.0
	 *
	 * @param $line
	 *
	 * @return void
	 */
	public function log_warning( $line ) {
		$this->log( $line, self::WARN );
	}

	/**
	 * Add error line.
	 *
	 * @since 1.0
	 *
	 * @param $line
	 *
	 * @return void
	 */
	public function log_error( $line ) {
		$this->log( $line, self::ERROR );
	}

	/**
	 * Add fatal line.
	 *
	 * @since 1.0
	 *
	 * @param $line
	 *
	 * @return void
	 */
	public function log_fatal( $line ) {
		$this->log( $line, self::FATAL );
	}

	/**
	 * Add a log line with a defined priority.
	 *
	 * @since 1.0
	 *
	 * @param $line
	 * @param $priority
	 *
	 * @return void
	 */
	public function log( $line, $priority ) {
		if ( $this->priority <= $priority ) {
			$status = $this->get_timeline( $priority );
			$this->write_line_to_log( "$status $line \n" );
		}
	}

	public function delete_log() {
		return;
	}

	/**
	 * Write a line to the log.
	 *
	 * @since 1.0
	 *
	 * @param $line
	 *
	 * @return void
	 */
	public function write_line_to_log( $line ) {
		if ( $this->log_status == self::LOG_OPEN && $this->priority != self::OFF ) {
			if ( fwrite( $this->file_handle, $line ) === false ) {
				$this->message_queue[] = __( "The file could not be written to. Check that appropriate permissions have been set.", 'gravitytools' );
			}
		}
	}

	public function get_lines() {
		$contents = file_get_contents( $this->get_log_file_path() );

		return $this->line_parser->parse_log( $contents );
	}

	/**
	 * Get the timeline tet for a given log type.
	 *
	 * @since 1.0
	 *
	 * @param $level
	 *
	 * @return string
	 */
	private function get_timeline( $level ) {

		if ( class_exists( 'DateTime' ) ) {
			$original_time = microtime( true ) + $this->offset;
			$microtime     = sprintf( '%06d', ( $original_time - floor( $original_time ) ) * 1000000 );
			$date          = new \DateTime( date( 'Y-m-d H:i:s.' . $microtime, (int) $original_time ) );
			$time          = $date->format( $this->date_format );

		} else {
			$time = gmdate( $this->date_format, time() + $this->offset );
		}

		return $this->get_formatted_timeline_for_type( $level, $time );
	}

	/**
	 * Get the formatted timeline text for a given type.
	 *
	 * @since 1.0
	 *
	 * @param $type
	 * @param $time
	 *
	 * @return string
	 */
	private function get_formatted_timeline_for_type( $type, $time ) {
		return sprintf( '[**] %s %s', $time, $this->timestamp_map[ $type ] );
	}
}