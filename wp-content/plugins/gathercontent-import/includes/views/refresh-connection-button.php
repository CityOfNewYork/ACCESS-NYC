<small><a href="<?php $this->output( 'flush_url', 'esc_url' ); ?>" class="button dashicons dashicons-controls-repeat gc-refresh-connection" title="<?php esc_attr_e( 'Refresh data from GatherContent?', 'gathercontent-import' ); ?>"></a></small>
<?php if ( $this->get( 'redirect_url' ) ) : ?>
	<script type="text/javascript">window.location = '<?php $this->output( 'redirect_url', 'esc_url_raw' ); ?>';</script>
<?php endif; ?>
