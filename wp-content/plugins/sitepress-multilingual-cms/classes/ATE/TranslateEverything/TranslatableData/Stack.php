<?php

namespace WPML\TM\ATE\TranslateEverything\TranslatableData;

class Stack {
	/** @var string $type */
	private $type;

	/** @var string $name */
	private $name;

	/** @var int $count */
	private $count;

	/** @var int|float $words */
	private $words = 0;

	/** @var bool $completed */
	private $completed = false;

	/** @var array $labels */
	private $labels;

	/**
	 * @param string                                  $type
	 * @param string                                  $name
	 * @param int                                     $count
	 * @param int|float                               $words
	 * @param array<singular: string, plural: string> $labels
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException When $type or $name is empty.
	 */
	public function __construct( $type, $name, $count = 0, $words = 0, $labels = [] ) {
		if ( empty( $type ) || empty( $name ) ) {
			throw new \InvalidArgumentException(
				'Stack "type" and "name" should not be empty.'
			);
		}
		$this->type   = $type;
		$this->name   = $name;
		$this->count  = $count;
		$this->words  = $words;
		$this->labels = $labels;
	}

	/** @return string  */
	public function type() {
		return $this->type;
	}

	/** @return string  */
	public function name() {
		return $this->name;
	}

	/** @return int  */
	public function count() {
		return $this->count;
	}

	/**
	 * @param int|float $words
	 *
	 * @return self
	 */
	public function addWords( $words ) {
		$this->words += $words;

		return $this;
	}

	/**
	 * @param int $count
	 *
	 * @return self
	 */
	public function addCount( $count ) {
		$this->count += $count;

		return $this;
	}

	public function completed() {
		$this->completed = true;
	}

	public function toArray() {
	  	return [
			'type'      => $this->type,
			'name'      => $this->name,
			'labels'    => $this->labels,
			'count'     => $this->count,
			'words'     => $this->words,
			'completed' => $this->completed,
		];
	}
}

