<?php
global $sitepress, $sitepress_settings;
?>
<div class="wpml-section" id="ml-content-setup-sec-wp-login">

	<div class="wpml-section-header">
		<h3><?php esc_html_e( 'Login and registration pages', 'sitepress' ); ?></h3>
	</div>

	<div class="wpml-section-content">
		<form id="icl_login_page_translation" name="icl_login_page_translation" action="">
			<?php
			wp_nonce_field( 'icl_login_page_translation_nonce', '_icl_nonce' );
			$login_page_documentation_url = 'https://wpml.org/documentation/getting-started-guide/translating-wordpress-login-and-registration-pages/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlcore';
			?>
			<p>
				<label for="login_page_translation">
					<input class="wpml-checkbox-native" type="checkbox" id="login_page_translation"
						   name="login_page_translation"
						<?php checked( get_option( \WPML\UrlHandling\WPLoginUrlConverter::SETTINGS_KEY, false ) ); ?>
						   value="1"/>
					<?php esc_html_e( 'Allow translating the login and registration pages', 'sitepress' ); ?>
				</label>
                <br/>
                <p class="sub-section" id="show_login_page_language_switcher_sub_section"
				<?php if ( ! get_option( \WPML\UrlHandling\WPLoginUrlConverter::SETTINGS_KEY, false ) ) : ?> style="display: none" <?php endif; ?>
                >
                    <label for="show_login_page_language_switcher">
                        <input class="wpml-checkbox-native" type="checkbox" id="show_login_page_language_switcher"
                               name="show_login_page_language_switcher"
                            <?php checked( get_option( \WPML\AdminLanguageSwitcher\AdminLanguageSwitcher::LANGUAGE_SWITCHER_KEY, true ) ); ?>
                               value="1"/>
                        <?php esc_html_e( 'Show Language Switcher on login and registration pages', 'sitepress' ); ?>
                    </label>
                </p>
			</p>
			<div class="notice-info notice below-h2">
				<p>
					<?php
					esc_html_e( 'You may need to adjust your server settings if your site uses nginx.', 'sitepress' );

					$nginx_documentation_url = 'https://wpml.org/faq/nginx-server-settings/?utm_source=plugin&utm_medium=gui&utm_campaign=core';

					/* translators: "nginx guide" is a link added to the middle of the sentence "See our nginx guide to learn more."  */
					$link_to_documentation = '<a class="wpml-external-link" target="_blank" href="' . $nginx_documentation_url . '">'
											 . esc_html__( 'nginx guide', 'sitepress' )
											 . '</a>';

					/* translators: $s: a link with "nginx guide" as a text  */
					echo sprintf( esc_html__( ' See our %s to learn more.', 'sitepress' ), $link_to_documentation );

					?>
				</p>
			</div>
			<div class="wpml-section-content-inner">
				<p class="buttons-wrap">
					<span class="icl_ajx_response" id="icl_ajx_response_login"></span>
					<input class="button-primary wpml-button base-btn" name="save" value="<?php esc_attr_e( 'Save', 'sitepress' ); ?>"
						   type="submit"/>
				</p>
			</div>

		</form>

	</div> <!-- wpml-section-content -->

</div> <!-- .wpml-section -->
