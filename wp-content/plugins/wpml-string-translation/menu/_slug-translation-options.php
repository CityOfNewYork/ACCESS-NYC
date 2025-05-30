<?php
	$slug_translation_settings = new WPML_ST_Slug_Translation_Settings();
?>
<div class="wpml-section" id="ml-content-setup-sec-4">

	<div class="wpml-section-header">
		<h3><?php esc_html_e( 'Slug translations', 'wpml-string-translation' ); ?></h3>
	</div>

	<div class="wpml-section-content">

		<form name="icl_slug_translation" id="icl_slug_translation" action="">
			<?php wp_nonce_field( 'icl_slug_translation_nonce', '_icl_nonce' ); ?>
			<p>
				<label>
					<input class="wpml-checkbox-native" type="checkbox" name="icl_slug_translation_on" value="1" <?php checked( 1, $slug_translation_settings->is_enabled(), true ); ?>  />&nbsp;
					<?php esc_html_e( 'Translate base slugs of custom post types and taxonomies (via WPML -> Taxonomy translation).', 'wpml-string-translation' ); ?>
				</label>
			</p>

			<p class="buttons-wrap">
				<span class="icl_ajx_response" id="icl_ajx_response_sgtr"></span>
				<input type="submit" class="button-primary wpml-button base-btn" value="<?php esc_html_e( 'Save', 'wpml-string-translation' ); ?>" />
			</p>
		</form>
	</div> <!-- .wpml-section-content -->

</div> <!-- .wpml-section -->
