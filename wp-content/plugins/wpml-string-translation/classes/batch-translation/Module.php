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
 *
 * @phpstan-type curried '__CURRIED_PLACEHOLDER__'
 *
 * @method static callable getBatchId() :: ( string → int )
 * @method static callable|void setBatchLanguage( ...$batchId, ...$sourceLang ) :: int → string → void
 */
class Module {

	use Macroable;

	const EXTERNAL_TYPE    = 'st-batch_strings';
	const STRING_ID_PREFIX = 'batch-string-';

	public static function init() {
		global $sitepress, $wpdb;

		Records::installSchema( $wpdb );

		self::macro( 'getBatchId', curryN( 1, function ( $batch ) {
			return \TranslationProxy_Batch::update_translation_batch( $batch );
		} ) );

		$setLanguage = curryN( 4, [ $sitepress, 'set_element_language_details' ] );

		self::macro( 'setBatchLanguage', $setLanguage( Fns::__, self::EXTERNAL_TYPE, null, Fns::__ ) );

		/** @var callable $initializeTranslation */
		$initializeTranslation = StringTranslations::markTranslationsAsInProgress(
			partial( [ Status::class, 'getStatusesOfBatch' ], $wpdb )
		);

		/** @var callable $recordSetter */
		$recordSetter = Records::set( $wpdb );

		Hooks::addHooks(
			self::getBatchId(),
			self::batchStringsStorage( $recordSetter ),
			Records::get( $wpdb ),
			self::getString()
		);

		Hooks::addStringTranslationStatusHooks( StringTranslations::updateStatus(), $initializeTranslation );
	}

	/**
	 * @param int $id
	 * @return string|callable
	 * @phpstan-return ($id is not null ? string : callable )
	 */
	public static function getString( $id = null ) {
		return call_user_func_array(
			curryN( 1, function ( $id ) {
				return make( '\WPML_ST_String', [ ':string_id' => $id ] )->get_value();
			} ),
			func_get_args()
		);
	}

	/**
	 * @param callable|curried $saveBatch
	 * @param int|curried $batchId
	 * @param int|curried $stringId
	 * @param string|curried $sourceLang
	 * @return void|callable
	 *
	 * @phpstan-param ?callable $saveBatch
	 * @phpstan-param ?int $batchId
	 * @phpstan-param ?int $stringId
	 * @phpstan-param ?string $sourceLang
	 *
	 * @phpstan-return ( $sourceLang is not null ? void : callable )
	 *
	 */
	public static function batchStringsStorage( callable $saveBatch = null, $batchId  = null, $stringId  = null, $sourceLang  = null ) {
		return call_user_func_array(
			curryN( 4, function ( callable $saveBatch, $batchId, $stringId, $sourceLang ) {
				self::setBatchLanguage( $batchId, $sourceLang );

				$saveBatch( $batchId, $stringId );
			} ),
			func_get_args()
		);
	}

}
