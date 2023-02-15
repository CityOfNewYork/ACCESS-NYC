<?php

namespace OTGS\Installer\CommercialTab;

class DownloadsList {
	public static function getDownloadRow( $download_id, $download, $site_key, $repository_id ) {
		$url = OTGS_Installer()->append_site_key_to_download_url( $download['url'], $site_key, $repository_id );

		$download_data = base64_encode( json_encode( [
			'url'           => $url,
			'slug'          => $download['slug'],
			'nonce'         => wp_create_nonce( 'install_plugin_' . $url ),
			'repository_id' => $repository_id
		] ) );

		$disabled = ( OTGS_Installer()->plugin_is_installed( $download['name'], $download['slug'], $download['version'] )
		              && ! OTGS_Installer()->plugin_is_embedded_version( $download['name'], $download['slug'] )
		              || OTGS_Installer()->dependencies->cant_download( $repository_id ) ) ? 'disabled="disabled"' : '';

		$upToDateClass = '';
		if ( $v = OTGS_Installer()->plugin_is_installed( $download['name'], $download['slug'] ) ) {

			$class = version_compare( $v, $download['version'], '>=' ) ? 'installer-green-text' : 'installer-red-text';
			$class .= version_compare( $v, $download['version'], '>' ) ? ' unstable' : '';

			$upToDateClass = '<span class="' . $class . '">' . $v . '</span>';

			if ( OTGS_Installer()->plugin_is_embedded_version( $download['name'], $download['slug'] ) ) {
				$upToDateClass .= '&nbsp;' . __( '(embedded)', 'installer' );
			}

			if (
				\WP_Installer_Channels()->get_channel( $repository_id ) !== \WP_Installer_Channels::CHANNEL_PRODUCTION &&
				$non_stable = \WP_Installer_Channels()->get_download_source_channel( $v, $repository_id, $download_id, 'plugins' )
			) {
				$upToDateClass .= '(' . $non_stable . ')';
			}
		}

		$releaseNotes          = '';
		$installerReleaseNotes = '';
		if ( ! empty( $download['release-notes'] ) ) {
			$releaseNotes = '<a class="js-release-notes handle" href="#">' . esc_html__( 'Release notes', 'installer' ) . '</a>';

			$installerReleaseNotes = '<tr class="installer-release-notes">
		                                <td colspan="9">
		                                    <div class="arrow_box">
		                                        <div>' . force_balance_tags( $download['release-notes'] ) . '</div>
		                                    </div>
		                                </td>
                            </tr>';
		}

		return '<tr>
	                <td class="installer_checkbox">
	                    <label>
	                    <input type="checkbox" name="downloads[]" value="' . $download_data . '" data-slug="' . $download['slug'] . '" ' . $disabled . ' />&nbsp;
	                    </label>
	                </td>
	                <td class="installer_plugin_name">' . $download['name'] . '</td>
	                <td class="installer_version_installed">' . $upToDateClass . '</td>
	                <td class="installer_version">' . $download['version'] . '</td>
	                <td class="installer_release_date">' . date_i18n( 'F j, Y', strtotime( $download['date'] ) ) . '</td>
	                <td>' . $releaseNotes . '</td>
	                <td>
	                    <span class="installer-status-installing">' . __( 'installing...', 'installer' ) . '</span>
	                    <span class="installer-status-updating">' . __( 'updating...', 'installer' ) . '</span>
	                    <span class="installer-status-installed" data-fail="' . __( 'failed!', 'installer' ) . '">' . __( 'installed', 'installer' ) . '</span>
	                    <span class="installer-status-updated" data-fail="' . __( 'failed!', 'installer' ) . '">' . __( 'updated', 'installer' ) . '</span>
	                </td>
	                <td>
	                    <span class="installer-status-activating">' . __( 'activating', 'installer' ) . '</span>
	                    <span class="installer-status-activated">' . __( 'activated', 'installer' ) . '</span>
	                </td>
	                <td class="for_spinner_js"><span class="spinner"></span></td>
	            </tr>' .
		        $installerReleaseNotes;
	}
}
