<?php wp_nonce_field( GATHERCONTENT_SLUG, 'gc-edit-nonce' ); ?>

<div id="gc-related-data" data-id="<?php $this->output( 'post_id' ); ?>" data-item="<?php $this->output( 'item_id' ); ?>" data-mapping="<?php $this->output( 'mapping_id' ); ?>" class="no-js gathercontent-admin">
	<?php if ( $this->get( 'mapping_id' ) ) : ?>
		<p><span class="spinner is-active"></span>  <?php esc_html_e( 'Loading...', 'gathercontent-importer' ); ?></p>
	<?php else: ?>
		<p><?php printf( esc_html__( 'This %s does not have an associated item or Template Mapping.', 'gathercontent-importer' ), $this->get( 'label' ) ); ?></p>
		<div class="gc-major-publishing-actions gc-no-mapping">
			<div class="gc-publishing-action">
				<span class="spinner"></span>
				<button id="gc-map" type="button" class="button gc-button-primary aligncenter"><?php esc_html_e( 'Map to GatherContent Template', 'gathercontent-importer' ); ?></button>
			</div>
			<div class="clear"></div>
		</div>
	<?php endif; ?>
</div>
