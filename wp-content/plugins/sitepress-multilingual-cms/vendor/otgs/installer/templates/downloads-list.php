<form method="post" class="otgsi_downloads_form">
	<?php

	use OTGS\Installer\CommercialTab\DownloadFilter;
	use OTGS\Installer\CommercialTab\DownloadsList;

	$sections = $this->get_plugins_sections( $repository_id, $package['downloads'] );
	if ( count( $sections ) === 1 ) {
		?>
        <div class="installer-table-wrap">
        <table class="widefat installer-plugins">
            <thead>
            <tr>
                <th>&nbsp;</th>
                <th><?php _e( 'Plugin', 'installer' ) ?></th>
                <th><?php _e( 'Installed', 'installer' ) ?></th>
                <th><?php _e( 'Current', 'installer' ) ?></th>
                <th><?php _e( 'Released', 'installer' ) ?></th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody><?php
			foreach ( reset( $sections )['downloads'] as $download_id => $download ) {
				if ( DownloadFilter::shouldDisplayRecord($download_id)) {
					echo DownloadsList::getDownloadRow( $download_id, $download, $site_key, $repository_id );
				}
			}
			?>
            </tbody>
        </table>
        </div><?php
	} else {
		foreach ( $sections as $section ) {
			if ( ! empty( $section['downloads'] ) ) {
				?>
                <div class="installer-table-wrap">
                <table class="widefat installer-plugins">
                    <thead>
                    <tr>
                        <th colspan="9"><strong><?php echo $section['name'] ?></strong></th>
                    </tr>
                    <tr>
                        <th>&nbsp;</th>
                        <th><?php _e( 'Plugin', 'installer' ) ?></th>
                        <th><?php _e( 'Installed', 'installer' ) ?></th>
                        <th><?php _e( 'Current', 'installer' ) ?></th>
                        <th><?php _e( 'Released', 'installer' ) ?></th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody><?php
					foreach ( $section['downloads'] as $download_id => $download ) {
					    if ( DownloadFilter::shouldDisplayRecord($download_id)) {
						    echo DownloadsList::getDownloadRow( $download_id, $download, $site_key, $repository_id );
					    }
					}
					?>
                    </tbody>
                </table>
                </div><?php
			}
		}
	}
	?>

    <br/>

    <div class="installer-error-box">
		<?php if ( ! WP_Installer()->dependencies->is_uploading_allowed() ): ?>
            <p><?php printf( __( 'Downloading is not possible because WordPress cannot write into the plugins folder. %sHow to fix%s.', 'installer' ),
					'<a href="http://codex.wordpress.org/Changing_File_Permissions">', '</a>' ) ?></p>
		<?php elseif ( WP_Installer()->dependencies->is_win_paths_exception( $repository_id ) ): ?>
            <p><?php echo WP_Installer()->dependencies->win_paths_exception_message() ?></p>
		<?php endif; ?>
    </div>

    <input type="submit" class="button-secondary" value="<?php esc_attr_e( 'Download', 'installer' ) ?>"
           disabled="disabled"/>
    &nbsp;
    <label><input name="activate" type="checkbox" value="1"
                  disabled="disabled"/>&nbsp;<?php _e( 'Activate after download', 'installer' ) ?></label>

    <div class="installer-download-progress-status"></div>

    <div class="installer-status-success"><?php _e( 'Operation complete!', 'installer' ) ?></div>

    <span class="installer-revalidate-message hidden"><?php _e( "Download failed!\n\nPlease refresh the page and try again.", 'installer' ) ?></span>
</form>
