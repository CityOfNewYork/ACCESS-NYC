<?php
/**
 * Formats the profile data for a QM panel.
 *
 * @package NdB\QM_Twig_Profile
 */

namespace NdB\QM_Twig_Profile;

use QM_Output_Html;
use Twig\Profiler\Dumper\BlackfireDumper;
use Twig\Profiler\Dumper\JSONDumper;

/**
 * Formats the output data for a QM panel.
 */
final class Output extends QM_Output_Html {
	const EDITOR_PROTOCOLS = array(
		'phpstorm',
		'vscode',
		'atom',
		'subl',
		'txmt',
		'nbopen',
	);

	/**
	 * Adds the twig profile panel to the menu.
	 *
	 * @param Collector $collector The Twig Profile collector.
	 */
	public function __construct( Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 110 );
	}

	/**
	 * The name of this panel.
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Twig profile', 'ndb_qm_twig' );
	}

	/**
	 * Renders the panel.
	 *
	 * @return void
	 */
	public function output() {
		$collector = $this->collector;
		if ( ! $collector instanceof Collector ) {
			return;
		}
		$environment_profiles = $collector->get_all();
		?>
		<div class="qm qm-non-tabular" id="qm-twig_profile">
			<div class='qm-boxed'>
				<h2><?php echo esc_html__( 'Twig profile', 'ndb_qm_twig' ); ?></h2>
			</div>
			<?php
			if ( empty( $environment_profiles ) ) {
				echo '<div class="qm-boxed">';
				echo '<section>';
				?>
				<p><?php echo esc_html__( 'No twig profiles on this page :) If you are using twig on this page, check out the README to find out how to capture profiling information.', 'ndb_qm_twig' ); ?></p>
				<?php
				echo '</section>';
				echo '</div>';
			} else {
				$qm_dark_mode = 'light';
				if ( defined( 'QM_DARK_MODE' ) && constant( 'QM_DARK_MODE' ) ) {
					$qm_dark_mode = 'dark';
				}
				require_once 'class-jsondumper.php';
				foreach ( $environment_profiles as $index => $environment_profile ) {
					echo '<div class="qm-boxed">';
					echo '<section>';
					$dumper = new JSONDumper( $environment_profile->environment->getLoader() );
					echo '<twig-profile qm_dark_mode="' . esc_html( $qm_dark_mode ) . '" profile=\'' . esc_html( $dumper->dump( $environment_profile->profile ) ) . '\'"></twig-profile>';
					$blackfire_dumper = new BlackfireDumper();
					echo '<button onclick="window.qm_twig_profile.save(\'twig-profile-' . (int) $index . '-' . (int) time() . '.prof\', \'' . esc_js( $blackfire_dumper->dump( $environment_profile->profile ) ) . '\')">' . esc_html__( 'Download blackfire.io profile', 'ndb_qm_twig' ) . '</button>';
					echo '</section>';
					echo '</div>';
				}
			}
			?>
		</div>
		<?php
	}
}
