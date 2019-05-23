<?php

class WPML_TP_Job_States {

	const RECEIVED             = 'received';
	const WAITING_TRANSLATIONS = 'waiting_translation';
	const TRANSLATION_READY    = 'translation_ready';
	const DELIVERED            = 'delivered';
	const CANCELLED            = 'cancelled';
	const ANY                  = 'any';

	/**
	 * @return array
	 */
	public static function get_possible_states() {
		return array(
			self::RECEIVED,
			self::WAITING_TRANSLATIONS,
			self::TRANSLATION_READY,
			self::DELIVERED,
			self::CANCELLED,
			self::ANY,
		);
	}

	/**
	 * @return string
	 */
	public static function get_default_state() {
		return self::WAITING_TRANSLATIONS;
	}

	/**
	 * @return array
	 */
	public static function get_finished_states() {
		return array(
			self::TRANSLATION_READY,
			self::DELIVERED,
			self::CANCELLED,
		);
	}

	public static function map_tp_state_to_local( $tp_state ) {
		switch ( $tp_state ) {
			case self::TRANSLATION_READY:
			case self::DELIVERED:
				return ICL_TM_TRANSLATION_READY_TO_DOWNLOAD;
			case self::CANCELLED:
				return ICL_TM_NOT_TRANSLATED;
			default:
				return ICL_TM_IN_PROGRESS;
		}
	}
}