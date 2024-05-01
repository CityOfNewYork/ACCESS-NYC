<?php

namespace WPML\Support\ATE;

use WPML\TM\ATE\ClonedSites\SecondaryDomains;
use WPML\TM\ATE\Log\Hooks;

class View {

	/** @var int */
	private $logCount;

	/** @var SecondaryDomains */
	private $secondaryDomains;

	public function __construct( int $logCount, SecondaryDomains $secondaryDomains ) {
		$this->logCount         = $logCount;
		$this->secondaryDomains = $secondaryDomains;
	}

	public function renderSupportSection() {
		?>
		<div class="wrap">
			<h2 id="ate-log">
				<?php esc_html_e( 'Advanced Translation Editor', 'wpml-translation-management' ); ?>
			</h2>
			<p>
				<a href="<?php echo admin_url( 'admin.php?page=' . Hooks::SUBMENU_HANDLE ); ?>">
					<?php echo sprintf( esc_html__( 'Error Logs (%d)', 'wpml-translation-management' ), $this->logCount ); ?>
				</a>
			</p>
			<?php
			$secondaryDomains = $this->secondaryDomains->getInfo();
			if ( $secondaryDomains ) {
				?>
				<div id="wpml-support-ate-alias-domains">
					<strong>
						<?php printf( __( 'Alias domains to %s domain:', 'sitepress-multilingual-cms' ), $secondaryDomains['originalSiteUrl'] ) ?>
					</strong>
					<ul style="list-style: square; padding-left: 15px;">
						<?php foreach ( $secondaryDomains['aliasDomains'] as $aliasDomain ) { ?>
							<li>
								<?php echo $aliasDomain ?>
							</li>
						<?php } ?>
					</ul>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
}
