<?php

namespace WPML\TM\Editor;

use WPML\FP\Str;
use WPML\FP\Cast;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Option;
use WPML\TM\ATE\ClonedSites\ApiCommunication;
use WPML\UIPage;
use function WPML\FP\pipe;

class ATEDetailedErrorMessage {

	const ERROR_DETAILS_OPTION = 'wpml_ate_error_details';

	/**
	 * Parses error data and saves it to options table.
	 *
	 * @param $errorResponse
	 *
	 * @return void
	 */
	public static function saveDetailedError( $errorResponse ) {
		$errorCode    = $errorResponse->get_error_code();
		$errorMessage = $errorResponse->get_error_message();
		$errorData    = $errorResponse->get_error_data( $errorCode );

		$errorDetails = [
			'code'       => $errorCode,
			'message'    => $errorMessage,
			'error_data' => $errorData,
		];

		self::saveErrorDetailsInOptions( $errorDetails );
	}

	/**
	 * Returns single or multiple formatted error message depending on errors array existence in response.
	 *
	 * @param string $appendText
	 *
	 * @return string|null
	 */
	public static function readDetailedError( $appendText = null ) {
		$errorDetails = Option::getOr( self::ERROR_DETAILS_OPTION, [] );

		$detailedError = self::hasValidExplainedMessage( $errorDetails )
			? self::formattedDetailedErrors( $errorDetails, $appendText )
			: (
			self::isSiteMigrationError( $errorDetails )
				? self::formattedSiteMigrationError( $errorDetails )
				: null
			);

		self::deleteErrorDetailsFromOptions(); // deleting the option after message is ready to be displayed.

		return $detailedError;
	}

	/**
	 * Checks if valid explained message exists in error response.
	 *
	 * @param array $errorDetails
	 *
	 * @return mixed
	 */
	private static function hasValidExplainedMessage( $errorDetails ) {
		$hasExplainedMessage = pipe(
			Obj::path( [ 'error_data', 0, 'explained_message' ] ),
			Str::len(),
			Cast::toBool()
		);

		return $hasExplainedMessage( $errorDetails );
	}

	/**
	 * Checks if error is "Site moved or copied" (Happens when error code is 426)
	 *
	 * @param array $errorDetails
	 *
	 * @return mixed
	 */
	private static function isSiteMigrationError( $errorDetails ) {
		$isSiteMigrationError = pipe(
			Obj::prop( 'code' ),
			Cast::toInt(),
			Relation::equals( ApiCommunication::SITE_CLONED_ERROR )
		);

		return $isSiteMigrationError( $errorDetails );
	}

	/**
	 * The purpose of this function is to avoid the case when redirect is happened and data saved in this static class is lost
	 *
	 * @return void
	 */
	private static function saveErrorDetailsInOptions( $errorDetails ) {
		Option::updateWithoutAutoLoad( self::ERROR_DETAILS_OPTION, $errorDetails );
	}

	/**
	 * Deletes the error details from options.
	 *
	 * @return void
	 */
	private static function deleteErrorDetailsFromOptions() {
		Option::delete( self::ERROR_DETAILS_OPTION );
	}

	/**
	 * Returns multiple formatted error messages from errors array.
	 *
	 * @param array $errorDetails
	 * @param string $appendText
	 *
	 * @return string
	 */
	private static function formattedDetailedErrors( array $errorDetails, $appendText ) {
		$appendText = $appendText ? '<div>' . $appendText . '</div>' : '';

		$allErrors = '<div>';

		foreach ( Obj::prop( 'error_data', $errorDetails ) as $error ) {
			$allErrors .= '<div>' . Obj::propOr( '', 'explained_message', $error ) . '</div>';
		}

		return $allErrors . $appendText . '</div>';
	}

	private static function formattedSiteMigrationError( $errorDetails ) {
		return '<div>' . Obj::prop( 'message', $errorDetails ) . '</div>';
	}
}