<?php
namespace EnableMediaReplace;

use EnableMediaReplace\EnableMediaReplacePlugin;
use EnableMediaReplace\UIHelper;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$date     = new \dateTime();
$uiHelper = emr()->uiHelper();

$attachment_id = $view->attachment->ID;
//$attachment_id = intval($_GET['attachment_id']);
//$attachment = get_post($attachment_id);

//$replacer = new Replacer($attachment_id);
//$file = $replacer->getSourceFile();

$uiHelper->setPreviewSizes();
$uiHelper->setSourceSizes($attachment_id);

$base_image = $uiHelper->getPreviewImage($attachment_id, $view->sourceFile );
$replace_image = $uiHelper->getPreviewImage(-1, $view->sourceFile, array('remove_bg_ui' => true) );

$formurl = $uiHelper->getFormUrl($attachment_id, 'do_background_replace');
//$formurl = wp_nonce_url( $formurl, "do_background_replace" );

$linebreak = '%0D%0A';
$linebreak_double = $linebreak . $linebreak;
$email_subject = __('Bad remove of background report', 'enable-media-replace');
$email_body = sprintf(__('Hello! %s This is a report of a background removal that did not go well %s Url: {url} %s Settings : {settings} %s Thank you! %s', 'enable-media-replace'), $linebreak_double, $linebreak_double, $linebreak, $linebreak_double, $linebreak_double);

$replace_url = add_query_arg(array(
'page' => 'enable-media-replace/enable-media-replace.php',
'action' => 'media_replace',
'attachment_id' => $attachment_id,
), admin_url("upload.php"));

?>

<div class="wrap emr_upload_form" id="remove-background-form">

	<form id="emr_replace_form" enctype="multipart/form-data" method="POST" action="<?php
	echo $formurl; ?>" >

	<?php wp_nonce_field('media_remove_background', 'emr_nonce'); ?>


	<input type="hidden" name="ID" value="<?php echo intval($attachment_id); ?>" />
	<input type='hidden' name='key' value='' />

		<div class="editor-wrapper" >
			<section class='image_chooser wrapper'>
				<div class='section-header'> <?php esc_html_e( 'Remove Media Background', 'enable-media-replace' ); ?></div>
				<div class='image_previews'>
						<?php echo $base_image; ?>
						<?php echo $replace_image ?>

				</div>

				<div class='bad-button'>
						<a href="" data-link="mailto:support@shortpixel.com?subject=<?php echo esc_attr($email_subject) ?>&body=<?php echo esc_attr($email_body) ?>" id="bad-background-link" class="button"><?php esc_html_e('Report bad background removal','enable-media-replace'); ?></a>

				</div>

			</section>

			<p><a href="<?php echo esc_attr(wp_nonce_url($replace_url, 'media_replace')); ?>">
					<?php esc_html_e('Replace this image with another one instead!', 'enable-media-replace'); ?>
			</a></p>
			<div class="option-flex-wrapper">
				<section class="replace_type wrapper">
					<div class="section-header"><?php esc_html_e('Background Removal Options', 'enable-media-replace'); ?></div>
					<div class="option replace ">
						<p>
							<?php esc_html_e('If a CDN is used, remember to clear the cache for this image!', 'enable-media-replace'); ?>
						</p>
						<label for="transparent_background">
							<input id="transparent_background" type="radio" name="background_type" value="transparent" <?php checked('transparent', $view->settings['bg_type']); ?> >
							<?php esc_html_e('Transparent/white background', 'enable-media-replace'); ?>
						</label>
						<p class="howto">
							<?php esc_html_e('Returns a transparent background if it is a PNG image, or a white one if it is a JPG image.', 'enable-media-replace'); ?>
						</p>
					</div>
					<div class="option searchreplace">
						<label for="solid_background">
							<input id="solid_background" type="radio" name="background_type" value="solid" <?php checked('solid', $view->settings['bg_type']); ?>>
							<?php esc_html_e('Solid background', 'enable-media-replace'); ?>
						</label>
						<p class="howto">
							<?php esc_html_e('If you select this option, the image will have a solid color background and you can choose the color code from the color picker below.', 'enable-media-replace'); ?>
						</p>
						<div id="solid_selecter" style="display:none;">
							<label for="bg_display_picker">
								<p><?php esc_html_e('Background Color:','enable-media-replace'); ?> <strong>
									<span style="text-transform: uppercase;" id="color_range">
										<?php echo esc_attr($view->settings['bg_color']); ?></span>
									</strong>
								</p>
								<input type="color" value="<?php echo esc_attr($view->settings['bg_color']); ?>" name="bg_display_picker" id="bg_display_picker" />
								<input type="hidden"  value="<?php echo esc_attr($view->settings['bg_color']); ?>" name="bg_color" id="bg_color" />
							</label>
							<hr>
							<label for="bg_transparency">
								<p><?php esc_html_e('Opacity:', 'enable-media-replace'); ?>
									<strong>
										<span id="transparency_range"><?php echo esc_attr($view->settings['bg_transparency']); ?></span>%</strong>
								</p>
								<input type="range" min="0" max="100" value="<?php echo esc_attr($view->settings['bg_transparency']); ?>" id="bg_transparency" />
							</label>
						</div>
					</div>
				</section>


			</div>
			<button type="button" class="button button-primary" id="remove_background_button"><?php esc_html_e('Preview', 'enable-media-replace'); ?></button>
			<button type="submit" class="button button-primary" id="replace_image_button" disabled><?php esc_html_e('Replace', 'enable-media-replace'); ?></button>
			<a class="button" href="javascript:history.back()"><?php esc_html_e('Cancel', 'enable-media-replace'); ?></a>
		</div> <!--- editor wrapper -->
		<?php include_once( 'upsell.php' ); ?>
	</form>
</div>
