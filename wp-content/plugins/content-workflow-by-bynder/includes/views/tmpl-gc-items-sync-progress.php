<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<legend class="screen-reader-text"><?php esc_html_e( 'Items Import Progress', 'content-workflow-by-bynder' ); ?></legend>
<table class="gc-sync-table">
	<tbody>
	<tr>
		<th id="progress">
			<div class="gc-progress-bar">
				<div class="gc-progress-bar-partial" style="width: {{ data.percent }}%"><span>{{ data.percent }}%</span>
				</div>
				<button type="button" class="notice-dismiss gc-cancel-sync"
						title="<?php esc_html_e( 'Cancel Import', 'content-workflow-by-bynder' ); ?>"><span
						class="screen-reader-text"><?php esc_html_e( 'Cancel Import', 'content-workflow-by-bynder' ); ?></span>
				</button>
			</div>
		</th>
	</tr>
	<# if ( data.loader ) { #>
	<tr id="gc-reload-spinner">
		<td><span class="gc-loader spinner is-active"> Please Wait&hellip;</span></td>
	</tr>
	<# } #>
	</tbody>
</table>
