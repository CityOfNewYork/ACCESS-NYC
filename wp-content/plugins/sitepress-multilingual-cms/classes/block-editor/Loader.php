<?php


namespace WPML\BlockEditor;

use WPML\BlockEditor\Blocks\LanguageSwitcher;
use WPML\LIB\WP\Hooks;
use WPML\Core\WP\App\Resources;
use function WPML\Container\make;
use function WPML\FP\spreadArgs;

class Loader implements \IWPML_Backend_Action, \IWPML_REST_Action {

	const BLOCK_LANGUAGE_SWITCHER            = 'wpml/language-switcher';
	const BLOCK_LANGUAGE_SWITCHER_NAVIGATION = 'wpml/navigation-language-switcher';

	const SCRIPT_NAME = 'wpml-blocks';

	/** @var array Contains the script data that needs to be localized for the registered blocks. */
	private $localizedScriptData = [];

	/** @var [string] Name of blocks which css is already loaded. */
	private $cssLoaded = [];

	public function add_hooks() {
		if ( \WPML_Block_Editor_Helper::is_active() ) {
			Hooks::onAction( 'init' )
			     ->then( [ $this, 'registerBlocks' ] )
			     ->then( [ $this, 'maybeEnqueueNavigationBlockStyles' ] );

			add_filter(
				'render_block',
				[ $this, 'frontendPrintStyleIfBlockIsUsed' ],
				10,
				2
			);

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
		$enqueuedApp = Resources::enqueueAppWithoutVendor( 'blocks' );
		$enqueuedApp( $localizedScriptData, $dependencies );
	}

	public function frontendPrintStyleIfBlockIsUsed( $content, $block ) {
		if ( is_admin() ) {
			// Dependening on the setup the backend might also use render_block.
			// We don't want to include the style in that case as the full css
			// file is already loaded. Same for ajax requests.
			return $content;
		}

		if (
			self::BLOCK_LANGUAGE_SWITCHER !== $block['blockName'] &&
			self::BLOCK_LANGUAGE_SWITCHER_NAVIGATION !== $block['blockName']
		) {
			// No language switcher block.
			return $content;
		}

		// Always include language switcher styles.
		$css = $this->styleLanguageSwitcher();

		// Check if the navigation language switcher is used.
		if ( self::BLOCK_LANGUAGE_SWITCHER_NAVIGATION === $block['blockName'] ) {
			$css .= $this->styleLanguageSwitcherNavigation();

			// Both css files are loaded, so we can remove the filter.
			remove_filter(
				'render_block',
				[ $this, 'frontendPrintStyleIfBlockIsUsed' ]
			);
		}

		return ! empty( $css )
			? '<style>' . $css . '</style>' . $content
			: $content;
	}

	/**
	 * @return string
	 */
	private function styleLanguageSwitcher() {
		if ( in_array( self::BLOCK_LANGUAGE_SWITCHER, $this->cssLoaded, true ) ) {
			return '';
		}

		$this->cssLoaded[] = self::BLOCK_LANGUAGE_SWITCHER;
		$css = file_get_contents(
			WPML_PLUGIN_PATH . '/dist/css/blocks/language-switcher.css'
		);

		return $css ?: '';
	}

	/**
	 * @return string
	 */
	private function styleLanguageSwitcherNavigation() {
		if ( in_array( self::BLOCK_LANGUAGE_SWITCHER_NAVIGATION, $this->cssLoaded, true ) ) {
			return '';
		}

		$this->cssLoaded[] = self::BLOCK_LANGUAGE_SWITCHER_NAVIGATION;
		$css = file_get_contents(
			WPML_PLUGIN_PATH . '/dist/css/blocks/language-switcher-navigation.css'
		);

		return $css ?: '';
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
