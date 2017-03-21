<legend class="screen-reader-text"><?php _e( 'Items Import Progress', 'gathercontent-import' ); ?></legend>
<table class="widefat">
	<tbody>
		<tr>
			<th id="progress">
				<div class="gc-progress-bar">
					<div class="gc-progress-bar-partial" style="width: {{ data.percent }}%"><span>{{ data.percent }}%</span></div>
					<button type="button" class="notice-dismiss gc-cancel-sync" title="<?php _e( 'Cancel Import', 'gathercontent-import' ); ?>"><span class="screen-reader-text"><?php _e( 'Cancel Import', 'gathercontent-import' ); ?></span></button>
				</div>
			</th>
		</tr>
	</tbody>
</table>
