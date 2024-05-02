<?php
/**
 * /premium/tabs/import-export-tab.php
 *
 * Prints out the Premium import/export tab in Relevanssi settings.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the Premium import/export tab in Relevanssi settings.
 */
function relevanssi_import_export_tab() {
	$serialized_options = relevanssi_serialize_options();
	?>
	<h2 id="options"><?php esc_html_e( 'Import or export options', 'relevanssi' ); ?></h2>

	<p><?php esc_html_e( 'Here you find the current Relevanssi Premium options in a text format. Copy the contents of the text field to make a backup of your settings. You can also paste new settings here to change all settings at the same time. This is useful if you have default settings you want to use on every system.', 'relevanssi' ); ?></p>

	<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="relevanssi_settings"><?php esc_html_e( 'Current Settings', 'relevanssi' ); ?></label></th>
		<td>
			<p>
				<textarea
					id='relevanssi_settings'
					name='relevanssi_settings'
					rows='4'
					cols='80'><?php echo esc_html( $serialized_options ); ?></textarea>
			</p>

			<input
				type='submit'
				name='import_options'
				id='import_options'
				value='<?php esc_html_e( 'Import settings', 'relevanssi' ); ?>'
				class='button'
			/>
		</td>
	</tr>
	</table>

	<p><?php esc_html_e( "Note! Make sure you've got correct settings from a right version of Relevanssi. Settings from a different version of Relevanssi may or may not work and may or may not mess your settings.", 'relevanssi' ); ?></p>
	<?php
}

/**
 * Collects all Relevanssi options to one array and JSON encodes it.
 *
 * @return string An array of Relevanssi options converted to JSON.
 */
function relevanssi_serialize_options() {
	$serialize_options = array();

	$bg_col                = get_option( 'relevanssi_bg_col' );
	$txt_col               = get_option( 'relevanssi_txt_col' );
	$index_taxonomies_list = get_option( 'relevanssi_index_taxonomies_list' );
	$index_terms           = get_option( 'relevanssi_index_terms' );

	$txt_col = relevanssi_sanitize_hex_color( $txt_col );
	$bg_col  = relevanssi_sanitize_hex_color( $bg_col );

	if ( empty( $index_post_types ) ) {
		$index_post_types = array();
	}
	if ( empty( $index_taxonomies_list ) ) {
		$index_taxonomies_list = array();
	}
	if ( empty( $index_terms ) ) {
		$index_terms = array();
	}

	$serialize_options['relevanssi_admin_search']             = get_option( 'relevanssi_admin_search' );
	$serialize_options['relevanssi_api_key']                  = get_option( 'relevanssi_api_key' );
	$serialize_options['relevanssi_bg_col']                   = $bg_col;
	$serialize_options['relevanssi_body_stopwords']           = get_option( 'relevanssi_body_stopwords' );
	$serialize_options['relevanssi_cat']                      = get_option( 'relevanssi_cat' );
	$serialize_options['relevanssi_class']                    = get_option( 'relevanssi_class' );
	$serialize_options['relevanssi_comment_boost']            = get_option( 'relevanssi_comment_boost' );
	$serialize_options['relevanssi_content_boost']            = get_option( 'relevanssi_content_boost' );
	$serialize_options['relevanssi_css']                      = get_option( 'relevanssi_css' );
	$serialize_options['relevanssi_db_version']               = get_option( 'relevanssi_db_version' );
	$serialize_options['relevanssi_default_orderby']          = get_option( 'relevanssi_default_orderby' );
	$serialize_options['relevanssi_disable_or_fallback']      = get_option( 'relevanssi_disable_or_fallback' );
	$serialize_options['relevanssi_disable_shortcodes']       = get_option( 'relevanssi_disable_shortcodes' );
	$serialize_options['relevanssi_do_not_call_home']         = get_option( 'relevanssi_do_not_call_home' );
	$serialize_options['relevanssi_exact_match_bonus']        = get_option( 'relevanssi_exact_match_bonus' );
	$serialize_options['relevanssi_excat']                    = get_option( 'relevanssi_excat' );
	$serialize_options['relevanssi_excerpt_allowable_tags']   = get_option( 'relevanssi_excerpt_allowable_tags' );
	$serialize_options['relevanssi_excerpt_custom_fields']    = get_option( 'relevanssi_excerpt_custom_fields' );
	$serialize_options['relevanssi_excerpt_length']           = get_option( 'relevanssi_excerpt_length' );
	$serialize_options['relevanssi_excerpt_specific_fields']  = get_option( 'relevanssi_excerpt_specific_fields' );
	$serialize_options['relevanssi_excerpt_type']             = get_option( 'relevanssi_excerpt_type' );
	$serialize_options['relevanssi_excerpts']                 = get_option( 'relevanssi_excerpts' );
	$serialize_options['relevanssi_exclude_posts']            = get_option( 'relevanssi_exclude_posts' );
	$serialize_options['relevanssi_expand_highlights']        = get_option( 'relevanssi_expand_highlights' );
	$serialize_options['relevanssi_expand_shortcodes']        = get_option( 'relevanssi_expand_shortcodes' );
	$serialize_options['relevanssi_extag']                    = get_option( 'relevanssi_extag' );
	$serialize_options['relevanssi_fuzzy']                    = get_option( 'relevanssi_fuzzy' );
	$serialize_options['relevanssi_hide_branding']            = get_option( 'relevanssi_hide_branding' );
	$serialize_options['relevanssi_hide_post_controls']       = get_option( 'relevanssi_hide_post_controls' );
	$serialize_options['relevanssi_highlight']                = get_option( 'relevanssi_highlight' );
	$serialize_options['relevanssi_highlight_comments']       = get_option( 'relevanssi_highlight_comments' );
	$serialize_options['relevanssi_highlight_docs']           = get_option( 'relevanssi_highlight_docs' );
	$serialize_options['relevanssi_hilite_title']             = get_option( 'relevanssi_hilite_title' );
	$serialize_options['relevanssi_implicit_operator']        = get_option( 'relevanssi_implicit_operator' );
	$serialize_options['relevanssi_index_author']             = get_option( 'relevanssi_index_author' );
	$serialize_options['relevanssi_index_comments']           = get_option( 'relevanssi_index_comments' );
	$serialize_options['relevanssi_index_excerpt']            = get_option( 'relevanssi_index_excerpt' );
	$serialize_options['relevanssi_index_fields']             = get_option( 'relevanssi_index_fields' );
	$serialize_options['relevanssi_index_image_files']        = get_option( 'relevanssi_index_image_files' );
	$serialize_options['relevanssi_index_limit']              = get_option( 'relevanssi_index_limit' );
	$serialize_options['relevanssi_index_pdf_parent']         = get_option( 'relevanssi_index_pdf_parent' );
	$serialize_options['relevanssi_index_post_type_archives'] = get_option( 'relevanssi_index_post_type_archives' );
	$serialize_options['relevanssi_index_post_types']         = get_option( 'relevanssi_index_post_types', array() );
	$serialize_options['relevanssi_index_subscribers']        = get_option( 'relevanssi_index_subscribers' );
	$serialize_options['relevanssi_index_synonyms']           = get_option( 'relevanssi_index_synonyms' );
	$serialize_options['relevanssi_index_taxonomies']         = get_option( 'relevanssi_index_taxonomies' );
	$serialize_options['relevanssi_index_taxonomies_list']    = $index_taxonomies_list;
	$serialize_options['relevanssi_index_terms']              = $index_terms;
	$serialize_options['relevanssi_index_user_fields']        = get_option( 'relevanssi_index_user_fields' );
	$serialize_options['relevanssi_index_users']              = get_option( 'relevanssi_index_users' );
	$serialize_options['relevanssi_internal_links']           = get_option( 'relevanssi_internal_links' );
	$serialize_options['relevanssi_link_boost']               = get_option( 'relevanssi_link_boost' );
	$serialize_options['relevanssi_link_pdf_files']           = get_option( 'relevanssi_link_pdf_files' );
	$serialize_options['relevanssi_log_queries']              = get_option( 'relevanssi_log_queries' );
	$serialize_options['relevanssi_log_queries_with_ip']      = get_option( 'relevanssi_log_queries_with_ip' );
	$serialize_options['relevanssi_max_excerpts']             = get_option( 'relevanssi_max_excerpts' );
	$serialize_options['relevanssi_min_word_length']          = get_option( 'relevanssi_min_word_length' );
	$serialize_options['relevanssi_mysql_columns']            = get_option( 'relevanssi_mysql_columns' );
	$serialize_options['relevanssi_omit_from_logs']           = get_option( 'relevanssi_omit_from_logs' );
	$serialize_options['relevanssi_polylang_all_languages']   = get_option( 'relevanssi_polylang_all_languages' );
	$serialize_options['relevanssi_post_type_ids']            = get_option( 'relevanssi_post_type_ids' );
	$serialize_options['relevanssi_post_type_weights']        = get_option( 'relevanssi_post_type_weights' );
	$serialize_options['relevanssi_punctuation']              = get_option( 'relevanssi_punctuation' );
	$serialize_options['relevanssi_read_new_files']           = get_option( 'relevanssi_read_new_files' );
	$serialize_options['relevanssi_recency_bonus']            = get_option( 'relevanssi_recency_bonus' );
	$serialize_options['relevanssi_redirects']                = get_option( 'relevanssi_redirects' );
	$serialize_options['relevanssi_related_settings']         = get_option( 'relevanssi_related_settings' );
	$serialize_options['relevanssi_related_style']            = get_option( 'relevanssi_related_style' );
	$serialize_options['relevanssi_respect_exclude']          = get_option( 'relevanssi_respect_exclude' );
	$serialize_options['relevanssi_searchblogs']              = get_option( 'relevanssi_searchblogs' );
	$serialize_options['relevanssi_searchblogs_all']          = get_option( 'relevanssi_searchblogs_all' );
	$serialize_options['relevanssi_send_pdf_files']           = get_option( 'relevanssi_send_pdf_files' );
	$serialize_options['relevanssi_seo_noindex']              = get_option( 'relevanssi_seo_noindex' );
	$serialize_options['relevanssi_server_location']          = get_option( 'relevanssi_server_location' );
	$serialize_options['relevanssi_show_matches']             = get_option( 'relevanssi_show_matches' );
	$serialize_options['relevanssi_show_matches_text']        = get_option( 'relevanssi_show_matches_text' );
	$serialize_options['relevanssi_show_post_controls']       = get_option( 'relevanssi_show_post_controls' );
	$serialize_options['relevanssi_spamblock']                = get_option( 'relevanssi_spamblock' );
	$serialize_options['relevanssi_stopwords']                = get_option( 'relevanssi_stopwords' );
	$serialize_options['relevanssi_synonyms']                 = get_option( 'relevanssi_synonyms' );
	$serialize_options['relevanssi_thousand_separator']       = get_option( 'relevanssi_thousand_separator' );
	$serialize_options['relevanssi_throttle']                 = get_option( 'relevanssi_throttle' );
	$serialize_options['relevanssi_throttle_limit']           = get_option( 'relevanssi_throttle_limit' );
	$serialize_options['relevanssi_title_boost']              = get_option( 'relevanssi_title_boost' );
	$serialize_options['relevanssi_trim_logs']                = get_option( 'relevanssi_trim_logs' );
	$serialize_options['relevanssi_txt_col']                  = $txt_col;
	$serialize_options['relevanssi_update_translations']      = get_option( 'relevanssi_update_translations' );
	$serialize_options['relevanssi_wpml_only_current']        = get_option( 'relevanssi_wpml_only_current' );

	$serialized_options = wp_json_encode( $serialize_options );

	return $serialized_options;
}
