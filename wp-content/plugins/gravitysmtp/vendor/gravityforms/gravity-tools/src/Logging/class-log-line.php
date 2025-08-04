<?php

namespace Gravity_Forms\Gravity_Tools\Logging;

class Log_Line {

	protected $timestamp;

	protected $priority;

	protected $line;

	protected $id;

	public function __construct( $timestamp, $priority, $line, $id ) {
		$this->timestamp = $timestamp;
		$this->priority  = $priority;
		$this->line      = $line;
		$this->id        = $id;
	}

	public function timestamp() {
		return $this->timestamp;
	}

	public function priority() {
		return $this->priority;
	}

	public function line() {
		return $this->line;
	}

	public function id() {
		return $this->id;
	}

}