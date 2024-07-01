<div class="misc-pub-section gc-item-name">
	<span class="dashicons dashicons-edit"></span> <?php echo esc_html_x( 'Item:', 'GatherContent item name', 'gathercontent-importer' ); ?> <# if ( data.item ) { #><a href="<?php $this->output( 'url' ); ?>item/{{ data.item }}" target="_blank" title="<?php esc_attr_e( 'Item ID:', 'gathercontent-importer' ); ?> {{ data.item }}"><# } #>{{ data.itemName }}<# if ( data.item ) { #></a><# } #>
</div>

<div class="misc-pub-section misc-gc-updated">
	<span class="dashicons dashicons-calendar"></span> <?php echo esc_html_x( 'Last Updated:', 'GatherContent updated date', 'gathercontent-importer' ); ?> <b><?php echo new self( 'underscore-data-updated', $this->args ); ?></b>
</div>

<div class="misc-pub-section misc-pub-gc-mapping">
	<span class="dashicons dashicons-media-document"></span>
	<?php esc_html_e( 'Mapping Template:', 'gathercontent-importer' ); ?>
	<strong><?php echo new self( 'underscore-data-mapping-name' ); ?></strong>
</div>

<div class="gc-major-publishing-actions">
	<div class="gc-publishing-action">
		<?php // $this->output( 'refresh_link' ); ?>
		<span class="spinner <# if ( data.mappingStatusId && data.mappingStatusId in { syncing : 1, starting: 1 } ) { #>is-active<# } #>"></span>
		<button id="gc-disconnect" type="button" class="button gc-button-danger alignright" <# if ( ! data.mapping ) { #>disabled="disabled"<# } #>><?php esc_html_e( 'Disconnect', 'gathercontent-importer' ); ?></button>
		<button id="gc-push" type="button" class="button gc-button-primary alignright" <# if ( ! data.mapping ) { #>disabled="disabled"<# } #>><?php esc_html_e( 'Push', 'gathercontent-importer' ); ?></button>
		<button id="gc-pull" type="button" class="button gc-button-primary alignright" <# if ( ! data.mapping || ! data.item ) { #>disabled="disabled"<# } #>><?php esc_html_e( 'Pull', 'gathercontent-importer' ); ?></button>
	</div>
	<div class="clear"></div>
</div>
<?php
	// echo "<# console.log( 'data', data ); #>";
