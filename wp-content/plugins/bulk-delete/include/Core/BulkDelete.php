<?php

namespace BulkWP\BulkDelete\Core;

use BulkWP\BulkDelete\Core\Addon\Upseller;
use BulkWP\BulkDelete\Core\Base\BasePage;
use BulkWP\BulkDelete\Core\Cron\CronListPage;
use BulkWP\BulkDelete\Core\Metas\DeleteMetasPage;
use BulkWP\BulkDelete\Core\Metas\Modules\DeleteCommentMetaModule;
use BulkWP\BulkDelete\Core\Metas\Modules\DeletePostMetaModule;
use BulkWP\BulkDelete\Core\Metas\Modules\DeleteUserMetaModule;
use BulkWP\BulkDelete\Core\Pages\DeletePagesPage;
use BulkWP\BulkDelete\Core\Pages\Modules\DeletePagesByStatusModule;
use BulkWP\BulkDelete\Core\Posts\DeletePostsPage;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByCategoryModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByCommentsModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByPostTypeModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByRevisionModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByStatusModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByStickyPostModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByTagModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByTaxonomyModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByURLModule;
use BulkWP\BulkDelete\Core\SystemInfo\SystemInfoPage;
use BulkWP\BulkDelete\Core\Terms\DeleteTermsPage;
use BulkWP\BulkDelete\Core\Terms\Modules\DeleteTermsByNameModule;
use BulkWP\BulkDelete\Core\Terms\Modules\DeleteTermsByPostCountModule;
use BulkWP\BulkDelete\Core\Users\DeleteUsersPage;
use BulkWP\BulkDelete\Core\Users\Modules\DeleteUsersByUserMetaModule;
use BulkWP\BulkDelete\Core\Users\Modules\DeleteUsersByUserRoleModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Main Plugin class.
 *
 * @since 5.0 Converted to Singleton
 * @since 6.0.0 Renamed to BulkDelete and added namespace.
 */
final class BulkDelete {
	/**
	 * The one true BulkDelete instance.
	 *
	 * @var BulkDelete
	 *
	 * @since 5.0
	 */
	private static $instance;

	/**
	 * Path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Path where translations are stored.
	 *
	 * @var string
	 */
	private $translations_path;

	/**
	 * Has the plugin loaded?
	 *
	 * @since 6.0.0
	 *
	 * @var bool
	 */
	private $loaded = false;

	/**
	 * Controller that handles all requests and nonce checks.
	 *
	 * @var \BulkWP\BulkDelete\Core\Controller
	 */
	private $controller;

	/**
	 * Upseller responsible for upselling add-ons.
	 *
	 * @since 6.0.0
	 *
	 * @var \BulkWP\BulkDelete\Core\Addon\Upseller
	 */
	private $upseller;

	/**
	 * Bulk Delete Autoloader.
	 *
	 * Will be used by add-ons to extend the namespace.
	 *
	 * @var \BulkWP\BulkDelete\BulkDeleteAutoloader
	 */
	private $loader;

	/**
	 * List of Primary Admin pages.
	 *
	 * @var \BulkWP\BulkDelete\Core\Base\BaseDeletePage[]
	 *
	 * @since 6.0.0
	 */
	private $primary_pages = array();

	/**
	 * List of Secondary Admin pages.
	 *
	 * @var BasePage[]
	 *
	 * @since 6.0.0
	 */
	private $secondary_pages = array();

	/**
	 * Plugin version.
	 */
	const VERSION = '6.0.2';

	/**
	 * Set the BulkDelete constructor as private.
	 *
	 * An instance should be created by calling the `get_instance` method.
	 *
	 * @see BulkDelete::get_instance()
	 */
	private function __construct() {}

	/**
	 * Main BulkDelete Instance.
	 *
	 * Insures that only one instance of BulkDelete exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since     5.0
	 * @static
	 * @staticvar array $instance
	 *
	 * @return BulkDelete The one true instance of BulkDelete.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof BulkDelete ) ) {
			self::$instance = new BulkDelete();
		}

		return self::$instance;
	}

	/**
	 * Load the plugin if it is not loaded.
	 * The plugin will be loaded only it is an admin request or a cron request.
	 *
	 * This function will be invoked in the `plugins_loaded` hook.
	 */
	public function load() {
		if ( $this->loaded ) {
			return;
		}

		if ( ! $this->is_admin_or_cron() ) {
			return;
		}

		$this->load_dependencies();
		$this->setup_actions();

		$this->loaded = true;

		/**
		 * Bulk Delete plugin loaded.
		 *
		 * @since 6.0.0
		 *
		 * @param string Plugin main file.
		 */
		do_action( 'bd_loaded', $this->get_plugin_file() );

		$this->load_primary_pages();
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since  5.0
	 * @access protected
	 *
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( "This class can't be cloned. Use `get_instance()` method to get an instance.", 'bulk-delete' ), '5.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since  5.0
	 * @access protected
	 *
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( "This class can't be serialized. Use `get_instance()` method to get an instance.", 'bulk-delete' ), '5.0' );
	}

	/**
	 * Load all dependencies.
	 *
	 * @since 6.0.0
	 */
	private function load_dependencies() {
		$this->controller = new Controller();
		$this->controller->load();

		$this->upseller = new Upseller();
		$this->upseller->load();
	}

	/**
	 * Loads the plugin's actions and hooks.
	 *
	 * @access private
	 *
	 * @since  5.0
	 *
	 * @return void
	 */
	private function setup_actions() {
		add_action( 'init', array( $this, 'on_init' ) );

		add_action( 'admin_menu', array( $this, 'on_admin_menu' ) );
	}

	/**
	 * Triggered when the `init` hook is fired.
	 *
	 * @since 6.0.0
	 */
	public function on_init() {
		$this->load_textdomain();
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @since  5.0
	 */
	private function load_textdomain() {
		load_plugin_textdomain( 'bulk-delete', false, $this->get_translations_path() );
	}

	/**
	 * Triggered when the `admin_menu` hook is fired.
	 *
	 * Register all admin pages.
	 *
	 * @since 6.0.0
	 */
	public function on_admin_menu() {
		foreach ( $this->get_primary_pages() as $page ) {
			$page->register();
		}

		\Bulk_Delete_Misc::add_menu();

		/**
		 * Runs just after adding all *delete* menu items to Bulk WP main menu.
		 *
		 * This action is primarily for adding extra *delete* menu items to the Bulk WP main menu.
		 *
		 * @since 5.3
		 */
		do_action( 'bd_after_primary_menus' );

		/**
		 * Runs just before adding non-action menu items to Bulk WP main menu.
		 *
		 * This action is primarily for adding extra menu items before non-action menu items to the Bulk WP main menu.
		 *
		 * @since 5.3
		 */
		do_action( 'bd_before_secondary_menus' );

		foreach ( $this->get_secondary_pages() as $page ) {
			$page->register();
		}

		$this->addon_page = add_submenu_page(
			\Bulk_Delete::POSTS_PAGE_SLUG,
			__( 'Addon Licenses', 'bulk-delete' ),
			__( 'Addon Licenses', 'bulk-delete' ),
			'activate_plugins',
			\Bulk_Delete::ADDON_PAGE_SLUG,
			array( 'BD_License', 'display_addon_page' )
		);

		/**
		 * Runs just after adding all menu items to Bulk WP main menu.
		 *
		 * This action is primarily for adding extra menu items to the Bulk WP main menu.
		 *
		 * @since 5.3
		 */
		do_action( 'bd_after_all_menus' );
	}

	/**
	 * Get the list of registered admin pages.
	 *
	 * @since 6.0.0
	 *
	 * @return \BulkWP\BulkDelete\Core\Base\BaseDeletePage[] List of Primary Admin pages.
	 */
	private function get_primary_pages() {
		if ( empty( $this->primary_pages ) ) {
			$this->load_primary_pages();
		}

		return $this->primary_pages;
	}

	/**
	 * Load Primary admin pages.
	 *
	 * The pages need to be loaded in `init` hook, since the association between page and modules is needed in cron requests.
	 */
	private function load_primary_pages() {
		$posts_page = $this->get_delete_posts_admin_page();
		$pages_page = $this->get_delete_pages_admin_page();
		$users_page = $this->get_delete_users_admin_page();
		$metas_page = $this->get_delete_metas_admin_page();
		$terms_page = $this->get_delete_terms_admin_page();

		$this->primary_pages[ $posts_page->get_page_slug() ] = $posts_page;
		$this->primary_pages[ $pages_page->get_page_slug() ] = $pages_page;
		$this->primary_pages[ $users_page->get_page_slug() ] = $users_page;
		$this->primary_pages[ $metas_page->get_page_slug() ] = $metas_page;
		$this->primary_pages[ $terms_page->get_page_slug() ] = $terms_page;

		/**
		 * List of primary admin pages.
		 *
		 * @since 6.0.0
		 *
		 * @param \BulkWP\BulkDelete\Core\Base\BaseDeletePage[] List of Admin pages.
		 */
		$this->primary_pages = apply_filters( 'bd_primary_pages', $this->primary_pages );
	}

	/**
	 * Get Bulk Delete Posts admin page.
	 *
	 * @return \BulkWP\BulkDelete\Core\Posts\DeletePostsPage
	 */
	private function get_delete_posts_admin_page() {
		$posts_page = new DeletePostsPage( $this->get_plugin_file() );

		$posts_page->add_module( new DeletePostsByStatusModule() );
		$posts_page->add_module( new DeletePostsByCategoryModule() );
		$posts_page->add_module( new DeletePostsByTagModule() );
		$posts_page->add_module( new DeletePostsByTaxonomyModule() );
		$posts_page->add_module( new DeletePostsByPostTypeModule() );
		$posts_page->add_module( new DeletePostsByCommentsModule() );
		$posts_page->add_module( new DeletePostsByURLModule() );
		$posts_page->add_module( new DeletePostsByRevisionModule() );
		$posts_page->add_module( new DeletePostsByStickyPostModule() );

		/**
		 * After the modules are registered in the delete posts page.
		 *
		 * @since 6.0.0
		 *
		 * @param DeletePostsPage $posts_page The page in which the modules are registered.
		 */
		do_action( "bd_after_modules_{$posts_page->get_page_slug()}", $posts_page );

		/**
		 * After the modules are registered in a delete page.
		 *
		 * @since 6.0.0
		 *
		 * @param BasePage $posts_page The page in which the modules are registered.
		 */
		do_action( 'bd_after_modules', $posts_page );

		return $posts_page;
	}

	/**
	 * Get Bulk Delete Pages admin page.
	 *
	 * @since 6.0.0
	 *
	 * @return \BulkWP\BulkDelete\Core\Pages\DeletePagesPage
	 */
	private function get_delete_pages_admin_page() {
		$pages_page = new DeletePagesPage( $this->get_plugin_file() );

		$pages_page->add_module( new DeletePagesByStatusModule() );

		/**
		 * After the modules are registered in the delete pages page.
		 *
		 * @since 6.0.0
		 *
		 * @param DeletePagesPage $pages_page The page in which the modules are registered.
		 */
		do_action( "bd_after_modules_{$pages_page->get_page_slug()}", $pages_page );

		/**
		 * After the modules are registered in a delete page.
		 *
		 * @since 6.0.0
		 *
		 * @param BasePage $pages_page The page in which the modules are registered.
		 */
		do_action( 'bd_after_modules', $pages_page );

		return $pages_page;
	}

	/**
	 * Get Bulk Delete Users admin page.
	 *
	 * @since 6.0.0
	 *
	 * @return \BulkWP\BulkDelete\Core\Users\DeleteUsersPage
	 */
	private function get_delete_users_admin_page() {
		$users_page = new DeleteUsersPage( $this->get_plugin_file() );

		$users_page->add_module( new DeleteUsersByUserRoleModule() );
		$users_page->add_module( new DeleteUsersByUserMetaModule() );

		/**
		 * After the modules are registered in the delete users page.
		 *
		 * @since 6.0.0
		 *
		 * @param DeleteUsersPage $users_page The page in which the modules are registered.
		 */
		do_action( "bd_after_modules_{$users_page->get_page_slug()}", $users_page );

		/**
		 * After the modules are registered in a delete page.
		 *
		 * @since 6.0.0
		 *
		 * @param BasePage $users_page The page in which the modules are registered.
		 */
		do_action( 'bd_after_modules', $users_page );

		return $users_page;
	}

	/**
	 * Get Bulk Delete Metas admin page.
	 *
	 * @since 6.0.0
	 *
	 * @return \BulkWP\BulkDelete\Core\Metas\DeleteMetasPage
	 */
	private function get_delete_metas_admin_page() {
		$metas_page = new DeleteMetasPage( $this->get_plugin_file() );

		$metas_page->add_module( new DeletePostMetaModule() );
		$metas_page->add_module( new DeleteUserMetaModule() );
		$metas_page->add_module( new DeleteCommentMetaModule() );

		/**
		 * After the modules are registered in the delete metas page.
		 *
		 * @since 6.0.0
		 *
		 * @param DeleteMetasPage $metas_page The page in which the modules are registered.
		 */
		do_action( "bd_after_modules_{$metas_page->get_page_slug()}", $metas_page );

		/**
		 * After the modules are registered in a delete page.
		 *
		 * @since 6.0.0
		 *
		 * @param BasePage $metas_page The page in which the modules are registered.
		 */
		do_action( 'bd_after_modules', $metas_page );

		return $metas_page;
	}

	/**
	 * Get Bulk Delete Terms admin page.
	 *
	 * @since 6.0.0
	 *
	 * @return \BulkWP\BulkDelete\Core\Terms\DeleteTermsPage
	 */
	private function get_delete_terms_admin_page() {
		$terms_page = new DeleteTermsPage( $this->get_plugin_file() );

		$terms_page->add_module( new DeleteTermsByNameModule() );
		$terms_page->add_module( new DeleteTermsByPostCountModule() );

		/**
		 * After the modules are registered in the delete terms page.
		 *
		 * @since 6.0.0
		 *
		 * @param DeleteTermsPage $terms_page The page in which the modules are registered.
		 */
		do_action( "bd_after_modules_{$terms_page->get_page_slug()}", $terms_page );

		/**
		 * After the modules are registered in a delete page.
		 *
		 * @since 6.0.0
		 *
		 * @param BasePage $terms_page The page in which the modules are registered.
		 */
		do_action( 'bd_after_modules', $terms_page );

		return $terms_page;
	}

	/**
	 * Get the Cron List admin page.
	 *
	 * @since 6.0.0
	 *
	 * @return \BulkWP\BulkDelete\Core\Cron\CronListPage
	 */
	private function get_cron_list_admin_page() {
		$cron_list_page = new CronListPage( $this->get_plugin_file() );

		return $cron_list_page;
	}

	/**
	 * Get the System Info page.
	 *
	 * @since 6.0.0
	 *
	 * @return \BulkWP\BulkDelete\Core\SystemInfo\SystemInfoPage
	 */
	private function get_system_info_page() {
		$system_info_page = new SystemInfoPage( $this->get_plugin_file() );

		return $system_info_page;
	}

	/**
	 * Get the list of secondary pages.
	 *
	 * @return BasePage[] Secondary Pages.
	 */
	private function get_secondary_pages() {
		if ( empty( $this->secondary_pages ) ) {
			$cron_list_page   = $this->get_cron_list_admin_page();
			$system_info_page = $this->get_system_info_page();

			$this->secondary_pages[ $cron_list_page->get_page_slug() ]   = $cron_list_page;
			$this->secondary_pages[ $system_info_page->get_page_slug() ] = $system_info_page;
		}

		/**
		 * List of secondary admin pages.
		 *
		 * @since 6.0.0
		 *
		 * @param BasePage[] List of Admin pages.
		 */
		return apply_filters( 'bd_secondary_pages', $this->secondary_pages );
	}

	/**
	 * Get path to main plugin file.
	 *
	 * @return string Plugin file.
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * Set path to main plugin file.
	 *
	 * @param string $plugin_file Path to main plugin file.
	 */
	public function set_plugin_file( $plugin_file ) {
		$this->plugin_file       = $plugin_file;
		$this->translations_path = dirname( plugin_basename( $this->get_plugin_file() ) ) . '/languages/';
	}

	/**
	 * Get path to translations.
	 *
	 * @return string Translations path.
	 */
	public function get_translations_path() {
		return $this->translations_path;
	}

	/**
	 * Get the hook suffix of a page.
	 *
	 * @param string $page_slug Page slug.
	 *
	 * @return string|null Hook suffix if found, null otherwise.
	 */
	public function get_page_hook_suffix( $page_slug ) {
		$admin_page = '';

		if ( array_key_exists( $page_slug, $this->get_primary_pages() ) ) {
			$admin_page = $this->primary_pages[ $page_slug ];
		}

		if ( array_key_exists( $page_slug, $this->get_secondary_pages() ) ) {
			$admin_page = $this->secondary_pages[ $page_slug ];
		}

		if ( $admin_page instanceof BasePage ) {
			return $admin_page->get_hook_suffix();
		}

		return null;
	}

	/**
	 * Register Add-on Namespace.
	 *
	 * @param \BulkWP\BulkDelete\Core\Addon\AddonInfo $addon_info Add-on Info.
	 */
	public function register_addon_namespace( $addon_info ) {
		$this->loader->add_namespace( 'BulkWP\BulkDelete', $addon_info->get_addon_directory() . 'includes' );
	}

	/**
	 * Setter for Autoloader.
	 *
	 * @param \BulkWP\BulkDelete\BulkDeleteAutoloader $loader Autoloader.
	 */
	public function set_loader( $loader ) {
		$this->loader = $loader;
	}

	/**
	 * Get the module object instance by page slug and module class name.
	 *
	 * @param string $page_slug         Page Slug.
	 * @param string $module_class_name Module class name.
	 *
	 * @return \BulkWP\BulkDelete\Core\Base\BaseModule|null Module object instance or null if no match found.
	 */
	public function get_module( $page_slug, $module_class_name ) {
		$page = $this->get_page( $page_slug );

		if ( is_null( $page ) ) {
			return null;
		}

		return $page->get_module( $module_class_name );
	}

	/**
	 * Get the page object instance by page slug.
	 *
	 * @param string $page_slug Page slug.
	 *
	 * @return \BulkWP\BulkDelete\Core\Base\BaseDeletePage|null Page object instance or null if no match found.
	 */
	public function get_page( $page_slug ) {
		$pages = $this->get_primary_pages();

		if ( ! isset( $pages[ $page_slug ] ) ) {
			return null;
		}

		return $pages[ $page_slug ];
	}

	/**
	 * Is the current request an admin or cron request?
	 *
	 * @return bool True, if yes, False otherwise.
	 */
	private function is_admin_or_cron() {
		return is_admin() || defined( 'DOING_CRON' ) || isset( $_GET['doing_wp_cron'] );
	}
}
