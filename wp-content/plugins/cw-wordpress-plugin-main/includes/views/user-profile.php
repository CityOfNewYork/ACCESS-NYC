<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<hr>
<div class="gc-profile">
	<img src="<?php $this->output( 'avatar' ); ?>" class="gc-avatar">
	<div>
		<h3 class="gc-hello"><?php printf( esc_html__( 'Hello %s!', 'content-workflow-by-bynder' ), esc_html( $this->get( 'first_name' ) ) ); ?></h3>
		<div><?php $this->output( 'message' ); ?></div>
	</div>
</div>
<hr>
<p>
	<?php
	echo esc_html__( 'For more information: ', 'content-workflow-by-bynder' ) .
		 '<a href="https://www.bynder.com/en/products/content-workflow/" target="_blank">https://www.bynder.com/en/products/content-workflow/</a>.';
	?>
</p>
