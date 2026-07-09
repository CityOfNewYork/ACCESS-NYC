<?php
namespace EnableMediaReplace;

use EnableMediaReplace\EnableMediaReplacePlugin;
use EnableMediaReplace\UIHelper;
use EnableMediaReplace\ApiKeyManager;

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

$apiKeyManager   = ApiKeyManager::getInstance();
$emr_has_api_key = $apiKeyManager->hasApiKey();
$emr_masked_key  = $apiKeyManager->getMaskedKey();
$emr_can_manage  = current_user_can('manage_options');

?>

<div id="emr-bg-notice" class="notice notice-error is-dismissible" style="display:none;">
	<p></p>
	<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e('Dismiss this notice.', 'enable-media-replace'); ?></span></button>
</div>

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

			<?php if ($emr_can_manage) : ?>
			<section class="emr-sp-account wrapper">
				<div class="section-header"><?php esc_html_e('ShortPixel Account', 'enable-media-replace'); ?></div>
				<div class="emr-sp-account-body">
					<p class="emr-sp-intro">
						<?php
						if ($emr_has_api_key) {
							esc_html_e('Your ShortPixel API Key is connected. Background Removal is unlimited as long as your Unlimited or Unlimited AI plan is active.', 'enable-media-replace');
						} else {
							esc_html_e('Background Removal is limited to 100 images on the Free plan. Add your ShortPixel Unlimited or Unlimited AI plan API Key to remove that limit.', 'enable-media-replace');
						}
						?>
					</p>

					<?php if ($emr_has_api_key) : ?>
						<div class="emr-sp-key-card">
							<div class="emr-sp-key-status">
								<div class="emr-sp-status-dot"></div>
								<div>
									<span class="emr-sp-key-label"><?php esc_html_e('API Key', 'enable-media-replace'); ?></span>
									<span class="emr-sp-key-value"><?php echo esc_html($emr_masked_key); ?></span>
								</div>
							</div>
							<div class="emr-sp-key-actions">
								<a href="#" id="emr-change-key-toggle" class="button emr-sp-change-btn">
									<?php esc_html_e('Change Key', 'enable-media-replace'); ?>
								</a>
								<a href="#" id="emr-remove-key" class="button emr-sp-remove-btn">
									<?php esc_html_e('Remove Key', 'enable-media-replace'); ?>
								</a>
							</div>
						</div>

						<div id="emr-change-key-form" class="emr-sp-edit-form" style="display:none;">
							<label for="emr-new-api-key"><?php esc_html_e('New API Key', 'enable-media-replace'); ?></label>
							<div class="emr-sp-edit-row">
								<input type="text"
								       id="emr-new-api-key"
								       placeholder="<?php esc_attr_e('20-character API Key', 'enable-media-replace'); ?>"
								       maxlength="20"
								       autocomplete="off">
								<button id="emr-update-api-key" class="button emr-sp-save-btn">
									<?php esc_html_e('Save', 'enable-media-replace'); ?>
								</button>
								<a href="#" id="emr-change-key-cancel" class="emr-sp-cancel-link">
									<?php esc_html_e('Cancel', 'enable-media-replace'); ?>
								</a>
							</div>
						</div>
					<?php else : ?>
						<div class="emr-sp-edit-form">
							<label for="emr-new-api-key"><?php esc_html_e('ShortPixel API Key', 'enable-media-replace'); ?></label>
							<div class="emr-sp-edit-row">
								<input type="text"
								       id="emr-new-api-key"
								       placeholder="<?php esc_attr_e('Enter your 20-character API Key', 'enable-media-replace'); ?>"
								       maxlength="20"
								       autocomplete="off">
								<button id="emr-update-api-key" class="button emr-sp-save-btn">
									<?php esc_html_e('Activate', 'enable-media-replace'); ?>
								</button>
							</div>
							<p class="description">
								<?php
								printf(
									wp_kses(
										__('Don\'t have a key yet? <a href="%s" target="_blank" rel="noopener">Get one here</a>.', 'enable-media-replace'),
										array('a' => array('href' => array(), 'target' => array(), 'rel' => array()))
									),
									'https://shortpixel.com/free-sign-up'
								);
								?>
							</p>
						</div>
					<?php endif; ?>

					<div id="emr-apikey-notice" class="notice" style="display:none;"></div>
				</div>
			</section>

			<style>
			.emr-sp-account { margin-top: 16px; margin-bottom: 24px; }
			.emr-sp-account-body { padding: 12px 0 4px; }
			.emr-sp-intro { margin: 0 0 12px; color: #50575e; }
			.emr-sp-key-card {
				display: flex;
				align-items: center;
				justify-content: space-between;
				background: #f8f8f8;
				border: 1px solid #e2e2e2;
				border-radius: 6px;
				padding: 12px 16px;
				gap: 12px;
			}
			.emr-sp-key-status { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
			.emr-sp-status-dot {
				width: 9px;
				height: 9px;
				border-radius: 50%;
				background: #46b450;
				flex-shrink: 0;
				box-shadow: 0 0 0 3px rgba(70,180,80,.15);
			}
			.emr-sp-key-label {
				font-size: 11px;
				text-transform: uppercase;
				letter-spacing: .05em;
				color: #888;
				display: block;
				margin-bottom: 2px;
			}
			.emr-sp-key-value { font-size: 13px; color: #333; }
			.emr-sp-key-actions { display: flex; gap: 8px; flex-shrink: 0; align-items: center; }
			.emr-sp-change-btn,
			.emr-sp-remove-btn {
				display: inline-flex !important;
				align-items: center !important;
				justify-content: center !important;
				font-size: 12px !important;
				padding: 0 14px !important;
				height: 32px !important;
				min-height: 32px !important;
				line-height: 1 !important;
				min-width: 0 !important;
				box-sizing: border-box !important;
			}
			.emr-sp-remove-btn {
				color: #b32d2e !important;
				border-color: #b32d2e !important;
			}
			.emr-sp-remove-btn:hover {
				background: #b32d2e !important;
				color: #fff !important;
			}
			.emr-sp-edit-form {
				margin-top: 12px;
				background: #fff;
				border: 1px solid #e2e2e2;
				border-radius: 6px;
				padding: 16px;
			}
			.emr-sp-edit-form label {
				display: block;
				font-size: 12px;
				font-weight: 600;
				color: #555;
				margin-bottom: 8px;
				text-transform: uppercase;
				letter-spacing: .04em;
			}
			.emr-sp-edit-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
			.emr-sp-edit-row input[type="text"] { flex: 1; min-width: 180px; max-width: 280px; }
			.emr-sp-save-btn {
				background: #1abdca !important;
				border-color: #15a8b4 !important;
				color: #fff !important;
				font-weight: 600 !important;
			}
			.emr-sp-save-btn:hover { background: #15a8b4 !important; }
			.emr-sp-save-btn:disabled { background: #ccc !important; border-color: #bbb !important; }
			.emr-sp-cancel-link { font-size: 12px; color: #888; text-decoration: none; }
			.emr-sp-cancel-link:hover { color: #555; }
			#emr-apikey-notice { border-radius: 4px; margin-top: 12px; padding: 8px 12px; }
			#emr-apikey-notice p { margin: 0; }
			</style>

			<script>
			jQuery(function ($) {
				var nonce = '<?php echo esc_js(wp_create_nonce('emr_save_api_key')); ?>';
				var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

				$('#emr-change-key-toggle').on('click', function (e) {
					e.preventDefault();
					$(this).hide();
					$('#emr-change-key-form').slideDown(160);
					$('#emr-new-api-key').focus();
				});

				$('#emr-change-key-cancel').on('click', function (e) {
					e.preventDefault();
					$('#emr-change-key-form').slideUp(160);
					$('#emr-change-key-toggle').show();
					$('#emr-new-api-key').val('');
					$('#emr-apikey-notice').hide().removeClass('notice-success notice-error');
				});

				$('#emr-remove-key').on('click', function (e) {
					e.preventDefault();
					if ( ! window.confirm('<?php echo esc_js(__('Remove the saved ShortPixel API Key? Background removal will fall back to the Free plan (100 image limit).', 'enable-media-replace')); ?>') ) {
						return;
					}
					var $btn = $(this);
					var $msg = $('#emr-apikey-notice');
					$btn.prop('disabled', true);
					$msg.hide().removeClass('notice-success notice-error');

					$.post(ajaxUrl, {
						action: 'emr_delete_api_key',
						nonce : nonce
					}, function (response) {
						if (response.success) {
							$msg.addClass('notice notice-success').html('<p>' + response.data.message + '</p>').show();
							setTimeout(function () { location.reload(); }, 900);
						} else {
							$btn.prop('disabled', false);
							$msg.addClass('notice notice-error').html('<p>' + response.data.message + '</p>').show();
						}
					}).fail(function () {
						$btn.prop('disabled', false);
						$msg.addClass('notice notice-error').html('<p><?php echo esc_js(__('Request failed. Please try again.', 'enable-media-replace')); ?></p>').show();
					});
				});

				$('#emr-update-api-key').on('click', function (e) {
					e.preventDefault();
					var $btn = $(this);
					var $msg = $('#emr-apikey-notice');
					var key  = $('#emr-new-api-key').val().trim();
					var originalText = $btn.text();

					$btn.prop('disabled', true).text('<?php echo esc_js(__('Verifying…', 'enable-media-replace')); ?>');
					$msg.hide().removeClass('notice-success notice-error');

					$.post(ajaxUrl, {
						action : 'emr_save_api_key',
						nonce  : nonce,
						api_key: key
					}, function (response) {
						$btn.prop('disabled', false).text(originalText);
						if (response.success) {
							$msg.addClass('notice notice-success').html('<p>' + response.data.message + '</p>').show();
							setTimeout(function () { location.reload(); }, 1200);
						} else {
							$msg.addClass('notice notice-error').html('<p>' + response.data.message + '</p>').show();
						}
					}).fail(function () {
						$btn.prop('disabled', false).text(originalText);
						$msg.addClass('notice notice-error').html('<p><?php echo esc_js(__('Request failed. Please try again.', 'enable-media-replace')); ?></p>').show();
					});
				});
			});
			</script>
			<?php endif; ?>

			<button type="button" class="button button-primary" id="remove_background_button"><?php esc_html_e('Preview', 'enable-media-replace'); ?></button>
			<button type="submit" class="button button-primary" id="replace_image_button" disabled><?php esc_html_e('Replace', 'enable-media-replace'); ?></button>
			<a class="button" href="javascript:history.back()"><?php esc_html_e('Cancel', 'enable-media-replace'); ?></a>
		</div> <!--- editor wrapper -->
		<?php include_once( 'upsell.php' ); ?>
	</form>
</div>
