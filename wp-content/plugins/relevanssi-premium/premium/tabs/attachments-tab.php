<?php
/**
 * /premium/tabs/attachments-tab.php
 *
 * Prints out the Premium Attachments tab in Relevanssi settings.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the Premium attachments tab in Relevanssi settings.
 */
function relevanssi_attachments_tab() {
	$index_post_types = get_option( 'relevanssi_index_post_types', array() );
	$index_pdf_parent = get_option( 'relevanssi_index_pdf_parent' );

	global $wpdb;
	$read_new_files = '';
	$send_pdf_files = '';
	$link_pdf_files = '';
	$us_selected    = '';
	$eu_selected    = '';

	if ( 'on' === get_option( 'relevanssi_read_new_files' ) ) {
		$read_new_files = 'checked';
	}
	if ( 'on' === get_option( 'relevanssi_send_pdf_files' ) ) {
		$send_pdf_files = 'checked';
	}
	if ( 'on' === get_option( 'relevanssi_link_pdf_files' ) ) {
		$link_pdf_files = 'checked';
	}
	if ( 'us' === get_option( 'relevanssi_server_location' ) ) {
		$us_selected = 'selected';
	}
	if ( 'eu' === get_option( 'relevanssi_server_location' ) ) {
		$eu_selected = 'selected';
	}

	$indexing_attachments = false;
	if ( in_array( 'attachment', $index_post_types, true ) ) {
		$indexing_attachments = true;
	}

	?>
	<div id="attachments_tab">
	<table class="form-table" role="presentation">
	<tr id="row_read_attachments">
		<td>
			<input type='button' id='index' value='<?php esc_html_e( 'Read all unread attachments', 'relevanssi' ); ?>' class='button-primary' /><br /><br />
		</td>
		<td>
			<p class="description" id="indexing_button_instructions">
				<?php /* translators: the placeholder has the name of the custom field for PDF content */ ?>
				<?php printf( esc_html__( 'Clicking the button will read the contents of all the unread attachments files and store the contents to the %s custom field for future indexing. Attachments with errors will be skipped, except for the files with timeout and connection related errors: those will be attempted again.', 'relevanssi' ), '<code>_relevanssi_pdf_content</code>' ); ?>
			</p>
			<div id='relevanssi-note' style='display: none'></div>
			<div id='relevanssi-progress' class='rpi-progress'><div></div></div>
			<div id='relevanssi-timer'><?php esc_html_e( 'Time elapsed', 'relevanssi' ); ?>: <span id="relevanssi_elapsed">0:00:00</span> | <?php esc_html_e( 'Time remaining', 'relevanssi' ); ?>: <span id="relevanssi_estimated"><?php esc_html_e( 'some time', 'relevanssi' ); ?></span></div>
			<label for="relevanssi_results" class="screen-reader-text"><?php esc_html_e( 'Results', 'relevanssi' ); ?></label>
			<textarea id='relevanssi_results' rows='10' cols='80'></textarea>
		</td>
	</tr>
	<tr id="row_attachmnets_state">
		<th scope="row"><?php esc_html_e( 'State of the attachments', 'relevanssi' ); ?></td>
		<?php
		$pdf_count = wp_cache_get( 'relevanssi_pdf_count' );
		if ( false === $pdf_count ) {
			$pdf_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pdf_content' AND meta_value != ''" );
			wp_cache_set( 'relevanssi_pdf_count', $pdf_count );

		}
		$pdf_error_count = wp_cache_get( 'relevanssi_pdf_error_count' );
		if ( false === $pdf_error_count ) {
			$pdf_error_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pdf_error' AND meta_value != ''" );
			wp_cache_set( 'relevanssi_pdf_error_count', $pdf_error_count );
		}
		?>
		<td id="stateofthepdfindex">
			<p><?php echo esc_html( $pdf_count ); ?> <?php echo esc_html( _n( 'document has read attachment content.', 'documents have read attachment content.', $pdf_count, 'relevanssi' ) ); ?></p>
			<p><?php echo esc_html( $pdf_error_count ); ?> <?php echo esc_html( _n( 'document has an attachment reading error.', 'documents have attachment reading errors.', $pdf_error_count, 'relevanssi' ) ); ?>
			<?php if ( $pdf_error_count > 0 ) : ?>
				<span id="relevanssi_show_pdf_errors"><?php esc_html_e( 'Show errors', 'relevanssi' ); ?></span>.
			<?php endif; ?></p>
			<label for="relevanssi_pdf_errors" class="screen-reader-text"><?php esc_html_e( 'Attachment reading errors', 'relevanssi' ); ?></label>
			<textarea id="relevanssi_pdf_errors" rows="4" cols="120"></textarea>
		</td>
	</tr>
	<tr id="row_server_location">
		<th scope="row"><label for="relevanssi_server_location"><?php esc_html_e( 'Server location', 'relevanssi' ); ?></label></th>
		<td>
			<select name="relevanssi_server_location" id="relevanssi_server_location">
				<option value="us" <?php echo esc_html( $us_selected ); ?>><?php esc_html_e( 'United States', 'relevanssi' ); ?></option>
				<option value="eu" <?php echo esc_html( $eu_selected ); ?>><?php esc_html_e( 'European Union', 'relevanssi' ); ?></option>
			</select>
		</td>
	</tr>
	<tr id="row_reset_attachment_content">
		<th scope="row"><?php esc_html_e( 'Reset attachment content', 'relevanssi' ); ?></td>
		<td>
			<input type="button" id="reset" value="<?php esc_html_e( 'Reset all attachment data from posts', 'relevanssi' ); ?>" class="button-primary" />
			<?php /* translators: the placeholders are the names of the custom fields */ ?>
			<p class="description"><?php printf( esc_html__( "This will remove all %1\$s and %2\$s custom fields from all posts. If you want to reread all attachment files, use this to clean up; clicking the reading button doesn't wipe the slate clean like it does in regular indexing.", 'relevanssi' ), '<code>_relevanssi_pdf_content</code>', '<code>_relevanssi_pdf_error</code>' ); ?></p>
			<p class="description"><?php esc_html_e( 'If you have posts where you have modified the attachment content after reading it, this will leave those posts untouched.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr id="row_reset_server_errors">
		<th scope="row"><?php esc_html_e( 'Clear server errors', 'relevanssi' ); ?></td>
		<td>
			<input type="button" id="clearservererrors" value="<?php esc_html_e( 'Clear server errors', 'relevanssi' ); ?>" class="button-primary" />
			<p class="description"><?php esc_html_e( "This will clear all 'Server did not respond' errors from the posts, so you can try reading those files again.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr id="row_read_new_files">
		<th scope="row">
			<?php esc_html_e( 'Read new files', 'relevanssi' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Read new files automatically', 'relevanssi' ); ?></legend>
			<label for='relevanssi_read_new_files'>
				<input type='checkbox' name='relevanssi_read_new_files' id='relevanssi_read_new_files' <?php echo esc_attr( $read_new_files ); ?> />
				<?php esc_html_e( 'Read new files automatically', 'relevanssi' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'If this option is enabled, Relevanssi will automatically read the contents of new attachments as they are uploaded. This may cause unexpected delays in uploading posts. If this is not enabled, new attachments are not read automatically and need to be manually read and reindexed.', 'relevanssi' ); ?></p>
		</fieldset>
		</td>
	</tr>
	<tr id="row_upload_files">
		<th scope="row">
			<?php esc_html_e( 'Upload files', 'relevanssi' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Upload files for reading', 'relevanssi' ); ?></legend>
			<label for='relevanssi_send_pdf_files'>
				<input type='checkbox' name='relevanssi_send_pdf_files' id='relevanssi_send_pdf_files' <?php echo esc_attr( $send_pdf_files ); ?> />
				<?php esc_html_e( 'Upload files for reading', 'relevanssi' ); ?>
			</label>
			<p class="description"><?php esc_html_e( "By default, Relevanssi only sends a link to the attachment to the attachment reader. If your files are not accessible (for example your site is inside an intranet, password protected, or a local dev site, and the files can't be downloaded if given the URL of the file), check this option to upload the whole file to the reader.", 'relevanssi' ); ?></p>
		</fieldset>
		</td>
	</tr>
	<tr id="row_link_files">
		<th scope="row">
			<?php esc_html_e( 'Link to files', 'relevanssi' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Link search results directly to the files', 'relevanssi' ); ?></legend>
			<label for='relevanssi_link_pdf_files'>
				<input type='checkbox' name='relevanssi_link_pdf_files' id='relevanssi_link_pdf_files' <?php echo esc_attr( $link_pdf_files ); ?> />
				<?php esc_html_e( 'Link search results directly to the files', 'relevanssi' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'If this option is checked, attachment results in search results will link directly to the file. Otherwise the results will link to the attachment page.', 'relevanssi' ); ?></p>
			<?php if ( ! $indexing_attachments ) : ?>
				<?php /* translators: the placeholder has name of the post type */ ?>
			<p class="important description"><?php printf( esc_html__( "You're not indexing the %s post type, so this setting doesn't have any effect.", 'relevanssi' ), '<code>attachment</code>' ); ?>
			<?php endif; ?>
			<?php if ( ! $indexing_attachments && ! $index_pdf_parent ) : ?>
				<?php /* translators: the placeholder has name of the post type */ ?>
			<p class="important description"><?php printf( esc_html__( "You're not indexing the %s post type and haven't connected the files to the parent posts in the indexing settings. You won't be seeing any files in the results.", 'relevanssi' ), '<code>attachment</code>' ); ?>
			<?php endif; ?>
		</fieldset>
		</td>
	</tr>
	<tr id="row_instructions">
		<th scope="row"><?php esc_html_e( 'Instructions', 'relevanssi' ); ?></th>
		<td>
			<?php /* translators: placeholder has the name of the custom field */ ?>
			<p><?php printf( esc_html__( 'When Relevanssi reads attachment content, the text is extracted and saved in the %s custom field for the attachment post. This alone does not add the attachment content in the Relevanssi index; it just makes the contents of the attachments easily available for the regular Relevanssi indexing process.', 'relevanssi' ), '<code>_relevanssi_pdf_content</code>' ); ?></p>
			<?php /* translators: placeholder has the name of the post type */ ?>
			<p><?php printf( esc_html__( 'There are two ways to index the attachment content. If you choose to index the %s post type, Relevanssi will show the attachment posts in the results.', 'relevanssi' ), '<code>attachment</code>' ); ?></p>
			<p><?php esc_html_e( "You can also choose to index the attachment content for the parent post, in which case Relevanssi will show the parent post in the results (this setting can be found on the indexing settings). Obviously this does not find the content in attachments that are not attached to another post – if you just upload a file to the WordPress Media Library, it is not attached and won't be found unless you index the attachment posts.", 'relevanssi' ); ?></p>
			<p><?php esc_html_e( "If you need to reread a file, you can do read individual files from Media Library. Choose an attachment and click 'Edit more details' to read the content.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr id="row_key_not_valid">
		<th scope="row"><?php esc_html_e( 'Key not valid?', 'relevanssi' ); ?></th>
		<td>
			<p><?php esc_html_e( "Are you a new Relevanssi customer and seeing 'Key xxxxxx is not valid' error messages? New API keys are delivered to the server once per hour, so if try again an hour later, the key should work.", 'relevanssi' ); ?></p>
			<p><?php esc_html_e( "A 'Key 0 is not valid' error message means you're on a multisite, but have only entered the API key in the subsite settings. Set the API key in the network settings to fix that.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr id="row_important">
		<th scope="row"><?php esc_html_e( 'Important!', 'relevanssi' ); ?></th>
		<td>
			<p><?php esc_html_e( "In order to read the contents of the files, the files are sent over to Relevanssiservices.com, a processing service hosted on a Digital Ocean Droplet. There are two servers: one in the US and another in the EU. The service creates a working copy of the files. The copy is removed after the file has been processed, but there are no guarantees that someone with an access to the server couldn't see the files. Do not read files with confidential information in them. In order to block individual files from reading, use the Relevanssi post controls on attachment edit page to exclude attachment posts from indexing.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	</table>
	</div>
	<?php
}
