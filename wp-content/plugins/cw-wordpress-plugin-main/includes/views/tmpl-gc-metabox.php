<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="misc-pub-section gc-item-name">
	<span
		class="dashicons dashicons-edit"></span> <?php echo esc_html_x( 'Item:', 'Content Workflow item name', 'content-workflow-by-bynder' ); ?>
	<# if ( data.item ) { #><a href="<?php $this->output( 'url' ); ?>item/{{ data.item }}" target="_blank"
							   title="<?php esc_attr_e( 'Item ID:', 'content-workflow-by-bynder' ); ?> {{ data.item }}"><#
		} #>{{ data.itemName }}<# if ( data.item ) { #></a><# } #>
</div>

<div class="misc-pub-section misc-gc-updated">
	<span
		class="dashicons dashicons-calendar"></span> <?php echo esc_html_x( 'Last Updated:', 'Content Workflow updated date', 'content-workflow-by-bynder' ); ?>
	<b>
		<?php
		/**
		 * Everything that needs escaping is escaped in @see includes/views/underscore-data-updated.php
		 */
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo new self( 'underscore-data-updated', $this->args );
		?>
	</b>
</div>

<div class="misc-pub-section misc-pub-gc-mapping">
	<span class="dashicons dashicons-media-document"></span>
	<?php esc_html_e( 'Mapping Template:', 'content-workflow-by-bynder' ); ?>
	<strong>
		<?php
		/**
		 * Everything that needs escaping is escaped in @see includes/views/underscore-data-mapping-name.php
		 */
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo new self( 'underscore-data-mapping-name' );
		?>
	</strong>
</div>

<div class="gc-major-publishing-actions">
	<div class="gc-publishing-action">
		<?php // $this->output( 'refresh_link' ); ?>
		<span
			class="spinner <# if ( data.mappingStatusId && data.mappingStatusId in { syncing : 1, starting: 1 } ) { #>is-active<# } #>"></span>
		<button id="gc-disconnect" type="button" class="button gc-button-danger alignright"
		<# if ( ! data.mapping ) { #>disabled="disabled"<# }
		#>><?php esc_html_e( 'Disconnect', 'content-workflow-by-bynder' ); ?></button>
		<button id="gc-push" type="button" class="button gc-button-primary alignright"
		<# if ( ! data.mapping ) { #>disabled="disabled"<# }
		#>><?php esc_html_e( 'Push', 'content-workflow-by-bynder' ); ?></button>
		<button id="gc-pull" type="button" class="button gc-button-primary alignright"
		<# if ( ! data.mapping || ! data.item ) { #>disabled="disabled"<# }
		#>><?php esc_html_e( 'Pull', 'content-workflow-by-bynder' ); ?></button>
	</div>
	<div class="clear"></div>
</div>
<?php
// echo "<# console.log( 'data', data ); #>";
