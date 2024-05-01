<?php


namespace WPML\BlockEditor;

use WPML\BlockEditor\Blocks\LanguageSwitcher;
use WPML\LIB\WP\Hooks;
use WPML\Core\WP\App\Resources;
use function WPML\Container\make;
use function WPML\FP\spreadArgs;

class Loader implements \IWPML_Backend_Action, \IWPML_REST_Action {

	const SCRIPT_NAME = 'wpml-blocks';

	/** @var array Contains the script data that needs to be localized for the registered blocks. */
	private $localizedScriptData = [];

	public function add_hooks() {
		if ( \WPML_Block_Editor_Helper::is_active() ) {
			Hooks::onAction( 'init' )
			     ->then( [ $this, 'registerBlocks' ] )
			     ->then( [ $this, 'maybeEnqueueNavigationBlockStyles' ] );

			Hooks::onAction( 'wp_enqueue_scripts' )
			     ->then( [ $this, 'enqueueBlockStyles' ] );

			Hooks::onAction( 'enqueue_block_editor_assets' )
			     ->then( [ $this, 'enqueueBlockAssets' ] );

			Hooks::onFilter( 'block_categories_all', 10, 2 )
			     ->then( spreadArgs( [ $this, 'registerCategory' ] ) );
		}
	}

	/**
	 * @param array[] $block_categories
	 *
	 * @return mixed
	 */
	public function registerCategory( $block_categories ) {
		array_push(
			$block_categories,
			[
				'slug'  => 'wpml',
				'title' => __( 'WPML', 'sitepress-multilingual-cms' ),
				'icon'  => null,
			]
		);

		return $block_categories;
	}

	/**
	 * Register blocks that need server side render.
	 */
	public function registerBlocks() {
		$LSLocalizedScriptData     = make( LanguageSwitcher::class )->register();
		$this->localizedScriptData = array_merge( $this->localizedScriptData, $LSLocalizedScriptData );
	}

	/**
	 * @return void
	 */
	public function enqueueBlockAssets() {
		$dependencies        = array_merge( [
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		], $this->getEditorDependencies() );
		$localizedScriptData = [ 'name' => 'WPMLBlocks', 'data' => $this->localizedScriptData ];
		$enqueuedApp         = Resources::enqueueApp( 'blocks' );
		$enqueuedApp( $localizedScriptData, $dependencies );
	}

	public function enqueueBlockStyles() {
		wp_enqueue_style(
			self::SCRIPT_NAME,
			ICL_PLUGIN_URL . '/dist/css/blocks/styles.css',
			[],
			ICL_SITEPRESS_VERSION
		);
	}

	/**
	 * We inherit the WP navigation block styles while rendering our Language Switcher Block,
	 * so when there's no navigation block is rendered, we still need to enqueue the wp-block-navigation styles so that.,
	 * the Language Switcher Block renders properly.
	 *
	 * @return void
	 * @see wpmldev-2422
	 * @see wpmldev-2491
	 *
	 */
	public function maybeEnqueueNavigationBlockStyles() {

		if ( ! wp_style_is( 'wp-block-navigation', 'enqueued' ) || ! wp_style_is( 'wp-block-navigation', 'queue' ) ) {
			add_filter( 'render_block', function ( $blockContent, $block ) {
				if ( $block['blockName'] === LanguageSwitcher::BLOCK_LANGUAGE_SWITCHER ) {
					wp_enqueue_style( 'wp-block-navigation' );
				}

				return $blockContent;
			}, 10, 2 );
		}
	}

	/**
	 * @return string[]
	 */
	public function getEditorDependencies() {
		global $pagenow;

		if ( is_admin() && 'widgets.php' === $pagenow ) {
			return [ 'wp-edit-widgets' ];
		}

		return [ 'wp-editor' ];
	}
}
