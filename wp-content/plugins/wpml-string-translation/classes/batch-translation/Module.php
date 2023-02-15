<?php

namespace WPML\ST\Batch\Translation;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use function WPML\Container\make;
use function WPML\FP\curryN;
use function WPML\FP\partial;

/**
 * Class Module
 * @package WPML\ST\Batch\Translation
 * @method static callable|string getString( ...$id ) :: int → string
 * @method static callable getBatchId() :: ( string → int )
 * @method static callable|void batchStringsStorage( ...$records, ...$batchId, ...$stringId, ...$sourceLang ) :: Records → int → int → string → void
 * @method static callable|void setBatchLanguage( ...$batchId, ...$sourceLang ) :: int → string → void
 */
class Module {

	use Macroable;

	const EXTERNAL_TYPE    = 'st-batch_strings';
	const STRING_ID_PREFIX = 'batch-string-';

	public static function init() {
		global $sitepress, $wpdb;

		Records::installSchema( $wpdb );

		self::macro( 'getString', curryN( 1, function ( $id ) {
			return make( '\WPML_ST_String', [ ':string_id' => $id ] )->get_value();
		} ) );

		self::macro( 'getBatchId', curryN( 1, function ( $batch ) {
			return \TranslationProxy_Batch::update_translation_batch( $batch );
		} ) );

		$setLanguage = curryN( 4, [ $sitepress, 'set_element_language_details' ] );

		self::macro( 'setBatchLanguage', $setLanguage( Fns::__, self::EXTERNAL_TYPE, null, Fns::__ ) );

		self::macro( 'batchStringsStorage', curryN( 4, function ( callable $saveBatch, $batchId, $stringId, $sourceLang ) {
			self::setBatchLanguage( $batchId, $sourceLang );

			$saveBatch( $batchId, $stringId );
		} ) );

		$initializeTranslation = StringTranslations::markTranslationsAsInProgress(
			partial( [ Status::class, 'getStatusesOfBatch' ], $wpdb )
		);

		Hooks::addHooks(
			self::getBatchId(),
			self::batchStringsStorage( Records::set( $wpdb ) ),
			Records::get( $wpdb ),
			self::getString()
		);

		Hooks::addStringTranslationStatusHooks( StringTranslations::updateStatus(), $initializeTranslation );
	}

}
