<?php

namespace WPML\Settings;

use WPML\Settings\PostType\Automatic;
use WPML\Setup\Option;

class PostTypesUI extends \WPML_Custom_Types_Translation_UI {

	public function renderModeLabels() {
		parent::renderModeLabels();
		$style_column = Option::shouldTranslateEverything()
			? ''
			: 'display: none';
			?>
		<div class="wpml-flex-table-cell text-center translate-automatically" style="<?php echo esc_attr( $style_column ); ?>">
			<?php esc_html_e( 'Translate', 'sitepress' ); ?>
			<br/><?php esc_html_e( 'automatically', 'sitepress' ); ?>
		</div>
	<?php
	}

	public function render_row( $content_label, $name, $content_slug, $disabled, $current_translation_mode, $unlocked, $content_label_singular = false ) {
		parent::render_row( ...func_get_args() );

		$style_column = Option::shouldTranslateEverything()
			? ''
			: 'display: none';

		$isAutomatic = Automatic::isAutomatic( $content_slug );
		$name        = 'automatic_post_type[' . $content_slug . ']';
		$style       = $current_translation_mode !== WPML_CONTENT_TYPE_TRANSLATE ? 'display:none' : '';
		?>
		<div class="wpml-flex-table-cell text-center translate-automatically" style="<?php echo esc_attr( $style_column ); ?>">
			<div class="otgs-toggle-group" style="<?php echo esc_attr( $style ); ?>">
				<input type="checkbox"
					   class="otgs-switcher-input"
					   name="<?php echo esc_attr( $name ); ?>"
					   data-was-automatic-before-user-changes="<?php echo $isAutomatic ? '1' : '0'; ?>"
					<?php echo $isAutomatic ? 'checked' : ''; ?>
					<?php echo ( $disabled && ! $unlocked ) ? 'disabled' : ''; ?>
					   id="<?php echo esc_attr( $name ); ?>"
				>
				<label for="<?php echo esc_attr( $name ); ?>" class="otgs-switcher"
					   data-on="<?php esc_attr_e( 'YES', 'sitepress' ); ?>"
					   data-off="<?php esc_attr_e( 'NO', 'sitepress' ); ?>"
				>
				</label>
			</div>
		</div>
	<?php
	}
}
