<?php

use WPML\ST\Gettext\AutoRegisterSettings;

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
						<td><input class="icl_st_row_cb" type="checkbox" name="icl_strings_selected[]"
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

			<p><span style="float: left"><input class="js-wpml-btn-cancel button" type="button" value="<?php echo esc_attr__( 'Cancel', 'wpml-string-translation' ); ?>"
												onclick="location.href='admin.php?page=<?php echo htmlspecialchars( $_GET['page'], ENT_QUOTES ); ?>'"/>
		&nbsp;<input class="js-wpml-btn-add-strings button-primary" type="submit" value="<?php echo esc_attr__( 'Add selected strings', 'wpml-string-translation' ); ?>"/></span><span class="spinner" style="float: left"></span>
		</p>
		</form>

	<?php else : ?>

		<p class="wpml-string-translation-filter">
			<?php echo esc_html__( 'Display:', 'wpml-string-translation' ); ?>
		<select name="icl_st_filter_status">
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
	?>

		</select>

			<?php if ( ! empty( $icl_contexts ) ) : ?>
				&nbsp;&nbsp;
				<span style="white-space:nowrap">
				<?php echo esc_html__( 'In domain:', 'wpml-string-translation' ); ?>
					<select name="icl_st_filter_context">
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
				<?php echo esc_html__( 'With Priority:', 'wpml-string-translation' ); ?>
				<select name="icl-st-filter-translation-priority">
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
		&nbsp;&nbsp;
		<span style="white-space:nowrap">
		<label>
		<?php echo esc_html__( 'Search for:', 'wpml-string-translation' ); ?>
		<input type="text" id="icl_st_filter_search" value="<?php echo $search_filter; ?>" />
		</label>

		<label>
		<input type="checkbox" id="icl_st_filter_search_em" value="1"
		<?php
		if ( $exact_match ) :
			?>
 checked="checked"<?php endif; ?> />
			<?php echo esc_html__( 'Exact match', 'wpml-string-translation' ); ?>
		</label>

		<label>
		<input
			type="checkbox"
			id="search_translation"
			value="1"
			<?php
			if ( $search_translation ) :
				?>
				checked="checked"<?php endif; ?>
			class="js-otgs-popover-tooltip"
			title="<?php echo esc_attr__( 'Search in both the original language and in translations. Searching in translations may take a bit of time.' ); ?>"
		/>
			<?php echo esc_html__( 'Include translations', 'wpml-string-translation' ); ?>
		</label>

		<input class="button" type="button" value="<?php esc_attr_e( 'Search', 'wpml-string-translation' ); ?>" id="icl_st_filter_search_sb"/>
		</span>

		<?php if ( $search_filter ) : ?>
		<span style="white-space:nowrap">
			<?php printf( esc_html__( 'Showing only strings that contain %s', 'wpml-string-translation' ), '<i>' . esc_html( $search_filter ) . '</i>' ); ?>
			<input class="button" type="button" value="<?php esc_attr_e( 'Exit search', 'wpml-string-translation' ); ?>" id="icl_st_filter_search_remove"/>
		</span>
		<?php endif; ?>

		</p>
		<div id="wpml-mo-scan-st-page"></div>
		<?php if ( ! empty( $icl_contexts ) ) : ?>
			<p><a href="#" id="wpml-language-of-domains-link"><?php esc_html_e( 'Languages of domains', 'wpml-string-translation' ); ?></a></p>
		<?php endif; ?>
		<?php
		$string_translation_table_ui = new WPML_String_Translation_Table( icl_get_string_translations() );
		$string_translation_table_ui->render();

		if ( ! empty( $icl_contexts ) ) {
			$string_factory                       = new WPML_ST_String_Factory( $wpdb );
			$change_string_domain_language_dialog = new WPML_Change_String_Domain_Language_Dialog( $wpdb, $sitepress, $string_factory );
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

	<div class="tablenav icl-st-tablenav">
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
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
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
							'<span class="displaying-num">' . esc_html__( 'Displaying %1$s&#8211;%2$s of %3$s', 'wpml-string-translation' ) . '</span>%4$s',
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
						<?php
						echo esc_html__( 'Strings per page:', 'wpml-string-translation' );

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
							</select>&nbsp;
							<a href="<?php echo esc_url( $url_show_all_results ); ?>"><?php echo esc_html__( 'Display all results', 'wpml-string-translation' ); ?></a>
						</div>
						<?php
					}
					?>
			</div>
				<?php
			}
		}
		?>

		<?php if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_translations' ) ) :  // the rest is only for admins or translation mangagers, not for editors ?>

		<div class="icl-st-bulk-actions">
			<input type="hidden" id="_icl_nonce_dstr"
				   value="<?php echo wp_create_nonce( 'icl_st_delete_strings_nonce' ); ?>"/>
						<div id="wpml-st-package-incomplete"
							 style="display:none;color:red;"><?php echo esc_html__( 'You have selected strings belonging to a package. Please select all strings from the affected package or unselect these strings.', 'wpml-string-translation' ); ?></div>
						<div id="wpml-st-non-default-language-string" data-show="true" class="ant-alert ant-alert-warning ant-alert-warning-override"
							 role="alert" style="display:none;">
							<i class="ant-alert-icon otgs-ico otgs-ico-warning-o"></i>
							<div class="ant-alert-content">
								<div class="ant-alert-message"><?php echo esc_html__( 'Selected strings are not in the site\'s default language and will not be translated automatically if you\'re using the "Translate Everything Automatically" mode. Instead, after sending them for translation here, you need to go to the WPML -> Translations page and translate them manually.', 'wpml-string-translation' ); ?></div>
								<div class="ant-alert-description"></div>
							</div>
						</div>
			<input type="button" class="button button-secondary" id="icl_st_delete_selected"
				   value="<?php echo esc_attr__( 'Delete selected strings', 'wpml-string-translation' ); ?>"
				   data-confirm="<?php echo esc_attr__( "Are you sure you want to delete these strings?\nTheir translations will be deleted too.", 'wpml-string-translation' ); ?>"
				   data-error="<?php echo __( 'WPML could not delete the strings', 'wpml-string-translation' ); ?>"
				   disabled="disabled"/>

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

		<br clear="all" />

			<?php do_action( 'wpml_st_below_menu', $status_filter_lang, 10, 2 ); ?>

		<br style="clear:both;" />
		<div id="dashboard-widgets-wrap" class="wpml-strings-widgets">
			<div id="dashboard-widgets" class="metabox-holder">

				<?php if ( current_user_can( 'manage_options' ) ) : ?>

				<div class="postbox-container" style="width: 49%;">
					<div id="normal-sortables-stsel" class="meta-box-sortables ui-sortable">

						<div id="dashboard_wpml_stsel_1" class="postbox">
							<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
                                <svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
							</div>
							<h3 class="hndle">
								<span><?php echo esc_html__( 'Track where strings appear on the site', 'wpml-string-translation' ); ?></span>
							</h3>
							<div class="inside">
								<p class="sub"><?php echo esc_html__( "WPML can keep track of where strings are used on the public pages. Activating this feature will enable the 'view in page' functionality and make translation easier.", 'wpml-string-translation' ); ?></p>
								<form id="icl_st_track_strings" name="icl_st_track_strings" action="">
									<?php wp_nonce_field( 'icl_st_track_strings_nonce', '_icl_nonce' ); ?>
									<p class="icl_form_errors" style="display:none"></p>
									<ul>
										<li>
											   <input type="hidden" name="icl_st[track_strings]" value="0" />
											<?php
											$track_strings         = array_key_exists( 'track_strings', $string_settings ) && $string_settings['track_strings'];
											$track_strings_checked = checked( true, $track_strings, false );
											$track_strings_display = ' style="color: red;' . ( ! $track_strings ? 'display: none;' : '' ) . '""';

											$url               = 'https://wpml.org/documentation/getting-started-guide/string-translation/finding-strings-that-dont-appear-on-the-string-translation-page/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlst';
											$message_sentences = array();

											$anchor_text         = esc_attr_x( 'String Tracking', 'String Tracking warning: sentence 1, anchor text', 'wpml-string-translation' );
											$message_sentences[] = esc_html_x( '%s allows you to see where strings come from, so you can translate them accurately.', 'String Tracking warning: sentence 1', 'wpml-string-translation' );
											$message_sentences[] = esc_html_x( 'It needs to parse the PHP source files and the output HTML.', 'String Tracking warning: sentence 2', 'wpml-string-translation' );
											$message_sentences[] = esc_html_x( 'This feature is CPU-intensive and should only be used while you are developing sites.', 'String Tracking warning: sentence 3', 'wpml-string-translation' );
											$message_sentences[] = esc_html_x( 'Remember to turn it off before going to production, to avoid performance problems.', 'String Tracking warning: sentence 4', 'wpml-string-translation' );

											$anchor  = '<a href="' . $url . '" target="_blank">' . $anchor_text . '</a>';
											$message = sprintf( implode( ' ', $message_sentences ), $anchor );
											?>
											<input type="checkbox" id="track_strings" name="icl_st[track_strings]" value="1" <?php echo $track_strings_checked; ?> />
											<label for="track_strings"><?php esc_html_e( 'Track where strings appear on the site', 'wpml-string-translation' ); ?></label>
											<p class="js-track-strings-note" <?php echo $track_strings_display; ?>>
												<?php echo $message; ?>
											</p>
											<p><a href="https://wpml.org/faq/prevent-performance-issues-with-wpml/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmlst" target="_blank"><?php esc_html_e( 'Performance considerations', 'wpml-string-translation' ); ?>&nbsp;&raquo;</a></p>
										</li>
										<li>
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
											);

											$wpml_color_picker = new WPML_Color_Picker( $color_picker_args );

											echo $wpml_color_picker->get_current_language_color_selector_control();

											?>
										</li>
									</ul>
									<p>
										<input class="button-secondary" type="submit" name="iclt_st_save" value="<?php esc_attr_e( 'Apply', 'wpml-string-translation' ); ?>"/>
									<span class="icl_ajx_response" id="icl_ajx_response2" style="display:inline"></span>
									</p>
								</form>

							</div>
						</div>
						<div id="dashboard_wpml_cleanup_strings" class="postbox"></div>

						<div id="dashboard_wpml_stsel_1.5" class="postbox wpml-st-auto-register-strings">
							<?php
							/** @var AutoRegisterSettings $auto_register_settings */
							$auto_register_settings = WPML\Container\make( AutoRegisterSettings::class );
							?>
							<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
                                <svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
							</div>
							<h3 class="hndle">
								<span><?php echo esc_html__( 'Auto register strings for translation', 'wpml-string-translation' ); ?></span>
							</h3>
							<div class="inside">
								<p>
									<label for="auto_register_enabled">
										<input type="checkbox"
											   class="js-auto-register-enabled"
											   id="<?php echo AutoRegisterSettings::KEY_ENABLED; ?>"
											   name="<?php echo AutoRegisterSettings::KEY_ENABLED; ?>"
											   <?php checked( true, $auto_register_settings->isEnabled() ); ?>"
										>
										<?php echo esc_html__( 'Look for strings while pages are rendered', 'wpml-string-translation' ); ?>
									</label>
								</p>

								<p class="js-auto-register-description sub"
								   data-enabled-string="<?php echo esc_attr( $auto_register_settings->getFeatureEnabledDescription() ); ?>"
								   data-disabled-string="<?php echo esc_attr( $auto_register_settings->getFeatureDisabledDescription() ); ?>"
								   data-running-countdown="<?php echo $auto_register_settings->getTimeToAutoDisable(); ?>"
								   data-reset-countdown="<?php echo AutoRegisterSettings::RESET_AUTOLOAD_TIMEOUT; ?>"
								></p>

								<div class="wpml-st-excluded-info-wrapper"
									<?php echo ! $auto_register_settings->isEnabled() ? 'style="display:none"' : ''; ?>
								>
									<p class="wpml-st-excluded-info"
									   data-all-included="<?php echo esc_attr__( 'Strings from all text domains will be auto-registered', 'wpml-string-translation' ); ?>"
									   data-all-excluded="<?php echo esc_attr__( 'Strings from all text domains are excluded', 'wpml-string-translation' ); ?>"
									   data-excluded-preview="<?php echo esc_attr__( 'You excluded: ', 'wpml-string-translation' ); ?>"
									   data-included-preview="<?php echo esc_attr__( 'You included: ', 'wpml-string-translation' ); ?>"
									   data-preview-suffix="<?php echo esc_attr__( 'and others', 'wpml-string-translation' ); ?>"
									>

									</p>
									<p>
										<input type="button"
											   class="button-secondary js-wpml-autoregister-edit-contexts"
											   value="<?php echo esc_attr__( 'Edit', 'wpml-string-translation' ); ?>"
										/>
									</p>

									<div class="wpml-st-exclude-contexts-box"
										 style="display:none;"
										 title="<?php echo esc_attr__( 'Auto-register strings from these text domains', 'wpml-string-translation' ); ?>"
									>
										<form method="post" action="" data-nonce="<?php echo wp_create_nonce( 'wpml-st-cancel-button' ); ?>" >
											<?php
											$excluded     = $auto_register_settings->getExcludedDomains();
											$has_excluded = count( $excluded ) > 0;
											?>

											<div id="wpml-st-filter-and-select-all-box">
												<input type="input" name="search" placeholder="<?php echo esc_attr__( 'Search', 'wpml-string-translation' ); ?>" />

												<br/>

												<p>
													<input type="checkbox" name="select_all" <?php checked( false, $has_excluded ); ?> />
													<span><?php echo esc_html__( 'Select all', 'wpml-string-translation' ); ?></span>
												</p>
											</div>

											<div class="contexts">
												<?php foreach ( $auto_register_settings->getDomainsAndTheirExcludeStatus() as $context => $status ) : ?>
													<?php if ( strlen( $context ) ) : ?>
													<p>
														<input
															type="checkbox"
															name="<?php echo AutoRegisterSettings::KEY_EXCLUDED_DOMAINS; ?>[]"
															value="<?php echo $context; ?>"
															<?php checked( false, $status ); ?>
														/>
														<span><?php echo $context; ?></span>
													</p>
													<?php endif; ?>
												<?php endforeach; ?>
											</div>
										</form>
									</div>
								</div>
							</div><!-- .wpml-st-excluded-info-wrapper -->
						</div>


					</div>
				</div>

				<?php endif; ?>

				<div class="postbox-container" style="width: 49%;">
					<div id="normal-sortables-poie" class="meta-box-sortables ui-sortable">
						<div id="dashboard_wpml_st_poie" class="postbox">
							<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
                                <svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
							</div>
							<h3 class="hndle">
								<span><?php echo esc_html__( 'Import / export .po', 'wpml-string-translation' ); ?></span>
							</h3>
							<div class="inside">
								<h5><?php echo esc_html__( 'Import', 'wpml-string-translation' ); ?></h5>
								<form id="icl_st_po_form" action="" name="icl_st_po_form" method="post" enctype="multipart/form-data">
									<?php wp_nonce_field( 'icl_po_form' ); ?>
									<p class="sub">
										<label for="icl_po_file"><?php echo esc_html__( '.po file:', 'wpml-string-translation' ); ?></label>
										<input id="icl_po_file" class="button primary" type="file" name="icl_po_file" />
									</p>
									<p class="sub" style="line-height:2.3em">
										<input type="checkbox" name="icl_st_po_translations" id="icl_st_po_translations" />
										<label for="icl_st_po_translations"><?php echo esc_html__( 'Also create translations according to the .po file', 'wpml-string-translation' ); ?></label>
										<select name="icl_st_po_language" id="icl_st_po_language" style="display:none">
										<?php
										foreach ( $active_languages as $al ) :
											if ( $al['code'] == $string_settings['strings_language'] ) {
												continue;}
											?>
										<option value="<?php echo esc_attr( $al['code'] ); ?>"><?php echo esc_html( $al['display_name'] ); ?></option>
										<?php endforeach; ?>
										</select>
									</p>
									<p class="sub" style="line-height:2.3em"    >
										<?php echo esc_html__( 'Select what the strings are for:', 'wpml-string-translation' ); ?>
										<?php if ( ! empty( $available_contexts ) ) : ?>

										&nbsp;&nbsp;
										<span>
										<select name="icl_st_i_context">
											<option value="">-------</option>
											<?php foreach ( $available_contexts as $v ) : ?>
											<option value="<?php echo esc_attr( (string) $v ); ?>"
																	  <?php
																		if ( $context_filter == $v ) :
																			?>
 selected="selected"<?php endif; ?>><?php echo $v; ?></option>
											<?php endforeach; ?>
										</select>
										<a href="#"
										   onclick="var __nxt = jQuery(this).parent().next(); jQuery(this).prev().val(''); jQuery(this).parent().fadeOut('fast',function(){__nxt.fadeIn('fast')});return false;"><?php echo esc_html__( 'new', 'wpml-string-translation' ); ?></a>
										</span>
										<?php endif; ?>
										<span
										<?php
										if ( ! empty( $available_contexts ) ) :
											?>
 style="display:none"<?php endif ?>>
										<input type="text" name="icl_st_i_context_new" />
										<?php if ( ! empty( $available_contexts ) ) : ?>
											<a href="#"
											   onclick="var __prv = jQuery(this).parent().prev(); jQuery(this).prev().val(''); jQuery(this).parent().fadeOut('fast',function(){__prv.fadeIn('fast')});return false;"><?php echo esc_html__( 'select from existing', 'wpml-string-translation' ); ?></a>
										<?php endif ?>
										</span>
									</p>

									<p>
										<input class="button" name="icl_po_upload" id="icl_po_upload" type="submit" value="<?php echo esc_attr__( 'Submit', 'wpml-string-translation' ); ?>"/>
										<span id="icl_st_err_domain" class="icl_error_text" style="display:none"><?php echo esc_html__( 'Please enter a domain!', 'wpml-string-translation' ); ?></span>
										<span id="icl_st_err_po" class="icl_error_text" style="display:none"><?php echo esc_html__( 'Please select the .po file to upload!', 'wpml-string-translation' ); ?></span>
									</p>

								</form>
								<?php if ( ! empty( $icl_contexts ) ) : ?>
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
								<p>
									<?php echo esc_html__( 'Select domain:', 'wpml-string-translation' ); ?>
									<select name="icl_st_e_context" id="icl_st_e_context">
										<?php foreach ( $icl_contexts as $v ) : ?>
										<option value="<?php echo esc_attr( $v->context ); ?>"
																  <?php
																	if ( $context_filter == $v->context ) :
																		?>
 selected="selected"<?php endif; ?>><?php echo $v->context . ' (' . $v->c . ')'; ?></option>
										<?php endforeach; ?>
									</select>
							   </p>
							   <p style="line-height:2.3em">
									<input type="checkbox" name="icl_st_pe_translations" id="icl_st_pe_translations" checked="checked" value="1" onchange="if(jQuery(this).prop('checked'))jQuery('#icl_st_e_language').fadeIn('fast'); else jQuery('#icl_st_e_language').fadeOut('fast')" />
								   <label for="icl_st_pe_translations"><?php echo esc_html__( 'Also include translations', 'wpml-string-translation' ); ?></label>
									<select name="icl_st_e_language" id="icl_st_e_language">
									<?php
									foreach ( $active_languages as $al ) :
										if ( $al['code'] == $string_settings['strings_language'] ) {
											continue;}
										?>
									<option value="<?php echo esc_attr( $al['code'] ); ?>"><?php echo esc_html( $al['display_name'] ); ?></option>
									<?php endforeach; ?>
									</select>
								</p>
									<p><input type="submit" class="button-secondary" name="icl_st_pie_e" value="<?php echo esc_attr__( 'Submit', 'wpml-string-translation' ); ?>"/></p>
								<?php endif ?>
								</form>
							</div>
						</div>
					</div>
				</div>

				<?php if ( current_user_can( 'manage_options' ) ) : ?>

				<div class="postbox-container" style="width: 49%;">
					<div id="normal-sortables-moreoptions" class="meta-box-sortables ui-sortable">
						<div id="dashboard_wpml_st_poie" class="postbox">
							<div class="handlediv" title="<?php echo esc_attr__( 'Click to toggle', 'wpml-string-translation' ); ?>">
                                <svg viewBox="64 64 896 896" focusable="false" class="" data-icon="right" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M765.7 486.8L314.9 134.7A7.97 7.97 0 0 0 302 141v77.3c0 4.9 2.3 9.6 6.1 12.6l360 281.1-360 281.1c-3.9 3-6.1 7.7-6.1 12.6V883c0 6.7 7.7 10.4 12.9 6.3l450.8-352.1a31.96 31.96 0 0 0 0-50.4z"></path></svg>
							</div>
							<h3 class="hndle">
								<span><?php echo esc_html__( 'More options', 'wpml-string-translation' ); ?></span>
							</h3>
							<div class="inside">
								<form id="icl_st_more_options" name="icl_st_more_options" method="post">
								<?php wp_nonce_field( 'icl_st_more_options_nonce', '_icl_nonce' ); ?>
								<div>
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
									$tustr .= '&nbsp;&nbsp;<a href="#" onclick="jQuery(\'#icl_st_tusers\').slideToggle();return false;">' . esc_html__( 'edit', 'wpml-string-translation' ) . '</a>';

									?>
									<?php printf( __( 'Translating users of types: %s', 'wpml-string-translation' ), $tustr ); ?>


									<div id="icl_st_tusers" style="padding:6px;display: none;">
									<?php
									foreach ( $editable_roles as $role => $details ) {
										$name    = translate_user_role( $details['name'] );
										$checked = in_array( $role, (array) $string_settings['translated-users'] ) ? ' checked="checked"' : '';
										?>
										<label><input type="checkbox" name="users[<?php echo $role; ?>]" value="1"<?php echo $checked; ?>/>&nbsp;<span><?php echo $name; ?></span></label>&nbsp;
										<?php
									}
									?>
									</div>

								</div>
                                <br />
								<p class="submit">
									<input class="button-secondary" type="submit" value="<?php esc_attr_e( 'Apply', 'wpml-string-translation' ); ?>" />
									<span class="icl_ajx_response" id="icl_ajx_response4" style="display:inline"></span>
								</p>

								</form>



							</div>
					</div>
				</div>

				<?php endif; ?>

			</div>
		</div>

		<br clear="all" /><br />

			<a href="admin.php?page=<?php echo WPML_ST_FOLDER; ?>/menu/string-translation.php&amp;trop=1"><?php esc_html_e( 'Translate texts in admin screens &raquo;', 'wpml-string-translation' ); ?></a>

	<?php endif; // if(current_user_can('manage_options') ?>
	<?php endif; ?>
	<?php do_action( 'icl_menu_footer' ); ?>
</div>
