<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<# if ( data[ data.step ] ) { #>
<div class="misc-pub-section">
	<label class="screen-reader-text" for="select-gc-next-step">{{ data.label }}</label>
	<select id="select-gc-next-step">
		<option value=""
		<# if ( ! data[ data.property ] ) { #>selected="selected"<# } #>>{{ data.label }}</option>
		<# _.each( data[ data.step ], function( thing ) { #>
		<option value="{{ thing.id }}"
		<# if ( data[ data.property ] == thing.id ) { #>selected="selected"<# } #>>{{ thing.name }}</option>
		<# } ); #>
	</select>
</div>
<# } else { #>
<p><?php $this->output( 'message' ); ?></p>
<# } #>
<div class="gc-major-publishing-actions gc-no-mapping">
	<div class="gc-publishing-action">
		<# if ( data.waiting ) { #>
		<span class="spinner is-active"></span>
		<# } else { #>
		<# if ( data.step ) { #>
		<button id="gc-map-cancel" type="button" class="button gc-button-secondary aligncenter">
			<?php esc_html_e( 'Cancel', 'content-workflow-by-bynder' ); ?>
		</button>
		<button
		<# if ( data.btnDisabled ) { #>disabled="disabled"<# } #>id="gc-map" type="button" class="button
		gc-button-primary aligncenter">
		<# if ( 'mappings' === data.step ) { #>
		<?php esc_html_e( 'Save Mapping', 'content-workflow-by-bynder' ); ?>
		<# } else { #>
		<?php esc_html_e( 'Next', 'content-workflow-by-bynder' ); ?>
		<# } #>
		</button>
		<# } else { #>
		<button id="gc-map" type="button"
				class="button gc-button-primary aligncenter"><?php esc_html_e( 'Map to Content Workflow Template', 'content-workflow-by-bynder' ); ?></button>
		<# } #>
		<# } #>
	</div>
	<div class="clear"></div>
</div>
<?php
// echo "<# console.log( 'data', data ); #>";
