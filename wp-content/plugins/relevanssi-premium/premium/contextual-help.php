<?php
/**
 * /premium/contextual-help.php
 *
 * Contextual help for Premium features.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Adds the Premium contextual help messages.
 *
 * Adds the Premium only contextual help messages to the WordPress contextual help menu.
 */
function relevanssi_premium_admin_help() {
	$screen = get_current_screen();
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-boolean',
			'title'   => __( 'Boolean operators', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( 'Relevanssi Premium offers limited support for Boolean logic. In addition of setting the default operator from Relevanssi settings, you can use AND and NOT operators in searches.', 'relevanssi' ) . '</li>' .
				'<li>' . __( 'To use the NOT operator, prefix the search term with a minus sign:', 'relevanssi' ) .
				sprintf( '<pre>%s</pre>', __( 'cats -dogs', 'relevanssi' ) ) .
				__( "This would only show posts that have the word 'cats' but not the word 'dogs'.", 'relevanssi' ) . '</li>' .
				'<li>' . __( 'To use the AND operator, set the default operator to OR and prefix the search term with a plus sign:', 'relevanssi' ) .
				sprintf( '<pre>%s</pre>', __( '+cats dogs mice', 'relevanssi' ) ) .
				__( "This would show posts that have the word 'cats' and either 'dogs' or 'mice' or both, and would prioritize posts that have all three.", 'relevanssi' ) . '</li>' .
				'</ul>',
		)
	);

	/* Translators:  first placeholder is the_permalink(), the second is relevanssi_the_permalink() */
	$permalinks_to_users = sprintf( esc_html__( "Permalinks to user profiles may not always work on search results templates. %1\$s should work, but if it doesn't, you can replace it with %2\$s.", 'relevanssi' ), '<code>the_permalink()</code>', '<code>relevanssi_the_permalink()</code>' );
	/* Translators:  the placeholder is the name of the relevanssi_index_user_fields option */
	$index_user_fields = sprintf( esc_html__( 'To control which user meta fields are indexed, you can use the %s option. It should have a comma-separated list of user meta fields. It can be set like this (you only need to run this code once):', 'relevanssi' ), '<code>relevanssi_index_user_fields</code>' );
	/* Translators: the first placeholder opens the link, the second closes the link */
	$knowledge_base = sprintf( esc_html__( 'For more details on user profiles and search results templates, see %1$sthis knowledge base entry%2$s.', 'relevanssi' ), "<a href='https://www.relevanssi.com/knowledge-base/user-profile-search/'>", '</a>' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-title-user-profiles',
			'title'   => __( 'User profiles', 'relevanssi' ),
			'content' => '<ul>' .
				"<li>$permalinks_to_users</li>" .
				"<li>$index_user_fields" .
				"<pre>update_option( 'relevanssi_index_user_fields', 'field_a,field_b,field_c' );</pre></li>" .
				"<li>$knowledge_base</li>" .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-internal-links',
			'title'   => __( 'Internal links', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( 'This option sets how Relevanssi handles internal links that point to your own site.', 'relevanssi' ) . '</li>' .
				'<li>' . __( "If you choose 'No special processing', Relevanssi doesn’t care about links and indexes the link anchor (the text of the link) like it is any other text.", 'relevanssi' ) . '</li>' .
				'<li>' . __( "If you choose 'Index internal links for target documents only', then the link is indexed like the link anchor text were the part of the link target, not the post where the link is.", 'relevanssi' ) . '</li>' .
				'<li>' . __( "If you choose 'Index internal links for target and source', the link anchor text will count for both posts.", 'relevanssi' ) . '</li>' .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-stemming',
			'title'   => __( 'Stemming', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( "By default Relevanssi doesn't understand anything about singular word forms, plurals or anything else. You can, however, add a stemmer that will stem all the words to their basic form, making all different forms equal in searching.", 'relevanssi' ) . '</li>' .
				'<li>' . __( 'To enable the English-language stemmer, add this to the theme functions.php:', 'relevanssi' ) .
				"<pre>add_filter( 'relevanssi_stemmer', 'relevanssi_simple_english_stemmer' );</pre>" . '</li>' .
				'<li>' . __( 'After you add the code, rebuild the index to get correct results.', 'relevanssi' ) . '</li>' .
				'</ul>',
		)
	);

	/* Translators: the placeholder has the WP CLI command */
	$wp_cli_command = sprintf( esc_html__( 'If you have WP CLI installed, Relevanssi Premium has some helpful commands. Use %s to get a list of available commands.', 'relevanssi' ), '<code>wp help relevanssi</code>' );
	/* Translators: the first placeholder opens the link, the second closes the link */
	$wp_cli_manual = sprintf( esc_html__( 'You can also see %1$sthe user manual page%2$s.', 'relevanssi' ), "<a href='https://www.relevanssi.com/user-manual/wp-cli/'>", '</a>' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-wpcli',
			'title'   => __( 'WP CLI', 'relevanssi' ),
			'content' => "<ul>
				<li>$wp_cli_command</li>
				<li>$wp_cli_manual</li>
				</ul>",
		)
	);

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'relevanssi' ) . '</strong></p>' .
		'<p><a href="http://www.relevanssi.com/support/" target="_blank">' . __( 'Plugin support page', 'relevanssi' ) . '</a></p>' .
		'<p><a href="http://wordpress.org/tags/relevanssi?forum_id=10" target="_blank">' . __( 'WordPress.org forum', 'relevanssi' ) . '</a></p>' .
		'<p><a href="mailto:support@relevanssi.zendesk.com">Support email</a></p>' .
		'<p><a href="http://www.relevanssi.com/knowledge-base/" target="_blank">' . __( 'Plugin knowledge base', 'relevanssi' ) . '</a></p>'
	);
}
