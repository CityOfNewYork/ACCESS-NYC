<?php
namespace BulkWP\BulkDelete\Core\Base;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Base class for all Admin Page including Bulk Delete pages and setting pages.
 *
 * All concrete implementation of a Bulk Delete Admin page will extend this class.
 *
 * @since 6.0.0
 */
abstract class BasePage {
	/**
	 * Slug of Bulk WP Menu.
	 */
	const BULK_WP_MENU_SLUG = 'bulk-delete-posts';

	/**
	 * Path to main plugin file.
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Page Slug.
	 *
	 * @var string
	 */
	protected $page_slug;

	/**
	 * Hook Suffix of the current page.
	 *
	 * @var string
	 */
	protected $hook_suffix;

	/**
	 * Current screen.
	 *
	 * @var \WP_Screen
	 */
	protected $screen;

	/**
	 * Minimum capability needed for viewing this page.
	 *
	 * @var string
	 */
	protected $capability = 'manage_options';

	/**
	 * Labels used in this page.
	 *
	 * @var array
	 */
	protected $label = array(
		'page_title' => '',
		'menu_title' => '',
	);

	/**
	 * Messages shown to the user.
	 *
	 * @var array
	 */
	protected $messages = array(
		'warning_message' => '',
	);

	/**
	 * Actions used in this page.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Should the link to this page be displayed in the plugin list. Default false.
	 *
	 * @var bool
	 */
	protected $show_link_in_plugin_list = false;

	/**
	 * Initialize and setup variables and attributes of the page.
	 *
	 * @return void
	 */
	abstract protected function initialize();

	/**
	 * Render body content.
	 *
	 * @return void
	 */
	abstract protected function render_body();

	/**
	 * BasePage constructor.
	 *
	 * @param string $plugin_file Path to the main plugin file.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
		$this->initialize();
	}

	/**
	 * Register the page.
	 *
	 * This function will be called in the `admin_menu` hook.
	 */
	public function register() {
		$this->register_page();
		$this->register_hooks();
	}

	/**
	 * Register page as a submenu to the Bulk WP Menu.
	 */
	protected function register_page() {
		$hook_suffix = add_submenu_page(
			self::BULK_WP_MENU_SLUG,
			$this->label['page_title'],
			$this->label['menu_title'],
			$this->capability,
			$this->page_slug,
			array( $this, 'render_page' )
		);

		if ( false !== $hook_suffix ) {
			$this->hook_suffix = $hook_suffix;
		}
	}

	/**
	 * Register hooks.
	 */
	protected function register_hooks() {
		add_filter( 'bd_action_nonce_check', array( $this, 'verify_nonce' ), 10, 2 );

		add_action( "load-{$this->hook_suffix}", array( $this, 'setup_contextual_help' ) );
		add_filter( 'bd_admin_help_tabs', array( $this, 'render_help_tab' ), 10, 2 );

		add_action( "bd_admin_footer_for_{$this->page_slug}", array( $this, 'modify_admin_footer' ) );

		if ( $this->show_link_in_plugin_list ) {
			add_filter( 'bd_plugin_action_links', array( $this, 'append_to_plugin_action_links' ) );
		}
	}

	/**
	 * Check for nonce before executing the action.
	 *
	 * @param bool   $result The current result.
	 * @param string $action Action name.
	 *
	 * @return bool True if nonce is verified, False otherwise.
	 */
	public function verify_nonce( $result, $action ) {
		/**
		 * List of actions for page.
		 *
		 * @param array    $actions Actions.
		 * @param BasePage $page    Page objects.
		 *
		 * @since 6.0.0
		 */
		$page_actions = apply_filters( 'bd_page_actions', $this->actions, $this );

		if ( in_array( $action, $page_actions, true ) ) {
			if ( check_admin_referer( "bd-{$this->page_slug}", "bd-{$this->page_slug}-nonce" ) ) {
				return true;
			}
		}

		return $result;
	}

	/**
	 * Setup hooks for rendering contextual help.
	 */
	public function setup_contextual_help() {
		/**
		 * Add contextual help for admin screens.
		 *
		 * @since 5.1
		 *
		 * @param string Hook suffix of the current page.
		 */
		do_action( 'bd_add_contextual_help', $this->hook_suffix );
	}

	/**
	 * Modify help tabs for the current page.
	 *
	 * @param array  $help_tabs   Current list of help tabs.
	 * @param string $hook_suffix Hook Suffix of the page.
	 *
	 * @return array Modified list of help tabs.
	 */
	public function render_help_tab( $help_tabs, $hook_suffix ) {
		if ( $this->hook_suffix === $hook_suffix ) {
			$help_tabs = $this->add_help_tab( $help_tabs );
		}

		return $help_tabs;
	}

	/**
	 * Add help tabs.
	 *
	 * Help tabs can be added by overriding this function in the child class.
	 *
	 * @param array $help_tabs Current list of help tabs.
	 *
	 * @return array List of help tabs.
	 */
	protected function add_help_tab( $help_tabs ) {
		return $help_tabs;
	}

	/**
	 * Render the page.
	 */
	public function render_page() {
	?>
		<div class="wrap">
			<h2><?php echo esc_html( $this->label['page_title'] ); ?></h2>
			<?php settings_errors(); ?>

			<form method="post">
			<?php $this->render_nonce_fields(); ?>

			<div id = "poststuff">
				<div id="post-body" class="metabox-holder columns-1">

					<?php $this->render_header(); ?>

					<div id="postbox-container-2" class="postbox-container">
						<?php $this->render_body(); ?>
					</div> <!-- #postbox-container-2 -->

				</div> <!-- #post-body -->
			</div><!-- #poststuff -->
			</form>
		</div><!-- .wrap -->
	<?php
		$this->render_footer();
	}

	/**
	 * Print nonce fields.
	 */
	protected function render_nonce_fields() {
		wp_nonce_field( "bd-{$this->page_slug}", "bd-{$this->page_slug}-nonce" );
	}

	/**
	 * Render page header.
	 */
	protected function render_header() {
		if ( empty( $this->messages['warning_message'] ) ) {
			return;
		}
?>
		<div class="notice notice-warning">
			<p>
				<strong>
					<?php echo esc_html( $this->messages['warning_message'] ); ?>
				</strong>
			</p>
		</div>
<?php
	}

	/**
	 * Render page footer.
	 */
	protected function render_footer() {
		/**
		 * Runs just before displaying the footer text in the admin page.
		 *
		 * This action is primarily for adding extra content in the footer of admin page.
		 *
		 * @since 5.5.4
		 */
		do_action( "bd_admin_footer_for_{$this->page_slug}" );
	}

	/**
	 * Modify admin footer in Bulk Delete plugin pages.
	 */
	public function modify_admin_footer() {
		add_filter( 'admin_footer_text', 'bd_add_rating_link' );
	}

	/**
	 * Append link to the current page in plugin list.
	 *
	 * @param array $links Array of links.
	 *
	 * @return array Modified list of links.
	 */
	public function append_to_plugin_action_links( $links ) {
		$links[ $this->get_page_slug() ] = '<a href="admin.php?page=' . $this->get_page_slug() . '">' . $this->label['page_title'] . '</a>';

		return $links;
	}

	/**
	 * Getter for screen.
	 *
	 * @return \WP_Screen Current screen.
	 */
	public function get_screen() {
		return $this->screen;
	}

	/**
	 * Getter for page_slug.
	 *
	 * @return string Slug of the page.
	 */
	public function get_page_slug() {
		return $this->page_slug;
	}

	/**
	 * Getter for Hook Suffix.
	 *
	 * @return string Hook Suffix of the page.
	 */
	public function get_hook_suffix() {
		return $this->hook_suffix;
	}

	/**
	 * Get the url to the plugin directory.
	 *
	 * @return string Url to plugin directory.
	 */
	protected function get_plugin_dir_url() {
		return plugin_dir_url( $this->plugin_file );
	}
}
