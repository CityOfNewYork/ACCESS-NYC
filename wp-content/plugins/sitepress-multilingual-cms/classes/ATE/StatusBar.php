<?php

namespace WPML\TM\ATE;

use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\User;
use WPML\TM\API\ATE\CachedLanguageMappings;
use WPML\TM\API\ATE\LanguageMappings;
use WPML\UIPage;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;
use WPML\Setup\Option;

class StatusBar {

	public static function add_hooks() {
		if (
			User::getCurrent()->has_cap( \WPML_Manage_Translations_Role::CAPABILITY )
			&& Option::shouldTranslateEverything()
		) {
			Hooks::onAction( 'admin_bar_menu', 999 )
			     ->then( spreadArgs( [ self::class, 'add' ] ) );
		}
	}

	public static function add( \WP_Admin_Bar $adminBar ) {
		$iconurl = "'" . ICL_PLUGIN_URL . '/res/img/icon16.png' . "'";

		$iconspan = '<span class="" style="
    float:left; width:22px !important; height:22px !important;
    margin-left: 5px !important; margin-top: 5px !important;
    background-image:url(' . $iconurl . ');"></span>';

		$title = '<div id="wpml-status-bar-count" style="display:inline-block"></div>';
		$adminBar->add_node(
			[
				'parent' => false,
				'id'     => 'ate-status-bar',
				'title'  => $iconspan . $title,
				'href'   => false
			]
		);
		$adminBar->add_node(
			[
				'parent' => 'ate-status-bar',
				'id'     => 'ate-status-bar-content',
				'meta'   => [ 'html' => '<div id="wpml-ate-status-bar-content"></div>' ],
				'href'   => false,
			]
		);
	}

	public static function getNotices() {
		return self::getNoticeAboutIneligibleLanguages();
	}

	private static function getNoticeAboutIneligibleLanguages() {
		if ( ! \WPML_TM_ATE_Status::is_enabled_and_activated() ) {
			return [];
		}

		$isNotDontMap = pipe( Obj::pathOr( null, [ 'mapping', 'targetId' ] ), Relation::equals( LanguageMappings::IGNORE_MAPPING_ID ), Logic::not() );
		$isDefault    = Relation::propEq( 'code', Languages::getDefaultCode() );
		$findDefault  = Fns::memorize( Lst::find( $isDefault ) );

		$supportedLanguages = Maybe::of( Languages::getActive() )
		                           ->map( CachedLanguageMappings::withCanBeTranslatedAutomatically() )
		                           ->map( Fns::filter( Obj::prop( 'can_be_translated_automatically' ) ) )
		                           ->reject( Logic::isEmpty() )
		                           ->getOrElse([]);

		$createNoticeWithText = function ( $text, $type = 'important', $isDefault = false ) use ( $supportedLanguages ) {
			return [
				[
					'id'     => 'unsupportedLanguages',
					'text'   => $text,
					'type'   => $type,
					'href'   => add_query_arg( [ 'trop' => 1 ] , UIPage::getLanguages() ),
					'target' => '_self',
					'_hasSupportedLanguages'    => ! $isDefault && count( $supportedLanguages ) > 1,
					// We consider that we support Automatic Translation when default and at least 1 more language is supported.
				]
			];
		};

		$createNoticeForDefaultLanguage = function ( $defaultLanguage ) use ( $createNoticeWithText ) {
			$txt = sprintf(
				__( "Your default language, %s, must be mapped to a supported language in order to use automatic translation.", 'wpml-translation-management' ),
				Obj::prop( 'english_name', $defaultLanguage )
			);

			return $createNoticeWithText( $txt, 'important', true );
		};

		$createNoticeForSecondaryLanguages = function ( $languages ) use ( $createNoticeWithText ) {
			if ( Lst::find( Logic::complement( CachedLanguageMappings::hasTheSameMappingAsDefaultLang() ), $languages ) ) {
				$txt = sprintf(
					__( "The following language(s) on your site must be mapped to a supported language in order to use automatic translation: %s", 'wpml-translation-management' ),
					Lst::join( ', ', Lst::pluck( 'english_name', $languages ) )
				);

				return $createNoticeWithText( $txt );
			}

			return $createNoticeWithText( self::getNoticeMsgForLanguageWithTheSameMappingAsDefault( $languages ), 'info' );
		};

		$createNotice = Logic::ifElse( $findDefault, pipe( $findDefault, $createNoticeForDefaultLanguage ), $createNoticeForSecondaryLanguages );

		return Maybe::of( Languages::getActive() )
		            ->map( CachedLanguageMappings::withCanBeTranslatedAutomatically() )
		            ->map( Fns::reject( Obj::prop( 'can_be_translated_automatically' ) ) )
		            ->reject( Logic::isEmpty() )
		            ->map( CachedLanguageMappings::withMapping() )
		            ->map( Fns::filter( Logic::anyPass( [ $isDefault, $isNotDontMap ] ) ) )
		            ->reject( Logic::isEmpty() )
		            ->map( $createNotice )
		            ->getOrElse( [] );
	}

	public static function getNoticeMsgForLanguageWithTheSameMappingAsDefault( $languages ) {
		return sprintf(
			__( "It's not possible to use automatic translation with your current language setup, as the following languages are mapped to the same language as the default: %s", 'wpml-translation-management' ),
			Lst::join( ', ', Lst::pluck( 'english_name', $languages ) )
		);
	}
}
