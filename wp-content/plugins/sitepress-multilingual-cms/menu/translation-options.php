<div class="wrap">
	<h2><?php echo __( 'Settings', 'sitepress' ); ?></h2>
	<br />

	<?php
	require __DIR__ . '/_posts_sync_options.php';
	require __DIR__ . '/_login_translation_options.php';

	if ( defined( 'WPML_ST_PATH' ) ) {
		include WPML_ST_PATH . '/menu/_slug-translation-options.php';
	}
	?>

	<br clear="all" />
	<?php
	require __DIR__ . '/_custom_types_translation.php';

	do_action( 'icl_tm_menu_mcsetup' );

	do_action( 'icl_menu_footer' );
	?>
</div>
