<?php

namespace WPML\AdminLanguageSwitcher;

class AdminLanguageSwitcherRenderer {
	public static function render( $languageOptions ) {
		?>
        <div class="wpml-login-ls">
            <form id="wpml-login-ls-form" action="" method="get">
				<?php if ( isset( $_GET['redirect_to'] ) && '' !== $_GET['redirect_to'] ) { ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url_raw( $_GET['redirect_to'] ); ?>"/>
				<?php } ?>

				<?php if ( isset( $_GET['action'] ) && '' !== $_GET['action'] ) { ?>
                    <input type="hidden" name="action" value="<?php echo esc_attr( $_GET['action'] ); ?>"/>
				<?php } ?>

                <label for="language-switcher-locales">
                    <span class="dashicons dashicons-translation" aria-hidden="true"></span>
                    <span class="screen-reader-text"><?php _e( 'Language' ); ?></span>
                </label>
                <select name="wpml_lang" id="wpml-language-switcher-locales">
					<?php
					echo implode( '', $languageOptions );
					?>
                </select>
                <input type="submit" class="button" value="<?php esc_attr_e( "Change" ); ?>">

            </form>
        </div>
		<?php
	}
}