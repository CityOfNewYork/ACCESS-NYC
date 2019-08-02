<?php
namespace EnableMediaReplace;

//use \EnableMediaReplace\UIHelper;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;


/**
 * Uploadscreen for selecting and uploading new media file
 *
 * @author      Måns Jonasson  <http://www.mansjonasson.se>
 * @copyright   Måns Jonasson 13 sep 2010
 * @version     $Revision: 2303 $ | $Date: 2010-09-13 11:12:35 +0200 (ma, 13 sep 2010) $
 * @package     wordpress
 * @subpackage  enable-media-replace
 *
 */

 if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly.

if (!current_user_can('upload_files'))
	wp_die( esc_html__('You do not have permission to upload files.', 'enable-media-replace') );

global $wpdb;

$table_name = $wpdb->prefix . "posts";

Log::addDebug('Load Popup Form View');

//$sql = "SELECT guid, post_mime_type FROM $table_name WHERE ID = " . (int) $_GET["attachment_id"];
//list($current_filename, $current_filetype) = $wpdb->get_row($sql, ARRAY_N);

$attachment_id = intval($_GET['attachment_id']);
$attachment = get_post($attachment_id);

$filepath = get_attached_file($attachment_id); // fullpath
$filetype = $attachment->post_mime_type;
$filename = basename($filepath);
$source_mime = get_post_mime_type($attachment_id);

$uiHelper = new UIHelper();
$uiHelper->setPreviewSizes();
$uiHelper->setSourceSizes($attachment_id);

Log::addDebug('Popup view Data', array('id' => $attachment_id, 'source_mime' => $source_mime, 'filepath' => $filepath));

?>
<style>
    .emr-plugin-button.emr-updating:before {
        font: 400 20px/1 dashicons;
        display: inline-block;
        content: "\f463";
        -webkit-animation: rotation 2s infinite linear;
        animation: rotation 2s infinite linear;
        margin: 3px 5px 0 -2px;
        vertical-align: top
    }
</style>
<div class="wrap emr_upload_form">
	<h1><?php echo esc_html__("Replace Media Upload", "enable-media-replace"); ?></h1>

	<?php

$url = $uiHelper->getFormUrl($attachment_id);
  $formurl = wp_nonce_url( $url, "media_replace_upload" );
	if (FORCE_SSL_ADMIN) {
			$formurl = str_replace("http:", "https:", $formurl);
		}
	?>

	<form enctype="multipart/form-data" method="POST" action="<?php echo $formurl; ?>">
    <section class='image_chooser wrapper'>
      <div class='section-header'> <?php _e('Choose Replacement Image', 'enable-replace-media'); ?></div>

	<?php
		#wp_nonce_field('enable-media-replace');
    $plugins = get_plugins();
    $spInstalled = isset($plugins['shortpixel-image-optimiser/wp-shortpixel.php']);
    $spActive = is_plugin_active('shortpixel-image-optimiser/wp-shortpixel.php');
	?>
		<input type="hidden" name="ID" value="<?php echo $attachment_id ?>" />
		<div id="message" class="updated notice notice-success is-dismissible"><p><?php printf( esc_html__('NOTE: You are about to replace the media file "%s". There is no undo. Think about it!', "enable-media-replace"), $filename ); ?></p></div>

		<?php if(!$spInstalled) {?>
		<div class='shortpixel-notice'>
			<h3 class="" style="margin-top: 0;text-align: center;">
				<a href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">
					<?php echo esc_html__("Optimize your images with ShortPixel, get +50% credits!", "enable-media-replace"); ?>
				</a>
			</h3>
			<div class="" style="text-align: center;">
				<a href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">
					<img src="https://optimizingmattersblog.files.wordpress.com/2016/10/shortpixel.png">
				</a>
			</div>
			<div class="" style="margin-bottom: 10px;">
				<?php echo esc_html__("Get more Google love by compressing your site's images! Check out how much ShortPixel can save your site and get +50% credits when signing up as an Enable Media Replace user! Forever!", "enable-media-replace"); ?>
			</div>
			<div class=""><div style="text-align: <?php echo (is_rtl()) ? 'left' : 'right' ?>;">
					<a class="button button-primary" id="shortpixel-image-optimiser-info" href="https://shortpixel.com/wp/af/VKG6LYN28044" target="_blank">
						<?php echo esc_html__("More info", "enable-media-replace"); ?></p>
					</a>
				</div>
			</div>
		</div>
		<?php } ?>

		<p><?php echo esc_html__("Choose a file to upload from your computer", "enable-media-replace"); ?></p>
    <p><?php printf(__('Maximum file size: <strong>%s</strong>','enable-media-replace'), size_format(wp_max_upload_size() ) ) ?></p>
    <div class='form-error filesize'><p><?php printf(__('%s f %s exceeds the maximum upload size for this site.', 'enable-media-replace'), '<span class="fn">', '</span>'); ?></p>
    </div>

    <div class='form-warning filetype'><p><?php printf(__('Replacement file is not the same filetype. This might cause unexpected issues')); ?></p></div>


		<input type="file" name="userfile" id="userfile" />
        <div class='image_previews'>
            <?php if (wp_attachment_is('image', $attachment_id) || $source_mime == 'application/pdf')
            {
                echo $uiHelper->getPreviewImage($attachment_id);
                echo $uiHelper->getPreviewImage(-1);
            }
            else {
                  if (strlen($filepath) == 0) // check if image in error state.
                  {
                      echo $uiHelper->getPreviewError(-1);
                      echo $uiHelper->getPreviewImage(-1);
                  }
                  else {
                      echo $uiHelper->getPreviewFile($attachment_id);
                      echo $uiHelper->getPreviewFile(-1);
                  }

            }
            ?>
        </div>

</section>
<div class='option-flex-wrapper'>
  <section class='replace_type wrapper'>
    <div class='section-header'> <?php _e('Replacement Options', 'enable-replace-media'); ?></div>

  		<?php
      // these are also used in externals, for checks.
      do_action( 'emr_before_replace_type_options' ); ?>


  	<?php $enabled_search = apply_filters( 'emr_display_replace_type_options', true );
       $search_disabled = (! $enabled_search) ? 'disabled' : '';
    ?>
      <div class='option replace <?php echo $search_disabled ?>'>
    		<label for="replace_type_1"  ><input CHECKED id="replace_type_1" type="radio" name="replace_type" value="replace" <?php echo $search_disabled ?> > <?php echo esc_html__("Just replace the file", "enable-media-replace"); ?>
        </label>

    		<p class="howto">
            <?php printf( esc_html__("Note: This option requires you to upload a file of the same type (%s) as the one you are replacing. The name of the attachment will stay the same (%s) no matter what the file you upload is called.", "enable-media-replace"), $filetype, $filename ); ?>
        </p>

        <?php do_action('emr_after_search_type_options'); ?>
      </div>

  		<?php $enabled_replacesearch = apply_filters( 'emr_enable_replace_and_search', true );
        $searchreplace_disabled = (! $enabled_replacesearch) ? 'disabled' : '';
      ?>

      <div class="option searchreplace <?php echo $searchreplace_disabled ?>">
  		<label for="replace_type_2"><input id="replace_type_2" type="radio" name="replace_type" value="replace_and_search" <?php echo $searchreplace_disabled ?> > <?php echo __("Replace the file, use new file name and update all links", "enable-media-replace"); ?>
      </label>

  		<p class="howto"><?php printf( esc_html__("Note: If you check this option, the name and type of the file you are about to upload will replace the old file. All links pointing to the current file (%s) will be updated to point to the new file name.", "enable-media-replace"), $filename ); ?></p>

  	<!--	<p class="howto"><?php echo esc_html__("Please note that if you upload a new image, only embeds/links of the original size image will be replaced in your posts.", "enable-media-replace"); ?></p> -->

      <?php do_action('emr_after_replace_type_options'); ?>
      </div>

    </section>
    <section class='options wrapper'>
      <div class='section-header'> <?php _e('Date Options', 'enable-media-replace'); ?></div>
      <div class='option timestamp'>
        <?php
          $attachment_current_date = date_i18n('d/M/Y H:i', strtotime($attachment->post_date) );
          $time = current_time('mysql');
          $date = new \dateTime($time);
        ?>
          <p><?php _e('When replacing the media, do you want to:', 'enable-media-replace'); ?></p>
          <ul>
            <li><label><input type='radio' name='timestamp_replace' value='1' checked /><?php _e('Replace the date', 'enable-media-replace'); ?></label></li>
            <li><label><input type='radio' name='timestamp_replace' value='2'  /><?php printf(__('Keep the date %s(%s)%s', 'enable-media-replace'), "<span class='small'>", $attachment_current_date, "</span>"); ?></label></li>
            <li><label><input type='radio' name='timestamp_replace' value='3' /><?php _e('Set a Custom Date', 'enable-media-replace'); ?></label></li>
          </ul>
          <div class='custom_date'>

            <span class='field-title dashicons dashicons-calendar'><?php _e('Custom Date', 'enable-media-replace'); ?></span>
           <input type='text' name="custom_date" value="<?php echo $date->format(get_option('date_format')); ?>" id='emr_datepicker'
            class='emr_datepicker' />

           @ <input type='text' name="custom_hour" class='emr_hour' value="<?php echo $date->format('H') ?>" /> &nbsp;
            <input type="text" name="custom_minute" class='emr_minute' value="<?php echo $date->format('i'); ?>" />
            <input type="hidden" name="custom_date_formatted" value="<?php echo $date->format('Y-m-d'); ?>" />
         </div>
      </div>
    </section>
  </div>
  <section class='form_controls wrapper'>
		<input id="submit" type="submit" class="button button-primary" disabled="disabled" value="<?php echo esc_attr__("Upload", "enable-media-replace"); ?>" />
        <a href="#" class="button" onclick="history.back();"><?php echo esc_html__("Cancel", "enable-media-replace"); ?></a>
  </section>
	</form>
</div>
