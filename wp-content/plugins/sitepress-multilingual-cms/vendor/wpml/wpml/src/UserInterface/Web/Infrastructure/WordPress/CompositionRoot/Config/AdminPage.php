<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config;

use WPML\UserInterface\Web\Core\SharedKernel\Config\Page as DomainPage;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Script;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Style;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\ApiInterface;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\PageInterface;

class AdminPage implements PageInterface {

  /** @var ApiInterface $api */
  private $api;


  public function __construct( ApiInterface $api ) {
    $this->api = $api;
  }


  public function register( DomainPage $page, $onLoadPageHandle ) {
    $loadPage =
      /** @return void */
      function( string $hook ) use ( $page, $onLoadPageHandle ) {
        add_action(
          $hook,
          function() use ( $page, $onLoadPageHandle ) {
            $onLoadPageHandle( $page );
          }
        );
      };

    if ( $useLegacy = $page->legacyExtension() ) {
      // Use legacy hook to load the page scripts, styles and endpoints.
      $loadPage( $useLegacy );
      return;
    }

    if ( $wpPageId = $this->loadPage( $page ) ) {
      // Subscribe to load page to trigger onLoadPageHandle when the page
      // is really loaded.
      $loadPage( 'load-' . str_replace( '.php', '', $wpPageId ) );
    }
  }


  public function loadStyle( Style $style ) {
      wp_enqueue_style(
        $style->id(),
        plugins_url( $style->src() ?: '', WPML_PUBLIC_DIR ),
        $style->dependencies(),
        WPML_VERSION
      );
  }


  public function registerScript( Script $script ) {
      wp_register_script(
        $script->id(),
        plugins_url( $script->src() ?: '', WPML_PUBLIC_DIR ),
        $script->dependencies(),
        WPML_VERSION,
        [
          'in_footer' => true
        ]
      );
  }


  public function loadScript( Script $script ) {
      wp_enqueue_script(
        $script->id(),
        plugins_url( $script->src() ?: '', WPML_PUBLIC_DIR ),
        $script->dependencies(),
        WPML_VERSION,
        [
          'in_footer' => true
        ]
      );
    wp_set_script_translations(
      $script->id(),
      'wpml',
      WPML_ROOT_DIR . '/languages/'
    );
  }


  /**
   * @param array<mixed> $data
   */
  public function provideDataForScript(
    Script $script,
    string $jsWindowKey,
    $data
  ) {
    wp_add_inline_script(
      $script->id(),
      'var ' . $jsWindowKey . ' = ' . json_encode( $data ) . ';',
      'before'
    );
  }


  /** @return ?string The WordPress name of the page or null if no page gets registered. */
  private function loadPage( DomainPage $page ) {
    if ( $page->legacyExtension() ) {
      // Legacy is registering the page and menu.
      return null;
    }

    if ( $page->parentId() ) {
      return $this->loadSubPage( $page );
    }

    if ( $page->legacyParentId() ) {
      return $this->legacyLoadPage( $page );
    }

    return add_menu_page(
      $page->title(),
      $page->menuTitle(),
      $page->capability(),
      $page->id(),
      [ $page, 'render' ],
      $page->icon(),
      $page->position()
    );
  }


  /**
   * @psalm-suppress HookNotFound Legacy hook 'wpml_admin_menu_configure'.
   */
  private function legacyLoadPage( DomainPage $page ): string {
    if ( ! $parentId = $page->legacyParentId() ) {
      return '';
    }

    add_action(
      'wpml_admin_menu_configure',
      /** @param string $menuId */
      function( $menuId ) use ( $page, $parentId )  {
        if ( $menuId !== $parentId ) {
          return;
        }

        $menu = [
          'order'      => $page->position(),
          'page_title' => $page->title(),
          'menu_title' => $page->menuTitle(),
          'capability' => $this->api->capabilityPlusAdmin( $page->capability() ),
          'menu_slug'  => $page->id(),
          'function'   => [ $page, 'render' ],
        ];

        do_action( 'wpml_admin_menu_register_item', $menu );
      }
    );

    // Manually create page hook name.
    $firstPart = $page->position() > 1 ? $parentId : 'toplevel'; // WordPress oddness.
    return strtolower( $firstPart . '_page_' . $page->id() );
  }


  /** @return string The WordPress name of the page. */
  private function loadSubPage( DomainPage $page ): string {
    return (string) add_submenu_page(
      $page->parentId() ?: '',
      $page->title(),
      $page->menuTitle(),
      $this->api->capabilityPlusAdmin( $page->capability() ),
      $page->id(),
      [ $page, 'render' ],
      $page->position()
    );
  }


}
