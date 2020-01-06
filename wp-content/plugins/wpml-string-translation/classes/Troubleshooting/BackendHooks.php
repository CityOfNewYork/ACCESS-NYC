<?php

namespace WPML\ST\Troubleshooting;

use function WPML\Container\make;
use WPML\ST\MO\Generate\DomainsAndLanguagesRepository;
use WPML\ST\MO\Generate\Process\ProcessFactory;

class BackendHooks implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	const SCRIPT_HANDLE = 'wpml-st-troubleshooting';
	const NONCE_KEY     = 'wpml-st-troubleshooting';

	/** @var DomainsAndLanguagesRepository $domainsAndLanguagesRepo */
	private $domainsAndLanguagesRepo;

	public function __construct( DomainsAndLanguagesRepository $domainsAndLanguagesRepo ) {
		$this->domainsAndLanguagesRepo = $domainsAndLanguagesRepo;
	}

	public function add_hooks() {
		add_action( 'after_setup_complete_troubleshooting_functions', [ $this, 'displayButtons' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'loadJS' ] );
	}

	public function displayButtons() {
		?><div><?php

		if ( ! $this->domainsAndLanguagesRepo->get()->isEmpty() ) {
			$this->displayButton(
				AjaxFactory::ACTION_SHOW_GENERATE_DIALOG,
				esc_attr__( 'Show custom MO Files Pre-generation dialog box', 'wpml-string-translation' ),
				false
			);
		}

		$this->displayButton(
			AjaxFactory::ACTION_CLEANUP,
			esc_attr__( 'Cleanup and optimize string tables', 'wpml-string-translation' ),
			esc_attr__( 'Cleanup and optimization completed!', 'wpml-string-translation' )
		);

		?></div><?php
	}

	/**
	 * @param string       $action
	 * @param string       $buttonLabel
	 * @param string|false $confirmationMessage A string to display or false if we want to immediately reload.
	 */
	private function displayButton( $action, $buttonLabel, $confirmationMessage ) {
		?>
		<p>
			<input id="<?php echo $action; ?>"
				   class="js-wpml-st-troubleshooting-action button-secondary"
				   type="button"
				   value="<?php echo $buttonLabel; ?>"
				   data-action="<?php echo $action; ?>"
				   data-success-message="<?php echo $confirmationMessage; ?>"
				   data-nonce="<?php echo wp_create_nonce( self::NONCE_KEY ) ?>"
				   data-reload="<?php echo ! (bool) $confirmationMessage; ?>"
			/>
			<br/>
		</p>
		<?php
	}

	/**
	 * @param string $hook
	 */
	public function loadJS( $hook ) {
		if ( WPML_PLUGIN_FOLDER . '/menu/troubleshooting.php' === $hook ) {
			wp_register_script(
				self::SCRIPT_HANDLE,
				WPML_ST_URL . '/res/js/troubleshooting.js',
				[ 'jquery', 'wp-util', 'jquery-ui-sortable', 'jquery-ui-dialog' ]
			);
			wp_enqueue_script( self::SCRIPT_HANDLE );
		}
	}
}
