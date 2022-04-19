<div id="gc-tablenav" class="tablenav top"></div>
<legend class="screen-reader-text"><?php _e( 'Import Items', 'gathercontent-import' ); ?></legend>
<table class="widefat striped gc-table">
	<thead>
		<tr>
			<td id="cb-for-gc" class="gc-field-th manage-column column-cb gc-check-column"><label class="screen-reader-text" for="gc-select-all-1"><?php _e( 'Select All', 'gathercontent-import' ); ?></label>
				<input <# if ( data.checked ) { #>checked="checked"<# } #> id="gc-select-all-1" type="checkbox">
			</td>
			<?php echo new self( 'table-headers', $this->args ); ?>
		</tr>
	</thead>
	<tbody>
		<tr><td colspan="6"><span class="gc-loader spinner is-active"></span></td></tr>
	</tbody>
	<tfoot>
		<tr>
			<td class="gc-field-th manage-column column-cb gc-check-column"><label class="screen-reader-text" for="gc-select-all-2"><?php _e( 'Select All', 'gathercontent-import' ); ?></label>
				<input <# if ( data.checked ) { #>checked="checked"<# } #> id="gc-select-all-2" type="checkbox">
			</td>
			<?php echo new self( 'table-headers', $this->args ); ?>
		</tr>
	</tfoot>
</table>
<?php
	// echo "<# console.log( 'data', data ); #>";
