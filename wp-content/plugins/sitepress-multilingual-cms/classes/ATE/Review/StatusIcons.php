<?php


namespace WPML\TM\ATE\Review;

use WPML\Element\API\Languages;
use WPML\Element\API\PostTranslations;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\Setup\Option;
use WPML\TM\API\Jobs;
use function WPML\FP\partial;
use function WPML\FP\pipe;

class StatusIcons implements \IWPML_Backend_Action {

	public function add_hooks() {
		$ifNeedsReview = function ( $fn ) {
			$getJob          = Fns::converge( Jobs::getTridJob(), [
				Obj::prop( 'trid' ),
				Obj::prop( 'languageCode' )
			] );
			$doesNeedReview = pipe(
				$getJob,
				ReviewStatus::doesJobNeedReview()
			);

			return Logic::ifElse( $doesNeedReview, $fn, Obj::prop( 'default' ) );
		};

		Hooks::onFilter( 'wpml_css_class_to_translation', PHP_INT_MAX , 5 )
		     ->then( Hooks::getArgs( [ 0 => 'default', 2 => 'languageCode', 3 => 'trid', 4 => 'status' ] ) )
		     ->then( $ifNeedsReview( Fns::always( 'otgs-ico-needs-review' ) ) );

		Hooks::onFilter( 'wpml_text_to_translation', PHP_INT_MAX, 6 )
		     ->then( Hooks::getArgs( [ 0 => 'default', 2 => 'languageCode', 3 => 'trid', 5 => 'status' ] ) )
		     ->then( $ifNeedsReview ( self::getReviewTitle( 'languageCode' ) ) );

		Hooks::onFilter( 'wpml_link_to_translation', PHP_INT_MAX, 6 )
		     ->then( Hooks::getArgs( [ 0 => 'default', 1 => 'postId', 2 => 'langCode', 3 => 'trid', 5 => 'status' ] ) )
		     ->then( $this->setLink() );
	}

	public static function getReviewTitle( $langProp ) {
		return pipe(
			self::getLanguageName( $langProp ),
			Fns::unary( partial( 'sprintf', __( 'Review %s language', 'wpml-translation-management' ) ) )
		);
	}

	public static function getEditTitle( $langProp ) {
		return pipe(
			self::getLanguageName( $langProp ),
			Fns::unary( partial( 'sprintf', __( 'Edit %s translation', 'wpml-translation-management' ) ) )
		);
	}

	private static function getLanguageName( $langProp ) {
		return Fns::memorize( pipe(
			Obj::prop( $langProp ),
			Languages::getLanguageDetails(),
			Obj::prop( 'display_name' )
		) );
	}

	private function setLink() {
		return function ( $data ) {
			$isInProgress            = pipe(
				Obj::prop( 'status' ),
				Lst::includes( Fns::__, [ ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS, ICL_TM_ATE_NEEDS_RETRY ] )
			);
			$isInProgressOrCompleted = Logic::anyPass( [ $isInProgress, Relation::propEq( 'status', ICL_TM_COMPLETE ) ] );

			$getTranslations = Fns::memorize( PostTranslations::get() );

			$getTranslation = Fns::converge( Obj::prop(), [
				Obj::prop( 'langCode' ),
				pipe( Obj::prop( 'postId' ), $getTranslations )
			] );

			$getJob = Fns::converge( Jobs::getPostJob(), [
				Obj::prop( 'postId' ),
				Fns::always( 'post' ),
				Obj::prop( 'langCode' )
			] );

			$doesNeedsReview = pipe( Obj::prop( 'job' ), ReviewStatus::doesJobNeedReview() );

			$getPreviewLink = Fns::converge( PreviewLink::get(), [
				Obj::path( [ 'translation', 'element_id' ] ),
				Obj::path( [ 'job', 'job_id' ] )
			] );

			$disableInProgressIconOfAutomaticJob = Logic::ifElse(
				Logic::both( $isInProgress, Obj::path( [ 'job', 'automatic' ] ) ),
				Fns::always( 0 ), // no link at all
				Obj::prop( 'default' )
			);

			return Maybe::of( $data )
			            ->filter( $isInProgressOrCompleted )
			            ->map( Obj::addProp( 'translation', $getTranslation ) )
			            ->filter( Obj::prop( 'translation' ) )
			            ->reject( Obj::path( [ 'translation', 'original' ] ) )
			            ->map( Obj::addProp( 'job', $getJob ) )
			            ->filter( Obj::prop( 'job' ) )
			            ->map( Logic::ifElse( $doesNeedsReview, $getPreviewLink, $disableInProgressIconOfAutomaticJob ) )
			            ->getOrElse( Obj::prop( 'default', $data ) );
		};
	}
}
