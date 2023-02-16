<?php

namespace WPML\Element\API;

use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;

class TranslationsRepository {
	/**
	 * It contains merged data from 3 tables: icl_translations, icl_translation_status and the latest record from icl_translate_job
	 *
	 * @var array
	 */
	private static $data = [];

	/**
	 * @var array
	 */
	private static $tridLanguageIndex = [];

	/** @var array */
	private static $translationIdIndex = [];

	public static function preloadForPosts( $posts ) {
		global $wpdb;

		$sql = self::getSqlTemplate();

		$condition = Lst::join(
			' OR ',
			Fns::map( function ( $post ) use ( $wpdb ) {
				return $wpdb->prepare(
					'(element_id = %d AND element_type = %s)',
					Obj::prop( 'ID', $post ),
					'post_' . Obj::prop( 'post_type', $post )
				);
			}, $posts ) );

		$sql .= "
			WHERE translations.trid IN (
				SELECT trid FROM {$wpdb->prefix}icl_translations WHERE {$condition} 
			)
		";

		self::appendResult( $sql );
	}

	public static function reset() {
		self::$data               = [];
		self::$translationIdIndex = [];
		self::$tridLanguageIndex  = [];
	}

	public static function getByTridAndLanguage( $trid, $language ) {
		return isset( self::$tridLanguageIndex[ $trid ][ $language ] ) ? self::$tridLanguageIndex[ $trid ][ $language ] : null;
	}

	public static function getByTranslationId( $translationId ) {
		return isset( self::$translationIdIndex[ $translationId ] ) ? self::$translationIdIndex[ $translationId ] : null;
	}

	private static function getSqlTemplate() {
		global $wpdb;

		$sql = "
			SELECT translations.translation_id,
				   translations.element_type,
				   translations.element_id,
			       translations.trid,
			       translations.language_code,
			       translations.source_language_code,
			       (
			           SELECT element_id FROM {$wpdb->prefix}icl_translations as originalTranslation 
			           WHERE originalTranslation.trid = translations.trid and originalTranslation.source_language_code IS NULL
			       ) as original_doc_id,
			       NULLIF(translations.source_language_code, '') IS NULL AS original,
			       translation_status.rid,
			       translation_status.status,
			       translation_status.translator_id,
			       translation_status.needs_update,
			       translation_status.review_status,
			       translation_status.translation_service,
			       translation_status.batch_id,
			       translation_status.timestamp,
			       translation_status.tp_id,
			       translation_status.ate_comm_retry_count,
			       jobs.max_job_id as job_id,
			       jobs.translated,
			       jobs.editor,
			       jobs.editor_job_id,
			       jobs.automatic,
			       jobs.ate_sync_count
			FROM {$wpdb->prefix}icl_translations as translations
			LEFT JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.translation_id = translations.translation_id
			LEFT JOIN (
			    SELECT 
			    	MAX(job_id) as max_job_id,
			        translated,
		            editor,
			        editor_job_id,
		            automatic,
			        ate_sync_count,
			        rid
			    FROM {$wpdb->prefix}icl_translate_job
			    GROUP BY job_id
			) as jobs ON jobs.rid = translation_status.rid
		";

		return $sql;
	}

	private static function appendResult( $sql ) {
		/** @var \wpdb */
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( $sql . ' AND 1 = %d', 1 ) ); // this is needed to utilize WPDBMock::prepare mock
		self::$data = Lst::concat( self::$data, Fns::map( Obj::evolve( [
			'translation_id'       => Cast::toInt(),
			'element_id'           => Cast::toInt(),
			'trid'                 => Cast::toInt(),
			'original_doc_id'      => Cast::toInt(),
			'rid'                  => Cast::toInt(),
			'status'               => Cast::toInt(),
			'translator_id'        => Cast::toInt(),
			'batch_id'             => Cast::toInt(),
			'tp_id'                => Cast::toInt(),
			'ate_comm_retry_count' => Cast::toInt(),
			'job_id'               => Cast::toInt(),
			'translated'           => Cast::toBool(),
			'editor_job_id'        => Cast::toInt(),
			'ate_sync_count'       => Cast::toInt(),
			'automatic'            => Cast::toBool(),
		] ), $results ) );

		foreach ( $results as &$row ) {
			self::$tridLanguageIndex[ Obj::prop( 'trid', $row ) ][ Obj::prop( 'language_code', $row ) ] = &$row;
			self::$translationIdIndex[ Obj::prop( 'translation_id', $row ) ] = &$row;
		}
	}
}