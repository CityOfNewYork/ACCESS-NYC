<td>
	<# if ( ( data.limit && data.limit_type ) || data.instructions || data.typeName ) { #>
	<# if ( ( data.is_repeatable ) ) { #>
		<span class="dashicons dashicons-controls-repeat" title="Repeatable Field"></span>
	<# } #>
	<a title="<?php _ex( 'Click to show additional details', 'About the GatherContent object', 'gathercontent-import' ); ?>" href="#" class="gc-reveal-items dashicons-before dashicons-arrow-<# if ( data.expanded ) { #>down<# } else { #>right<# } #>"><strong>{{ data.label }} <small>{{ data.subtitle }}</small></strong></a>
	<ul class="gc-reveal-items-list <# if ( ! data.expanded ) { #>hidden<# } #>">
		<# if ( data.typeName ) { #>
		<li><strong><?php _e( 'Type:', 'gathercontent-import' ); ?></strong> {{ data.typeName }}</li>
		<# } #>
		<# if ( data.limit && data.limit_type ) { #>
		<li><strong><?php _e( 'Limit:', 'gathercontent-import' ); ?></strong> {{ data.limit }} {{ data.limit_type }} </li>
		<# } #>
		<# if ( data.instructions ) { #>
		<li><strong><?php _e( 'Description:', 'gathercontent-import' ); ?></strong> {{ data.instructions }}</li>
		<# } #>
	</ul>
	<# } else { #>
	<strong>{{ data.label }}</strong>
	<# } #>
</td>
<td>
	<select class="wp-type-select" name="<?php $this->output( 'option_base' ); ?>[mapping][{{ data.name }}][type]">
		<option <# if ( '' === data.field_type ) { #>selected="selected"<# } #> value=""><?php _e( 'Unused', 'gathercontent-import' ); ?></option>
		<?php do_action( 'gathercontent_field_type_option_underscore_template', $this ); ?>
	</select>

	<?php do_action( 'gathercontent_field_type_underscore_template', $this ); ?>
</td>
