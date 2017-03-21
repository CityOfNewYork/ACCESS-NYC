<span class="dashicons dashicons-post-status"></span> <?php echo esc_html_x( 'Status:', 'GatherContent item status', 'gathercontent-importer' ); ?>
<# if ( data.status && data.status.name ) { #>
<span class="gc-metabox-status">
	<?php echo new self( 'underscore-data-status' ); ?>
</span>
<a href="#gc_status" class="edit-gc-status"><span aria-hidden="true"><?php echo esc_html_x( 'Edit', 'Edit the GatherContent item status', 'gathercontent-importer' ); ?></span> <span class="screen-reader-text"><?php esc_html_e( 'Edit GatherContent status', 'gathercontent-importer' ); ?></span></a>
<div id="gc-post-status-select" style="display:none;">
	<div id="gc-status-selec2"><span class="spinner is-active"></span></div>
	<button type="button" class="save-gc-status button"><?php echo esc_html_x( 'Update', 'Update the GatherContent item status', 'gathercontent-importer' ); ?></button>
	<a href="#gc-set-status" class="cancel-gc-status button-cancel"><?php echo esc_html_x( 'Cancel', 'Cancel editing the GatherContent item status', 'gathercontent-importer' ); ?></a>
</div>
<# } else { #>
<?php esc_html_e( 'N/A', 'gathercontent-importer' ); ?>
<# } #>
