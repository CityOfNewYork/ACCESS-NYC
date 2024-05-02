<?php

use WPML\LIB\WP\Nonce;

class WPML_Media_Settings {
	const ID = 'ml-content-setup-sec-media';

	private $wpdb;

	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_action( 'icl_tm_menu_mcsetup', array( $this, 'render' ) );
		add_filter( 'wpml_mcsetup_navigation_links', array( $this, 'mcsetup_navigation_links' ) );
	}

	public function enqueue_script() {
		$handle = 'wpml-media-settings';

		wp_register_script(
			$handle,
			ICL_PLUGIN_URL . '/res/js/media/settings.js',
			[],
			ICL_SITEPRESS_VERSION,
			true
		);

		wp_localize_script(
			$handle,
			'wpml_media_settings_data',
			[
				'nonce_wpml_media_scan_prepare'         => wp_create_nonce( 'wpml_media_scan_prepare' ),
				'nonce_wpml_media_set_initial_language' => wp_create_nonce( 'wpml_media_set_initial_language' ),
				'nonce_wpml_media_translate_media'      => wp_create_nonce( 'wpml_media_translate_media' ),
				'nonce_wpml_media_duplicate_featured_images' => wp_create_nonce( 'wpml_media_duplicate_featured_images' ),
				'nonce_wpml_media_set_content_prepare'  => wp_create_nonce( 'wpml_media_set_content_prepare' ),
				'nonce_wpml_media_set_content_defaults' => wp_create_nonce( 'wpml_media_set_content_defaults' ),
				'nonce_wpml_media_duplicate_media'      => wp_create_nonce( 'wpml_media_duplicate_media' ),
				'nonce_wpml_media_mark_processed'       => wp_create_nonce( 'wpml_media_mark_processed' ),
            ]
        );

		wp_enqueue_script( $handle );
	}

	public function render() {
		$orphan_attachments_sql = "
		SELECT COUNT(*)
		FROM {$this->wpdb->posts}
		WHERE post_type = 'attachment'
			AND ID NOT IN (
				SELECT element_id
				FROM {$this->wpdb->prefix}icl_translations
				WHERE element_type='post_attachment'
			)
		";

		$orphan_attachments = $this->wpdb->get_var( $orphan_attachments_sql );

		?>
		<div class="wpml-section" id="<?php echo esc_attr( self::ID ); ?>">

			<div class="wpml-section-header">
				<h3><?php esc_html_e( 'Media Translation', 'sitepress' ); ?></h3>
			</div>

			<div class="wpml-section-content">
				<?php if ( $orphan_attachments ) : ?>

					<p><?php esc_html_e( "The Media Translation plugin needs to add languages to your site's media. Without this language information, existing media files will not be displayed in the WordPress admin.", 'sitepress' ); ?></p>

				<?php else : ?>

					<p><?php esc_html_e( 'You can check if some attachments can be duplicated to translated content:', 'sitepress' ); ?></p>

				<?php endif ?>

				<form id="wpml_media_options_form">
					<input type="hidden" name="no_lang_attachments" value="<?php echo $orphan_attachments; ?>"/>
					<input type="hidden" id="wpml_media_options_action"/>
					<table class="wpml-media-existing-content">

						<tr>
							<td colspan="2">
								<ul class="wpml_media_options_language">
									<li><label><input type="checkbox" id="set_language_info" name="set_language_info" value="1"
									<?php
									if ( ! empty( $orphan_attachments ) ) :
										?>
										checked="checked"<?php endif; ?>
													  <?php
														if ( empty( $orphan_attachments ) ) :
															?>
															disabled="disabled"<?php endif ?> />&nbsp;<?php esc_html_e( 'Set language information for existing media', 'sitepress' ); ?></label></li>
									<li><label><input type="checkbox" id="translate_media" name="translate_media" value="1" checked="checked"/>&nbsp;<?php esc_html_e( 'Translate existing media in all languages', 'sitepress' ); ?></label></li>
									<li><label><input type="checkbox" id="duplicate_media" name="duplicate_media" value="1" checked="checked"/>&nbsp;<?php esc_html_e( 'Duplicate existing media for translated content', 'sitepress' ); ?></label></li>
									<li><label><input type="checkbox" id="duplicate_featured" name="duplicate_featured" value="1" checked="checked"/>&nbsp;<?php esc_html_e( 'Duplicate the featured images for translated content', 'sitepress' ); ?></label></li>
								</ul>
							</td>
						</tr>

						<tr>
							<td><a href="https://wpml.org/documentation/getting-started-guide/media-translation/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlcore" target="_blank"><?php esc_html_e( 'Media Translation Documentation', 'sitepress' ); ?></a></td>
							<td align="right">
								<input class="button-primary" name="start" type="submit" value="<?php esc_attr_e( 'Start', 'sitepress' ); ?> &raquo;"/>
							</td>

						</tr>

						<tr>
							<td colspan="2">
								<img class="progress" src="<?php echo ICL_PLUGIN_URL; ?>/res/img/ajax-loader.gif" width="16" height="16" alt="loading" style="display: none;"/>
								&nbsp;<span class="status"> </span>
							</td>
						</tr>
					</table>


					<table class="wpml-media-new-content-settings">

						<tr>
							<td colspan="2">
								<h4><?php esc_html_e( 'How to handle media for new content:', 'sitepress' ); ?></h4>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<ul class="wpml_media_options_language">
									<?php
									$content_defaults = \WPML\Media\Option::getNewContentSettings();

									$always_translate_media_html_checked = $content_defaults['always_translate_media'] ? 'checked="checked"' : '';
									$duplicate_media_html_checked        = $content_defaults['duplicate_media'] ? 'checked="checked"' : '';
									$duplicate_featured_html_checked     = $content_defaults['duplicate_featured'] ? 'checked="checked"' : '';
									?>
									<li>
										<label><input type="checkbox" name="content_default_always_translate_media"
													  value="1" <?php echo $always_translate_media_html_checked; ?> />&nbsp;<?php esc_html_e( 'When uploading media to the Media library, make it available in all languages', 'sitepress' ); ?></label>
									</li>
									<li>
										<label><input type="checkbox" name="content_default_duplicate_media"
													  value="1" <?php echo $duplicate_media_html_checked; ?> />&nbsp;<?php esc_html_e( 'Duplicate media attachments for translations', 'sitepress' ); ?></label>
									</li>
									<li>
										<label><input type="checkbox" name="content_default_duplicate_featured"
													  value="1"  <?php echo $duplicate_featured_html_checked; ?> />&nbsp;<?php esc_html_e( 'Duplicate featured images for translations', 'sitepress' ); ?></label>
									</li>
								</ul>
							</td>
						</tr>



						<tr>
							<td colspan="2">
								<h4><?php esc_html_e( 'How to handle media library texts:', 'sitepress' ); ?></h4>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<ul class="wpml_media_options_media_library_texts">
									<?php
									$translateMediaLibraryTexts = \WPML\Media\Option::getTranslateMediaLibraryTexts() ? 'checked="checked"' : '';
									?>
									<li>
										<label><input type="checkbox" name="translate_media_library_texts"
													  value="1" <?php echo $translateMediaLibraryTexts; ?> />&nbsp;<?php esc_html_e( 'Translate media library texts with posts', 'sitepress' ); ?></label>
									</li>
								</ul>
							</td>
						</tr>





						<tr>
							<td colspan="2" align="right">
								<input class="button-secondary" name="set_defaults" type="submit" value="<?php esc_attr_e( 'Apply', 'sitepress' ); ?>"/>
							</td>
						</tr>

						<tr>
							<td colspan="2">
								<img class="content_default_progress" src="<?php echo ICL_PLUGIN_URL; ?>/res/img/ajax-loader.gif" width="16" height="16" alt="loading" style="display: none;"/>
								&nbsp;<span class="content_default_status"> </span>
							</td>
						</tr>




					</table>

					<div id="wpml_media_all_done" class="hidden updated">
						<p><?php esc_html_e( "You're all done. From now on, all new media files that you upload to content will receive a language. You can automatically duplicate them to translations from the post-edit screen.", 'sitepress' ); ?></p>
					</div>

				</form>
			</div>

		</div>
		<?php
	}

	public function mcsetup_navigation_links( array $mcsetup_sections ) {
		$mcsetup_sections[ self::ID ] = esc_html__( 'Media Translation', 'sitepress' );

		return $mcsetup_sections;
	}
}
