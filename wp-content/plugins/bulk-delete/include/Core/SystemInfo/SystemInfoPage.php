<?php namespace BulkWP\BulkDelete\Core\SystemInfo;

use BulkWP\BulkDelete\Core\Base\BasePage;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * System Info Page.
 *
 * This page displays information about the current WordPress install that can be used in support requests.
 *
 * @since 6.0.0
 */
class SystemInfoPage extends BasePage {
	/**
	 * SystemInfo class.
	 *
	 * @var BulkDeleteSystemInfo
	 */
	protected $system_info;

	/**
	 * Action.
	 *
	 * Use for nonce verification.
	 *
	 * @var string
	 */
	protected $action = 'download-system-info';

	protected function initialize() {
		$this->page_slug  = 'bulk-delete-system-info';
		$this->capability = 'manage_options';
		$this->actions[]  = $this->action;

		$this->label = array(
			'page_title' => __( 'Bulk Delete - System Info', 'bulk-delete' ),
			'menu_title' => __( 'System Info', 'bulk-delete' ),
		);
	}

	public function register() {
		parent::register();

		add_action( 'bd_' . $this->action, array( $this, 'download_system_info' ) );

		$this->system_info = new BulkDeleteSystemInfo( 'bulk-delete' );
		$this->system_info->load();
	}

	protected function render_header() {
		?>
		<div class="updated">
			<p>
				<strong>
					<?php _e( 'Please include this information when posting support requests.', 'bulk-delete' ); ?>
				</strong>
			</p>
		</div>

		<?php if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) : ?>
			<div class="notice notice-warning">
				<p>
					<strong>
						<?php
						printf(
							/* translators: 1 Codex URL */
							__( 'SAVEQUERIES is <a href="%s" target="_blank">enabled</a>. This puts additional load on the memory and will restrict the number of items that can be deleted.', 'bulk-delete' ),
							'https://codex.wordpress.org/Editing_wp-config.php#Save_queries_for_analysis'
						);
						?>
					</strong>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
			<div class="notice notice-warning">
				<p>
					<strong>
						<?php
						printf(
							/* translators: 1 Codex URL. */
							__( 'DISABLE_WP_CRON is <a href="%s" rel="noopener" target="_blank">enabled</a>. Scheduled deletion will not work if WP Cron is disabled. Please disable it to enable scheduled deletion.', 'bulk-delete' ),
							'https://codex.wordpress.org/Editing_wp-config.php#Disable_Cron_and_Cron_Timeout'
						);
						?>
					</strong>
				</p>
			</div>
		<?php endif; ?>
		<?php
	}

	public function render_body() {
		$this->system_info->render();
		?>
			<p class="submit">
				<input type="hidden" name="bd_action" value="<?php echo esc_attr( $this->action ); ?>">
				<?php
					submit_button( __( 'Download System Info File', 'bulk-delete' ), 'primary', 'bd-download-system-info', false );
				?>
			</p>
		<?php
	}

	/**
	 * Download System info file.
	 */
	public function download_system_info() {
		$this->system_info->download_as_file();
	}
}
