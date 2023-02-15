<?php

namespace WPML\TM\Troubleshooting\SynchronizeSourceIdOfATEJobs;

use WPML\TM\Upgrade\Commands\SynchronizeSourceIdOfATEJobs\Command;
use WPML\Upgrade\CommandsStatus;

class TriggerSynchronization implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	const ACTION_ID = 'wpml-tm-ate-source-id-migration';

	/** @var CommandsStatus */
	private $commandStatus;

	/**
	 * @param CommandsStatus $commandStatus
	 */
	public function __construct( CommandsStatus $commandStatus ) {
		$this->commandStatus = $commandStatus;
	}


	public function add_hooks() {
		add_action( 'wpml_troubleshooting_after_fix_element_type_collation', [ $this, 'displayButton' ] );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
		add_action( 'wp_ajax_' . self::ACTION_ID, array( $this, 'clearExecutedStateToForceUpgrade' ) );
	}

	public function displayButton() {
		?>

		<p>
			<input id="wpml_tm_ate_source_id_migration_btn"
				   type="button"
				   class="button-secondary"
				   value="<?php esc_attr_e( 'Synchronize local job ids with ATE jobs', 'wpml-translation-manager' ); ?>"
				   data-action="<?php echo self::ACTION_ID; ?>"
				   data-nonce="<?php echo wp_create_nonce( self::ACTION_ID ); ?>"
			/>
			<br/>

			<small style="margin-left:10px;">
			<?php
			esc_attr_e(
				'Synchronize local job ids with their ATE counterparts. You will have to refresh a few times any admin page to accomplish the process.',
				'wpml-translation-manager'
			)
			?>
					</small>
		</p>
		<?php
	}

	public function enqueueScripts( $hook ) {
		if ( WPML_PLUGIN_FOLDER . '/menu/troubleshooting.php' === $hook ) {
			wp_enqueue_script(
				self::ACTION_ID,
				WPML_TM_URL . '/res/js/ate-jobs-migration.js',
				[ 'jquery' ],
				WPML_TM_VERSION
			);
		}
	}

	public function clearExecutedStateToForceUpgrade() {
		$this->commandStatus->markAsExecuted( Command::class, false );
		wp_send_json_success();
	}
}
