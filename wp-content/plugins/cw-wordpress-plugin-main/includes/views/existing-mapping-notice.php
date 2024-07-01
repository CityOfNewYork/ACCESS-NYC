<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="notice notice-info is-dismissible">
	<p><?php printf( esc_html__( 'NOTE: There can be only one %1$s per project template. You are editing an existing mapping (ID: %2$d).', 'content-workflow-by-bynder' ), esc_html($this->get( 'name' )), esc_html($this->get( 'id' )) ); ?></p>
</div>
