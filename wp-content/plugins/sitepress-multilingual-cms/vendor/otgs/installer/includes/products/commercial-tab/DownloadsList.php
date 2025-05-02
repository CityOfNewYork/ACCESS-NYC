<?php

namespace OTGS\Installer\CommercialTab;

class DownloadsList {
	public static function getDownloadRow( $download_id, $download, $site_key, $repository_id ) {
		$url = OTGS_Installer()->append_site_key_to_download_url( $download['url'], $site_key, $repository_id );

		$download_data = base64_encode( (string) json_encode( [
			'url'           => $url,
			'slug'          => $download['slug'],
			'nonce'         => wp_create_nonce( 'install_plugin_' . $url ),
			'repository_id' => $repository_id
		] ) );

		$disabled = ! OTGS_Installer()->can_plugin_be_installed($download,$repository_id)  ? 'disabled' : '';
		$checked = $download['is_preselected_plugin'] ? 'checked' : '';

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
			$releaseNotes = '<a class="js-release-notes handle" data-plugin-id="'.$download_id.'" href="#">' . esc_html__( 'Release notes', 'installer' ) . '</a>';

			$installerReleaseNotes = '<tr id="'.$download_id.'_release-notes" class="installer-release-notes">
		                                <td colspan="9">
		                                    <div class="arrow_box">
		                                        <div>' . force_balance_tags( $download['release-notes'] ) . '</div>
		                                    </div>
		                                </td>
                            </tr>';
		}

		$leadDescription = '';
		if (array_key_exists('lead_description', $download)) {
			if (array_key_exists('en', $download['lead_description'])) {
				$leadDescription = '<p id="'.$download_id.'_lead-description">'.$download['lead_description']['en'].'</p>';
			}
		}

		return '<tr id="'.$download_id.'_plugin-row">
	                <td class="installer_checkbox for_spinner_js">
	                    <label>
	                       <input type="checkbox" ' . $checked . ' name="downloads[]" value="' . $download_data . '" data-slug="' . $download['slug'] . '" ' . $disabled . ' />&nbsp;
	                    </label>
	                    <span class="spinner"></span>
	                </td>
	                <td class="installer_plugin_name">
	                	<span>' . $download['name'] . '</span>'
	                	. $leadDescription . '
	                </td>
	                <td class="installer_version_installed for_spinner_js">
	                	<div class="installer_version_installed_wrapper">
	                		<span class="installed-version">' . $upToDateClass . '</span>
							<span class="spinner"></span>
						</div>
	                </td>
	                <td class="installer_version">' . $download['version'] . '</td>
	                <td class="installer_release_date">' . date_i18n( 'F j, Y', strtotime( $download['date'] ) ) . '</td>
	                <td></td>
					<td>' . $releaseNotes . '</td>
	            </tr>' .
		       $installerReleaseNotes;
	}
}
