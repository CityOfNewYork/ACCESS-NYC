<?php

use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Wrapper;
use function WPML\FP\partialRight;

class WPML_String_Translation_Table {

	private $strings;
	private $active_languages;
	private $additional_columns_to_render;
	private $strings_in_page;

	/**
	 * WPML_String_Translation_Table constructor.
	 *
	 * @param array<string> $strings
	 */
	public function __construct( $strings ) {
		global $sitepress;

		$this->strings = $strings;
		if ( ! empty( $strings ) ) {
			foreach ( $this->strings as $id => $data ) {
				$this->strings[ $id ]['hasFrontendKind'] = 0;
			}

			$this->strings_in_page = icl_get_strings_tracked_in_pages( $strings );
			$frontendKind = ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND;
			if ( isset( $this->strings_in_page[ $frontendKind ] ) ) {
				foreach ( $this->strings_in_page[ $frontendKind ] as $id => $value ) {
					if ( ! isset( $this->strings[ $id ] ) ) {
						continue;
					}
					$this->strings[ $id ]['hasFrontendKind'] = 1;
				}
			}
		}


		$this->additional_columns_to_render = wpml_collect();
		$this->active_languages             = $sitepress->get_active_languages();
	}

	public function render() {
		?>
		<div id="icl_string_translations_wrap" class="wpml-st-table-loader-wrap">
			<table id="icl_string_translations" class="widefat wpml-st-table js-wpml-st-table" cellspacing="0">
				<?php
				Fns::each( [ $this, 'updateColumnsForString' ], $this->strings );

				$this->render_table_header_or_footer( 'thead' );
				$this->render_table_header_or_footer( 'tfoot' );
				?>
				<tbody>
				<?php
				if ( empty( $this->strings ) ) {
					?>
					<tr>
						<td colspan="6" align="center">
							<?php esc_html_e( 'No strings found', 'wpml-string-translation' ); ?>
						</td>
					</tr>
					<?php
				} else {
					foreach ( $this->strings as $string_id => $icl_string ) {
						$this->render_string_row( $string_id, $icl_string );
					}
				}
				?>
				</tbody>
			</table>

			<div id="wpml-icl-string-translations-batch-loader" class="wpml-st-table-batch-loader">
				<div class="loader-bg"></div>
				<div class="loader-content">
					<div class="content-text"><?php esc_html_e( 'Processing', 'wpml-string-translation' ); ?></div>
					<div class="content-percentage js-content-percentage">0%</div>
					<div class="content-percentage-bar js-content-percentage-bar">
						<div class="content-percentage-bar-status js-content-percentage-bar-status" style="width: 0%"></div>
					</div>
				</div>
			</div>
		</div>

		<?php
	}

	private function render_table_header_or_footer( $tag ) {

		$codes = Obj::keys( $this->active_languages );

		$getFlagData = function ( $langData ) {
			return [
				'title'   => $langData['display_name'],
				'flagUrl' => Languages::getFlagUrl( $langData['code'] ),
				'code'    => $langData['code'],
			];
		};

		$getFlagImg = function ($langData) {
		    if ($langData['flagUrl'] !== '') {
			    return '<img
					src="'.esc_attr( $langData['flagUrl'] ).'"
            alt="'.esc_attr( $langData['title'] ).'"
            >';
		    } else {
		        return $langData['code'];
		    }

		};

		$makeFlag = function ( $langData ) use ($getFlagImg) {
			ob_start();
			?>
			<span
				data-code="<?php esc_attr_e( $langData['code'] ); ?>"
				title="<?php esc_attr_e( $langData['title'] ); ?>"
			>
				<?php echo $getFlagImg($langData) ?>
			</span>
			<?php
			return ob_get_clean();
		};

		$flags = Wrapper::of( $this->active_languages )
						->map( Fns::map( $getFlagData ) )
						->map( Fns::map( $makeFlag ) )
						->map( Fns::reduce( WPML\FP\Str::concat(), '' ) );

		$bulkActionsColspan = 4;
		$renderContext      = false;
		$renderName         = false;
		$renderView         = false;
		if ( $this->additional_columns_to_render->contains( 'context' ) ) {
			$renderContext = true;
			$bulkActionsColspan++;
		}
		if ( $this->additional_columns_to_render->contains( 'name' ) ) {
			$renderName = true;
			$bulkActionsColspan++;
		}
		if ( $this->additional_columns_to_render->contains( 'view' ) ) {
			$renderView = true;
			$bulkActionsColspan++;
		}
		?>
		<<?php echo $tag; ?>>
		<?php
			if ( 'tfoot' === $tag ) {
				$this->renderBulkActionsRow( $bulkActionsColspan );
			}
		?>
		<tr>
			<td scope="col" class="manage-column column-cb check-column"><input class="wpml-checkbox-native" type="checkbox"/></td>
			<th scope="col"><?php esc_html_e( 'Domain', 'wpml-string-translation' ); ?></th>
			<?php if ( $renderContext ) : ?>
				<th scope="col"><?php esc_html_e( 'Context', 'wpml-string-translation' ); ?></th>
			<?php endif; ?>
			<?php if ( $renderName ) : ?>
				<th scope="col"><?php esc_html_e( 'Name', 'wpml-string-translation' ); ?></th>
			<?php endif; ?>
			<?php if ( $renderView ) : ?>
				<th class="usage" scope="col"><?php esc_html_e( 'Usage', 'wpml-string-translation' ); ?></th>
			<?php endif; ?>
			<th scope="col"><?php esc_html_e( 'String', 'wpml-string-translation' ); ?></th>
			<th scope="col" class="wpml-col-languages"
				data-langs="<?php echo esc_attr( (string) json_encode( $codes ) ); ?>"><?php echo $flags->get(); ?></th>
		</tr>
		<?php
			if ( 'thead' === $tag ) {
				$this->renderBulkActionsRow( $bulkActionsColspan );
			}
		?>
		</<?php echo $tag; ?>>
		<?php
	}

	private function renderBulkActionsRow( $colspan ) {
		$msgAllOnPageSelected = esc_html( __( 'All %d strings on this page are selected.', 'wpml-string-translation' ) );
		$msgSelectAll         = esc_html( __( 'Select all strings that match this search', 'wpml-string-translation' ) );
		$msgAllSelected       = esc_html( __( 'All %d strings from all pages are selected.', 'wpml-string-translation' ) );
		$msgUnselectAll       = esc_html( __( 'Unselect all strings that match this search', 'wpml-string-translation' ) );
		?>
		<tr>
			<td scope="col" colspan="<?php echo $colspan; ?>" class="js-wpml-st-icl-string-translations-bulk-select-msg wpml-st-table-col-header-msg-row">
				<p class="selected-all-on-page-msg js-selected-all-on-page-msg"><?php echo $msgAllOnPageSelected; ?></p>
				<a href="#" class="select-all-link js-select-all-link"><?php echo $msgSelectAll; ?></a>
				<p class="selected-all-msg js-selected-all-msg" style="display: none"><?php echo $msgAllSelected; ?></p>
				<a href="#" class="unselect-all-link js-unselect-all-link" style="display: none"><?php echo $msgUnselectAll; ?></a>
			</td>
		</tr>
		<?php
	}

	public function render_string_row( $string_id, $icl_string ) {
		global $wpdb, $sitepress, $WPML_String_Translation;
		$icl_string = $this->decodeHtmlEntitiesForStringAndTranslations( $icl_string );
		?>
		<tr valign="top" data-string="<?php echo esc_attr( htmlentities( (string) json_encode( $icl_string ), ENT_QUOTES ) ); ?>">
			<?php echo $this->render_checkbox_cell( $icl_string ); ?>
			<td class="wpml-st-col-domain"><?php echo esc_html( $icl_string['context'] ); ?></td>
			<?php if ( $this->additional_columns_to_render->contains( 'context' ) ) : ?>
				<td class="wpml-st-col-context"><?php echo esc_html( $icl_string['gettext_context'] ); ?></td>
			<?php endif; ?>
			<?php if ( $this->additional_columns_to_render->contains( 'name' ) ) : ?>
				<td class="wpml-st-col-name"><?php echo esc_html( $this->hide_if_md5( $icl_string['name'] ) ); ?></td>
			<?php endif; ?>

			<?php if ( $this->additional_columns_to_render->contains( 'view' ) ) : ?>
				<td class="wpml-st-col-view" nowrap="nowrap">
					<?php $this->render_view_column( $string_id, $icl_string, $sitepress ); ?>
				</td>
			<?php endif; ?>
			<td class="wpml-st-col-string">
				<div class="icl-st-original">
					<img width="18" height="12"
						 src="<?php echo esc_url( $sitepress->get_flag_url( $icl_string['string_language'] ) ); ?>"> <?php echo esc_html( $icl_string['value'] ); ?>
				</div>
				<input type="hidden" id="icl_st_wc_<?php echo esc_attr( $string_id ); ?>" value="
															  <?php
																echo $WPML_String_Translation->estimate_word_count( $icl_string['value'], $icl_string['string_language'] )
																?>
				"/>
			</td>
			<td class="languages-status wpml-col-languages"></td>
		</tr>
		<?php
	}

	private function decodeHtmlEntitiesForStringAndTranslations( $string ) {
		$decode = function( $string ) {
			return is_null( $string ) ? '' : partialRight( 'html_entity_decode', ENT_QUOTES )( $string );
		};

		$string['value'] = $decode( $string['value'] );
		$string['name']  = $decode( $string['name'] );
		if ( Obj::prop( 'translations', $string ) ) {
			$string['translations'] = Fns::map(
				Obj::over( Obj::lensProp( 'value' ), $decode ),
				$string['translations']
			);
		}

		return $string;
	}

	/**
	 * @param array $string
	 *
	 * @return string html for the checkbox and the table cell it resides in
	 */
	private function render_checkbox_cell( $string ) {
		$class = 'icl_st_row_cb' . ( ! empty( $string['string_package_id'] ) ? ' icl_st_row_package' : '' ) . ' js-icl-st-row-cb';

		return '<td><input class="wpml-checkbox-native ' . esc_attr( $class ) . '" type="checkbox" value="' . esc_attr( $string['string_id'] )
			   . '" data-language="' . esc_attr( $string['string_language'] ) . '" /></td>';
	}

	private function render_view_column( $string_id, $icl_string, $sitepress ) {
		if ( isset( $this->strings_in_page[ ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE ][ $string_id ] ) ) {

			$thickbox_url = $this->get_thickbox_url( WPML_ST_String_Tracking_AJAX_Factory::ACTION_POSITION_IN_SOURCE, $string_id );
			echo $this->generate_string_preview_link( $icl_string, $thickbox_url, $sitepress );
			return;
		}

		if ( isset( $this->strings_in_page[ ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE ][ $string_id ] ) ) {
			$thickbox_url = $this->get_thickbox_url( WPML_ST_String_Tracking_AJAX_Factory::ACTION_POSITION_IN_PAGE, $string_id );
			echo $this->generate_string_preview_link( $icl_string, $thickbox_url, $sitepress );
			return;
		}

		if ( isset( $this->strings_in_page[ ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND ][ $string_id ] ) ) {
			$thickbox_url = $this->get_thickbox_url( WPML_ST_String_Tracking_AJAX_Factory::ACTION_POSITION_IN_PAGE, $string_id );
			echo $this->generate_string_preview_link($icl_string, $thickbox_url, $sitepress);
		}
	}

	private function generate_string_preview_link( $icl_string, $thickbox_url, $sitepress ): string
	{
		return '<a
                class="thickbox"
                data-domain-title="' . esc_attr( __('Domain', 'wpml-string-translation') ) . '"
                data-string-title="' . esc_attr( __('String', 'wpml-string-translation') ) . '"
                data-domain="' . esc_html( $icl_string['context'] ) . '"
                data-string="' . esc_html( $icl_string['value'] ) . '"
                data-popup-title="' . esc_attr( __('Preview string on site', 'wpml-string-translation') ) . '"
                data-popup-description="' . esc_attr( __('This preview shows where the selected string appears on your site’s front-end.', 'wpml-string-translation') ) . '"
                data-iframe-title="' . esc_attr( __('Frontend preview', 'wpml-string-translation') ) . '"
                data-toggle="tooltip"
                data-image-url="' . esc_url( $sitepress->get_flag_url( $icl_string['string_language'] ) ) . '"
                title="' . esc_attr( __('Preview where this string appears on your site', 'wpml-string-translation') ) . '"
                href="' . esc_url( $thickbox_url ) . '">
                <i class="otgs-ico otgs-ico-eye-show"></i>
            </a>';
	}

	/**
	 * @param string $action
	 * @param int    $string_id
	 *
	 * @return string
	 */
	private function get_thickbox_url( $action, $string_id ) {
		return add_query_arg(
			array(
				'page'      => WPML_ST_FOLDER . '/menu/string-translation.php',
				'action'    => $action,
				'nonce'     => wp_create_nonce( $action ),
				'string_id' => $string_id,
				'width'     => 926,
				'height'    => 453,
			),
			'admin-ajax.php'
		);
	}


	private function hide_if_md5( $str ) {
		return preg_replace( '#^((.+)( - ))?([a-z0-9]{32})$#', '$2', $str );
	}

	/**
	 * @param array<string,string|int> $string
	 */
	public function updateColumnsForString( $string ) {
		if (
			! $this->additional_columns_to_render->contains( 'context' )
			&& $string['gettext_context']
		) {
			$this->additional_columns_to_render->push( 'context' );
		}

		if (
			! $this->additional_columns_to_render->contains( 'name' )
			&& $this->hide_if_md5( $string['name'] )
		) {
			$this->additional_columns_to_render->push( 'name' );
		}

		if (
			! $this->additional_columns_to_render->contains( 'view' )
			&& $this->is_string_tracked( $string['string_id'] )
		) {
			$this->additional_columns_to_render->push( 'view' );
		}
	}

	private function is_string_tracked( $string_id ) {
		$tracked_source   = Obj::prop( ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE, $this->strings_in_page );
		$tracked_page     = Obj::prop( ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE, $this->strings_in_page );
		$tracked_frontend = Obj::prop( ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND, $this->strings_in_page );

		return ( $tracked_source && Obj::prop( $string_id, $tracked_source ) )
			   || ( $tracked_page && Obj::prop( $string_id, $tracked_page ) )
			   || ( $tracked_frontend && Obj::prop( $string_id, $tracked_frontend ) );
	}
}

