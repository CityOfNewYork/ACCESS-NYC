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
				<label>
					<input type="checkbox" id="login_page_translation"
						   name="login_page_translation"
						<?php checked( get_option( \WPML\UrlHandling\WPLoginUrlConverter::SETTINGS_KEY, false ) ); ?>
						   value="1"/>
					<?php esc_html_e( 'Allow translating the login and registration pages', 'sitepress' ); ?>
				</label>
				<br/>
				<a href="<?php esc_attr_e( $login_page_documentation_url ); ?>" target="_blank"
				   class="wpml-external-link">
					<?php esc_html_e( 'How to translate login and registration pages', 'sitepress' ); ?>
				</a>
                <br/>
                <p class="sub-section" id="show_login_page_language_switcher_sub_section"
				<?php if ( ! get_option( \WPML\UrlHandling\WPLoginUrlConverter::SETTINGS_KEY, false ) ) : ?> style="display: none" <?php endif; ?>
                >
                    <label>
                        <input type="checkbox" id="show_login_page_language_switcher"
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
					esc_html_e( 'If your site uses nginx, you may need to adjust your server settings. ', 'sitepress' );

					$nginx_documentation_url = 'https://wpml.org/documentation/getting-started-guide/translating-wordpress-login-and-registration-pages/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlcore#server-requirements-for-sites-that-use-nginx';

					/* translators: "server requirements for sites that use nginx" is a link added to the end of "Read more about the"  */
					$link_to_documentation = '<a class="wpml-external-link" target="_blank" href="' . $nginx_documentation_url . '">'
											 . esc_html__( 'server requirements for sites that use nginx', 'sitepress' )
											 . '</a>';

					/* translators: $s: a link with "server requirements for sites that use nginx" as a text  */
					echo sprintf( esc_html__( ' Read more about the %s.', 'sitepress' ), $link_to_documentation );

					?>
				</p>
			</div>
			<div class="wpml-section-content-inner">
				<p class="buttons-wrap">
					<span class="icl_ajx_response" id="icl_ajx_response_login"></span>
					<input class="button button-primary" name="save" value="<?php esc_attr_e( 'Save', 'sitepress' ); ?>"
						   type="submit"/>
				</p>
			</div>

		</form>

	</div> <!-- wpml-section-content -->

</div> <!-- .wpml-section -->
