<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="gc-auth-enabled">
	<p>
		<?php
		echo esc_html__( 'It appears you have enabled ', 'content-workflow-by-bynder' ) .
			 '<a href="http://www.htaccesstools.com/htaccess-authentication/">' .
			 esc_html__( 'HTTP authentication', 'content-workflow-by-bynder' ) .
			 '</a>' .
			 esc_html__( ' for this site.', 'content-workflow-by-bynder' );
		?>
		<br>
		<?php esc_html_e( 'Please enter the authentication username and password in order for this plugin to work.', 'content-workflow-by-bynder' ); ?>
	</p>
	<p class="description">
		<?php esc_html_e( 'If you\'re not sure what this is, please contact your site adminstrator.', 'content-workflow-by-bynder' ); ?>
	</p>
</div>
