<?php

use WPML\ST\Gettext\AutoRegisterSettings;
use function WPML\Container\make;
use WPML\UIPage;

/** @var WPML_String_Translation $WPML_String_Translation */
global $sitepress, $WPML_String_Translation, $wpdb, $wp_query;

$string_settings = $WPML_String_Translation->get_strings_settings();

if ( ( ! isset( $sitepress_settings['existing_content_language_verified'] ) || ! $sitepress_settings['existing_content_language_verified'] ) /*|| 2 > count($sitepress->get_active_languages())*/ ) {
	return;
}

if ( filter_input( INPUT_GET, 'trop', FILTER_SANITIZE_NUMBER_INT ) > 0 ) {
	include dirname( __FILE__ ) . '/string-translation-translate-options.php';
	return;
} elseif ( filter_input( INPUT_GET, 'download_mo', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) {
	include dirname( __FILE__ ) . '/auto-download-mo.php';
	return;
}
$status_filter      = filter_input( INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
$status_filter_text = $status_filter;
$status_filter_lang = false;
if ( preg_match(
	'#' . ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR . '-(.+)#',
	$status_filter_text,
	$matches
)
) {
	$status_filter      = ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR;
	$status_filter_lang = $matches[1];
} else {
	$status_filter = filter_input( INPUT_GET, 'status', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
}
// $status_filter  = $status_filter !== false ? (int) $status_filter : null;
$context_filter = filter_input( INPUT_GET, 'context', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

$search_filter      = filter_input( INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS );
$exact_match        = filter_input( INPUT_GET, 'em', FILTER_VALIDATE_BOOLEAN );
$search_translation = filter_input( INPUT_GET, 'search_translation', FILTER_VALIDATE_BOOLEAN );
$is_troubleshooting = filter_input( INPUT_GET, 'troubleshooting', FILTER_VALIDATE_BOOLEAN );

$filter_translation_priority = filter_var( isset( $_GET['translation-priority'] ) ? $_GET['translation-priority'] : '', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$translation_priorities      = class_exists( 'WPML_TM_Translation_Priorities' ) ? get_terms(
	array(
		'taxonomy'   => 'translation_priority',
		'hide_empty' => false,
	)
) : array();

$active_languages          = $sitepress->get_active_languages();
$icl_contexts              = icl_st_get_contexts( $status_filter );
$unfiltered_context_counts = $status_filter !== false ? icl_st_get_contexts( false ) : $icl_contexts;

function context_array( $contexts ) {
	$count_array = array();
	$contexts    = $contexts ? array_filter( $contexts ) : array();
	foreach ( $contexts as $c ) {
		$count_array[ $c->context ] = $c->c;
	}

	return $count_array;
}

$available_contexts  = array_keys( context_array( $icl_contexts ) );
$unfiltered_contexts = context_array( $unfiltered_context_counts );

function _icl_string_translation_rtl_div( $language ) {
	if ( in_array( $language, array( 'ar', 'he', 'fa' ) ) ) {
		echo ' dir="rtl" style="text-align:right;direction:rtl;"';
	} else {
		echo ' dir="ltr" style="text-align:left;direction:ltr;"';
	}
}
function _icl_string_translation_rtl_textarea( $language ) {
	if ( in_array( $language, array( 'ar', 'he', 'fa' ) ) ) {
		echo ' dir="rtl" style="text-align:right;direction:rtl;width:100%"';
	} else {
		echo ' dir="ltr" style="text-align:left;direction:ltr;width:100%"';
	}
}

$po_importer = apply_filters( 'wpml_st_get_po_importer', null );

/** @var WPML_PO_Import_Strings $wpml_po_import_strings */
$wpml_po_import_strings = WPML\Container\make( WPML_PO_Import_Strings::class );

wp_enqueue_script( 'wpml-tooltip', WPML_ST_URL . '/res/js/tooltip.js', array( 'wp-pointer', 'jquery' ), WPML_ST_VERSION );
wp_enqueue_style( 'wpml-tooltip', WPML_ST_URL . '/res/css/tooltip/tooltip.css', array( 'wp-pointer' ), WPML_ST_VERSION );
?>
<div class="wrap<?php if ($is_troubleshooting): ?> st-troubleshooting<?php endif; ?>">
	<h2><?php echo esc_html__( $is_troubleshooting ? 'String Troubleshooting' : 'String translation', 'wpml-string-translation' ); ?></h2>

	<?php if ($is_troubleshooting): ?>
		<div data-show="true" class="ant-alert ant-alert-info st-troubleshooting-alert" role="alert">
			<svg width="22" height="22" fill="#33879e" xmlns="http://www.w3.org/2000/svg">
				<path d="M10.267 5.867a.733.733 0 111.466 0v6.6a.733.733 0 11-1.466 0v-6.6zM11 14.667a.733.733 0 100 1.466.733.733 0 000-1.466z"></path>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M11 22c6.075 0 11-4.925 11-11S17.075 0 11 0 0 4.925 0 11s4.925 11 11 11zm0-1.467a9.533 9.533 0 100-19.066 9.533 9.533 0 000 19.066z"></path>
			</svg>
			<div class="ant-alert-content">
				<div class="ant-alert-message">
					<p><?php echo esc_html__( 'This is the list of strings that are not used or they are linked to wrong translation data.', 'wpml-string-translation' ); ?></p>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php
		do_action( 'display_basket_notification', 'st_dashboard_top' );
	?>

	<?php if ( isset( $po_importer ) && $po_importer->has_strings() ) : ?>

		<p><?php printf( esc_html__( "These are the strings that we found in your .po file. Please carefully review them. Then, click on the 'add' or 'cancel' buttons at the %1\$sbottom of this screen%2\$s. You can exclude individual strings by clearing the check boxes next to them.", 'wpml-string-translation' ), '<a href="#add_po_strings_confirm">', '</a>' ); ?></p>
		<form method="post" id="wpml_add_strings" action="<?php echo admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php' ); ?>">
		<input type="hidden" id="strings_json" name="strings_json">
		<?php wp_nonce_field( 'add_po_strings' ); ?>
		<?php $use_po_translations = filter_input( INPUT_POST, 'icl_st_po_translations', FILTER_VALIDATE_BOOLEAN ); ?>
		<?php if ( $use_po_translations == true ) : ?>
		<input type="hidden" name="action" value="icl_st_save_strings" />
		<input
			type="hidden"
			name="icl_st_po_language"
			value="<?php echo filter_input( INPUT_POST, 'icl_st_po_language', FILTER_SANITIZE_FULL_SPECIAL_CHARS ); ?>"
		/>
		<?php endif; ?>
			<?php
			$icl_st_domain = filter_input( INPUT_POST, 'icl_st_i_context_new', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$icl_st_domain = $icl_st_domain ? $icl_st_domain : filter_input( INPUT_POST, 'icl_st_i_context', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			?>
		<input
			type="hidden"
			name="icl_st_domain_name"
			value="<?php echo $icl_st_domain; ?>"
		/>
		<input
			type="hidden"
			name="icl_st_po_source_language"
			value="<?php echo filter_input( INPUT_POST, 'icl_st_po_source_language', FILTER_SANITIZE_FULL_SPECIAL_CHARS ); ?>"
		/>

		<table id="icl_po_strings" class="widefat" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-cb check-column"><input type="checkbox" checked="checked" name="" /></th>
					<th><?php echo esc_html__( 'String', 'wpml-string-translation' ); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" class="manage-column column-cb check-column"><input type="checkbox" checked="checked" name="" /></th>
					<th><?php echo esc_html__( 'String', 'wpml-string-translation' ); ?></th>
				</tr>
			</tfoot>
			<tbody>
				<?php
				$k = -1;
				foreach ( $po_importer->get_strings() as $str ) :
					$k++;
					?>
					<tr>
						<td><input class="icl_st_row_cb js-icl-st-row-cb" type="checkbox" name="icl_strings_selected[]"
							<?php
							if ( $str['exists'] || $use_po_translations !== true ) :
								?>
								checked="checked"<?php endif; ?> value="<?php echo $k; ?>" /></td>
						<td>
							<input type="text" name="icl_strings[]" value="<?php echo esc_attr( $str['string'] ); ?>" readonly="readonly" style="width:100%;" size="100" />
							<?php if ( $use_po_translations === true ) : ?>
							<input type="text" name="icl_translations[]" value="<?php echo esc_attr( $str['translation'] ); ?>" readonly="readonly" style="width:100%;
																						   <?php
																							if ( $str['fuzzy'] ) :
																								?>
 ;background-color:#ffecec<?php endif; ?>" size="100" />
							<input type="hidden" name="icl_fuzzy[]" value="<?php echo $str['fuzzy']; ?>" />
							<input type="hidden" name="icl_name[]" value="<?php echo $str['name']; ?>" />
							<input type="hidden" name="icl_context[]" value="<?php echo $str['context']; ?>" />
							<?php endif; ?>
							<?php if ( $str['name'] != md5( $str['string'] ) ) : ?>
								<i><?php printf( esc_html__( 'Name: %s', 'wpml-string-translation' ), $str['name'] ); ?></i><br/>
							<?php endif ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<a name="add_po_strings_confirm"></a>

			<p><span style="float: left"><input class="js-wpml-btn-cancel button wpml-button base-btn gray-light-btn" type="button" value="<?php echo esc_attr__( 'Cancel', 'wpml-string-translation' ); ?>"
												onclick="location.href='admin.php?page=<?php echo htmlspecialchars( $_GET['page'], ENT_QUOTES ); ?>'"/>
		&nbsp;<input disabled="disabled" class="js-wpml-btn-add-strings button-primary wpml-button base-btn" type="submit" value="<?php echo esc_attr__( 'Add selected strings', 'wpml-string-translation' ); ?>"/></span><span class="spinner" style="float: left"></span>
		</p>
		</form>

	<?php else : ?>

		<div class="wpml-string-translation-filter">
		<select aria-label="<?php echo esc_html__( 'Display:', 'wpml-string-translation' ); ?>" name="icl_st_filter_status">
			<?php
			$createOption = function( $str, $option ) use ( $status_filter ) {
				$selected = selected( $option, $status_filter, false );
				?>
				<option value="<?php echo $option; ?>" <?php echo $selected; ?>>
					<?php echo esc_html( $str ); ?>
				</option>
				<?php
			};

			$createOption( __( 'All strings', 'wpml-string-translation' ), false );
			$createOption( WPML_ST_String_Statuses::get_status( ICL_TM_COMPLETE ), ICL_TM_COMPLETE );
			$createOption( __( 'Translation needed', 'wpml-string-translation' ), ICL_TM_NOT_TRANSLATED );
			$createOption( __( 'Waiting for translator', 'wpml-string-translation' ), ICL_TM_WAITING_FOR_TRANSLATOR );
			$createOption( __( 'Partial Translation', 'wpml-string-translation' ), ICL_STRING_TRANSLATION_PARTIAL );
			$createOption( __( 'Auto-registered, translation needed', 'wpml-string-translation' ), ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND );
	?>

		</select>
		<span style="white-space:nowrap">
			<label for="icl_st_filter_search" class="wpml-string-translation-filter-search">
				<span class="visually-hidden"><?php echo esc_html__( 'Search for:', 'wpml-string-translation' ); ?></span>
				<input
					placeholder="<?php echo esc_html__( 'Search', 'wpml-string-translation' ); ?>"
					type="text" id="icl_st_filter_search" value="<?php echo $search_filter; ?>"
				/>
			</label>
			<div class="wpml-string-translation-filter__checkboxes" style="display: none;">
				<label for="icl_st_filter_search_em">
					<input
						type="checkbox"
						id="icl_st_filter_search_em"
						value="1"
						<?php
						if ( $exact_match ) :
							?>
							checked="checked"
						<?php endif; ?>
					/>
					<span><?php echo esc_html__( 'Exact match', 'wpml-string-translation' ); ?></span>
				</label>

				<label for="search_translation">
					<input
						type="checkbox"
						id="search_translation"
						value="1"
						<?php
						if ( $search_translation ) :
							?>
							checked="checked"
						<?php endif; ?>
						class="js-otgs-popover-tooltip"
						title="<?php echo esc_attr__( 'Search in both the original language and in translations. Searching in translations may take a bit of time.', 'wpml-string-translation' ); ?>"
					/>
					<span><?php echo esc_html__( 'Include translations', 'wpml-string-translation' ); ?></span>
				</label>
			</div>
		</span>

			<?php if ( ! empty( $icl_contexts ) ) : ?>
				<span style="white-space:nowrap">
					<select aria-label="<?php echo esc_html__( 'In domain:', 'wpml-string-translation' ); ?>" name="icl_st_filter_context">
						<option value=""
								<?php
								if ( $context_filter === false ) :
									?>
									selected="selected"<?php endif; ?>><?php echo esc_html__( 'All domains', 'wpml-string-translation' ); ?></option>
						<?php foreach ( $icl_contexts as $v ) : ?>
							<?php
							if ( ! $v->context ) {
								$v->context = WPML_ST_Strings::EMPTY_CONTEXT_LABEL;
							}
							?>
							<option value="<?php echo esc_attr( $v->context ); ?>"
									data-unfiltered-count="<?php echo( isset( $unfiltered_contexts[ $v->context ] ) ? $unfiltered_contexts[ $v->context ] : 0 ); ?>"
									<?php
									if ( $context_filter == filter_var( $v->context, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) :
										?>
										selected="selected"<?php endif; ?>><?php echo esc_html( $v->context ) . ' (' . $v->c . ')'; ?></option>
						<?php endforeach; ?>
					</select>
		</span>
			<?php endif; ?>
		<?php if ( $translation_priorities ) : ?>
			<span style="white-space:nowrap">
				<select aria-label="<?php echo esc_html__( 'With Priority:', 'wpml-string-translation' ); ?>" name="icl-st-filter-translation-priority">
					<option value=""><?php esc_html_e( 'All Translation Priorities', 'wpml-string-translation' ); ?></option>
					<?php
					foreach ( $translation_priorities as $translation_priority ) {
						$translation_priority_selected = selected( $filter_translation_priority, $translation_priority->name, false );
						?>
						<option value="<?php echo $translation_priority->name; ?>" <?php echo $translation_priority_selected; ?>>
							<?php echo $translation_priority->name; ?>
						</option>
					<?php } ?>
				</select>
			</span>
		<?php endif; ?>
			<?php $search_filter_safe = is_null($search_filter) || $search_filter === false ? '' : $search_filter; ?>
		<input class="button" type="button" value="<?php esc_attr_e( 'Filter', 'wpml-string-translation' ); ?>" id="icl_st_filter_search_sb"/>
		<label for="icl_st_filter_search_remove" style="white-space:nowrap;">
			<span class="visually-hidden">
				<?php printf( esc_html__( 'Showing only strings that contain %s', 'wpml-string-translation' ), '<i>' . esc_html( $search_filter_safe ) . '</i>' ); ?>
			</span>
			<input style="display:none;" class="button" type="button" value="<?php esc_attr_e( 'x &nbsp;Clear filters', 'wpml-string-translation' ); ?>" id="icl_st_filter_search_remove"/>
		</label>

		</div>
		<div id="wpml-mo-scan-st-page"<?php if ( ! $search_filter ) : ?> style="display: none"<?php endif; ?>>
			<div class="wpml-strings-widgets-wrap wpml-strings-single-widget-wrap">
				<div class="wpml-string-widgets clear">
					<div class="postbox-container">
						<div class="postbox closed">
							<div class="hndle-wrap clear">
								<h3 class="hndle">
									<span><?php echo esc_html__( "Can't find the strings you're looking to translate? Add more strings for translation.", 'wpml-string-translation' ); ?></span>
								</h3>
								<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
									<div class="icon-wrap">
										<div class="icon-text">more details</div>
										<svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
									</div>
								</div>
							</div>
							<div class="inside wpml-st-translate-user-fields">
								<?php
									/** @var AutoRegisterSettings $auto_register_settings */
									$auto_register_settings = WPML\Container\make( AutoRegisterSettings::class );
								?>
								<p class="link-wrap">
									<a href="admin.php?page=<?php echo WPML_PLUGIN_FOLDER; ?>/menu/theme-localization.php" class="external-link"><?php esc_html_e( 'Strings in the theme and plugins', 'wpml-string-translation' ); ?></a>
								</p>
								<p class="link-wrap">
									<a
										href="admin.php?page=<?php echo WPML_ST_FOLDER; ?>/menu/string-translation.php&amp;trop=1"
										class="external-link js-wpml-translate-admin-texts js-wpml-st-tooltip-open wpml-st-tooltip-open"
										data-content="<?php echo esc_attr__( 'Translate front-end texts you can customize from the WordPress admin like footer text, copyright notices, plugin options and settings, time format, widget texts, and more.', 'wpml-string-translation' ); ?>"
										data-link-text="<?php echo esc_attr__( 'Translating Strings From Admin and Settings', 'wpml-string-translation' ); ?>"
										data-link-url="https://wpml.org/documentation/getting-started-guide/string-translation/finding-strings-that-dont-appear-on-the-string-translation-page/?utm_source=plugin&utm_medium=gui&utm_campaign=string-translation&utm_term=admin-texts-tooltip#translate-admin-and-settings-strings"
										data-link-target="blank"
									>
										<?php esc_html_e( 'Translate texts in admin screens', 'wpml-string-translation' ); ?>
									</a>
								</p>
								<p class="link-wrap">
									<a
										href="#"
										class="wpml-st-link-no-border external-link js-wpml-translate-user-fields js-wpml-st-tooltip-open wpml-st-tooltip-open"
										data-content="<?php echo esc_attr__( 'Translate User Meta Information', 'wpml-string-translation' ); ?>"
										data-link-text="<?php echo esc_attr__( 'Making User Meta Information Translatable', 'wpml-string-translation' ); ?>"
										data-link-url="https://wpml.org/documentation/getting-started-guide/string-translation/translating-user-meta-information-with-wpml/?utm_source=plugin&utm_campaign=string-translation&utm_medium=gui&utm_term=user-meta-tooltip"
										data-link-target="blank"
									>
										<?php esc_html_e( 'Translate User Meta Information', 'wpml-string-translation' ); ?>
									</a>
								</p>

								<div class="wpml-st-select-translate-user-fields-box"
									 style="display:none;"
									 title="<?php echo esc_attr__( 'Translate User Meta Information', 'wpml-string-translation' ); ?>"
									 data-saveButtonTitle="<?php echo esc_attr__( 'Apply', 'wpml-string-translation' ); ?>"
									 data-cancelButtonTitle="<?php echo esc_attr__( 'Cancel', 'wpml-string-translation' ); ?>"
									 data-saveConfirmMsg="<?php echo esc_attr__( 'Data saved', 'wpml-string-translation' ); ?>"
								>
									<div class="wpml-st-select-translate-user-fields-box-subheading">
										<p>
											<?php echo esc_html__( 'WPML allows you to translate user information like the name, nickname, biography, and more.', 'wpml-string-translation' ); ?>
										</p>
										<p>
											<?php echo esc_html__( 'Select the user roles whose information you want to make translatable and then use the String Translation page to translate it.', 'wpml-string-translation' ); ?>
										</p>
										<p>
											<?php echo sprintf(
												esc_html__( 'Learn more about %1$stranslating user meta information.%2$s', 'wpml-string-translation' ),
												'<a class="wpml-st-link-no-border" href="https://wpml.org/documentation/getting-started-guide/string-translation/translating-user-meta-information-with-wpml/?utm_source=plugin&utm_campaign=string-translation&utm_medium=gui&utm_term=user-meta-dialog" target="_blank">',
												'</a>'
											); ?>
										</p>
									</div>
									<form id="icl_st_more_options" name="icl_st_more_options" method="post" action="">
										<?php wp_nonce_field( 'icl_st_more_options_nonce', '_icl_nonce' ); ?>
										<?php
											$editable_roles = get_editable_roles();
											if ( ! isset( $string_settings['translated-users'] ) ) {
												$string_settings['translated-users'] = array();
											}
											$areAllChecked = true;
											foreach ( $editable_roles as $role => $details ) {
												$name = translate_user_role( $details['name'] );
												if ( ! in_array( $role, (array) $string_settings['translated-users'] ) ) {
													$areAllChecked = false;
												}
											}
										?>

										<div class="checkboxes-select-all-box modal-float-childs clear">
											<div class="checkbox-wrap checkbox-select-all-wrap">
												<div class="checkbox">
													<p class="select-all-wrap">
														<input type="checkbox" name="select_all" <?php checked( $areAllChecked ); ?> />
														<span class='checkbox-label'><?php echo esc_html__( 'Select all', 'wpml-string-translation' ); ?></span>
													</p>
												</div>
											</div>
										</div>

										<div class="separator separator-no-padding-top"></div>

										<div class="checkboxes-list">
											<?php foreach ( $editable_roles as $role => $details ) : ?>
												<?php
													$name    = translate_user_role( $details['name'] );
													$checked = in_array( $role, (array) $string_settings['translated-users'] ) ? ' checked="checked"' : '';
												?>
												<div class="checkbox-wrap">
													<div class="checkbox">
														<input
															type="checkbox"
															name="users[<?php echo $role; ?>]"
															value="1"
															<?php echo $checked; ?>
														/>
														<span class='checkbox-label'><?php echo $name; ?></span>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="wpml-mo-scan-st-page-pregenerate"></div>
		</div>
		<!-- Consider removal
		<?php if ( ! empty( $icl_contexts ) ) : ?>
			<p><a href="#" id="wpml-language-of-domains-link"><?php esc_html_e( 'Languages of domains', 'wpml-string-translation' ); ?></a></p>
		<?php endif; ?>
		-->
		<?php
		$string_translation_table_ui = new WPML_String_Translation_Table( icl_get_string_translations() );
		$string_translation_table_ui->render();

		if ( ! empty( $icl_contexts ) ) {
			$change_string_domain_language_dialog = make( \WPML_Change_String_Domain_Language_Dialog::class );
			$change_string_domain_language_dialog->render( $icl_contexts );
		}
		$get_show_results = filter_var( isset( $_GET['show_results'] ) ? $_GET['show_results'] : '', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$get_page         = filter_var( $_GET['page'], FILTER_SANITIZE_URL );

		$query_args = array(
			'page' => $get_page,
		);
		if ( $context_filter ) {
			$query_args['context'] = $context_filter;
		}
		if ( $status_filter ) {
			$query_args['status'] = $status_filter;
		}
		if ( $filter_translation_priority ) {
			$query_args['tp'] = $filter_translation_priority;
		}
		if ( $search_filter ) {
			$query_args['search'] = $search_filter;
		}
		if ( $exact_match ) {
			$query_args['em'] = $exact_match;
		}
		if ( $search_translation ) {
			$query_args['search_translation'] = $search_translation;
		}
		if ( $is_troubleshooting ) {
			$query_args['troubleshooting'] = $is_troubleshooting;
		}
		?>

	<div class="tablenav icl-st-tablenav js-icl-st-tablenav">
		<?php
		if ( $wp_query->found_posts > 10 ) {
			$paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
			if ( ! $paged || $get_show_results === 'all' ) {
				$paged = 1;
			}

			if ( $paged && (int) $paged > 1 ) {
				$query_args['pages'] = $paged;
			}

			if ( $get_show_results === 'all' ) {
				$url_show_paginated_results = add_query_arg( $query_args, admin_url( 'admin.php' ) );
				?>
					<div class="tablenav-pages">
						<a href="<?php echo esc_url( $url_show_paginated_results ); ?>"><?php printf( esc_html__( 'Display %d results per page', 'wpml-string-translation' ), $sitepress_settings['st']['strings_per_page'] ); ?></a>
					</div>
					<?php
			} else {
				/** @var array|null $icl_translation_filter */
				/** @var string $page_links */
				$page_links = paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'total'     => (int) $wp_query->max_num_pages,
						'current'   => (int) $paged,
						'prev_text' => '',
						'next_text' => '',
						'add_args'  => isset( $icl_translation_filter ) ? $icl_translation_filter : array(),
					)
				);

				?>

					<div class="tablenav-pages">
					<?php
					$url_page_size = add_query_arg( $query_args, admin_url( 'admin.php' ) );

					$query_args['show_results'] = 'all';
					$url_show_all_results       = add_query_arg( $query_args, admin_url( 'admin.php' ) );

					if ( $page_links ) {
						$page_links_text = sprintf(
							'<span class="displaying-num">' . esc_html__( 'Displaying %1$s&#8211;%2$s of %3$s', 'wpml-string-translation' ) . '</span><div class="page-buttons">%4$s</div>',
							number_format_i18n( ( (int) $paged - 1 ) * $wp_query->query_vars['posts_per_page'] + 1 ),
							number_format_i18n( min( (int) $paged * $wp_query->query_vars['posts_per_page'], $wp_query->found_posts ) ),
							number_format_i18n( $wp_query->found_posts ),
							$page_links
						);

						echo $page_links_text;
					}

					if ( ! $get_show_results ) {
						?>
						<div class="icl-st-per-page">
						<span><?php
						echo esc_html__( 'Strings per page:', 'wpml-string-translation' );?></span>
						<?php
						$strings_per_page = $wp_query->query_vars['posts_per_page'];

						$option_values = array( 10, 20, 50, 100 );
						$options       = array();

						foreach ( $option_values as $option_value ) {
							$option = '<option value="' . $option_value . '"';
							if ( $strings_per_page == $option_value ) {
								$option .= ' selected="selected"';
							}
							$option .= '>';
							$option .= $option_value;
							$option .= '</option>';

							$options[] = $option;
						}

						?>
							<select name="icl_st_per_page"
									onchange="location.href='<?php echo esc_url( $url_page_size ); ?>&amp;strings_per_page='+this.value">
							<?php echo implode( $options ); ?>
							</select>
							<a href="<?php echo esc_url( $url_show_all_results ); ?>"><?php echo esc_html__( 'Display all results', 'wpml-string-translation' ); ?></a>
						</div>
						<?php
					}
					?>
			</div>
				<?php
			}
		} else {?>
			<div></div>
		<?php }?>

		<?php if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_translations' ) ) :  // the rest is only for admins or translation mangagers, not for editors ?>

		<div class="icl-st-bulk-actions">
			<input type="hidden" id="_icl_nonce_dstr"
				   value="<?php echo wp_create_nonce( 'icl_st_delete_strings_nonce' ); ?>"/>
						<div class="error notice-error otgs-notice notice" id="wpml-st-package-incomplete"
							 style="display:none;color:red;"><?php echo esc_html__( 'You have selected strings belonging to a package. Please select all strings from the affected package or unselect these strings.', 'wpml-string-translation' ); ?></div>
			<button type="button" class="button button-secondary" id="icl-st-delete-selected"
				   data-confirm="<?php echo esc_attr__( "Are you sure you want to delete these strings?\nTheir translations will be deleted too.", 'wpml-string-translation' ); ?>"
				   data-error="<?php echo __( 'WPML could not delete the strings', 'wpml-string-translation' ); ?>"
					disabled="disabled"
			>
				<?php echo esc_attr__( 'Delete selected strings', 'wpml-string-translation' ); ?>
			</button>

			<?php

			$change_string_language_dialog = new WPML_Change_String_Language_Select( $wpdb, $sitepress );
			$change_string_language_dialog->show();

			if ( $translation_priorities ) {
				$change_translation_priority_select = new WPML_Translation_Priority_Select();
				$change_translation_priority_select->show();
				wp_enqueue_script( 'wpml-st-translation-priority', WPML_ST_URL . '/res/js/string-translation-priority.js', array( 'jquery-ui-dialog', 'wpml-st-scripts', 'wpml-select-2' ), WPML_ST_VERSION );
			}
			?>

			<span class="spinner icl-st-change-spinner"></span>

		</div>
			</div>

		<br style="clear:both;" />
		<div class="wpml-strings-widgets-wrap">
			<div class="wpml-string-widgets clear">

				<div class="postbox-container">
					<div class="wpml-strings-widgets-header clear">
						<div class="utilities-icon">
						</div>

						<h2><?php echo __('Utilities', 'wpml-string-translation'); ?></h2>
					</div>

					<?php if ( current_user_can( 'manage_options' ) ) : ?>

						<!-- manage_options / Auto register untranslated strings -->
						<div id="dashboard_wpml_st_autoregister" class="postbox wpml-st-auto-register-strings closed">
							<?php
							/** @var AutoRegisterSettings $auto_register_settings */
							$auto_register_settings = WPML\Container\make( AutoRegisterSettings::class );
							?>
							<div class="hndle-wrap clear">
								<h3 class="hndle">
									<span><?php echo esc_html__( 'Auto register untranslated strings', 'wpml-string-translation' ); ?></span>
									<?php
									if ( $auto_register_settings->getIsTypeDisabled() ) {
										?>
										<span class="wpml-string-widgets-notice">
											<?php echo sprintf( esc_html__( 'This feature is disabled. %1$sClick here to enable it.%2$s', 'wpml-string-translation' ), '<span>', '</span>' ); ?>
										</span>
										<?php
									}
									?>
								</h3>
								<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
									<div class="icon-wrap">
										<svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
									</div>
								</div>
							</div>
							<div class="inside">
								<p><?php echo __('WPML can detect untranslated strings and automatically register them for translation. WPML will register any untranslated strings encountered while browsing the site.', 'wpml-string-translation'); ?></p>
								<label for="autoregister-strings-type-only-viewed-by-admin" style="display: block; padding-top: 10px; padding-bottom: 10px">
									<input type="radio"
										   class="wpml-radio-native js-auto-register-enabled"
										   id="autoregister-strings-type-only-viewed-by-admin"
										   name="<?php echo AutoRegisterSettings::KEY_ENABLED; ?>"
										   value="<?php echo $auto_register_settings->getTypeOnlyViewedByAdmin(); ?>"
									<?php checked( true, $auto_register_settings->getIsTypeOnlyViewedByAdmin() ); ?>
									>
									<?php echo esc_html__( 'Untranslated strings that I encounter while logged in', 'wpml-string-translation' ); ?>
									<mark class="wpml-blue-badge"><?php echo __('recommended', 'wpml-string-translation'); ?></mark>
								</label>
								<label for="autoregister-strings-type-viewed-by-all-users" style="display: block; padding-top: 10px; padding-bottom: 10px">
									<input type="radio"
										   class="wpml-radio-native js-auto-register-enabled"
										   id="autoregister-strings-type-viewed-by-all-users"
										   name="<?php echo AutoRegisterSettings::KEY_ENABLED; ?>"
										   value="<?php echo $auto_register_settings->getTypeViewedByAllUsers(); ?>"
									<?php checked( true, $auto_register_settings->getIsTypeViewedByAllUsers() ); ?>
									>
									<?php echo esc_html__( 'Untranslated strings that all logged in, logged out users, and site visitors encounter', 'wpml-string-translation' ); ?>
								</label>
								<label for="autoregister-strings-type-disabled" style="display: block; padding-top: 10px; padding-bottom: 10px">
									<input type="radio"
										   class="wpml-radio-native js-auto-register-enabled"
										   id="autoregister-strings-type-disabled"
										   name="<?php echo AutoRegisterSettings::KEY_ENABLED; ?>"
										   value="<?php echo $auto_register_settings->getTypeDisabled(); ?>"
									<?php checked( true, $auto_register_settings->getIsTypeDisabled() ); ?>
									>
									<?php echo esc_html__( 'Disable auto register of untranslated strings', 'wpml-string-translation' ); ?>
								</label>

								<div class="wpml-st-excluded-info-wrapper clear" style="padding-top: 10px; display: flex; align-items: center">
									<p class="button-wrap">
										<input type="button"
											   id="save-autoregister-strings-type"
											   class="button-secondary wpml-button base-btn wpml-button--outlined"
											   value="<?php echo esc_attr__( 'Save settings', 'wpml-string-translation' ); ?>"
										/>
										<span class="icl_ajx_response" id="icl-ajx-response-autoregister-strings-type" style="display:inline"></span>
									</p>
									<div style="margin-left: 15px">
										<label
											htmlFor="autoregister-strings-should-register-backend-strings"
										>
											<input
												id="autoregister-strings-should-register-backend-strings"
												class="wpml-checkbox-native"
												type="checkbox"
											<?php checked( true, $auto_register_settings->getShouldRegisterBackendStrings() ); ?>
											<?php echo $auto_register_settings->getIsTypeDisabled() ? 'disabled="disabled"' : ""; ?>
											/>
											<span <?php echo $auto_register_settings->getIsTypeDisabled() ? 'class="wpml-disabled-text"' : ""; ?>>
												<?php echo __('Also register strings from the website\'s back-end', 'sitepress'); ?>
											</span>
										</label>
									</div>
								</div>
							</div>
						</div>
						<!-- EO Auto register untranslated strings -->

						<!-- manage_options / Translate strings automatically -->
						<div id="dashboard_wpml_open_tm_page" class="postbox closed">
							<div class="hndle-wrap clear">
								<h3 class="hndle">
									<span><?php echo esc_html__( 'Translate strings automatically, with your translators or a translation service', 'wpml-string-translation' ); ?></span>
								</h3>
								<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
									<div class="icon-wrap">
										<svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
									</div>
								</div>
							</div>
							<div class="inside">
								<p><?php echo sprintf( esc_html__( "Use WPML's %1\$sTranslation Dashboard%2\$s to send strings to translation.", 'wpml-string-translation' ), '<a href="' . UIPage::getTM() . '">', '</a>' ); ?></p>
							</div>
						</div>
						<!-- EO Translate strings automatically -->

					<?php endif; ?>

					<!-- Import / export .po -->
					<div id="dashboard_wpml_st_poie" class="postbox closed">
						<div class="hndle-wrap clear">
							<h3 class="hndle">
								<span><?php echo esc_html__( 'Import / export .po', 'wpml-string-translation' ); ?></span>
							</h3>
							<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
								<div class="icon-wrap">
									<svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
								</div>
							</div>
						</div>
						<div class="inside float-childs clear column-borders">
							<div class="column form-in-column">
								<h5><?php echo esc_html__( 'Import', 'wpml-string-translation' ); ?></h5>
								<?php
								if ( $wpml_po_import_strings->get_errors() ) {
									?>
									<div id="wpml-st-import-error-notice-js" class="otgs-notice warning st-po-import-error"><p><?php echo $wpml_po_import_strings->get_errors(); ?></p></div>
									<?php
								}
								?>
								<form id="icl_st_po_form" action="" name="icl_st_po_form" method="post" enctype="multipart/form-data">
									<?php wp_nonce_field( 'icl_po_form' ); ?>
									<div class="sub clear field-bottom-spacer">
										<div class="file-upload-field-label">
											<label for="icl_po_file"><?php echo esc_html__( '.po file :', 'wpml-string-translation' ); ?></label>
										</div>
										<div class="file-upload-field">
											<div class="upload-icon"></div>
											<input id="icl_po_file" class="button primary" type="file" name="icl_po_file" />
										</div>
									</div>
									<div class="sub field-bottom-spacer">
										<p><label for="st-i-source-lang"><?php esc_html_e( 'Select the original language of strings to import', 'wpml-string-translation' ); ?></label></p>
										<select class="st-i-source-lang-select" name="icl_st_po_source_language" id="st-i-source-lang">
											<option value="en"><?php esc_html_e( 'English', 'wpml-string-translation' ); ?></option>
											<?php
											foreach ( $active_languages as $lang ) {
												if ( $lang['code'] === 'en' ) {
													// We need English to be on top and always there even if not active.
													continue;
												}
												?>
												<option value="<?php echo esc_attr( $lang['code'] ); ?>"><?php echo esc_html( $lang['display_name'] ); ?></option>
												<?php
											}
											?>
										</select>
									</div>
									<div class="sub field-bottom-spacer">
										<div class="clear">
											<div class="checkbox-and-select-checkbox checkbox-and-small-select-checkbox">
												<input type="checkbox" class="wpml-checkbox-native" name="icl_st_po_translations" id="icl_st_po_translations" />
												<label for="icl_st_po_translations"><?php echo esc_html__( 'Also create translations according to the .po file', 'wpml-string-translation' ); ?></label>
											</div>
											<div class="checkbox-and-select-select checkbox-and-small-select-select">
												<select name="icl_st_po_language" id="icl_st_po_language" style="display:none">
												<?php
												foreach ( $active_languages as $al ) :
													?>
													<option value="<?php echo esc_attr( $al['code'] ); ?>"><?php echo esc_html( $al['display_name'] ); ?></option>
												<?php endforeach; ?>
												</select>
											</div>
										</div>
									</div>
									<div class="sub field-bottom-spacer">
										<?php echo esc_html__( 'Select what the strings are for:', 'wpml-string-translation' ); ?>
										<?php if ( ! empty( $available_contexts ) ) : ?>
										<br/>
										<div class="clear">
											<div class="select-and-button-select">
												<input type="text" name="icl_st_i_context_new" id="icl_st_i_context_new" style="display: none" />
												<select name="icl_st_i_context" id="icl_st_i_context">
													<option value="">-------</option>
													<?php foreach ( $available_contexts as $v ) : ?>
													<option value="<?php echo esc_attr( (string) $v ); ?>"
																			<?php
																				if ( $context_filter == $v ) :
																					?>
		selected="selected"<?php endif; ?>><?php echo $v; ?></option>
													<?php endforeach; ?>
												</select>
											</div>
											<div class="select-and-button-button">
												<button class="button-secondary wpml-button base-btn wpml-button--outlined" id="icl_st_importpo_newbutton"><?php echo esc_html__( 'New', 'wpml-string-translation' ); ?></button>
												<button class="button-secondary wpml-button base-btn wpml-button--outlined" style="display: none" id="icl_st_importpo_existingbutton"><?php echo esc_html__( 'Select from existing', 'wpml-string-translation' ); ?></button>
											</div>
										</div>
										<?php endif; ?>
									</div>

									<p class="button-wrap">
										<input class="button-secondary wpml-button base-btn wpml-button--outlined" name="icl_po_upload" id="icl_po_upload" type="submit" value="<?php echo esc_attr__( 'Submit', 'wpml-string-translation' ); ?>"/>
										<span id="icl_st_err_domain" class="icl_error_text" style="display:none"><?php echo esc_html__( 'Please enter a domain!', 'wpml-string-translation' ); ?></span>
										<span id="icl_st_err_po" class="icl_error_text" style="display:none"><?php echo esc_html__( 'Please select the .po file to upload!', 'wpml-string-translation' ); ?></span>
									</p>

								</form>
							</div>
							<div class="spacer-column"></div>
							<?php if ( ! empty( $icl_contexts ) ) : ?>
							<div class="column form-in-column">
								<h5><?php echo esc_html__( 'Export strings into .po/.pot file', 'wpml-string-translation' ); ?></h5>
									<?php
									if ( version_compare( WPML_ST_VERSION, '2.2', '<=' ) ) {
										?>
									<div class="below-h2 error">
										<?php echo esc_html__( 'PO export may be glitchy. We are working to fix it.', 'wpml-string-translation' ); ?>
									</div>
										<?php
									}
									?>
								<form method="post" action="">
									<?php wp_nonce_field( 'icl_po_export' ); ?>
									<div class="field-bottom-spacer">
										<p><?php echo esc_html__( 'Select domain:', 'wpml-string-translation' ); ?></p>
										<select name="icl_st_e_context" id="icl_st_e_context">
											<?php foreach ( $icl_contexts as $v ) : ?>
											<option value="<?php echo esc_attr( $v->context ); ?>"
																	<?php
																		if ( $context_filter == $v->context ) :
																			?>
	selected="selected"<?php endif; ?>><?php echo $v->context . ' (' . $v->c . ')'; ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div>
										<div class="clear field-bottom-spacer">
											<div class="checkbox-and-select-checkbox">
												<input type="checkbox" class="wpml-checkbox-native" name="icl_st_pe_translations" id="icl_st_pe_translations" checked="checked" value="1" onchange="if(jQuery(this).prop('checked'))jQuery('#icl_st_e_language').fadeIn('fast'); else jQuery('#icl_st_e_language').fadeOut('fast')" />
												<label for="icl_st_pe_translations"><?php echo esc_html__( 'Also include translations', 'wpml-string-translation' ); ?></label>
											</div>
											<div class="checkbox-and-select-select">
												<select name="icl_st_e_language" id="icl_st_e_language">
												<?php
												$poExportTranslationLangs = $active_languages;
												if ( isset( $poExportTranslationLangs['en'] ) ) {
													// Push English at last.
													$temp = $poExportTranslationLangs['en'];
													unset( $poExportTranslationLangs['en'] );
													$poExportTranslationLangs['en'] = $temp;
													unset( $temp );
												}
												foreach ( $poExportTranslationLangs as $al ) :
												?>
													<option value="<?php echo esc_attr( $al['code'] ); ?>"><?php echo esc_html( $al['display_name'] ); ?></option>
												<?php endforeach; ?>
												</select>
											</div>
										</div>
									</div>
									<p class="button-wrap"><input type="submit" class="button-secondary wpml-button base-btn wpml-button--outlined" name="icl_st_pie_e" value="<?php echo esc_attr__( 'Submit', 'wpml-string-translation' ); ?>"/></p>
								</form>
							</div>
							<?php endif ?>
						</div>
					</div>
					<!-- EO Import / export .po -->


					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<!-- manage_options / Cleanup strings  -->
						<div id="dashboard_wpml_cleanup_strings" class="postbox closed">
							<div class="hndle-wrap clear">
								<h3 class="hndle">
									<span><?php echo esc_html__( 'Remove strings by domain', 'wpml-string-translation' ); ?></span>
								</h3>
								<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
									<div class="icon-wrap">
										<svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
									</div>
								</div>
							</div>
							<div id="dashboard_wpml_cleanup_strings_content" class="inside">
							</div>
						</div>
						<!-- EO Cleanup strings -->


						<!-- manage_options / Set the original language of themes and plugins -->
						<div id="dashboard_wpml_set_orig_lang" class="postbox closed">
							<div class="hndle-wrap clear">
								<h3 class="hndle">
									<span><?php echo esc_html__( 'Set the original language of themes and plugins', 'wpml-string-translation' ); ?></span>
								</h3>
								<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
									<div class="icon-wrap">
										<svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
									</div>
								</div>
							</div>
							<div class="inside">
								<p><?php echo __( "By default WPML assumes that strings in themes and plugins are in English. If you're using a theme or plugin that has strings in other languages you can set the language of text-domains.", 'wpml-string-translation' ); ?></p>

								<div class="wpml-st-excluded-info-wrapper clear">
									<p class="button-wrap">
										<input type="button"
											   class="button-secondary wpml-button base-btn wpml-button--outlined"
											   id="wpml-language-of-domains-link"
											   value="<?php echo __( "Set the language of text-domains", "sitepress" ); ?>"
										/>
									</p>
								</div>
							</div>
						</div>
						<!-- EO Set the original language of themes and plugins -->
					<?php endif; ?>

					<!-- Not seeing strings that you are looking for? -->
					<div id="dashboard_wpml_open_admin_st" class="postbox closed">
						<div class="hndle-wrap clear">
							<h3 class="hndle">
								<span><?php echo esc_html__( 'Not seeing strings that you are looking for?', 'wpml-string-translation' ); ?></span>
							</h3>
							<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
								<div class="icon-wrap">
									<svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
								</div>
							</div>
						</div>
						<div class="inside">
							<p><?php echo sprintf( esc_html__( "You can add to the String Translations table texts that appear in the admin screens of the theme and plugins. To do this, go to %1\$sAdmin Texts Translation%2\$s", 'wpml-string-translation' ), '<a href="admin.php?page='.  WPML_ST_FOLDER . '/menu/string-translation.php&amp;trop=1">', '</a>' ); ?></p>
						</div>
					</div>
					<!-- EO Not seeing strings that you are looking for? -->

					<!-- Translate User properties -->
					<!-- @todo is this and previous supposed to be outside the current_user_can( 'manage_options' ) condition? it was inside before-->
					<div id="dashboard_wpml_user_properties" class="postbox closed">
						<div class="hndle-wrap clear">
							<h3 class="hndle">
								<span><?php echo esc_html__( 'Translate User properties', 'wpml-string-translation' ); ?></span>
							</h3>
							<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
								<div class="icon-wrap">
									<svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
								</div>
							</div>
						</div>
						<div class="inside">
							<form id="icl_st_more_options_utilities" name="icl_st_more_options" method="post" action="">
								<?php wp_nonce_field( 'icl_st_more_options_nonce', '_icl_nonce' ); ?>
								<?php
								$editable_roles = get_editable_roles();
								if ( ! isset( $string_settings['translated-users'] ) ) {
									$string_settings['translated-users'] = array();
								}

								$tnames = array();
								foreach ( $editable_roles as $role => $details ) {
									if ( in_array( $role, $string_settings['translated-users'] ) ) {
										$tnames[] = translate_user_role( $details['name'] );
									}
								}

								$tustr = '<span id="icl_st_tusers_list">';
								if ( ! empty( $tnames ) ) {
									$tustr .= join( ', ', array_map( 'translate_user_role', $tnames ) );
								} else {
									$tustr = esc_html__( 'none', 'wpml-string-translation' );
								}
								$tustr .= '</span>';

								?>
								<p><?php echo sprintf( esc_html__( "Choose the user roles you would like to make translatable: %s", 'wpml-string-translation' ), $tustr ) ?></p>

								<div id="roles_list" class="checkboxes-list" style="display: none">
									<?php foreach ( $editable_roles as $role => $details ) : ?>
										<?php
										$name    = translate_user_role( $details['name'] );
										$checked = in_array( $role, (array) $string_settings['translated-users'] ) ? ' checked="checked"' : '';
										?>
										<div class="checkbox-wrap">
											<div class="checkbox">
												<input
													type="checkbox" class="wpml-checkbox-native"
													name="users[<?php echo $role; ?>]"
													value="1"
													<?php echo $checked; ?>
												/>
												<span class='checkbox-label'><?php echo $name; ?></span>
											</div>
										</div>
									<?php endforeach; ?>
								</div>

								<div id="action-buttons" class="clear">
									<p class="button-wrap">
										<input type="submit"
											   class="button-secondary wpml-button base-btn wpml-button--outlined"
											   id="wpml-user-properties"
											   data-editUserRoleText="<?php echo esc_attr__( 'Edit user roles', 'wpml-string-translation' ); ?>"
											   data-applyText="<?php echo esc_attr__( 'Apply', 'wpml-string-translation' ); ?>"
											   value="<?php echo __( "Edit user roles", 'wpml-string-translation' ); ?>"
										/>
									</p>
									<p class="link-wrap">
										<a href="https://wpml.org/documentation/getting-started-guide/string-translation/translating-user-meta-information-with-wpml/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlst" target="_blank" class="external-link"><?php esc_html_e( 'Translating User Meta Information With WPML', 'wpml-string-translation' ); ?></a>
									</p>
								</div>
							</form>
						</div>
					</div>
					<!-- EO Translate User properties -->

					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<!-- manage_options / String tracking -->
						<div id="dashboard_wpml_stsel_1" class="postbox closed postbox-last">
							<div class="hndle-wrap clear">
								<h3 class="hndle">
									<span><?php echo esc_html__( 'Track where strings appear on the site', 'wpml-string-translation' ); ?></span>
								</h3>
								<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
									<div class="icon-wrap">
										<svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
									</div>
								</div>
							</div>
							<div class="inside">
								<p class="sub">
									<?php
									echo esc_html__(
										"This feature helps you find where the text (strings) appears on your site, so you can translate it more easily. 
											It may slow down your site while it's running, so it's best to use it only during development. 
											Remember to turn it off when your site goes live to keep things running smoothly.",
										'wpml-string-translation'
									);
									?>
								</p>
								<p class="link-wrap">
									<a href="https://wpml.org/documentation/getting-started-guide/string-translation/finding-strings-that-dont-appear-on-the-string-translation-page/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlst" target="_blank" class="external-link"><?php esc_html_e( 'Learn more about finding strings', 'wpml-string-translation' ); ?></a>
								</p>
								<form id="icl_st_track_strings" name="icl_st_track_strings" class="clear" action="">
									<?php wp_nonce_field( 'icl_st_track_strings_nonce', '_icl_nonce' ); ?>
									<p class="icl_form_errors" style="display:none"></p>
									<ul>
										<li class="list-vertical-spacer">
											<input type="hidden" name="icl_st[track_strings]" value="0" />
											<?php
											$track_strings         = array_key_exists( 'track_strings', $string_settings ) && $string_settings['track_strings'];
											$track_strings_checked = checked( true, $track_strings, false );
											?>
											<input type="checkbox" class="wpml-checkbox-native" id="track_strings" name="icl_st[track_strings]" value="1" <?php echo $track_strings_checked; ?> />
											<label for="track_strings"><?php esc_html_e( 'Track where strings appear on the site', 'wpml-string-translation' ); ?></label>
										</li>
										<li class="clear wpml-picker-container">
											<?php

											$hl_color_default                                  = '#FFFF00';
											$hl_color = ! empty( $string_settings['hl_color'] ) ? $string_settings['hl_color'] : $hl_color_default;
											$hl_color_label                                    = __( 'Highlight color for strings', 'wpml-string-translation' );
											$color_picker_args                                 = array(
												'input_name_group' => 'icl_st',
												'input_name_id' => 'hl_color',
												'default' => $hl_color_default,
												'value'   => $hl_color,
												'label'   => $hl_color_label,
												'labelNoNewline' => true,
											);

											$wpml_color_picker = new WPML_Color_Picker( $color_picker_args );

											echo $wpml_color_picker->get_current_language_color_selector_control();

											?>
										</li>
									</ul>
									<p class="button-wrap">
										<input class="button-secondary wpml-button base-btn wpml-button-outlined" type="submit" name="iclt_st_save" value="<?php esc_attr_e( 'Apply', 'wpml-string-translation' ); ?>"/>
										<span class="icl_ajx_response" id="icl_ajx_response2" style="display:inline"></span>
									</p>
								</form>

							</div>
						</div>
						<!-- EO String tracking -->
					<?php endif; ?>
				</div>
			</div>
		</div>

	<!-- String Tracking warning dialog. -->
	<div id="wpml-track-strings-info-dialog"
		 class="hidden"
		 title="<?php esc_attr_e( 'String Tracking Enabled', 'wpml-string-translation' ); ?>"
		 data-ok-btn-label="<?php esc_attr_e( 'OK', 'wpml-string-translation' ); ?>"
		 data-close-btn-label="<?php esc_attr_e( 'Close', 'wpml-string-translation' ); ?>">
		<p>
			<?php
			echo esc_html__(
				'WPML will now track where your site\'s text (strings) appears as you browse both the admin and front-end.',
				'wpml-string-translation'
			);
			?>
			<br />
			<?php
			echo esc_html__(
				'Be sure to turn off this feature before your site goes live to avoid performance issues.',
				'wpml-string-translation'
			);
			?>
		</p>
	</div>

	<?php endif; // if(current_user_can('manage_options') ?>
	<?php endif; ?>
	<?php do_action( 'icl_menu_footer' ); ?>
</div>
