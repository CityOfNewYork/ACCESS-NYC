<?php

use WPML\Element\API\Entity\LanguageMapping;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\FP\Lst;
use WPML\FP\Logic;
use WPML\FP\Relation;
use WPML\Setup\Option;
use WPML\TM\API\ATE\CachedLanguageMappings;
use WPML\TM\ATE\API\CachedATEAPI;
use WPML\TM\API\ATE\LanguageMappings;
use WPML\TM\ATE\API\CacheStorage\Transient;
use function WPML\FP\pipe;

class SitePress_EditLanguages {
	const ACCEPTED_MIME_TYPES = [
		'gif'          => 'image/gif',
		'jpg|jpeg|jpe' => 'image/jpg',
		'png'          => 'image/png',
		'svg'          => 'image/svg+xml',
	];

	public static $active_languages;
	public $upload_dir;
	public $is_writable         = false;
	public $required_fields     = [
		'code'           => '',
		'english_name'   => '',
		'translations'   => 'array',
		'flag'           => '',
		'default_locale' => '',
		'tag'            => '',
	];
	private $mode               = 'edit';
	private $validation_action  = null;
	public $validation_failed   = false;
	private $error              = '';
	private $message            = '';
	private $max_file_size;
	private $max_locale_length = 35;

	/**
	 * @var WPML_Flags
	 */
	private $wpml_flags;

	/**
	 * @var array<string>
	 */
	private $wpml_flag_files;

	/** @var bool $update_language_packs_if_needed */
	private $update_language_packs_if_needed;

	/** @var array */
	private static $languages_available_in_ate;

	/**
     * This is a helper variable that stores just saved mapping.
     * It is needed to avoid the problem with delayed mapping propagation in ATE.
     * When we save a new mapping, we try to retrieve those data immediately after which does not always work.
     * Instead of that, we store this data here after saving. When a user reloads the page, we will ask ATE API for those data.
     *
	 * @var array|null
	 */
	public static $newlySavedMapping;

	/**
	 * @param \WPML_Flags $wpml_flags
	 * @param bool        $update_language_packs_if_needed
	 */
	public function __construct( WPML_Flags $wpml_flags, $update_language_packs_if_needed = true ) {
		$this->wpml_flags = $wpml_flags;

		$this->wpml_flag_files                 = $this->wpml_flags->get_wpml_flags( array_keys( self::ACCEPTED_MIME_TYPES ) );
		$this->update_language_packs_if_needed = $update_language_packs_if_needed;

		wp_enqueue_script(
			'edit-languages',
			ICL_PLUGIN_URL . '/res/js/languages/edit-languages.js',
			[ 'jquery', 'sitepress-scripts' ],
			ICL_SITEPRESS_VERSION,
			true
		);

		$this->max_file_size = 100000;

		if ( $this->is_delete_language_action() ) {
			$lang_id = (int) $_GET['id'];
			$this->delete_language( $lang_id );
		}

		// Set upload dir
		$wp_upload_dir    = wp_upload_dir();
		$this->upload_dir = $wp_upload_dir['basedir'] . '/flags';

		if ( ! is_dir( $this->upload_dir ) ) {
			$this->is_writable = is_writable( $wp_upload_dir['basedir'] );
			if ( $this->is_writable ) {
				try {
					mkdir( $this->upload_dir );
				} catch ( Exception $ex ) {
					$this->set_errors( __( 'Upload directory cannot be created. Check your permissions.', 'sitepress' ) );
				}
			} else {
				$this->set_errors( __( 'Upload dir is not writable', 'sitepress' ) );
			}
		}
		$this->is_writable = is_writable( $this->upload_dir );

		$this->migrate();

		// Trigger save.
		if ( isset( $_POST['icl_edit_languages_action'] ) && $_POST['icl_edit_languages_action'] === 'update' ) {
			if ( wp_verify_nonce( $_POST['_wpnonce'], 'icl_edit_languages' ) ) {
				$this->update();
				self::$active_languages = null; // clear cache
			}
		}

		$transientStorage = new Transient();
		$transientStorage->delete( CachedATEAPI::CACHE_OPTION );
	}

	/**
	 * @param bool $clearCache
	 *
	 * @return array
	 */
	public static function getLanguagesAvailableInATE( $clearCache = false ) {
		if ( ! self::$languages_available_in_ate || $clearCache ) {
			self::$languages_available_in_ate = Option::isTMAllowed() ? LanguageMappings::getAvailable() : [];
		}

		return self::$languages_available_in_ate;
	}

	function render() {
		?>
		<div class="wrap">
			<h2><?php echo esc_html_x( 'Edit Languages', 'Edit languages page: page title', 'sitepress' ); ?></h2>
			<div id="icl_edit_languages_info">
				<?php echo esc_html_x( 'This table allows you to edit languages for your site. Each row represents a language.', 'Edit languages page: sentence #1', 'sitepress' ); ?>
				<br/><br/>
				<?php echo esc_html_x( 'For each language, you need to enter the following information:', 'Edit languages page: sentence #2', 'sitepress' ); ?>
				<ul>
					<li>
						<strong><?php echo esc_html_x( 'Code:', 'Edit languages page: subtitle #1', 'sitepress' ); ?></strong> <?php echo esc_html_x( 'a unique value that identifies the language. Once entered, the language code cannot be changed.', 'Edit languages page: subtitle #1, description', 'sitepress' ); ?>
					</li>
					<li>
						<strong><?php echo esc_html_x( 'Translations:', 'Edit languages page: subtitle #2', 'sitepress' ); ?></strong> <?php echo esc_html_x( 'the way the language name will be displayed in different languages.', 'Edit languages page: subtitle #2, description', 'sitepress' ); ?>
					</li>
					<li>
						<strong><?php echo esc_html_x( 'Flag:', 'Edit languages page: subtitle #3', 'sitepress' ); ?></strong> <?php echo esc_html_x( 'the flag to display next to the language (optional). You can either upload your own flag or use one of WPML\'s built in flag images.', 'Edit languages page: subtitle #3, description', 'sitepress' ); ?>
					</li>
					<li>
						<strong><?php echo esc_html_x( 'Default locale:', 'Edit languages page: subtitle #4', 'sitepress' ); ?></strong> <?php echo esc_html_x( 'This determines the locale value for this language. You should check the name of WordPress localization file to set this correctly.', 'Edit languages page: subtitle #4, description', 'sitepress' ); ?>
					</li>
					<li>
						<strong><?php echo esc_html_x( 'Encode URLs:', 'Edit languages page: subtitle #5', 'sitepress' ); ?></strong> <?php echo esc_html_x( 'yes/no, determines if URLs in this language are encoded or use ASCII characters (leave ‘no’ if you are not sure).', 'Edit languages page: subtitle #5, description', 'sitepress' ); ?>
					</li>
					<li>
						<strong><?php echo esc_html_x( 'hreflang:', 'Edit languages page: subtitle #6', 'sitepress' ); ?></strong> <?php echo esc_html_x( 'the code Google expects for this language. The hreflang should contain at least the language code (usually, made of two letters), or, if you want to specify the country/region, it sould be the same information as the locale name, but in a slightly different format. If the locale for Canadian French is fr_CA, the corresponding hreflang would be fr-ca. Instead of an underscore, use a dash (-) and all letters should be lowercase.', 'Edit languages page: subtitle #6, description', 'sitepress' ); ?>
					</li>
                    <li>
                        <strong><?php echo esc_html_x( 'Language mapping:', 'Edit languages page: subtitle #7', 'sitepress' ); ?></strong>
                        <?php
                        $link = 'https://wpml.org/documentation/automatic-translation/using-automatic-translation-with-custom-languages/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlcore';

                        echo sprintf( _x(
	                        'To use automatic translation with a custom or country-specific language, you must map it to a supported language. Read more about <a href="%s" target="_blank" rel="noopener noreferrer">using automatic translation with custom languages</a>.',
	                        'Edit languages page: subtitle #7, description',
	                        'sitepress'
                        ), $link );
                        ?>
                    </li>
				</ul>
			</div>
			<?php
			if ( $this->error ) {
				echo '	<div class="below-h2 error"><p>' . $this->error . '</p></div>';
			}

			if ( $this->message ) {
				echo '    <div class="below-h2 updated"><p>' . $this->message . '</p></div>';
			}

			?>
			<br/>
			<?php $this->edit_table(); ?>
		</div>
		<?php
	}

	function edit_table() {
		?>
		<form enctype="multipart/form-data" action="<?php echo admin_url( 'admin.php?page=' . WPML_PLUGIN_FOLDER . '/menu/languages.php&amp;trop=1' ); ?>" method="post" id="icl_edit_languages_form">
			<input type="hidden" name="icl_edit_languages_action" value="update"/>
			<input type="hidden" name="icl_edit_languages_ignore_add" id="icl_edit_languages_ignore_add" value="<?php echo ( $this->is_new_data_and_invalid() ) ? 'false' : 'true'; ?>"/>
			<?php wp_nonce_field( 'icl_edit_languages' ); ?>
			<table id="icl_edit_languages_table" class="widefat" cellspacing="0">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Language name', 'sitepress' ); ?></th>
					<th><?php esc_html_e( 'Code', 'sitepress' ); ?></th>
					<th
						<?php
						if ( $this->must_display_new_language_translation_column() ) {
							echo 'style="display:none;" ';
						}
						?>
					class="icl_edit_languages_show"><?php esc_html_e( 'Translation (new)', 'sitepress' ); ?></th>
					<?php foreach ( self::get_active_languages() as $lang ) { ?>
						<th><?php esc_html_e( 'Translation', 'sitepress' ); ?> (<?php echo esc_html( $lang['english_name'] ); ?>)</th>
					<?php } ?>
					<th><?php esc_html_e( 'Flag', 'sitepress' ); ?></th>
					<th><?php esc_html_e( 'Default locale', 'sitepress' ); ?></th>
					<th><?php esc_html_e( 'Encode URLs', 'sitepress' ); ?></th>
					<th><?php esc_html_e( 'hreflang', 'sitepress' ); ?></th>

		            <?php if ( \WPML_TM_ATE_Status::is_enabled_and_activated() ) { ?>
					<th><?php esc_html_e( 'Language mapping', 'sitepress' ); ?></th>
                    <?php } ?>

					<th>&nbsp;</th>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<th><?php esc_html_e( 'Language name', 'sitepress' ); ?></th>
					<th><?php esc_html_e( 'Code', 'sitepress' ); ?></th>
					<th
						<?php
						if ( $this->must_display_new_language_translation_column() ) {
							echo 'style="display:none;" ';
						}
						?>
					class="icl_edit_languages_show"><?php _e( 'Translation (new)', 'sitepress' ); ?></th>
					<?php foreach ( self::get_active_languages() as $lang ) { ?>
						<th><?php esc_html_e( 'Translation', 'sitepress' ); ?> (<?php echo $lang['english_name']; ?>)</th>
					<?php } ?>
					<th><?php esc_html_e( 'Flag', 'sitepress' ); ?></th>
					<th><?php esc_html_e( 'Default locale', 'sitepress' ); ?></th>
					<th><?php esc_html_e( 'Encode URLs', 'sitepress' ); ?></th>
					<th><?php esc_html_e( 'hreflang', 'sitepress' ); ?></th>

					<?php if ( \WPML_TM_ATE_Status::is_enabled_and_activated() ) { ?>
                        <th><?php esc_html_e( 'Language mapping', 'sitepress' ); ?></th>
					<?php } ?>

					<th>&nbsp;</th>
				</tr>
				</tfoot>
				<tbody>
				<?php
				foreach ( self::get_active_languages() as $lang ) {
					$this->table_row( $lang );
				}
				if ( $this->is_new_data_and_invalid() ) {
					$_POST['icl_edit_languages']['add']['id'] = 'add';
					$new_lang                                 = $this->prepare_new_lang_data( $_POST['icl_edit_languages']['add'] );
				} else {
					$new_lang = $this->prepare_new_lang_data( [ 'id' => 'add' ] );
				}
				$this->table_row( $new_lang, true, true );
				?>
				</tbody>
			</table>
			<span class="icl_error_text icl_edit_languages_show"
				  style="display: none; margin:10px;"><p><?php esc_html_e( 'Please note: language codes cannot be changed after adding languages. Make sure you enter the correct code.', 'sitepress' ); ?></p></span>
			<p class="submit"><a href="admin.php?page=<?php echo WPML_PLUGIN_FOLDER; ?>/menu/languages.php">&laquo;&nbsp;<?php esc_html_e( 'Back to languages', 'sitepress' ); ?></a></p>

			<p class="submit alignright">
				<input type="button" name="icl_edit_languages_add_language_button" id="icl_edit_languages_add_language_button"
					   value="<?php esc_html_e( 'Add Language', 'sitepress' ); ?>" class="button-secondary"
												<?php
												if ( $this->is_new_data_and_invalid() ) {
													?>
							 style="display:none;"<?php } ?> />
				&nbsp;
				<input type="button" name="icl_edit_languages_cancel_button" id="icl_edit_languages_cancel_button"
					   value="<?php esc_html_e( 'Cancel', 'sitepress' ); ?>" class="button-secondary icl_edit_languages_show"
												<?php
												if ( ! $this->validation_failed ) {
													?>
							 style="display:none;"<?php } ?> />
				&nbsp;
                <span id="wpml-language-edit-page" ></span>
				<input style="display:none;" type="submit" class="button-primary" value="<?php _e( 'Save', 'sitepress' ); ?>"/>
			</p>
			<br/>
		</form>
		<?php
	}

	private function prepare_new_lang_data( $new_lang ) {
		$new_lang = stripslashes_deep( $new_lang );
		$keys     = [ 'english_name', 'code', 'default_locale', 'tag' ];

		foreach ( $keys as $key ) {
			if ( isset( $new_lang[ $key ] ) ) {
				$new_lang[ $key ] = filter_var( $new_lang[ $key ], FILTER_SANITIZE_STRING );
			} else {
				$new_lang[ $key ] = '';
			}
		}

		$new_lang['flag']          = '';
		$new_lang['from_template'] = true;

		return $new_lang;
	}

	private function table_row( $lang, $echo = true, $add = false ) {
		global $sitepress;

		$styles  = [];
		$classes = [];

		if ( $add ) {
			if ( ( $this->is_new_data_and_valid() ) || 'add' !== $this->validation_action ) {
				$styles[] = 'display:none';
			}

			$styles[] = 'background-color:yellow';
		}

		if ( $add ) {
			$classes[] = 'icl_edit_languages_show';
		}

		$style = 'style="' . implode( ';', $styles ) . '"';
		$class = 'class="' . implode( ' ', $classes ) . '"';
		?>

		<tr <?php echo $style; ?> <?php echo $class; ?>>
			<td>
				<?php
				if ( $add ) {
					?>
					<input type="text" class="wpml_edit_languages_name" name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][english_name]" value="<?php echo esc_attr( $lang['english_name'] ); ?>"/>
					<?php
				} else {
					?>
					<div class="read-only" id="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][english_name]">
						<?php echo esc_attr( $lang['english_name'] ); ?>
                            <input type="hidden"
                                    class="wpml_edit_languages_name"
                                    name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][english_name]"
                                    value="<?php echo esc_attr( $lang['english_name'] ); ?>"/>
					</div>
					<?php
				}
				?>
			</td>
			<td>
				<?php
				if ( $add ) {
					?>
					<input type="text" class="wpml_edit_languages_code" name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][code]" value="<?php echo esc_attr( $lang['code'] ); ?>" maxlength="7" style="width:30px; max-width: 7em"/>
					<?php
				} else {
					?>
					<div class="read-only" id="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][code]"><?php echo esc_attr( $lang['code'] ); ?>
                        <input type="hidden"
                               name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][code]"
                               class="wpml_edit_languages_code"
                               value="<?php echo esc_attr( $lang['code'] ); ?>"
                               style="width:30px;"/>
					</div>
					<?php
				}
				?>
			</td>
			<td
				<?php
				if ( $this->must_display_new_language_translation_column() ) {
					echo 'style="display:none;" ';
				}
				?>
				class="icl_edit_languages_show"><input type="text"
														 name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][translations][add]"
														 value="<?php echo esc_attr( $this->get_add_language_from_post_data( $lang['id'] ) ); ?>"/>
			</td>
			<?php
			foreach ( self::get_active_languages() as $translation ) {
				?>
				<td><input type="text" name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][translations][<?php echo esc_attr( $translation['code'] ); ?>]"
						   value="<?php echo esc_attr( $this->get_translations_data( $lang, $translation ) ); ?>"/></td>
				<?php
			}
			?>
			<td>
				<?php
				if ( $this->is_writable ) {
				    $custom_flag_url = null;
					$allowed_types = array_keys( self::ACCEPTED_MIME_TYPES );
					?>
					<div style="float:left;">
						<ul>
							<li>
								<input type="radio"
									   id="wpm-edit-languages-<?php echo esc_attr( $lang['id'] ); ?>-flag-upload"
									   name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][flag_upload]"
									   value="true"
									   class="radio icl_edit_languages_use_upload"
									<?php
									if ( esc_attr( $lang['from_template'] ) ) {
										?>
											 checked="checked"<?php } ?> />
								&nbsp;
								<label for="wpm-edit-languages-<?php echo esc_attr( $lang['id'] ); ?>-flag-upload">
									<?php if ( $lang['code'] && $lang['from_template'] ) :
                                        $custom_flag_url = isset( $lang['flag_url'] ) ? $lang['flag_url'] : null; ?>
										&nbsp;<img
                                                src="<?php echo $this->wpml_flags->get_flag_url( $lang['code'] ); ?>"
                                                alt="<?php echo esc_attr( $lang ['code'] ); ?>"
                                                width="18"
                                        />
									<?php endif; ?>
									<?php _e( 'Custom flag', 'sitepress' ); ?>
								</label>
								<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo esc_attr( $this->max_file_size ); ?>"/>

								<div class="wpml-edit-languages-flag-upload-wrapper"
									<?php
									if ( ! $lang['from_template'] ) {
										?>
									style="display: none;"<?php } ?>>
									<input type="hidden"
										   name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][flag]"
										   value="<?php echo esc_attr( $lang['flag'] ); ?>"
										   class="icl_edit_languages_flag_enter_field" style="width: auto;"/>
									<input type="file" name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][flag_file]" class="icl_edit_languages_flag_upload_field file" style="width: 200px;"/>
									<br/>
									<?php echo sprintf( esc_html__( '(allowed: %s)', 'sitepress' ), implode( ', ', $allowed_types ) ); ?>
								</div>

							</li>
							<?php if ( Obj::prop( 'built_in', $lang ) ) : ?>
								<li>
									<label>
										<input type="radio"
											   name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][flag_upload]"
											   value="false"
											   class="radio icl_edit_languages_use_field"
											   <?php
												if ( ! $lang['from_template'] ) {
													?>
													 checked="checked"<?php } ?> />
										&nbsp;<img
											src="<?php echo WPML_Flags::get_wpml_flags_url() . $lang['code'] . '.png'; ?>"
											alt="<?php echo esc_attr( $lang ['code'] ); ?>"/>
										<?php esc_html_e( 'WPML flag', 'sitepress' ); ?>
									</label>
								</li>
							<?php endif ?>

							<?php if ( !Obj::prop( 'built_in', $lang ) && Obj::prop( 'flag_url', $lang ) && $lang['flag_url'] != $custom_flag_url ): ?>
                                <li>
                                    <label>
                                        <input type="radio"
                                               name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][flag_upload]"
                                               value="false"
                                               class="radio icl_edit_languages_use_field"
											<?php
											if ( ! $lang['from_template'] ) {
												?>
                                                checked="checked"<?php } ?> />
                                        &nbsp;<img
                                                src="<?php echo $lang['flag_url']; ?>"
                                                alt="<?php echo esc_attr( $lang ['code'] ); ?>"/>
										<?php esc_html_e( 'WPML flag', 'sitepress' ); ?>
                                    </label>
                                </li>
							<?php endif ?>
						</ul>
					</div>
					<?php
				}
				?>
			</td>
			<td>
				<div class="wpml-edit-languages-flag-use-field">
					<input type="text"
						   name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][default_locale]"
						   value="<?php echo esc_attr( $lang['default_locale'] ); ?>"
						   maxlength="<?php echo esc_attr( $this->max_locale_length ); ?>"
						   style="width: auto; max-width: 5em;"/>
				</div>
			</td>
			<td>
				<select name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][encode_url]">
					<option value="0"
						<?php
						if ( empty( $lang['encode_url'] ) ) :
							?>
						selected="selected"
											   <?php
					endif;
						?>
					><?php esc_html_e( 'No', 'sitepress' ); ?></option>
					<option value="1"
						<?php
						if ( ! empty( $lang['encode_url'] ) ) :
							?>
						selected="selected"
											   <?php
					endif;
						?>
					><?php esc_html_e( 'Yes', 'sitepress' ); ?></option>
				</select>
			</td>

			<td><input type="text" name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][tag]" maxlength="<?php echo esc_attr( $this->max_locale_length ); ?>" value="<?php echo esc_html( $lang['tag'] ); ?>"
					   style="width: auto; max-width: 5em;"/></td>

		    <?php
		    if ( \WPML_TM_ATE_Status::is_enabled_and_activated() ) {
			    $this->displayMappingField( $lang, $add );
		    }
            ?>


			<td>
				<?php
				if (
					! $add
					&& ! Obj::prop( 'built_in', $lang )
					&& $lang['code'] != $sitepress->get_default_language()
					&& count( self::get_active_languages() ) > 1
				) :
					?>
					<a href="
					<?php
					echo admin_url(
						'admin.php?page=' . WPML_PLUGIN_FOLDER . '/menu/languages.php&amp;trop=1&amp;action=delete-language&amp;id=' .
						urlencode( $lang['id'] ) . '&amp;icl_nonce=' . wp_create_nonce( 'delete-language' . $lang['id'] )
					)
					?>
												   " title="
												   <?php
													esc_attr_e( 'Delete', 'sitepress' )
													?>
					" onclick="if(!confirm('
					<?php
					echo esc_js( sprintf( __( 'Are you sure you want to delete this language?%sALL the data associated with this language will be ERASED!', 'sitepress' ), "\n" ) )
					?>
					')) return false;"><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/close.png" alt="
															<?php
															esc_attr_e( 'Delete', 'sitepress' )
															?>
						" width="16" height="16"/></a>
				<?php endif; ?>
			</td>

		</tr>
		<?php
	}

	public static function get_active_languages() {
		if ( self::$active_languages ) {
			return self::$active_languages;
		}

		global $sitepress;
		$result = [];

		$active_languages = Languages::withFlags( Languages::getActive() );
		if ( \WPML_TM_ATE_Status::is_enabled_and_activated() ) {
			if ( self::$newlySavedMapping ) {
				$active_languages = Fns::map( function ( $language ) {
					$newlyCreatedMapping = Lst::find( pipe(
						Obj::pathOr( null, [ 'source_language', 'code' ] ),
						Relation::equals( Obj::prop( 'code', $language ) )
					), self::$newlySavedMapping );

					if ( ! $newlyCreatedMapping || ! Obj::pathOr( false, [ 'result', 'created' ], $newlyCreatedMapping ) ) {
						$mapping = new LanguageMapping(
							Obj::prop( 'code', $language ),
							Obj::prop( 'english_name', $language ),
							LanguageMappings::IGNORE_MAPPING_ID
						);

						return array_merge( $language, [ 'can_be_translated_automatically' => false, 'mapping' => $mapping ] );
					} else {
						return array_merge( $language, [
							'can_be_translated_automatically' => true,
							'mapping'                         => new LanguageMapping(
								Obj::prop( 'code', $language ),
								Obj::prop( 'english_name', $language ),
								Obj::path( [ 'target_language', 'id' ], $newlyCreatedMapping ),
								Obj::path( [ 'target_language', 'code' ], $newlyCreatedMapping )
							)
						] );
					}
				}, $active_languages );
			} else {
				$active_languages = LanguageMappings::withCanBeTranslatedAutomatically( LanguageMappings::withMapping( $active_languages ) );
			}
		}

		foreach ( $active_languages as $lang ) {
			$code = Obj::prop( 'code', $lang );

			// Most of predefined languages can be mapped out of the box, even without defining mapping explicitly.
            // For instance, we know that French should be mapped to French in ATE and so on.
            // Here I perform this check for languages which do not have explicitly defined mapping
			if ( ! Obj::prop( 'mapping', $lang ) ) {
				foreach ( self::getLanguagesAvailableInATE() as $ateLanguage ) {
					if ( $code === Obj::prop( 'iso', $ateLanguage ) ) {
						$lang['mapping'] = new LanguageMapping(
							$code,
							Obj::prop( 'display_name', $lang ),
							Obj::prop( 'id', $ateLanguage ),
							Obj::prop( 'iso', $ateLanguage )
						);
						break;
					}
				}
			}


			$result[ $code ]                  = $lang;
			$result[ $code ]['flag']          = Obj::prop( 'flag_url', $lang );
			$result[ $code ]['from_template'] = Obj::prop( 'flag_from_template', $lang );

			foreach ( $active_languages as $lang_translation ) {
				$result[ $code ]['translation'][ Obj::prop( 'id', $lang_translation ) ] = $sitepress->get_display_language_name( $code, Obj::prop( 'code', $lang_translation ) );
			}
		}

        self::$active_languages = $result;

		return $result;
	}

	function update_main_table( $id, $code, $default_locale, $encode_url, $tag ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'icl_languages',
			[
				'code'           => $code,
				'default_locale' => $default_locale,
				'encode_url'     => $encode_url,
				'tag'            => $tag,
			],
			[ 'ID' => $id ]
		);
	}

	function insert_translation( $name, $language_code, $display_language_code ) {
		return Languages::setLanguageTranslation( $language_code, $display_language_code, $name );
	}

	function update_translation( $name, $language_code, $display_language_code ) {
		global $wpdb;
		$update_sql      = "UPDATE {$wpdb->prefix}icl_languages_translations SET name=%s WHERE language_code = %s AND display_language_code = %s";
		$update_prepared = $wpdb->prepare( $update_sql, [ $name, $language_code, $display_language_code ] );
		$wpdb->query( $update_prepared );
	}

	function insert_flag( $lang_code, $flag, $from_template ) {
		global $wpdb;
		$insert_sql      = "INSERT INTO {$wpdb->prefix}icl_flags (lang_code, flag, from_template) VALUES(%s, %s, %s)";
		$insert_prepared = $wpdb->prepare( $insert_sql, [ $lang_code, $flag, $from_template ] );

		return $wpdb->query( $insert_prepared );
	}

	function update_flag( $lang_code, $flag, $from_template ) {
		global $wpdb;
		$insert_and_update_sql = "INSERT INTO {$wpdb->prefix}icl_flags (lang_code, flag, from_template) VALUES(%s, %s, %s) ON DUPLICATE KEY UPDATE flag= %s,from_template=%s";
		$update_prepared       = $wpdb->prepare( $insert_and_update_sql, [ $lang_code, $flag, $from_template, $flag, $from_template ] );
		$wpdb->query( $update_prepared );
	}

	function update() {
		$this->mode = 'save';

		// Basic check.
		if ( ! isset( $_POST['icl_edit_languages'] ) || ! is_array( $_POST['icl_edit_languages'] ) ) {
			$this->set_errors( __( 'Please, enter valid data.', 'sitepress' ) );

			return;
		}

		global $sitepress, $wpdb;

		// First check if add and validate it.
		if ( isset( $_POST['icl_edit_languages']['add'] ) && $_POST['icl_edit_languages_ignore_add'] == 'false' ) {
			if ( $this->validate_one( 'add', $_POST['icl_edit_languages']['add'] ) ) {
				$this->insert_one( $this->sanitize( $_POST['icl_edit_languages']['add'] ) );
			}
			// Reset flag upload field.
			$_POST['icl_edit_languages']['add']['flag_upload'] = 'false';
		}

		foreach ( $_POST['icl_edit_languages'] as $id => $data ) {
			// Ignore insert.
			if ( $id == 'add' ) {
				continue;
			}

			// Validate and sanitize data.
			if ( ! $this->validate_one( $id, $data ) ) {
				continue;
			}
			$data = stripslashes_deep( $data );

			// Update main table.
			$this->update_main_table( $id, $data['code'], $data['default_locale'], $data['encode_url'], $data['tag'] );

			if (
			$wpdb->get_var(
				$wpdb->prepare( "SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE code = %s", $data['code'] )
			)
			) {
				$wpdb->update( $wpdb->prefix . 'icl_locale_map', [ 'locale' => $data['default_locale'] ], [ 'code' => $data['code'] ] );
			} else {
				$wpdb->insert(
					$wpdb->prefix . 'icl_locale_map',
					[
						'code'   => $data['code'],
						'locale' => $data['default_locale'],
					]
				);
			}

			// Update translations table.
			foreach ( $data['translations'] as $translation_code => $translation_value ) {

				// If new (add language) translations are submitted.
				if ( $translation_code == 'add' ) {
					if ( ( $this->is_new_data_and_invalid() ) || $_POST['icl_edit_languages_ignore_add'] == 'true' ) {
						continue;
					}
					if ( empty( $translation_value ) ) {
						$translation_value = $data['english_name'];
					}
					$translation_code = filter_var( $_POST['icl_edit_languages']['add']['code'], FILTER_SANITIZE_STRING );
				}

				// Check if update.
				if ( $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}icl_languages_translations WHERE language_code = %s AND display_language_code=%s",
						$data['code'],
						$translation_code
					)
				)
				) {
					$this->update_translation( $translation_value, $data['code'], $translation_code );
				} else {
					if ( ! $this->insert_translation( $translation_value, $data['code'], $translation_code ) ) {
						$this->set_errors( sprintf( __( 'Error adding translation %1$s for %2$s.', 'sitepress' ), $data['code'], $translation_code ) );
					}
				}
			}



			// Handle flag.
			$from_template = $this->handle_flag_post_data( $data, $id );

			// Update flag table.
			$this->update_flag( $data['code'], $data['flag'], $from_template );
			// Reset flag upload field.
			$_POST['icl_edit_languages'][ $id ]['flag_upload'] = 'false';
		}

		$this->saveLanguageMapping( $_POST['icl_edit_languages'] );

		// Refresh cache.
		$sitepress->get_language_name_cache()->clear();
		$sitepress->clear_flags_cache();
		delete_option( '_icl_cache' );

		// Unset ADD fields.
		if ( $this->is_new_data_and_valid() ) {
			unset( $_POST['icl_edit_languages']['add'] );
		}

		$this->update_language_packs( $sitepress );
	}

	/**
	 * @param array<string,string|array<string,string>> $data
	 * @param int                                       $id
	 *
	 * @return int
	 */
	private function handle_flag_post_data( array &$data, $id ) {
		$from_template = 0;
		if ( $this->is_flag_uploading_process( $data, $id ) ) {
			if ( $filename = $this->upload_flag( $id ) ) {
				$data['flag']  = $filename;
				$from_template = 1;
			} else {
				$data['flag'] = $data['code'] . '.png';
				$this->set_errors( __( 'Error uploading flag file.', 'sitepress' ) );
			}
			$this->wpml_flags->clear();
		} elseif ( empty( $data['flag'] ) || \WPML\FP\Relation::propEq( 'flag_upload', 'false', $data ) ) {
			if( $this->isBuildInLang($data['code']) ) {
				$data['flag'] = $data['code'] . '.png';
			} elseif (!empty($data['flag'])) {
				$data['flag'] = basename($data['flag']);
			} else {
				$data['flag'] = '';
			}
		} else {
		    // This is needed for the case that some other language is updated. In that case this will
            // part will for any custom flag and flag becomes the complete url.
			if (!empty($data['flag'])) {
				$data['flag'] = basename( $data['flag'] );
			}
			$from_template = 1;
		}

		return $from_template;
	}

	private function isBuildInLang( $langCode ) {
		$builtInLanguageCodes = Obj::values( \icl_get_languages_codes() );
		return Lst::includes( $langCode, $builtInLanguageCodes );
	}
	/**
	 * @param array<string,string|array<string,string>> $data
	 * @param int                                       $id
	 *
	 * @return bool
	 */
	private function is_flag_uploading_process( array &$data, $id ) {
		return array_key_exists( 'flag_upload', $data ) && 'true' == $data['flag_upload'] && ! empty( $_FILES['icl_edit_languages']['name'][ $id ]['flag_file'] );
	}

	function insert_one( $data ) {
		global $sitepress, $wpdb;

		$data = stripslashes_deep( stripslashes_deep( $data ) );
		// Insert main table.
		if ( ! Languages::add( $data['code'], $data['english_name'], $data['default_locale'], 0, 1, $data['encode_url'], $data['tag'] ) ) {
			$this->set_errors( __( 'Adding language failed.', 'sitepress' ) );

			return false;
		}

		// add locale map
		$locale_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT code
                                                        FROM {$wpdb->prefix}icl_locale_map
                                                        WHERE code=%s",
				$data['code']
			)
		);
		if ( $locale_exists ) {
			$wpdb->update( $wpdb->prefix . 'icl_locale_map', [ 'locale' => $data['default_locale'] ], [ 'code' => $data['code'] ] );
		} else {
			$wpdb->insert(
				$wpdb->prefix . 'icl_locale_map',
				[
					'code'   => $data['code'],
					'locale' => $data['default_locale'],
				]
			);
		}

		// Insert translations.
		$all_languages = $sitepress->get_languages();
		foreach ( $all_languages as $key => $lang ) {

			// If submitted.
			if ( array_key_exists( $lang['code'], $data['translations'] ) ) {
				if ( empty( $data['translations'][ $lang['code'] ] ) ) {
					$data['translations'][ $lang['code'] ] = $data['english_name'];
				}
				if ( ! $this->insert_translation(
					$data['translations'][ $lang['code'] ],
					$data['code'],
					$lang['code']
				)
				) {
					$this->set_errors(
						sprintf(
							__( 'Error adding translation %1$s for %2$s.', 'sitepress' ),
							$data['code'],
							$lang['code']
						)
					);
				}
			} else {
				if ( ! $this->insert_translation( $data['english_name'], $data['code'], $lang['code'] ) ) {
					$this->set_errors(
						sprintf(
							__( 'Error adding translation %1$s for %2$s.', 'sitepress' ),
							$data['code'],
							$lang['code']
						)
					);
				}
			}
		}

		// Insert native name.
		if ( ! isset( $data['translations']['add'] ) || empty( $data['translations']['add'] ) ) {
			$data['translations']['add'] = $data['english_name'];
		}
		if ( ! $this->insert_translation( $data['translations']['add'], $data['code'], $data['code'] ) ) {
			$this->set_errors( __( 'Error adding native name.', 'sitepress' ) );
		}

		// Handle flag.
		$from_template = $this->handle_flag_post_data( $data, 'add' );

		// Insert flag table.
		if ( ! $this->insert_flag( $data['code'], $data['flag'], $from_template ) ) {
			$this->set_errors( __( 'Error adding flag.', 'sitepress' ) );
		}
		SitePress_Setup::insert_default_category( $data['code'] );
	}

	function validate_one( $id, $data ) {

		global $wpdb;

		$new_record = 'add' === $id;

		$unique_columns = [
			'code'           => esc_html__( 'The Language code already exists.', 'sitepress' ),
			'english_name'   => esc_html__( 'The Language name already exists.', 'sitepress' ),
			'default_locale' => esc_html__( 'The default locale already exists.', 'sitepress' ),
			'tag'            => esc_html__( 'The hreflang already exists.', 'sitepress' ),
		];

		foreach ( $unique_columns as $column => $message ) {
			$exists_args = [ $data[ $column ] ];

			$exists_query = 'SELECT ' . esc_sql( $column ) . ' FROM ' . $wpdb->prefix . 'icl_languages WHERE ' . esc_sql( $column ) . '=%s ';

			if ( ! $new_record ) {
				$exists_query .= 'AND id!=%d ';
				$exists_args[] = $id;
			}

			$exists_query .= 'LIMIT 1';

			$exists = $wpdb->get_var( $wpdb->prepare( $exists_query, $exists_args ) );
			if ( $exists ) {
				$this->error = $message;
				$this->set_validation_failed( $id );

				return false;
			}
		}

		foreach ( $this->required_fields as $name => $type ) {
			if ( 'flag' === $name ) {
				if ( isset( $data['flag_upload'] ) && 'true' === $data['flag_upload'] ) {
					$check = $_FILES['icl_edit_languages']['name'][ $id ]['flag_file'];
					if ( empty( $check ) ) {
						continue;
					}
					if ( ! $this->check_extension( $check ) ) {
						if ( 'add' === $id ) {
							$this->set_validation_failed( $id );
						}

						return false;
					}
				}
				continue;
			}

			if ( 'code' === $name ) {
				if ( ! $this->is_language_code_valid( $data[ $name ] ) ) {
					$this->set_errors( __( 'Invalid character in language code.', 'sitepress' ) );
					$this->set_validation_failed( $id );

					return false;
				}

				continue;
			}

			if ( ! isset( $_POST['icl_edit_languages'][ $id ][ $name ] ) || empty( $_POST['icl_edit_languages'][ $id ][ $name ] ) ) {
				if ( 'true' === $_POST['icl_edit_languages_ignore_add'] ) {
					return false;
				}
				$this->set_errors( __( 'Please, enter required data.', 'sitepress' ) );
				if ( 'add' === $id ) {
					$this->set_validation_failed( $id );
				}

				return false;
			}
			if ( 'array' === $type && ! is_array( $_POST['icl_edit_languages'][ $id ][ $name ] ) ) {
				if ( 'add' === $id ) {
					$this->set_validation_failed( $id );
				}
				$this->set_errors( __( 'Please, enter valid data.', 'sitepress' ) );

				return false;
			}
		}

		return true;
	}

	/**
	 * Checks that language code is valid.
	 *
	 * @param string $language_code Unvalidated language code from input.
	 *
	 * @return bool
	 */
	private function is_language_code_valid( $language_code ) {
		$pattern = '/^[a-zA-Z0-9\-\_]+$/';

		return (bool) preg_match( $pattern, $language_code );
	}

	/**
	 * @return bool
	 */
	private function is_delete_language_action() {
		return isset( $_GET['action'] ) && 'delete-language' === $_GET['action'] && wp_create_nonce( 'delete-language' . (int) $_GET['id'] ) == $_GET['icl_nonce'];
	}

	/**
	 * @return bool
	 */
	private function must_display_new_language_translation_column() {
		return $this->is_edit_mode() || $this->is_new_data_and_valid();
	}

	/**
	 * @return bool
	 */
	private function is_new_data_and_invalid() {
		return $this->validation_action && $this->validation_failed && 'add' === $this->validation_action;
	}

	/**
	 * @return bool
	 */
	private function is_new_data_and_valid() {
		return $this->validation_action && ! $this->validation_failed && 'add' === $this->validation_action;
	}

	/**
	 * @return bool
	 */
	private function is_edit_mode() {
		return 'edit' === $this->mode;
	}

	private function set_validation_failed( $id ) {
		$this->validation_action = ( 'add' === $id ) ? 'add' : 'update';
		$this->validation_failed = true;
	}

	function delete_language( $lang_id ) {
		global $wpdb, $sitepress;
		$lang = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}icl_languages WHERE id=%d", $lang_id ) );
		if ( $lang ) {
			if ( in_array( $lang->code, array_values( icl_get_languages_codes() ), true ) ) {
				$error = __( "Error: This is a built in language. You can't delete it.", 'sitepress' );
			} else {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_languages WHERE id=%d", $lang_id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_languages_translations WHERE language_code=%s", $lang->code ) );

				$translation_ids = $wpdb->get_col( $wpdb->prepare( "SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE language_code=%s", $lang->code ) );
				if ( $translation_ids ) {
					$rids = $wpdb->get_col( "SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id IN (" . wpml_prepare_in( $translation_ids, '%d' ) . ')' );
					if ( $rids ) {
						$job_ids = $wpdb->get_col( "SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid IN (" . wpml_prepare_in( $rids, '%d' ) . ')' );
						if ( $job_ids ) {
							$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id IN (" . wpml_prepare_in( $job_ids, '%d' ) . ')' );
						}
					}
				}

				// delete posts
				$post_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type LIKE %s AND language_code=%s",
						[ wpml_like_escape( 'post_' ) . '%', $lang->code ]
					)
				);
				remove_action( 'delete_post', [ $sitepress, 'delete_post_actions' ] );
				foreach ( $post_ids as $post_id ) {
					wp_delete_post( $post_id, true );
				}
				add_action( 'delete_post', [ $sitepress, 'delete_post_actions' ] );

				// delete terms
				remove_action( 'delete_term', [ $sitepress, 'delete_term' ], 1 );
				$tax_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type LIKE %s AND language_code=%s",
						[ wpml_like_escape( 'tax_' ) . '%', $lang->code ]
					)
				);
				foreach ( $tax_ids as $tax_id ) {
					$row = $wpdb->get_row( $wpdb->prepare( "SELECT term_id, taxonomy FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d", $tax_id ) );
					if ( $row ) {
						wp_delete_term( $row->term_id, $row->taxonomy );
					}
				}
				add_action( 'delete_term', [ $sitepress, 'delete_term' ], 1, 3 );

				// delete comments
				global $IclCommentsTranslation;
				remove_action( 'delete_comment', [ $IclCommentsTranslation, 'delete_comment_actions' ] );
				foreach ( $post_ids as $post_id ) {
					wp_delete_post( $post_id, true );
				}
				add_action( 'delete_comment', [ $IclCommentsTranslation, 'delete_comment_actions' ] );

				do_action(
					'wpml_translation_update',
					[
						'type'     => 'before_language_delete',
						'language' => $lang->code,
					]
				);

				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translations WHERE language_code=%s", $lang->code ) );

				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_strings WHERE language=%s", $lang->code ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_string_translations WHERE language=%s", $lang->code ) );

				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_locale_map WHERE code=%s", $lang->code ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_flags WHERE lang_code=%s", $lang->code ) );

				icl_cache_clear( false );

				$sitepress->get_translations_cache()->clear();
				$sitepress->clear_flags_cache();
				$sitepress->get_language_name_cache()->clear();

				$this->set_messages( sprintf( esc_html__( 'The language %s was deleted.', 'sitepress' ), '<strong>' . $lang->code . '</strong>' ) );

				$this->update_language_packs( $sitepress );
			}
		} else {
			$error = __( 'Error: Language not found.', 'sitepress' );
		}
		if ( ! empty( $error ) ) {
			$this->set_errors( $error );
		}
	}

	function sanitize( $data ) {
		global $wpdb;
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $k => $v ) {
					$data[ $key ][ $k ] = esc_sql( $v );
				}
			}
			$data[ $key ] = esc_sql( $value );
		}

		return $data;
	}

	function check_extension( $file ) {
		$extension = substr( $file, strrpos( $file, '.' ) + 1 );
		if ( ! in_array( strtolower( $extension ), [ 'png', 'gif', 'jpg', 'svg' ], true ) ) {
			$this->set_errors( __( 'File extension not allowed.', 'sitepress' ) );

			return false;
		}

		return true;
	}

	function get_errors() {
		return $this->error;
	}

	function set_errors( $str = false ) {
		$this->error .= $str . '<br />';
	}

	function get_messages() {
		return $this->message;
	}

	function set_messages( $str = false ) {
		$this->message .= $str . '<br />';
	}

	function upload_flag( $id ) {
		$result        = false;
		$uploaded_file = false;

		if ( isset( $_FILES['icl_edit_languages']['tmp_name'][ $id ]['flag_file'] ) ) {
			$uploaded_file = filter_var(
				$_FILES['icl_edit_languages']['tmp_name'][ $id ]['flag_file'],
				FILTER_SANITIZE_FULL_SPECIAL_CHARS
			);
		}

		if ( $uploaded_file ) {
			$original_file = filter_var(
				$_FILES['icl_edit_languages']['name'][ $id ]['flag_file'],
				FILTER_SANITIZE_FULL_SPECIAL_CHARS
			);
			$target_path   = $this->upload_dir . '/' . $original_file;

			$wpml_wp_api = new WPML_WP_API();

			add_filter( 'upload_mimes', \WPML\FP\Fns::always( self::ACCEPTED_MIME_TYPES ) );

			$mime = $wpml_wp_api->get_file_mime_type( $uploaded_file, $original_file );

			$allowed_mime_types = array_values( self::ACCEPTED_MIME_TYPES );
			$validated          = in_array( $mime, $allowed_mime_types, true );

			if ( $validated && move_uploaded_file( $uploaded_file, $target_path ) ) {

				if ((!defined('WPML_DO_NOT_RESIZE_UPLOADED_FLAGS') || !WPML_DO_NOT_RESIZE_UPLOADED_FLAGS) && function_exists( 'wp_get_image_editor' ) && 'image/svg+xml' !== $mime ) {
					$image = wp_get_image_editor( $target_path );
					if ( ! is_wp_error( $image ) ) {
						$image->resize( 18, 12, true );
						$image->save( $target_path );
					}
				}

				$result = $original_file;
			}
		} else {
			$error_message = __( 'There was an error uploading the file, please try again!', 'sitepress' );
			if ( ! empty( $_FILES['icl_edit_languages']['error'][ $id ]['flag_file'] ) ) {
				switch ( filter_var( $_FILES['icl_edit_languages']['error'][ $id ]['flag_file'], FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) {
					case UPLOAD_ERR_INI_SIZE;
						$error_message = __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'sitepress' );
						break;
					case UPLOAD_ERR_FORM_SIZE;
						$error_message = sprintf( __( 'The uploaded file exceeds %s bytes.', 'sitepress' ), $this->max_file_size );
						break;
					case UPLOAD_ERR_PARTIAL;
						$error_message = __( 'The uploaded file was only partially uploaded.', 'sitepress' );
						break;
					case UPLOAD_ERR_NO_FILE;
						$error_message = __( 'No file was uploaded.', 'sitepress' );
						break;
					case UPLOAD_ERR_NO_TMP_DIR;
						$error_message = __( 'Missing a temporary folder.', 'sitepress' );
						break;
					case UPLOAD_ERR_CANT_WRITE;
						$error_message = __( 'Failed to write file to disk.', 'sitepress' );
						break;
					case UPLOAD_ERR_EXTENSION;
						$error_message = __( 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.', 'sitepress' );
						break;
				}
			}
			$this->set_errors( $error_message );
		}

		return $result;
	}

	function migrate() {
		global $sitepress, $sitepress_settings;
		if ( ! isset( $sitepress_settings['edit_languages_flag_migration'] ) ) {
			foreach ( glob( get_stylesheet_directory() . '/flags/*' ) as $filename ) {
				rename( $filename, $this->upload_dir . '/' . basename( $filename ) );
			}
			$sitepress->save_settings( [ 'edit_languages_flag_migration' => 1 ] );
		}
	}

	/**
	 * @param \SitePress $sitepress
	 */
	private function update_language_packs( SitePress $sitepress ) {
		if ( $this->update_language_packs_if_needed ) {
			$wpml_localization = new WPML_Download_Localization( $sitepress->get_active_languages(), $sitepress->get_default_language() );
			$wpml_localization->download_language_packs();
			$wpml_languages_notices = new WPML_Languages_Notices( wpml_get_admin_notices() );
			$wpml_languages_notices->missing_languages( $wpml_localization->get_not_founds() );
		}
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	private function get_add_language_from_post_data( $id ) {
		$value = isset( $_POST['icl_edit_languages'][ $id ]['translations']['add'] ) ? stripslashes_deep( $_POST['icl_edit_languages'][ $id ]['translations']['add'] ) : '';
		$value = filter_var( $value, FILTER_SANITIZE_STRING );

		return $value;
	}

	/**
	 * @param array<string,string|array<string,string>> $lang
	 * @param array<string,string>                      $translation
	 *
	 * @return string
	 */
	private function get_translations_data( $lang, $translation ) {
		if ( $lang['id'] == 'add' ) {
			$value = isset( $_POST['icl_edit_languages']['add']['translations'][ $translation['code'] ] ) ? $_POST['icl_edit_languages']['add']['translations'][ $translation['code'] ] : '';
			$value = filter_var( $value, FILTER_SANITIZE_STRING );
		} else {
			$value = isset( $lang['translation'][ $translation['id'] ] ) ? $lang['translation'][ $translation['id'] ] : '';
		}
		$value = stripslashes_deep( $value );

		return $value;
	}

	private function saveLanguageMapping( $languagesData ) {
		if ( ! \WPML_TM_ATE_Status::is_enabled_and_activated() ) {
			return;
		}

		// for a new language if nothing was chosen, select "Don't map"
		$languagesData = Fns::map( function ( $data, $id ) {
			if ( $id === 'add' && ! Obj::prop( 'mapping', $data ) ) {
				$data['mapping'] = LanguageMappings::IGNORE_MAPPING_ID;
			}

			return $data;
		}, $languagesData );

		$mapping = Fns::map( function ( $data ) {
			if ( $data['mapping'] == LanguageMappings::IGNORE_MAPPING_ID ) {
				return new LanguageMapping( $data['code'], $data['english_name'], $data['mapping'], null );
			} else {
				list( $targetId, $targetCode ) = Str::split( '###', $data['mapping'] );

				return new LanguageMapping( $data['code'], $data['english_name'], $targetId, $targetCode );
			}
		}, Fns::filter( Obj::prop( 'mapping' ), $languagesData ) );

		$storeDataInTemporaryVariable = function ( $data ) {
			self::$newlySavedMapping = $data;
		};

		$findErrors      = Fns::filter( Logic::complement( Obj::pathOr( false, [ 'result', 'created' ] ) ) );
		$getSourceNames  = Fns::map( Obj::path( [ 'source_language', 'name' ] ) );
		$displayErrorMsg = function ( $languageNames ) {
			$this->set_errors( sprintf(
				__( 'The language mapping could not be saved for languages: %s', 'sitepress-multilingual-cms' ),
				Lst::join( ', ', $languageNames )
			) );
		};
		$logError        = pipe( $findErrors, $getSourceNames, $displayErrorMsg );

		LanguageMappings::saveMapping( $mapping )->bimap( $logError, $storeDataInTemporaryVariable );
	}

	private function displayMappingField( $lang, $add ) {
	    ?>
            <td>
	            <?php
	            $targetId = Obj::pathOr(null, [ 'mapping', 'targetId' ], $lang );
	            $displayNotice = ! $add && ( ! Obj::prop( 'can_be_translated_automatically', $lang ) ||
	                                         Obj::pathOr(null, [ 'mapping', 'targetId' ], $lang ) === LanguageMappings::IGNORE_MAPPING_ID );
	            ?>

                <select class="icl_edit_languages_mapping" name="icl_edit_languages[<?php echo esc_attr( $lang['id'] ); ?>][mapping]">
	                <?php if ( ! $targetId ) { ?>
                        <option value="0"><?php _e( 'Choose mapping', 'sitepress-multilingual-cms' ) ?></option>
	                <?php } ?>
                    <option value="<?php echo LanguageMappings::IGNORE_MAPPING_ID ?>" <?php if ( $targetId === LanguageMappings::IGNORE_MAPPING_ID ) echo 'selected="selected"'; ?> >
                        <?php echo __( "Don't map this language", 'sitepress-multilingual-cms' ); ?>
                    </option>
	                <?php foreach ( self::getLanguagesAvailableInATE() as $ateLanguage ) { ?>
                        <option value="<?php echo Obj::prop('id', $ateLanguage) . '###' . Obj::prop('iso', $ateLanguage )?>"
	                        <?php if ( Obj::prop( 'id', $ateLanguage ) === $targetId ) {
		                        echo 'selected="selected"';
	                        } ?>
                        >
                            <?php echo Obj::prop('name', $ateLanguage); ?>
                        </option>
                    <?php } ?>
                </select>
                <br/>

                    <?php if (Obj::prop('code', $lang) === Languages::getDefaultCode()) { ?>
                        <div class="notice-info <?php echo Option::shouldTranslateEverything() ? 'error' : 'notice' ?> inline" style="max-width: 190px;<?php echo ! $displayNotice ? 'display:none;' : '' ?>">
				            <?php echo sprintf( __(
				                    'None of your content can be automatically translated until you <a href="%s" target="_blank" referrerpolicy="no-referrer">map your default language to a supported language</a>.', 'sitepress-multilingual-cms' ),
                                'https://wpml.org/documentation/automatic-translation/using-automatic-translation-with-custom-languages/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlcore'
                            ) ?>
                        </div>
                    <?php } elseif ( CachedLanguageMappings::hasTheSameMappingAsDefaultLang ( $lang ) ) { ?>
                        <div class="notice-info notice inline" style="max-width: 190px;<?php echo ! $displayNotice ? 'display:none;' : '' ?>">
	                        <?php echo sprintf( __(
		                        "Content in this language cannot be automatically translated unless you map it to a language that's different from the default. <a href='%s' target='_blank' referrerpolicy='no-referrer'>Read more</a>", 'sitepress-multilingual-cms' ),
		                        'https://wpml.org/documentation/automatic-translation/using-automatic-translation-with-custom-languages/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlcore#important-considerations-when-mapping-languages'
	                        ) ?>
                        </div>
                    <?php }else{ ?>
                        <div class="notice-info notice inline" style="max-width: 190px;<?php echo ! $displayNotice ? 'display:none;' : '' ?>">
                            <?php _e( 'Content in this language cannot be automatically translated unless you map it to a supported language.', 'sitepress-multilingual-cms' ) ?>
                        </div>
                    <?php } ?>
            </td>
        <?php
	}
}
