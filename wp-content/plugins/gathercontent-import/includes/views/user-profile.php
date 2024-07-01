<hr>
<div class="gc-profile">
	<img src="<?php $this->output( 'avatar' ); ?>" class="gc-avatar">
	<div>
		<h3 class="gc-hello"><?php printf( esc_html__( 'Hello %s!', 'gathercontent-import' ), $this->get( 'first_name' ) ); ?></h3>
		<div><?php $this->output( 'message' ); ?></div>
	</div>
</div>
<hr>
<p><?php printf( __( 'For more information: <a href="%s" target="_blank">https://gathercontent.com/how-it-works</a>.', 'gathercontent-import' ), 'https://gathercontent.com/how-it-works' ); ?></p>
