<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<fieldset class="inline-edit-col-right inline-edit-gc-status">
	<?php wp_nonce_field( GATHERCONTENT_SLUG, 'gc-edit-nonce' ); ?>
	<div class="inline-edit-col column-<?php $this->output( 'column_name' ); ?>">
		<label class="inline-edit-group">
			<span class="title"><?php esc_html_e( 'Content Workflow Status', 'content-workflow-by-bynder' ); ?></span>
			<span class="gc-status-select2"><span class="spinner"></span></span>
		</label>
	</div>
</fieldset>

