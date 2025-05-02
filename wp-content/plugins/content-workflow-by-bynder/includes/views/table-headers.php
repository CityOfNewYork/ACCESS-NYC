<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php foreach ( $this->get( 'headers' ) as $sort_key => $label ) : ?>
	<?php
	/**
	 * This is escaped at the end of the flow @see includes/views/table-header.php
	 */
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo new self( 'table-header', [ 'sort_key' => $sort_key, 'label' => $label, ] );
	?>
<?php
endforeach;
