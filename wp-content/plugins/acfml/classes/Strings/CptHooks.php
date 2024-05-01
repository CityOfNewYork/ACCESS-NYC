<?php

namespace ACFML\Strings;

use ACFML\Strings\Helper\ContentTypeLabels;
use WPML\FP\Obj;

class CptHooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/**
	 * @var Factory $factory
	 */
	private $factory;

	/**
	 * @var Translator $translator
	 */
	private $translator;

	/**
	 * @param Factory    $factory
	 * @param Translator $translator
	 */
	public function __construct( Factory $factory, Translator $translator ) {
		$this->factory    = $factory;
		$this->translator = $translator;
	}

	public function add_hooks() {
		add_action( 'acf/update_post_type', [ $this, 'register' ] );
		add_filter( 'acf/post_type/registration_args', [ $this, 'translate' ], 10, 2 );
		add_filter( 'acf/load_post_types', [ $this, 'enterCptTitleHere' ] );
		add_action( 'acf/delete_post_type', [ $this, 'delete' ] );
	}

	/**
	 * @param array $postData
	 */
	public function register( $postData ) {
		$this->translator->registerCpt( $postData );
	}

	/**
	 * @param  array $postTypeArgs
	 * @param  array $postData
	 *
	 * @return array
	 */
	public function translate( $postTypeArgs, $postData ) { // phpcs:disable WordPress.WP.I18n
		return ContentTypeLabels::translateLabels(
			$postTypeArgs,
			$this->translator->translateCpt( $postData, $postTypeArgs ),
			[ 'description', 'enter_title_here' ]
		);
	}

	/**
	 * @param array $postData
	 */
	public function delete( $postData ) {
		$this->factory->createPackage( $postData['post_type'], Package::CPT_PACKAGE_KIND_SLUG )->delete();
	}

	/**
	 * @param  array $postsData
	 *
	 * @return array
	 */
	public function enterCptTitleHere( $postsData ) {
		array_walk( $postsData, function( &$value ) {
			/* phpcs:disable WordPress.CodeAnalysis.AssignmentInCondition.Found */
			if ( ! $this->isDoingEnterTitleHereFilter( $value['post_type'] ) ) {
				return;
			}

			if ( ! Obj::prop( 'enter_title_here', $value ) ) {
				return;
			}

			$translatedLabels = $this->translator->translateCpt(
				[
					'post_type'        => $value['post_type'],
					'enter_title_here' => $value['enter_title_here'],
				]
			);

			/* phpcs:disable WordPress.CodeAnalysis.AssignmentInCondition.Found */
			$value['enter_title_here'] = Obj::propOr( $value['enter_title_here'], 'enter_title_here', $translatedLabels );
		} );

		return $postsData;
	}

	/**
	 * @return string|null
	 */
	private static function getCurrentScreenPostType() {
		$currentScreen = get_current_screen();
		if ( ! $currentScreen ) {
			return null;
		}
		return $currentScreen->post_type;
	}

	/**
	 * @param  string $postType
	 *
	 * @return bool
	 */
	private function isDoingEnterTitleHereFilter( $postType ) {
		global $pagenow;

		if ( ! doing_filter( 'enter_title_here' ) ) {
			return false;
		}

		$isPostEditScreen = in_array( $pagenow, [ 'post.php', 'post-new.php' ], true );
		if ( ! $isPostEditScreen ) {
			return false;
		}

		if ( self::getCurrentScreenPostType() !== $postType ) {
			return false;
		}

		return true;
	}

}
