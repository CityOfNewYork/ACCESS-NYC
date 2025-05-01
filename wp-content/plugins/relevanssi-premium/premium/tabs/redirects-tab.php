<?php
/**
 * /premium/tabs/redirects-tab.php
 *
 * Prints out the Premium Redirects tab in Relevanssi settings.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the Premium Redirects tab in Relevanssi settings.
 */
function relevanssi_redirects_tab() {
	$site_url  = site_url();
	$redirects = get_option( 'relevanssi_redirects' );
	if ( ! isset( $redirects['empty'] ) ) {
		$redirects['empty'] = '';
	}
	if ( ! isset( $redirects['no_terms'] ) ) {
		$redirects['no_terms'] = '';
	}
	?>
<h2 id="redirect_options"><?php esc_html_e( 'Redirects', 'relevanssi' ); ?></h2>

<p><?php esc_html_e( 'If you want a particular search to always lead to a specific page, you can use the redirects. Whenever the search query matches a redirect, the search is automatically bypassed and the user is redirected to the target page.', 'relevanssi' ); ?></p>

<p><?php esc_html_e( 'Enter the search term and the target URL, which may be relative to your site home page or an absolute URL. If "Partial match" is checked, the redirect happens if the query word appears anywhere in the search query, even inside a word, so use it with care. If the search query matches multiple redirections, the first one it matches will trigger.', 'relevanssi' ); ?></p>

<p><?php esc_html_e( 'The "Hits" column shows how many times each redirect has been used.', 'relevanssi' ); ?></p>

<table class="form-table" role="presentation" id="redirect_settings">
	<tbody>
		<tr id="row_redirect_empty">
			<th scope="row"><label for="redirect_empty_searches"><?php esc_html_e( 'Redirect empty searches', 'relevanssi' ); ?></label></th>
			<td><input type="text" id="redirect_empty_searches" name="redirect_empty_searches" size="60" value="<?php echo esc_attr( str_replace( $site_url, '', $redirects['empty'] ) ); ?>" />
			<p class="description"><?php esc_html_e( 'Enter an URL here to redirect all searches that find nothing to this URL.', 'relevanssi' ); ?></p></td>
		</tr>
		<tr id="row_redirect_termless">
			<th scope="row"><label for="redirect_no_terms"><?php esc_html_e( 'Redirect searches without terms', 'relevanssi' ); ?></label></th>
			<td><input type="text" id="redirect_no_terms" name="redirect_no_terms" size="60" value="<?php echo esc_attr( str_replace( $site_url, '', $redirects['no_terms'] ) ); ?>" />
			<p class="description"><?php esc_html_e( 'Enter an URL here to redirect all searches without any search terms.', 'relevanssi' ); ?></p></td>
		</tr>
	</tbody>
</table>

<table class="form-table" id="redirect_table">
	<thead>
	<tr>
	<th><?php esc_html_e( 'Query', 'relevanssi' ); ?></th>
	<th><?php esc_html_e( 'Partial match', 'relevanssi' ); ?></th>
	<th><?php esc_html_e( 'URL', 'relevanssi' ); ?></th>
	<th><?php esc_html_e( 'Hits', 'relevanssi' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	if ( ! isset( $redirects[0] ) ) {
		?>
	<tr class="redirect_table_row" id="row_0">
	<td><input type="text" name="query_0" size="60" />
	<div class="row-actions">
		<span class="copy"><a href="#" class="copy"><?php esc_html_e( 'Copy', 'relevanssi' ); ?></a> |</span>
		<span class="delete"><a href="#" class="remove"><?php esc_html_e( 'Remove', 'relevanssi' ); ?></a></span>
	</div>
	</td>
	<td><input type="checkbox" name="partial_0" /></td>
	<td><input type="text" name="url_0" size="60" /></td>
	<td><input type="hidden" name="hits_0" /><span>0</span></td>
	</tbody>
	</tr>
		<?php
	} else {
		$row = 0;
		foreach ( $redirects as $redirect ) {
			if ( ! isset( $redirect['query'] ) ) {
				continue;
			}

			$row_id  = esc_attr( $row );
			$query   = esc_attr( $redirect['query'] );
			$partial = '';
			if ( $redirect['partial'] ) {
				$partial = 'checked="checked"';
			}
			$url = esc_attr( $redirect['url'] );
			$url = str_replace( $site_url, '', $url );

			$hits = $redirect['hits'] ?? 0;
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<tr class="redirect_table_row" id="row_<?php echo $row_id; ?>">
		<td>
			<label
				class="screen-reader-text"
				for="query_<?php echo $row_id; ?>">
					<?php esc_html_e( 'Query string', 'relevanssi' ); ?>
			</label>
			<input
				type="text"
				id="query_<?php echo $row_id; ?>"
				name="query_<?php echo $row_id; ?>"
				size="60"
				value="<?php echo $query; ?>" />
			<div class="row-actions">
				<span class="copy"><a href="#" class="copy"><?php esc_html_e( 'Copy', 'relevanssi' ); ?></a> |</span>
				<span class="delete"><a href="#" class="remove"><?php esc_html_e( 'Remove', 'relevanssi' ); ?></a></span>
		</div>
		</td>
		<td>
			<label
				class="screen-reader-text"
				for="partial_<?php echo $row_id; ?>">
					<?php esc_html_e( 'Partial match', 'relevanssi' ); ?>
			</label>
			<input
				type="checkbox"
				id="partial_<?php echo $row_id; ?>"
				name="partial_<?php echo $row_id; ?>"
				<?php echo $partial; ?> />
		</td>
		<td>
			<label
				class="screen-reader-text"
				for="url_<?php echo $row_id; ?>">
					<?php esc_html_e( 'Target URL', 'relevanssi' ); ?>
			</label>
			<input
				type="text"
				name="url_<?php echo $row_id; ?>"
				id="url_<?php echo $row_id; ?>"
				size="60"
				value="<?php echo $url; ?>" />
		</td>
		<td>
			<input
				type="hidden"
				name="hits_<?php echo $row_id; ?>"
				id="hits_<?php echo $row_id; ?>"
				value="<?php echo $hits; ?>" />
			<span><?php echo $hits; ?></span>
		</td>
		</tr>
			<?php
			++$row;
		}
	}
	?>
	</tbody>
	</table>

	<button type="button" class="secondary" id="add_redirect"><?php esc_html_e( 'Add a redirect', 'relevanssi' ); ?></button>

	<p><?php esc_html_e( "Once you're done, remember to click the save button below!", 'relevanssi' ); ?></p>
	<?php
}
