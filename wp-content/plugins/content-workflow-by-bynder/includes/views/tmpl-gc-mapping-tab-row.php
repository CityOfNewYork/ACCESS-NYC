<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
/*******************************************
 * Component: Wrapper table - Open
 ***********************************************/ ?>
<# if ( data.typeName === 'component' ) { #>
<td class="component-td-wrapper component-td" colspan="2">
	<table id="{{data.name}}" class="component-table-wrapper">
		<tr>
			<# } #>


			<?php /*******************************************
			 * Standard TD Items - Lvl 1
			 ***********************************************/ ?>
			<?php // LEFT COLUMN - GC DATA ?>
			<td
			<# if (data.typeName === 'component') { #> class="gc-component gc-component-disabled column"<# } #>>
			<# if ( ( data.limit && data.limit_type ) || data.instructions || data.typeName ) { #>
			<?php // echo '<pre>{{ JSON.stringify(data, null, 2) }}</pre>' ?>
			<# if ( ( data.is_repeatable ) ) { #>
			<span class="dashicons dashicons-controls-repeat" title="Repeatable Field"></span>
			<# } #>
			<a title="<?php echo esc_html_x( 'Click to show additional details', 'About the Content Workflow object', 'content-workflow-by-bynder' ); ?>"
			   href="#"
			   class="gc-reveal-items <# if(data.component){ #>gc-reveal-items-component<# } #> dashicons-before dashicons-arrow-<# if ( data.expanded ) { #>down<# } else { #>right<# } #>"><strong>{{
					data.label }} <small>{{ data.subtitle }}</small></strong></a>
			<ul class="gc-reveal-items-list <# if ( !data.expanded ) { #>hidden<# } #>">
				<# if ( data.typeName ) { #>
				<li><strong><?php esc_html_e( 'Type:', 'content-workflow-by-bynder' ); ?></strong> {{ data.typeName }}
				</li>
				<# } #>

				<# if ( data.limit && data.limit_type ) { #>
				<li><strong><?php esc_html_e( 'Limit:', 'content-workflow-by-bynder' ); ?></strong> {{ data.limit }} {{
					data.limit_type }}
				</li>
				<# } #>

				<# if ( data.instructions ) { #>
				<li><strong><?php esc_html_e( 'Description:', 'content-workflow-by-bynder' ); ?></strong> {{
					data.instructions
					}}
				</li>
				<# } #>
			</ul>
			<# } else { #>
			<strong>{{ data.label }}</strong>
			<# } #>
</td>

<?php // RIGHT COLUMN - WP DATA FIELDS ?>
<td id="<# if (data.typeName !== 'component') { #>{{data.name}}<# } #>"
	class="<# if (data.typeName === 'component') { #>gc-component gc-component-disabled column<# } #> <# if ( data.is_repeatable ) { #>type-repeater<# } #>">
	<select class="wp-type-select type-select"
			name="<?php $this->output( 'option_base' ); ?>[mapping][{{ data.name }}][type]">
		<option
		<# if ( '' === data.field_type ) { #>selected="selected"<# } #>
		value=""><?php esc_html_e( 'Unused', 'content-workflow-by-bynder' ); ?></option>
		<?php do_action( 'cwby_field_type_option_underscore_template', $this ); ?>
	</select>
	<?php do_action( 'cwby_field_type_underscore_template', $this ); ?>
</td>


<?php /*******************************************
 * Component: Sub-Fields Row & Close Wrapper
 ***********************************************/ ?>
<#
var subfield_type_translate = {
'text'            : 'Rich Text',
'text_rich'       : 'Rich Text',
'text_plain'      : 'Plain Text',
'choice_radio'    : 'Muliple Choice',
'choice_checkbox' : 'Checkboxes',
'files'           : 'Attachment',
'attachment'      : 'Attachment',
};
#>

<# if ( data.component ) { index = 1; #>
</tr>

<?php // Component - Sub Fields Data ?>
<tr class="gc-component-row <# if ( !data.expanded ) { #>hidden<# } #>">
	<td class="component-td" colspan="2">

		<?php /** COMPONENT SUB-FIELDS: FROM GC **/ ?>
		<table class="component-table-inner">
			<# _.each(data.component.fields, function(field) { #>
			<?php // echo '<pre>{{ JSON.stringify(data.component.fields[index], null, 2) }}</pre>' ?>
			<tr class="{{ field.field_type }} <# if ( field?.metadata?.repeatable?.isRepeatable ) { #>repeater<# } #>">
				<td class="">
					<# if ( field.metadata && field.metadata.repeatable && field.metadata.repeatable.isRepeatable ) { #>
					<span class="dashicons dashicons-controls-repeat" title="Repeatable Field"></span>
					<# } #>
					<a title="<?php echo esc_html_x( 'Click to show additional details', 'About the Content Workflow object', 'content-workflow-by-bynder' ); ?>"
					   href="#" class="gc-reveal-items dashicons-before dashicons-arrow-right">
						<strong>{{ field.label }} <small>{{ field.subtitle }}</small></strong>
					</a>
					<ul class="gc-reveal-items-list gc-reveal-items-hidden hidden">
						<# if(( field.field_type )){ #>
						<li><strong><?php esc_html_e( 'Type:', 'content-workflow-by-bynder' ); ?></strong>
							<# if(field.field_type === 'text' && field.metadata && field.metadata.is_plain) { #>
							{{ subfield_type_translate['text_plain'] }}
							<# } else { #>
							{{ subfield_type_translate[field.field_type] }}
							<# } #>
						</li>
						<# } #>
						<# if(( field.instructions )){ #>
						<li><strong><?php esc_html_e( 'Instructions:', 'content-workflow-by-bynder' ); ?></strong> {{
							field.instructions }}
						</li>
						<# } #>
					</ul>
				</td>

				<?php /** COMPONENT SUB-FIELDS: WP SELECTs **/ ?>
				<td class="acf-components" data-set="{{ data.name }}">
					<select id="component-child-{{ data.name }}-{{ index }}" data-set="{{ data.name }}"
							class="component-child wp-subfield-select" data-index="{{index}}"
							name="<?php $this->output( 'option_base' ); ?>[mapping][{{ data.name }}][sub_fields][{{index}}]">
						<option value=""><?php esc_html_e( 'Unused', 'content-workflow-by-bynder' ); ?></option>
						<?php // do_action( 'cwby_field_type_option_underscore_template', $this ); ?>
					</select>
				</td>
			</tr>
			<# index = index + 1; }); #>
		</table>
	</td>

	<?php // Component - Wrapper table: Close ?>
</tr>
</table>
</td>
<# } #>
