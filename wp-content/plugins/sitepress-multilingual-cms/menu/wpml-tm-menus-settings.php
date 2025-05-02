<?php

use WPML\API\Settings;
use WPML\DocPage;
use WPML\TM\Menu\TranslationMethod\TranslationMethodSettings;
use WPML\LIB\WP\User;

class WPML_TM_Menus_Settings extends WPML_TM_Menus {

	/** @var WPML_Translate_Link_Targets_UI $translate_link_targets_ui */
	private $translate_link_targets_ui;

	/** @var bool $end_user_feature_enabled */
	private $end_user_feature_enabled;

	private $mcsetup_sections = array();

	public function init() {
		$this->init_navigation_links();
	}

	private function init_navigation_links() {
		/**
		 * @var SitePress             $sitepress
		 * @var TranslationManagement $iclTranslationManagement
		 */
		global $sitepress, $iclTranslationManagement;
		$is_admin = current_user_can( 'manage_options' );

		$this->mcsetup_sections['ml-content-setup-sec-1'] = esc_html__( 'Translation Editor', 'wpml-translation-management' );

		if ( $is_admin ) {
			$this->mcsetup_sections['ml-content-setup-sec-2'] = esc_html__( 'Posts and pages synchronization', 'wpml-translation-management' );
			$this->mcsetup_sections['ml-content-setup-sec-3'] = esc_html__( 'Translated documents options', 'wpml-translation-management' );

			$this->mcsetup_sections['ml-content-setup-sec-wp-login'] = esc_html__( 'Login and registration pages', 'wpml-translation-management' );

			if ( wpml_is_st_loaded() ) {
				$this->mcsetup_sections['ml-content-setup-sec-4'] = esc_html__( 'Custom posts slug translation options', 'wpml-translation-management' );
			}

			if ( TranslationProxy::is_current_service_active_and_authenticated() ) {
				$this->mcsetup_sections['ml-content-setup-sec-5'] = esc_html__( 'Translation pickup mode', 'wpml-translation-management' );
			}
		}

		$this->mcsetup_sections['ml-content-setup-sec-5-1'] = esc_html__( 'XLIFF file options', 'wpml-translation-management' );

		if ( $is_admin ) {
			$this->mcsetup_sections['ml-content-setup-sec-cf']  = esc_html__( 'Custom Fields Translation', 'wpml-translation-management' );
			$this->mcsetup_sections['ml-content-setup-sec-tcf'] = esc_html__( 'Custom Term Meta Translation', 'wpml-translation-management' );

			$custom_posts     = array();
			$this->post_types = $sitepress->get_translatable_documents( true );

			foreach ( $this->post_types as $k => $v ) {
				$custom_posts[ $k ] = $v;
			}

			global $wp_taxonomies;
			$custom_taxonomies = array_diff( array_keys( (array) $wp_taxonomies ), array(
				'post_tag',
				'category',
				'nav_menu',
				'link_category',
				'post_format'
			) );

			if ( $custom_posts ) {
				$this->mcsetup_sections['ml-content-setup-sec-7'] = esc_html__( 'Post Types Translation', 'wpml-translation-management' );
			}

			if ( $custom_taxonomies ) {
				$this->mcsetup_sections['ml-content-setup-sec-8'] = esc_html__( 'Taxonomies Translation', 'wpml-translation-management' );
			}

			if ( ! empty( $iclTranslationManagement->admin_texts_to_translate ) && function_exists( 'icl_register_string' ) ) {
				$this->mcsetup_sections['ml-content-setup-sec-9'] = esc_html__( 'Admin Strings to Translate', 'wpml-translation-management' );
			}
		}

		$this->get_translate_link_targets_ui()->add_hooks();

		$this->mcsetup_sections = apply_filters( 'wpml_mcsetup_navigation_links', $this->mcsetup_sections, $sitepress, $iclTranslationManagement );
	}

	protected function render_main() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Settings', 'wpml-translation-management' ); ?></h1>

			<?php
			do_action( 'icl_tm_messages' );
			$this->build_tab_items();
			$this->render_items();
			?>
		</div>
		<?php
	}

	protected function build_tab_items() {
		$this->build_mcs_item();
		$this->build_translation_notifications_item();

		$this->tab_items = apply_filters( 'wpml_tm_tab_items', $this->tab_items );
	}

	private function build_mcs_item() {
		global $sitepress;

		$this->tab_items['mcsetup']['caption'] = esc_html__( 'Multilingual Content Setup', 'wpml-translation-management' );
		$translate_link_targets                = new WPML_Translate_Link_Target_Global_State( $sitepress );
		if ( $translate_link_targets->is_rescan_required() ) {
			$this->tab_items['mcsetup']['caption'] = '<i class="otgs-ico-warning"></i>' . esc_html( $this->tab_items['mcsetup']['caption'] );
		}
		$this->tab_items['mcsetup']['callback']         = [ $this, 'build_content_mcs' ];
		$this->tab_items['mcsetup']['current_user_can'] = [
			User::CAP_MANAGE_TRANSLATIONS,
			User::CAP_ADMINISTRATOR,
		];
	}

	private function build_translation_notifications_item() {
		$this->tab_items['notifications'] = [
			'caption'          => esc_html__( 'Translation Notifications', 'wpml-translation-management' ),
			'current_user_can' => [ User::CAP_ADMINISTRATOR, User::CAP_MANAGE_TRANSLATIONS ],
			'callback'         => [ $this, 'build_content_translation_notifications' ],
		];
	}

	public function build_content_mcs() {
		/**
		 * included by menu translation-management.php
		 *
		 * @var TranslationManagement $iclTranslationManagement
		 */
		global $sitepress, $sitepress_settings, $iclTranslationManagement;

		$translate_link_targets = new WPML_Translate_Link_Target_Global_State( $sitepress );
		if ( $translate_link_targets->is_rescan_required() ) {
			?>
			<div class="update-nag ant-alert ant-alert-info ant-alert-with-description" role="alert">
				<span role="img" aria-label="info-circle" class="anticon anticon-info-circle ant-alert-icon">
					<svg viewBox="64 64 896 896" focusable="false" data-icon="info-circle" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path><path d="M464 336a48 48 0 1096 0 48 48 0 10-96 0zm72 112h-48c-4.4 0-8 3.6-8 8v272c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8V456c0-4.4-3.6-8-8-8z"></path></svg>
				</span>
				<div class="ant-alert-content">
					<p class="ant-alert-message">
						<?php
						echo esc_html__(
							'There is new translated content on this site. You can scan posts and strings to adjust links to point to translated content.',
							'wpml-translation-management'
						);
						?>
					</p>
					<span class="ant-alert-description"><?php echo $this->get_navigation_link( $this->get_translate_link_targets_ui()->get_id() ); ?></span>
				</div>
			</div>
			<?php
		}

		$this->render_mcsetup_navigation_links();

		if ( $this->should_show_mcsetup_section( 'ml-content-setup-sec-1' ) ) : ?>
            <?php \WPML\UserInterface\Web\Core\Component\Preferences\Application\AutomaticTranslationsSectionController::render(); ?>


			<div class="wpml-section" id="ml-content-setup-sec-1">
				<?php
				$doc_translation_method = Settings::pathOr( ICL_TM_TMETHOD_MANUAL, [
					'translation-management',
					'doc_translation_method'
				] );
				$isClassicEditor        = (string) ICL_TM_TMETHOD_EDITOR === (string) $doc_translation_method;
				$isATEEditor            = (string) ICL_TM_TMETHOD_ATE === (string) $doc_translation_method;
				$editor_features = [
					__( 'Side-by-side editing', 'sitepress' ),
					__( 'Unique designs for translations', 'sitepress' ),
					__( 'Glossary Support', 'sitepress' ),
					__( 'Automatic Translation', 'sitepress' ),
					__( 'Use your free automatic translation quota', 'sitepress' ),
					__( 'Spell Checker', 'sitepress' ),
					__( 'Safe HTML editing', 'sitepress' ),
					__( 'Translation Memory', 'sitepress' ),
				];

				?>

				<div class="wpml-section-header translation-method-header">
					<h3>
						<?php echo esc_html__( 'Translation Editor', 'wpml-translation-management' ); ?>
					</h3>
				</div>

				<div class="wpml-section-content translation-method-content">

					<form id="icl_doc_translation_method" name="icl_doc_translation_method" action="">
						<?php wp_nonce_field( 'icl_doc_translation_method_nonce', '_icl_nonce' ); ?>

						<div class="wpml-section-content-inner">
							<div class="translation-method__table-container">
								<table class="t_method__table">
									<thead>
									<tr>
										<th></th>
										<th>
											<span class="recommendation-badge ate-recommended"><?php esc_html_e('Recommended', 'sitepress'); ?></span>
											<span class="column-heading"><?php esc_html_e('Advanced Translation Editor', 'sitepress'); ?></span>
										</th>
										<th>
											<span class="recommendation-badge"><?php esc_html_e('Legacy', 'sitepress'); ?></span>
											<span class="column-heading"><?php esc_html_e('Classic Translation Editor', 'sitepress'); ?></span>
										</th>
									</tr>
									</thead>
									<tbody>
									<?php
									foreach ($editor_features as $index => $editor_feature) {
										?>
										<tr class="<?php if ($editor_feature === 'Translation Memory'): echo 'translation-memory-row'; endif; ?>">
											<th scope="row" class="row-heading"><span><?php echo esc_html( $editor_feature ); ?></span></th>
											<td class="check-td left">
												<div class="check-container">
													<i aria-label="<?php esc_html_e('Includes ' . $editor_feature, 'sitepress'); ?>" class="otgs-ico otgs-ico-ok"></i>
												</div>
											</td>
											<?php if ($index < 2) { ?>
												<td class="check-td right">
													<div class="check-container">
														<i aria-label="<?php esc_html_e('Includes ' . $editor_feature, 'sitepress'); ?>" class="otgs-ico otgs-ico-ok"></i>
													</div>
												</td>
											<?php } else { ?>
												<td class="right"><div class="check-container"></div></td>
											<?php } ?>
										</tr>
									<?php } ?>
									<tr>
										<td></td>
										<td class="choose-method left">
											<label class="wpml-radio wpml-radio-green">
												<input
													type="radio" name="t_method" value="<?php echo ICL_TM_TMETHOD_ATE; ?>"
													<?php if ( $isATEEditor ) :?>
														checked="checked"
													<?php endif; ?>
												/>
												<span class="wpml-radio-label"></span>
											</label>
										</td>
										<td class="choose-method right">
											<label class="wpml-radio wpml-radio-green">
												<input
													type="radio" name="t_method" value="<?php echo ICL_TM_TMETHOD_EDITOR; ?>"
													<?php if ( $isClassicEditor ) :?>
														checked="checked"
													<?php endif; ?>
												/>
												<span class="wpml-radio-label"></span>
											</label>
										</td>
									</tr>
									<tr>
										<td></td>
										<td class="old-translations">
												<label class="wpml-checkbox">
													<?php
													$default_editor_for_old_jobs = get_option( WPML_TM_Old_Jobs_Editor::OPTION_NAME, null );
													$different_translation_designs_link = 'https://wpml.org/documentation/translating-your-contents/using-different-translation-editors-for-different-pages/?utm_source=plugin&utm_medium=gui&utm_campaign=core';
													?>
													<input name="wpml-old-jobs-editor" type="checkbox" value="<?php echo WPML_TM_Editors::ATE; ?>" <?php checked( $default_editor_for_old_jobs === WPML_TM_Editors::ATE ); ?> />
													<span><?php esc_html_e('Use also for old translations created with the classic editor', 'sitepress'); ?></span>
												</label>
										</td>
										<td></td>
									</tr>
									</tbody>
								</table>
							</div>
						</div>

						<p class="buttons-wrap">
							<span class="icl_ajx_response" id="icl_ajx_response_dtm"> </span>
							<input type="submit" class="button-primary" style="display: none;"
								   value="<?php echo esc_html__( 'Save', 'wpml-translation-management' ); ?>"/>
						</p>



						<div class="wpml-section-content-inner t_editor-faqs">
							<h3>
								<?php

								/* translators: heading shown for selecting the editor to use when updating content that was created with WPML's Classic Translation Editor */
								esc_html_e( "Translation Editor FAQ", 'sitepress' );

								?>
							</h3>
							<div class="faqs">

								<div class="faq">
									<h4>
										<?php esc_html_e('Can I have completely different designs for translations?', 'sitepress'); ?>
									</h4>
									<p>
										<?php
										/* translators: %s links */
										echo sprintf( esc_html__( 'Yes, WPML allows you to use %1$scompletely different designs for translations%2$s.', 'sitepress' ), '<a href="' . esc_url( $different_translation_designs_link ) . '" target="_blank">', '</a>' );
										?>
									</p>
								</div>
								<div class="faq">
									<h4>
										<?php esc_html_e('Can I use my free automatic translation quota with the Classic Translation Editor?', 'sitepress'); ?>
									</h4>
									<p>
										<?php esc_html_e('No. Automatic translation is only available via WPML’s Advanced Translator Editor, so you can only use your free quota using it.', 'sitepress'); ?>
									</p>
								</div>
								<div class="faq">
									<h4>
										<?php esc_html_e('When should I use WPML’s Classic Translation Editor?', 'sitepress'); ?>
									</h4>
									<p>
										<?php esc_html_e('We maintain WPML’s Classic Translation Editor as part of WPML for backward compatibility. You should only use it if you’ve started with the Classic Editor and are concerned about losing the translation history.', 'sitepress'); ?>
									</p>
								</div>
							</div>
						</div>

						<?php do_action( 'wpml_doc_translation_method_below' ); ?>

					</form>
				</div>
				<!-- .wpml-section-content -->

			</div><!-- #ml-content-setup-sec-1 -->
		<?php endif; ?>

		<?php if ( $this->should_show_mcsetup_section( 'ml-content-setup-sec-2' ) ) : ?>
			<?php include ICL_PLUGIN_PATH . '/menu/_posts_sync_options.php'; ?>
		<?php endif; ?><!-- #ml-content-setup-sec-2 -->

		<?php if ( $this->should_show_mcsetup_section( 'ml-content-setup-sec-3' ) ) : ?>
			<div class="wpml-section" id="ml-content-setup-sec-3">

				<div class="wpml-section-header">
					<h3><?php echo esc_html__( 'Translated documents options', 'wpml-translation-management' ); ?></h3>
				</div>

				<div class="wpml-section-content">

					<form name="icl_tdo_options" id="icl_tdo_options" action="">
						<?php
						wp_nonce_field(
							'wpml-translated-document-options-nonce',
							WPML_TM_Options_Ajax::NONCE_TRANSLATED_DOCUMENT
						);
						?>

						<div class="wpml-section-content-inner">
							<h4>
								<?php echo esc_html__( 'When you receive completed translations', 'sitepress' ); ?>
							</h4>
							<ul>
								<li>
									<label>
										<input class="wpml-radio-native" type="radio" name="icl_translated_document_status" value="1"
											<?php
											checked(
												(bool) icl_get_setting( 'translated_document_status' ),
												true
											);
											?>
										/>
										<?php
										echo esc_html__(
											'Publish the translated post when original is also published (default)',
											'sitepress'
										)
										?>
									</label>
								</li>
								<li>
									<label>
										<input class="wpml-radio-native" type="radio" name="icl_translated_document_status" value="0"
											<?php
											checked(
												(bool) icl_get_setting( 'translated_document_status' ),
												false
											);
											?>
										/>
										<?php echo esc_html__( 'Save the translated post as a draft', 'sitepress' ); ?>
									</label>
								</li>
							</ul>
							<p class="explanation-text">
								<?php
								echo esc_html__(
									'Choose if translations should be published when received. Note: If Publish is selected, the translation will only be published if the original document is published when the translation is received.',
									'sitepress'
								)
								?>
							</p>
						</div>

						<div class="wpml-section-content-inner">
							<h4>
								<?php echo esc_html__( 'When you publish the original post', 'sitepress' ); ?>
							</h4>
							<ul>
								<li>
									<label>
										<input class="wpml-radio-native" type="radio" name="icl_translated_document_status_sync" value="1"
											<?php
											checked(
												(bool) icl_get_setting( 'translated_document_status_sync' ),
												true
											);
											?>
										/>
										<?php echo esc_html__( 'Publish the post translations', 'sitepress' ); ?>
									</label>
								</li>
								<li>
									<label>
										<input class="wpml-radio-native" type="radio" name="icl_translated_document_status_sync" value="0"
											<?php
											checked(
												(bool) icl_get_setting( 'translated_document_status_sync' ),
												false
											);
											?>
										/>
										<?php
										echo esc_html__(
											'Do not publish the post translations',
											'sitepress'
										)
										?>
									</label>
								</li>
							</ul>
						</div>

						<div class="wpml-section-content-inner">
							<h4>
								<?php echo esc_html__( 'Page URL', 'wpml-translation-management' ); ?>
							</h4>
							<ul>
								<li>
									<label><input class="wpml-radio-native" type="radio" name="icl_translated_document_page_url"
												  value="auto-generate"
											<?php
											if ( empty( $sitepress_settings['translated_document_page_url'] )
												|| $sitepress_settings['translated_document_page_url']
												=== 'auto-generate' ) :

												?>
												checked="checked"<?php endif; ?> />
										<?php
										echo esc_html__(
											'Auto-generate from title (default)',
											'wpml-translation-management'
										)
										?>
									</label>
								</li>
								<li>
									<label><input class="wpml-radio-native" type="radio" name="icl_translated_document_page_url" value="translate"
											<?php
											if ( $sitepress_settings['translated_document_page_url']
												=== 'translate' ) :

												?>
												checked="checked"<?php endif; ?> />
										<?php
										echo esc_html__(
											'Translate (this will include the slug in the translation and not create it automatically from the title)',
											'wpml-translation-management'
										)
										?>
									</label>
								</li>
								<li>
									<label><input class="wpml-radio-native" type="radio" name="icl_translated_document_page_url"
												  value="copy-encoded"
											<?php
											if ( $sitepress_settings['translated_document_page_url']
												=== 'copy-encoded' ) :

												?>
												checked="checked"<?php endif; ?> />
										<?php
										echo esc_html__(
											'Copy from original language if translation language uses encoded URLs',
											'wpml-translation-management'
										)
										?>
									</label>
								</li>
							</ul>
						</div>

						<div class="wpml-section-content-inner">
							<h4>
								<?php echo esc_html__( 'Translated taxonomies', 'sitepress' ) ?>
							</h4>

							<p id="tm_block_retranslating_terms">
								<label>
									<input
										class="wpml-checkbox-native"
										name="tm_block_retranslating_terms"
										value="1"
										<?php checked( wpml_get_setting( 'tm_block_retranslating_terms' ), "1" ) ?>
										type="checkbox"
									/>
									<?php echo esc_html__( "Don't show translated taxonomies in Translation Editor", 'sitepress' ) ?>
								</label>
							</p>
						</div>

						<div class="wpml-section-content-inner">
							<p class="buttons-wrap">
								<span class="icl_ajx_response" id="icl_ajx_response_tdo"> </span>
								<input id="js-translated_document-options-btn" type="button" class="button-primary wpml-button base-btn"
									   value="
								<?php
									   echo esc_attr__(
										   'Save',
										   'wpml-translation-management'
									   )
									   ?>
																																				  "/>
							</p>
						</div>

					</form>
				</div>
				<!-- .wpml-section-content -->
			</div><!-- #ml-content-setup-sec-3 -->
		<?php endif; ?>

		<?php
		if ( $this->should_show_mcsetup_section( 'ml-content-setup-sec-wp-login' ) ) {
			include ICL_PLUGIN_PATH . '/menu/_login_translation_options.php';
		}
		?>
		<!-- #ml-content-setup-sec-wp-login -->

		<?php if ( $this->should_show_mcsetup_section( 'ml-content-setup-sec-4' ) ) : ?>
			<?php include WPML_ST_PATH . '/menu/_slug-translation-options.php'; ?><!-- #ml-content-setup-sec-4 -->
		<?php endif; ?>

		<?php if ( $this->should_show_mcsetup_section( 'ml-content-setup-sec-5' ) ) : ?>
			<div class="wpml-section" id="ml-content-setup-sec-5">

				<div class="wpml-section-header">
					<h3><?php echo esc_html__( 'Translation pickup mode', 'wpml-translation-management' ); ?></h3>
				</div>

				<div class="wpml-section-content">

					<form id="icl_translation_pickup_mode" name="icl_translation_pickup_mode" action="">
						<?php
						wp_nonce_field(
							'wpml_save_translation_pickup_mode',
							WPML_TM_Pickup_Mode_Ajax::NONCE_PICKUP_MODE
						)
						?>

						<p>
							<?php
							echo esc_html__(
								'How should the site receive completed translations from Translation Service?',
								'wpml-translation-management'
							);
							?>
						</p>

						<p>
							<label>
								<input class="wpml-radio-native" type="radio" name="icl_translation_pickup_method"
									   value="<?php echo ICL_PRO_TRANSLATION_PICKUP_XMLRPC; ?>"
									<?php
									if ( $sitepress_settings['translation_pickup_method']
										=== ICL_PRO_TRANSLATION_PICKUP_XMLRPC ) :

										?>
										checked="checked"<?php endif ?>/>
								<?php
								echo esc_html__(
									'Translation Service will deliver translations automatically using XML-RPC',
									'wpml-translation-management'
								);
								?>
							</label>
						</p>

						<p>
							<label>
								<input class="wpml-radio-native" type="radio" name="icl_translation_pickup_method"
									   value="<?php echo ICL_PRO_TRANSLATION_PICKUP_POLLING; ?>"
									<?php
									if ( $sitepress_settings['translation_pickup_method']
										=== ICL_PRO_TRANSLATION_PICKUP_POLLING ) :

										?>
										checked="checked"<?php endif; ?> />
								<?php
								echo esc_html__(
									'The site will fetch translations manually',
									'wpml-translation-management'
								);
								?>
							</label>
						</p>


						<p class="buttons-wrap">
							<span class="icl_ajx_response" id="icl_ajx_response_tpm"> </span>
							<input
								id="translation-pickup-mode"
								class="button-primary wpml-button base-btn"
								name="save"
								value="<?php echo esc_attr__( 'Save', 'wpml-translation-management' ) ?>"
								type="button"
							/>
						</p>

						<?php
						$this->build_content_dashboard_fetch_translations_box();
						?>
					</form>

					<?php do_action( 'wpml_tm_mcs_translation_pickup_mode' ); ?>

				</div>
				<!-- .wpml-section-content -->
			</div><!-- #ml-content-setup-sec-5 -->
		<?php endif; ?>

		<?php if ( $this->should_show_mcsetup_section( 'ml-content-setup-sec-5-1' ) ) : ?>
			<?php include WPML_TM_PATH . '/menu/xliff-options.php'; ?><!-- #ml-content-setup-sec-5-1 -->
		<?php endif; ?>

		<?php $this->build_content_mcs_custom_fields(); ?>

		<?php if ( $this->should_show_mcsetup_section( 'ml-content-setup-sec-7' ) ) : ?>
			<?php include ICL_PLUGIN_PATH . '/menu/_custom_types_translation.php'; ?><!-- #ml-content-setup-sec-7 -->
		<?php endif; ?>

		<?php if ( $this->should_show_mcsetup_section( 'ml-content-setup-sec-9' ) ) : ?>
			<div class="wpml-section" id="ml-content-setup-sec-9">

				<div class="wpml-section-header">
					<h3><?php echo esc_html__( 'Admin Strings to Translate', 'wpml-translation-management' ); ?></h3>
				</div>

				<div class="wpml-section-content">
					<table class="widefat">
						<thead>
						<tr>
							<th colspan="3">
								<?php echo esc_html__( 'Admin Strings', 'wpml-translation-management' ); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<tr>
							<td>
								<?php
								foreach (
									$iclTranslationManagement->admin_texts_to_translate as $option_name =>
									$option_value
								) {
									$iclTranslationManagement->render_option_writes( $option_name, $option_value );
								}
								?>
								<br/>

								href="
								<?php
								echo admin_url(
									'admin.php?page='
									. WPML_ST_FOLDER
									. '/menu/string-translation.php'
								)
								?>
								">
								<?php
								echo esc_html__(
									'Edit translatable strings',
									'wpml-translation-management'
								)
								?>
								</a>
								</p>
							</td>
						</tr>
						</tbody>
					</table>

				</div>
				<!-- .wpml-section-content -->

			</div><!-- #ml-content-setup-sec-9 -->
		<?php endif; ?>

		<?php if ( $this->should_show_mcsetup_section( $this->get_translate_link_targets_ui()->get_id() ) ) : ?>
			<?php echo $this->get_translate_link_targets_ui()->render(); ?><!-- #ml-content-setup-sec-links-target -->
		<?php endif; ?>

		<?php
		wp_enqueue_script( 'wpml-tm-mcs' );
		wp_enqueue_script( 'wpml-tm-mcs-translate-link-targets' );
	}

	private function build_content_mcs_custom_fields() {
		global $wpdb;

		$factory = new WPML_TM_MCS_Custom_Field_Settings_Menu_Factory();

		if ( $this->should_show_mcsetup_section( 'ml-content-setup-sec-cf' ) ) {
			$menu_item_posts = $factory->create_post();
			$menu_item_posts->init_data();
			echo $menu_item_posts->render();
		}

		if ( ! empty( $wpdb->termmeta ) && $this->should_show_mcsetup_section( 'ml-content-setup-sec-tcf' ) ) {
			$menu_item_terms = $factory->create_term();
			$menu_item_terms->init_data();
			echo $menu_item_terms->render();
		}
	}

	public function build_content_translation_notifications() {
		?>
		<form method="post" name="translation-notifications" id="translation-notifications"
			  action="admin.php?page=<?php echo WPML_TM_FOLDER . $this->get_page_slug(); ?>&amp;sm=notifications">
			<input type="hidden" name="icl_tm_action" value="save_notification_settings"/>

			<?php do_action( 'wpml_tm_translation_notification_setting_after' ); ?>

			<div class="wpml-section" id="translation-notifications-sec-3">
				<p class="submit">
					<input type="submit" class="button-primary wpml-button base-btn"
						   value="<?php echo esc_html__( 'Save', 'wpml-translation-management' ); ?>"/>
				</p>
			</div>

			<?php wp_nonce_field( 'save_notification_settings_nonce', 'save_notification_settings_nonce' ); ?>
		</form>

		<?php
	}

	protected function get_page_slug() {
		return WPML_Translation_Management::PAGE_SLUG_SETTINGS;
	}

	protected function get_default_tab() {
		return 'mcsetup';
	}

	private function render_mcsetup_navigation_links() {
		echo '<ul class="wpml-navigation-links js-wpml-navigation-links">';

		foreach ( $this->mcsetup_sections as $anchor => $title ) {
			echo '<li>' . $this->get_navigation_link( $anchor ) . '</li>';
		}

		echo '</ul>';
	}

	private function get_navigation_link( $anchor ) {
		if ( array_key_exists( $anchor, $this->mcsetup_sections ) ) {
			return '<a href="#' . $anchor . '">' . $this->mcsetup_sections[ $anchor ] . '</a>';
		}
	}

	/** @return bool */
	private function should_show_mcsetup_section( $anchor ) {
		return array_key_exists( $anchor, $this->mcsetup_sections );
	}

	/** @return WPML_Translate_Link_Targets_UI */
	private function get_translate_link_targets_ui() {
		/**
		 * @var SitePress $sitepress
		 * @var wpdb      $wpdb
		 * @var           $ICL_Pro_Translation
		 */
		global $sitepress, $wpdb, $ICL_Pro_Translation;

		if ( ! $this->translate_link_targets_ui ) {
			$this->translate_link_targets_ui = new WPML_Translate_Link_Targets_UI(
				__( 'Translate Link Targets', 'wpml-translation-management' ),
				$wpdb,
				$sitepress,
				$ICL_Pro_Translation
			);
		}

		return $this->translate_link_targets_ui;
	}
}
