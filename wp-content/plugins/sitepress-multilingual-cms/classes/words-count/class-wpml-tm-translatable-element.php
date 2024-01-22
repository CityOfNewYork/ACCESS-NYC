<?php
abstract class WPML_TM_Translatable_Element {

	/** @var WPML_TM_Word_Count_Records $word_count_records */
	protected $word_count_records;

	/** @var WPML_TM_Word_Count_Single_Process $single_process */
	protected $single_process;

	/** @var int $id */
	protected $id;

	/**
	 * @param int|false                         $id
	 * @param WPML_TM_Word_Count_Records        $word_count_records
	 * @param WPML_TM_Word_Count_Single_Process $single_process
	 */
	public function __construct(
		$id,
		WPML_TM_Word_Count_Records $word_count_records,
		WPML_TM_Word_Count_Single_Process $single_process
	) {
		$this->word_count_records = $word_count_records;
		$this->single_process     = $single_process;
		$this->set_id( $id );
	}

	public function set_id( $id ) {
		if ( ! $id ) {
			return;
		}

		$this->id = $id;
		$this->init( $id );
	}

	abstract protected function init( $id );

	abstract public function get_type_name( $label = null );

	abstract protected function get_type();

	abstract protected function get_total_words();

	/** @return int */
	public function get_words_count() {
		$total_words = $this->get_total_words();

		if ( $total_words ) {
			return $total_words;
		}

		$this->single_process->process( $this->get_type(), $this->id );
		return $this->get_total_words();
	}
}
