<?php
// This is sample of data that mapper returns formatted data same as it.
//		return Either::of( [
//			'translators'   => [
//               [
//					'user' => [
//						'id'            => 1,
//						'first'   		=> 'Translator',
//						'last'   		=> 'First',
//						'email'         => 'translator3@ytest.com',
//						'userName'      => 'translator',
//						'wpRole'        => 'author',
//					],
//					'languagePairs' => [
//						'en' => [ 'ar', 'bs'],
//					],
//				],
//            ]
//		] );

namespace WPML\ICLToATEMigration\Endpoints\Translators;

use WPML\FP\Either;
use WPML\FP\Fns;

class GetFromICLResponseMapper {

	/**
	 * Returns formatted data of translators.
	 *
	 * @param array $records
	 *
	 * @return callable|\WPML\FP\Right
	 */
	public static function map( $records ) {
		return Either::of( [ 'translators' => Fns::map( [ self::class, 'constructUserData' ], $records ) ] );
	}

	/**
	 * Formats translator data
	 *
	 * @param object $record
	 *
	 * @return array
	 */
	public static function constructUserData( $record ) {
		$user = get_user_by( 'email', $record->email );

		return [
			'user'          => [
				'id'       => $record->icl_id,
				'first'    => $record->first_name,
				'last'     => $record->last_name,
				'email'    => $record->email,
				'userName' => $user ? $user->data->user_login : strtolower( $record->first_name . '_' . $record->last_name ),
				'wpRole'   => $user ? current( $user->roles ) : 'subscriber'
			],
			'languagePairs' => self::constructUserLanguagePairs( $record->lang_pairs )
		];
	}

	/**
	 * Formats translator language pairs data
	 *
	 * @param array $langPairs
	 *
	 * @return array
	 */
	public static function constructUserLanguagePairs( $langPairs ) {
		$constructedLangPairs = [];

		foreach ( $langPairs as $langPair ) {
			$constructedLangPairs[ $langPair->from ][] = $langPair->to;
		}

		return $constructedLangPairs;
	}
}
