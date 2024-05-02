<?php
/**
 * /premium/uninstall.php
 *
 * @package Relevanssi Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Uninstalls Relevanssi Premium.
 *
 * Deletes all options and removes database tables.
 *
 * @global object $wpdb The WordPress database interface.
 */
function relevanssi_uninstall() {
	delete_option( 'relevanssi_admin_search' );
	delete_option( 'relevanssi_api_key' );
	delete_option( 'relevanssi_bg_col' );
	delete_option( 'relevanssi_body_stopwords' );
	delete_option( 'relevanssi_cat' );
	delete_option( 'relevanssi_class' );
	delete_option( 'relevanssi_click_tracking' );
	delete_option( 'relevanssi_comment_boost' );
	delete_option( 'relevanssi_content_boost' );
	delete_option( 'relevanssi_css' );
	delete_option( 'relevanssi_db_version' );
	delete_option( 'relevanssi_default_orderby' );
	delete_option( 'relevanssi_disable_or_fallback' );
	delete_option( 'relevanssi_disable_shortcodes' );
	delete_option( 'relevanssi_do_not_call_home' );
	delete_option( 'relevanssi_doc_count' );
	delete_option( 'relevanssi_exact_match_bonus' );
	delete_option( 'relevanssi_excat' );
	delete_option( 'relevanssi_excerpt_allowable_tags' );
	delete_option( 'relevanssi_excerpt_custom_fields' );
	delete_option( 'relevanssi_excerpt_length' );
	delete_option( 'relevanssi_excerpt_specific_fields' );
	delete_option( 'relevanssi_excerpt_type' );
	delete_option( 'relevanssi_excerpts' );
	delete_option( 'relevanssi_exclude_posts' );
	delete_option( 'relevanssi_expand_highlights' );
	delete_option( 'relevanssi_expand_shortcodes' );
	delete_option( 'relevanssi_extag' );
	delete_option( 'relevanssi_fuzzy' );
	delete_option( 'relevanssi_hide_branding' );
	delete_option( 'relevanssi_hide_post_controls' );
	delete_option( 'relevanssi_highlight' );
	delete_option( 'relevanssi_highlight_comments' );
	delete_option( 'relevanssi_highlight_docs' );
	delete_option( 'relevanssi_hilite_title' );
	delete_option( 'relevanssi_implicit_operator' );
	delete_option( 'relevanssi_index' );
	delete_option( 'relevanssi_index_author' );
	delete_option( 'relevanssi_index_comments' );
	delete_option( 'relevanssi_index_excerpt' );
	delete_option( 'relevanssi_index_fields' );
	delete_option( 'relevanssi_index_limit' );
	delete_option( 'relevanssi_index_pdf_parent' );
	delete_option( 'relevanssi_index_post_type_archives' );
	delete_option( 'relevanssi_index_post_types' );
	delete_option( 'relevanssi_index_subscribers' );
	delete_option( 'relevanssi_index_synonyms' );
	delete_option( 'relevanssi_index_taxonomies' );
	delete_option( 'relevanssi_index_taxonomies_list' );
	delete_option( 'relevanssi_index_terms' );
	delete_option( 'relevanssi_index_user_fields' );
	delete_option( 'relevanssi_index_user_meta' );
	delete_option( 'relevanssi_index_users' );
	delete_option( 'relevanssi_indexed' );
	delete_option( 'relevanssi_internal_links' );
	delete_option( 'relevanssi_link_boost' );
	delete_option( 'relevanssi_link_pdf_files' );
	delete_option( 'relevanssi_log_queries' );
	delete_option( 'relevanssi_log_queries_with_ip' );
	delete_option( 'relevanssi_max_excerpts' );
	delete_option( 'relevanssi_min_word_length' );
	delete_option( 'relevanssi_mysql_columns' );
	delete_option( 'relevanssi_omit_from_logs' );
	delete_option( 'relevanssi_polylang_all_languages' );
	delete_option( 'relevanssi_post_type_ids' );
	delete_option( 'relevanssi_post_type_weights' );
	delete_option( 'relevanssi_punctuation' );
	delete_option( 'relevanssi_read_new_files' );
	delete_option( 'relevanssi_recency_bonus' );
	delete_option( 'relevanssi_redirects' );
	delete_option( 'relevanssi_related_settings' );
	delete_option( 'relevanssi_related_style' );
	delete_option( 'relevanssi_respect_exclude' );
	delete_option( 'relevanssi_searchblogs' );
	delete_option( 'relevanssi_searchblogs_all' );
	delete_option( 'relevanssi_send_pdf_files' );
	delete_option( 'relevanssi_server_location' );
	delete_option( 'relevanssi_show_matches' );
	delete_option( 'relevanssi_show_matches_text' );
	delete_option( 'relevanssi_show_post_controls' );
	delete_option( 'relevanssi_spamblock' );
	delete_option( 'relevanssi_synonyms' );
	delete_option( 'relevanssi_taxterm_count' );
	delete_option( 'relevanssi_terms_count' );
	delete_option( 'relevanssi_thousand_separator' );
	delete_option( 'relevanssi_throttle' );
	delete_option( 'relevanssi_throttle_limit' );
	delete_option( 'relevanssi_title_boost' );
	delete_option( 'relevanssi_trim_click_logs' );
	delete_option( 'relevanssi_trim_logs' );
	delete_option( 'relevanssi_txt_col' );
	delete_option( 'relevanssi_update_translations' );
	delete_option( 'relevanssi_user_count' );
	delete_option( 'relevanssi_words' );
	delete_option( 'relevanssi_wpml_only_current' );

	global $wpdb;
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_hide_post'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_hide_content'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pin_for_all'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pin'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pin_weights'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_unpin'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pdf_content'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pdf_error'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pdf_modified'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_related_keywords'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_related_posts'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_related_include_ids'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_related_exclude_ids'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_related_no_append'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_related_not_related'" );
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_noindex_reason'" );

	// Unused options, removed in case they are still left.
	delete_option( 'relevanssi_cache_seconds' );
	delete_option( 'relevanssi_custom_types' );
	delete_option( 'relevanssi_enable_cache' );
	delete_option( 'relevanssi_hidesponsor' );
	delete_option( 'relevanssi_index_attachments' );
	delete_option( 'relevanssi_index_drafts' );
	delete_option( 'relevanssi_index_limit' );
	delete_option( 'relevanssi_index_type' );
	delete_option( 'relevanssi_show_matches_txt' );
	delete_option( 'relevanssi_tag_boost' );
	delete_option( 'relevanssi_include_cats' );
	delete_option( 'relevanssi_include_tags' );
	delete_option( 'relevanssi_custom_taxonomies' );
	delete_option( 'relevanssi_taxonomies_to_index' );
	delete_option( 'relevanssi_highlight_docs_external' );
	delete_option( 'relevanssi_word_boundaries' );

	if ( ! defined( 'UNINSTALLING_RELEVANSSI_PREMIUM' ) ) {
		// The if clause is required to avoid nagging from testing.
		define( 'UNINSTALLING_RELEVANSSI_PREMIUM', true );
	}

	wp_clear_scheduled_hook( 'relevanssi_update_counts' );

	relevanssi_drop_database_tables();
}
