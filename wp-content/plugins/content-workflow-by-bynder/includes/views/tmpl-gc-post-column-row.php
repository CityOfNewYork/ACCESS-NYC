<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<span class="gc-status-column" data-id="{{ data.id }}" data-item="{{ data.item }}" data-mapping="{{ data.mapping }}">
<# if ( data.status.display_name ) { #>
	<div class="gc-item-status">
		<?php
		/**
		 * Nothing to escape here as nothing is coming from the PHP side @see includes/views/underscore-data-status.php
		 */
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo new self( 'underscore-data-status' );
		?>
	</div>
<# } else { #>
	&mdash;
<# } #>
</span>
<?php
