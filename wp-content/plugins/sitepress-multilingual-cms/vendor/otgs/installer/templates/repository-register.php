<?php

namespace OTGS\Installer\Templates\Repository;

class Register {

	public static function render( $model ) {
		?>
		<div class="otgs-installer-registered clearfix">
			<div class="enter_site_key_wrap_js otgs-installer-notice-info inline otgs-installer-notice otgs-installer-notice-<?php echo $model->repoId; ?>"
				 xmlns="http://www.w3.org/1999/html">
				<div class="otgs-installer-notice-content">
					<h2>
						<?php echo esc_html( sprintf( __( 'Already purchased %s?', 'installer' ), $model->productName ) ); ?>
						<a class="enter_site_key_js otgs-installer-notice-link-register"
						   href="#"
							<?php
							if ( \WP_Installer::get_repository_hardcoded_site_key( $model->repoId ) ): ?>
								disabled
								title="<?php printf( esc_attr__( "Site-key was set by %s, most likely in wp-config.php. Please remove the constant before attempting to register.", 'installer' ), 'OTGS_INSTALLER_SITE_KEY_' . strtoupper( $model->repoId ) ) ?>"
							<?php endif; ?>
						>
							<?php printf( __( 'Register %s', 'installer' ), $model->productName ); ?>
						</a>
					</h2>
				</div>
			</div>
		</div>
        <?php
        echo self::getRegistrationForm($model);
	}

	private static function getRegistrationForm( $model ) {
		$registrationText = sprintf(
			__( 'Enter the site key, from your %1$s account, to receive automatic updates for %2$s.', 'installer' ),
			self::removeScheme( $model->productUrl ),
			$model->productName
		);

		return '<form class="otgsi_site_key_form" method="post">
			<input type="hidden" name="action" value="save_site_key"/>
			<input type="hidden" name="nonce" value="' . esc_attr( $model->saveSiteKeyNonce ) . '"/>
			<input type="hidden" name="repository_id" value="' . esc_attr( $model->repoId ) . '">
            <h3 class="otgs-installer-register-title">Register ' . esc_html( $model->productName ) . '</h3>
            <p class="otgs-installer-register-info">' . esc_html( $registrationText ) . '</p>
            <div class="otgs-installer-register-inputs">
                <label for="site_key_' . esc_attr( $model->repoId ) . '">' . esc_html( __( 'Site key', 'installer' ) ) . '</label>
                <input type="text" size="20" name="site_key_' . esc_attr( $model->repoId ) . '" id="site_key_' . esc_attr( $model->repoId ) . '"/>
                <input class="button-primary" type="submit" value="' . esc_attr__( 'Register', 'installer' ) . '" />
            </div>
            <p class="otgs-installer-register-link"><a target="_blank" rel="nofollow" href="' . esc_url( self::getAccountUrl( $model ) ) . '">' . esc_html( __( 'Get a key for this site', 'installer' ) ) . '</a></p>

            <div class="installer-error-box hidden"></div>
		</form>';
	}

	private static function removeScheme( $str ) {
		return str_replace( [ 'https://', 'http://' ], '', $str );
	}

	/**
	 * @param $model
	 *
	 * @return string
	 */
	private static function getAccountUrl( $model ) {
		return apply_filters(
			'otgs_installer_add_site_url',
			$model->siteKeysManagementUrl . '?add=' . urlencode( $model->siteUrl ),
			$model->repoId
		);
	}

	/**
	 * @param $model
	 *
	 * @return string
	 */
	private static function getRegisterLink( $model ) {
		$buttonText = sprintf( esc_attr( 'register on %s.' ), self::removeScheme( $model->productUrl ) );
		ob_start();
		?>
		<a target="_blank" rel="nofollow"
		   href="<?php echo esc_url( $model->productUrl ); ?>"><?php echo $buttonText ?></a>
		<?php
		return trim( (string) ob_get_clean() );
	}

}
