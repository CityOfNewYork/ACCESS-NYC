<?php

namespace Gravity_Forms\Gravity_Tools\Logging;

class DB_Logging_Provider implements Logging_Provider {

	const DEBUG = 1;
	const INFO  = 2;
	const WARN  = 3;
	const ERROR = 4;
	const FATAL = 5;

	private $timestamp_map = array(
		self::INFO  => "- INFO  -->",
		self::WARN  => "- WARN  -->",
		self::DEBUG => "- DEBUG -->",
		self::ERROR => "- ERROR -->",
		self::FATAL => "- FATAL -->",
	);

	protected $model;

	public function __construct( $model ) {
		$this->model = $model;
	}

	public function log_info( $line ) {
		$this->log( $line, self::INFO );
	}

	public function log_debug( $line ) {
		$this->log( $line, self::DEBUG );
	}

	public function log_warning( $line ) {
		$this->log( $line, self::WARN );
	}

	public function log_error( $line ) {
		$this->log( $line, self::ERROR );
	}

	public function log_fatal( $line ) {
		$this->log( $line, self::FATAL );
	}

	public function log( $line, $priority ) {
		return $this->model->create( $line, $priority );
	}

	public function write_line_to_log( $line ) {
		return $this->log_debug( $line );
	}

	public function get_lines() {
		return $this->model->all( true );
	}

	public function delete_log() {
		return $this->model->clear();
	}

}