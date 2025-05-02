<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="tablenav-pages one-page">
	<span class="displaying-num"><span
			class="gc-item-count">{{ data.count }}</span> <?php esc_html_e( 'items', 'content-workflow-by-bynder' ); ?></span>
	<# if ( data.selected ) { #>
	<strong class="selected-num">| <span
			class="gc-item-count">{{ data.selected }}</span> <?php esc_html_e( 'selected', 'content-workflow-by-bynder' ); ?>
	</strong>
	<# } #>
</div>
<br class="clear">
