<?php
/**
 * /premium/tabs/spamblock-tab.php
 *
 * Prints out the Premium Spam Block tab in Relevanssi settings.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the Premium Spam Block tab in Relevanssi settings.
 */
function relevanssi_spamblock_tab() {
	$spamblock = get_option( 'relevanssi_spamblock' );
	if ( ! isset( $spamblock['keywords'] ) ) {
		$spamblock['keywords'] = '';
	}
	if ( ! isset( $spamblock['regex'] ) ) {
		$spamblock['regex'] = '';
	}
	if ( ! isset( $spamblock['chinese'] ) ) {
		$spamblock['chinese'] = '';
	}
	if ( ! isset( $spamblock['cyrillic'] ) ) {
		$spamblock['cyrillic'] = '';
	}
	if ( ! isset( $spamblock['emoji'] ) ) {
		$spamblock['emoji'] = '';
	}
	if ( ! isset( $spamblock['bots'] ) ) {
		$spamblock['bots'] = '';
	}
	$chinese  = relevanssi_check( $spamblock['chinese'] );
	$cyrillic = relevanssi_check( $spamblock['cyrillic'] );
	$emoji    = relevanssi_check( $spamblock['emoji'] );
	$bots     = relevanssi_check( $spamblock['bots'] );

	?>
<h2 id="options"><?php esc_html_e( 'Spam Blocking', 'relevanssi' ); ?></h2>

<div id="spamblock_settings">
<p><?php esc_html_e( "These tools can be used to block spam searches on your site. It's best if the spam searches can be blocked earlier on server level before WordPress starts at all, but if that's not possible, this is a fine option.", 'relevanssi' ); ?></p>

<p>
	<?php
	printf(
		// Translators: %1$s is '?s=', %2$s is '/search/', %3$ is the filter hook name and %4$ is 'highlight'.
		esc_html__( 'These filters are applied to all searches done using the %1$s parameter, the %2$s pretty URLs (if your pretty URLs are using a different prefix, you can use the %3$s filter hook to adjust the spam block) and also on page views with the %4$s parameter.', 'relevanssi' ),
		'<code>?s=</code>',
		'<code>/search/</code>',
		'<code>relevanssi_search_url_prefix</code>',
		'<code>highlight</code>'
	);
	?>
</p>

<p><?php esc_html_e( "You can figure out the suitable keywords from your User searches page. Look for common terms. Often spam queries contain URLs, and the top level domain names are good keywords, things like '.shop', '.online', '.com' – those appear rarely in legitimate searches.", 'relevanssi' ); ?></p>

<table class="form-table" role="presentation" id="spamblock_settings">
	<tbody>
		<tr id="row_keywords">
			<th scope="row"><label for="relevanssi_spamblock_keywords"><?php esc_html_e( 'Keyword spam blocking', 'relevanssi' ); ?></label></th>
			<td><textarea name="relevanssi_spamblock_keywords" id="relevanssi_spamblock_keywords" rows="9" cols="60"><?php echo esc_textarea( $spamblock['keywords'] ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Enter keywords, one per line. If these keywords appear anywhere in the search string, the search will be stopped. Use as short keywords as possible, but be careful to avoid blocking legitimate searches. The keywords are case insensitive.', 'relevanssi' ); ?></p></td>
		</tr>
		<tr id="row_regex">
			<th scope="row"><label for="relevanssi_spamblock_regex"><?php esc_html_e( 'Regex keywords', 'relevanssi' ); ?></label></th>
			<td><textarea name="relevanssi_spamblock_regex" id="relevanssi_spamblock_regex" rows="9" cols="60"><?php echo esc_textarea( $spamblock['regex'] ); ?></textarea>
			<?php // Translators: %1$s is <code>/.../iu</code>. ?>
			<p class="description"><?php printf( esc_html__( 'These keywords support the use of regular expressions with preg_match(). The keywords will be wrapped with %1$s.', 'relevanssi' ), '<code>/.../iu</code>' ); ?></p></td>
		</tr>
		<tr id="row_chinese">
			<th scope="row"><label for="relevanssi_spamblock_chinese"><?php esc_html_e( 'Block Chinese queries', 'relevanssi' ); ?></label></th>
			<td><input type='checkbox' name='relevanssi_spamblock_chinese' id='relevanssi_spamblock_chinese' <?php echo esc_attr( $chinese ); ?> />
			<?php esc_html_e( 'Block queries that contain Chinese characters.', 'relevanssi' ); ?>
		</tr>
		<tr id="row_cyrillic">
			<th scope="row"><label for="relevanssi_spamblock_cyrillic"><?php esc_html_e( 'Block Cyrillic queries', 'relevanssi' ); ?></label></th>
			<td><input type='checkbox' name='relevanssi_spamblock_cyrillic' id='relevanssi_spamblock_cyrillic' <?php echo esc_attr( $cyrillic ); ?> />
			<?php esc_html_e( 'Block queries that contain Cyrillic characters.', 'relevanssi' ); ?>
		</tr>
		<tr id="row_emoji">
			<th scope="row"><label for="relevanssi_spamblock_emoji"><?php esc_html_e( 'Block emoji queries', 'relevanssi' ); ?></label></th>
			<td><input type='checkbox' name='relevanssi_spamblock_emoji' id='relevanssi_spamblock_emoji' <?php echo esc_attr( $emoji ); ?> />
			<?php esc_html_e( 'Block queries that contain emoji characters.', 'relevanssi' ); ?>
		</tr>
		<tr id="row_bots">
			<th scope="row"><label for="relevanssi_spamblock_bots"><?php esc_html_e( 'Block bot queries', 'relevanssi' ); ?></label></th>
			<td><input type='checkbox' name='relevanssi_spamblock_bots' id='relevanssi_spamblock_bots' <?php echo esc_attr( $bots ); ?> />
			<?php esc_html_e( 'Block queries from bots. Only applied to searches, not to page views with highlights.', 'relevanssi' ); ?>
			<p class="description">
			<?php
			esc_html_e( 'Current list of bots: ', 'relevanssi' );
			/**
			 * Filter documented in /premium/spamblock.php.
			 */
			echo esc_html( implode( ', ', array_keys( apply_filters( 'relevanssi_bots_to_block', relevanssi_bot_block_list() ) ) ) . '. ' );
			// Translators: %1$s is the name of the filter hook.
			printf( esc_html__( 'You can add new bots to the list with the filter hook %1$s.', 'relevanssi' ), '<code>relevanssi_bots_to_block</code>' );
			?>
			</p>
		</tr>
	</tbody>
</table>
</div>

<div id="block_bots">
<h3><?php esc_html_e( 'Blocking bots', 'relevanssi' ); ?></h3>

<p><?php esc_html_e( "You can use the Relevanssi spam block to also block requests from bots. In general there's very little reason to allow bots to crawl search results pages. They can create lots of really quite pointless traffic. On one of my sites, out of 20.000 search queries, 16.000 were useless queries by the Bing bot. Nice bots will obey the robots.txt instructions. This code snippet adds robots.txt rules that block rule-obeying bots from accessing search results pages:", 'relevanssi' ); ?></p>

<p><pre>
add_action( 'do_robots', 'rlv_block_bots_robots_txt' );
function rlv_block_bots_robots_txt() {
	?&gt;
User-agent: *
Disallow: /search/
Disallow: /?s=
	&lt;?php
}
</pre></p>
</div>

<div id="server_block_tips">
<h3><?php esc_html_e( 'Blocking at the server level', 'relevanssi' ); ?></h3>

<p><?php esc_html_e( "It's best if the blocking is done before WordPress starts up in the first place: that will increase security and will save server resources. These tools can be used to block bot traffic on your site, but using them requires expertise on server settings. Use them only if you know what you're doing, or have a professional help you.", 'relevanssi' ); ?></p>

<ul>
	<li><a href="https://github.com/mitchellkrogza/nginx-ultimate-bad-bot-blocker">NGINX Ultimate Bad Bot & Referrer Blocker</a></li>
	<li><a href="https://github.com/mitchellkrogza/apache-ultimate-bad-bot-blocker">Apache Ultimate Bad Bot & Referrer Blocker</a></li>
</ul>
</div>
	<?php
}
