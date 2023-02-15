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
			$this->strings_in_page = icl_get_strings_tracked_in_pages( $strings );
		}

		$this->additional_columns_to_render = wpml_collect();
		$this->active_languages             = $sitepress->get_active_languages();
	}

	public function render() {
		?>
		<table id="icl_string_translations" class="widefat" cellspacing="0">
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
		?>
		<<?php echo $tag; ?>>
		<tr>
			<td scope="col" class="manage-column column-cb check-column"><input type="checkbox"/></td>
			<th scope="col"><?php esc_html_e( 'Domain', 'wpml-string-translation' ); ?></th>
			<?php if ( $this->additional_columns_to_render->contains( 'context' ) ) : ?>
				<th scope="col"><?php esc_html_e( 'Context', 'wpml-string-translation' ); ?></th>
			<?php endif; ?>
			<?php if ( $this->additional_columns_to_render->contains( 'name' ) ) : ?>
				<th scope="col"><?php esc_html_e( 'Name', 'wpml-string-translation' ); ?></th>
			<?php endif; ?>
			<?php if ( $this->additional_columns_to_render->contains( 'view' ) ) : ?>
				<th scope="col"><?php esc_html_e( 'View', 'wpml-string-translation' ); ?></th>
			<?php endif; ?>
			<th scope="col"><?php esc_html_e( 'String', 'wpml-string-translation' ); ?></th>
			<th scope="col" class="wpml-col-languages"
				data-langs="<?php echo esc_attr( json_encode( $codes ) ); ?>"><?php echo $flags->get(); ?></th>
		</tr>
		<<?php echo $tag; ?>>
		<?php
	}

	public function render_string_row( $string_id, $icl_string ) {
		global $wpdb, $sitepress, $WPML_String_Translation;
		$icl_string = $this->decodeHtmlEntitiesForStringAndTranslations( $icl_string );

		?>
		<tr valign="top" data-string="<?php echo esc_attr( htmlentities( json_encode( $icl_string ), ENT_QUOTES ) ); ?>">
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
					<?php $this->render_view_column( $string_id ); ?>
				</td>
			<?php endif; ?>
			<td class="wpml-st-col-string">
				<div class="icl-st-original"<?php _icl_string_translation_rtl_div( $icl_string['string_language'] ); ?>>
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
		$decode = partialRight( 'html_entity_decode', ENT_QUOTES );

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
		$class = 'icl_st_row_cb' . ( ! empty( $string['string_package_id'] ) ? ' icl_st_row_package' : '' );

		return '<td><input class="' . esc_attr( $class ) . '" type="checkbox" value="' . esc_attr( $string['string_id'] )
			   . '" data-language="' . esc_attr( $string['string_language'] ) . '" /></td>';
	}

	private function render_view_column( $string_id ) {
		if ( isset( $this->strings_in_page[ ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE ][ $string_id ] ) ) {

			$thickbox_url = $this->get_thickbox_url( WPML_ST_String_Tracking_AJAX_Factory::ACTION_POSITION_IN_SOURCE, $string_id );

			?>
			<a class="thickbox" title="<?php esc_attr_e( 'view in source', 'wpml-string-translation' ); ?>"
			   href="<?php echo esc_url( $thickbox_url ); ?>">
				<img src="<?php echo WPML_ST_URL; ?>/res/img/view-in-source.png" width="16" height="16"
					 alt="<?php esc_attr_e( 'view in page', 'wpml-string-translation' ); ?>"/>
			</a>
			<?php
		}

		if ( isset( $this->strings_in_page[ ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE ][ $string_id ] ) ) {
			$thickbox_url = $this->get_thickbox_url( WPML_ST_String_Tracking_AJAX_Factory::ACTION_POSITION_IN_PAGE, $string_id );

			?>
			<a class="thickbox" title="<?php esc_attr_e( 'view in page', 'wpml-string-translation' ); ?>"
			   href="<?php echo esc_url( $thickbox_url ); ?>">
				<img src="<?php echo WPML_ST_URL; ?>/res/img/view-in-page.png" width="16" height="16"
					 alt="<?php esc_attr_e( 'view in page', 'wpml-string-translation' ); ?>"/>
			</a>
			<?php
		}
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
				'width'     => 810,
				'height'    => 600,
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
		$tracked_source = Obj::prop( ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE, $this->strings_in_page );
		$tracked_page   = Obj::prop( ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE, $this->strings_in_page );

		return ( $tracked_source && Obj::prop( $string_id, $tracked_source ) )
			   || ( $tracked_page && Obj::prop( $string_id, $tracked_page ) );
	}
}

