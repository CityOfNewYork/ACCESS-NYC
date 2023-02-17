<?php
namespace EnableMediaReplace;

//use \EnableMediaReplace\UIHelper;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;

if (! apply_filters('emr/upsell', current_user_can('install_plugins')))
{
	 return;
}

	#wp_nonce_field('enable-media-replace');
  $plugins = get_plugins();

  $spio_installed = isset($plugins['shortpixel-image-optimiser/wp-shortpixel.php']);
  $spio_active = is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php');


	$spai_installed = isset($plugins['shortpixel-adaptive-images/short-pixel-ai.php']);
	$spai_active = is_plugin_active('shortpixel-adaptive-images/short-pixel-ai.php');

	$envira_installed = isset($plugins['envira-gallery-lite/envira-gallery-lite.php']);
	$envira_active = is_plugin_active('envira-gallery-lite/envira-gallery-lite.php');
	$envira_pro_active = is_plugin_active('envira-gallery/envira-gallery.php');


?>

	<input type="hidden" id='upsell-nonce' value="<?php echo wp_create_nonce( 'emr-plugin-install' ); ?>" />
	<input type="hidden" id='upsell-nonce-activate' value="<?php echo wp_create_nonce( 'emr-plugin-activate' ); ?>" />
  <section class='upsell-wrapper'>

		<!--- SHORTPIXEL -->
    <?php if(! $spio_active): ?>

    <div class='shortpixel-offer spio'>
      <div class='img-wrapper'>
          <img width="40" height="40" src="<?php echo $emr->getPluginURL('img/sp-logo-regular.svg') ?>" alt="ShortPixel">
      </div>
			<h4 class="grey">
		     <?php echo esc_html__("ShortPixel Image Optimizer", "enable-media-replace"); ?>
			 </h4>
			<h3 class="red ucase"><?php _e('Is your website slow?', 'enable-media-replace'); ?></h3>
			<br>
			<h3 class="cyan ucase"><?php printf(__('Optimize all images %s automatically', 'enable-media-replace'), '<br>'); ?></h3>
      <p class='button-wrapper '>
			<?php
			  $install_class = (! $spio_installed) ? '' : 'hidden';
				$activate_class = ($spio_installed && ! $spio_active) ? '' : 'hidden';
			?>
					<a class="emr-installer <?php echo $install_class ?>"  data-action="install" data-plugin="spio" href="javascript:void(0)">
						<?php _e('INSTALL NOW', 'enable-media-replace') ?>
					</a>

				<a class='emr-activate <?php echo $activate_class ?>' data-action="activate" data-plugin="spio" href="javascript:void(0)">
					<?php _e('ACTIVATE', 'enable-media-replace') ?>
				</a>

				<h4 class='emr-activate-done hidden' data-plugin='spio'><?php _e('Shortpixel activated!', 'enable-media-replace'); ?></h4>
			</p>

    </div>
	<?php endif; ?>
	<!--- // SHORTPIXEL -->

		<!--- SHORTPIXEL AI -->
    <?php if(! $spai_active): ?>

    <div class='shortpixel-offer spai'>
      <div class='img-wrapper'>
          <img width="40" height="40" src="<?php echo $emr->getPluginURL('img/spai-logo.svg') ?>" alt="ShortPixel">
      </div>
			<h4 class="grey">
		     <?php echo esc_html__("ShortPixel Adaptive Images", "enable-media-replace"); ?>
			 </h4>


			<h3 class="cyan ucase"><?php printf(__('Start Serving %s Optimized, %s Nextgen images %s From a global CDN', 'enable-media-replace'), '<br>', '<br>', '<br>'); ?></h3>
			<h3 class="red ucase"><?php _e('In Minutes', 'enable-media-replace'); ?></h3>
      <p class='button-wrapper '>
			<?php
			  $install_class = (! $spai_installed) ? '' : 'hidden';
				$activate_class = ($spai_installed && ! $spai_active) ? '' : 'hidden';
			?>
					<a class="emr-installer <?php echo $install_class ?>"  data-action="install" data-plugin="spai" href="javascript:void(0)">
						<?php _e('INSTALL NOW', 'enable-media-replace') ?>
					</a>

				<a class='emr-activate <?php echo $activate_class ?>' data-action="activate" data-plugin="spai" href="javascript:void(0)">
					<?php _e('ACTIVATE', 'enable-media-replace') ?>
				</a>

				<h4 class='emr-activate-done hidden' data-plugin='spai'><?php _e('Shortpixel Adaptive Images activated!', 'enable-media-replace'); ?></h4>
			</p>

    </div>
	<?php endif; ?>
	<!--- // SHORTPIXEL -->

	<!--- WPSO -->
    <div class='shortpixel-offer site-speed'>
      <p class='img-wrapper'><img width="40" height="40" src="<?php echo $emr->getPluginURL('img/sp-logo-wink.svg'); ?>" alt='ShortPixel'></p>
      <h3><?php printf(__('GET AN ASSESSMENT FOR %s YOUR WEBSITE %s AND %s %s FIND OUT HOW TO MAKE IT FASTER %s', 'enable-media-replace'),'<br>', '<br>','<br>', '<span class="red">','</span>'); ?></h3>

      <p class='button-wrapper'><a href='https://wso.shortpixel.com/?utm_source=EMR' target="_blank"><?php _e('FIND OUT MORE', 'enable-media-replace') ?></a></p>
    </div>
	<!--- // WPSO -->

		<!--- ENVIRA temprary deactivated
		<?php if (! $envira_pro_active): ?>
  <div class='envira-shortpixel-install shortpixel-offer'>

	<p class='img-wrapper'><img src="<?php echo $emr->getPluginURL('img/envira-logo.png'); ?>" alt='Envira Gallery'></p>
	<p><?php esc_html_e('Create beautiful, fast-loading photo & video galleries for your site in minutes.', 'enable-media-replace' ); ?></p>

		 <?php
			  $install_class = (! $envira_installed) ? '' : 'hidden';
				$activate_class = ($envira_installed && ! $envira_active) ? '' : 'hidden';
		 ?>
		 <?php if (! $envira_active) { ?>
	   <p class='button-wrapper envira-emr-button-wrap'>

			 <a class="emr-installer button button-envira-emr emr-install-envira <?php echo $install_class ?>"  data-action="install" data-plugin="envira" href='javascript:void(0)'><?php _e('Install now', 'enable-media-replace') ?></a>

			 <a class="emr-activate button button-envira-emr emr-activate-envira <?php echo $activate_class ?>" href='javascript:void(0)' data-action="activate" data-plugin="envira" ><?php _e('Activate', 'enable-media-replace') ?></a>

				<h4 class='emr-activate-done hidden' data-plugin='envira'><?php _e('Envira Gallery activated!', 'enable-media-replace'); ?></h4>

		</p>

		<?php } else {
				if ( is_plugin_active( 'envira-gallery-lite/envira-gallery-lite.php' ) ) {
			?>
				<p class='button-wrapper envira-emr-button-wrap'><a class="button button-envira-emr" href='https://enviragallery.com/pricing' target="_blank"><?php _e('Get Pro', 'enable-media-replace') ?></a></p>

				<?php } else { ?>

				<?php }
			} ?>
    </div>
    <style>
	    .envira-emr-button-wrap {
		text-align: center;
	}
	.button-envira-emr {
		background-color: #7cc048 !important;
		border: none !important;
		color: rgb(255,255,255) !important;
		font-size: 21px !important;
	}
	.button-envira-emr:hover {
		background-color: #95dc5e !important;
	}
    </style>
	<?php endif; // envira ?>
-->

</section>
