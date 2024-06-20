<?php
namespace EnableMediaReplace;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

//use \EnableMediaReplace\UIHelper;
use EnableMediaReplace\ShortPixelLogger\ShortPixelLogger as Log;
use EnableMediaReplace\Notices\NoticeController as Notices;
use EnableMediaReplace\Controller\ReplaceController as ReplaceController;


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


$attachment_id = $view->attachment->ID;
$settings = $view->settings;
$sourceFile = $view->sourceFile;

$uiHelper = emr()->uiHelper();

?>

<div class="wrap emr_upload_form">

	<div class='emr_drop_area' id='emr-drop-area'><h3><?php _e('Drop File', 'enable-media-replace'); ?></h3></div>

	<h1><?php echo esc_html__("Replace Media Upload", "enable-media-replace"); ?></h1>

	<?php

	$formurl = $uiHelper->getFormUrl($attachment_id);

	if (FORCE_SSL_ADMIN) {
			$formurl = str_replace("http:", "https:", $formurl);
		}
	?>

	<form enctype="multipart/form-data" method="POST" action="<?php echo esc_url($formurl); ?>">
		<?php wp_nonce_field('media_replace_upload', 'emr_nonce'); ?>

<div class='editor-wrapper'>
    <section class='image_chooser wrapper'>
      <div class='section-header'> <?php _e('Select Replacement Media', 'enable-media-replace'); ?></div>

<!--
		<div id="message" class=""><strong><?php printf( esc_html__('NOTE: You are about to replace the media file "%s". There is no undo. Think about it!', "enable-media-replace"), $sourceFile->getFileName() ); ?></strong></div>
-->

		<input type="hidden" name="ID" value="<?php echo $attachment_id ?>" />

		<p class='explainer'>
			<?php printf(esc_html__('			You are about to replace %s in your media library. This will be %spermanent%s. %s You can click on the new image panel and select a file from your computer. You can also drag and drop a file into this window', 'enable-media-replace'), '<b class="underline" title="' . $sourceFile->getFullPath() . '">' . $sourceFile->getFileName()  . '</b>', '<b>','</b>', '<br>' );
			?>
		</p>

    <p><?php printf(__('Maximum file size: <strong>%s</strong>','enable-media-replace'), size_format(wp_max_upload_size() ) ) ?></p>
    <div class='form-error filesize'><p><?php printf(__('%s f %s exceeds the maximum upload size for this site.', 'enable-media-replace'), '<span class="fn">', '</span>'); ?></p>
    </div>

    <div class='form-warning filetype'><p><?php printf(__('The replacement file does not have the same file type. This can lead to unexpected issues ( %s )', 'enable-media-replace'), '<span class="source_type"></span> - <span class="target_type"></span>'); ?>

    </p></div>

    <div class='form-warning mimetype'><p><?php printf(__('The replacement file type does not seem to be allowed by WordPress. This can lead to unexpected issues')); ?></p></div>

    <div class='image_previews'>

              <?php

							if (wp_attachment_is('image', $attachment_id) || $view->sourceMime == 'application/pdf')
              {
                  echo $uiHelper->getPreviewImage($attachment_id, $sourceFile);
                  echo $uiHelper->getPreviewImage(-1, $sourceFile, array('is_upload' => true));
              }
              else {

                    if (strlen($sourceFile->getFullPath()) == 0) // check if image in error state.
                    {
                        echo $uiHelper->getPreviewError(-1);
                        echo $uiHelper->getPreviewImage(-1, $sourceFile, array('is_upload' => true));
                    }
                    else {
                        echo $uiHelper->getPreviewFile($attachment_id, $sourceFile);
                        echo $uiHelper->getPreviewFile(-1, $sourceFile, array('is_upload' => true));
                    }

              }
              ?>
      </div>
      <?php
        $url = admin_url("upload.php");
        $url = add_query_arg(array(
        'page' => 'enable-media-replace/enable-media-replace.php',
        'action' => 'emr_prepare_remove',
        'attachment_id' => $attachment_id,
        ), $url);
      ?>

			<p>&nbsp;</p>
			<?php if ($uiHelper->isBackgroundRemovable($view->attachment)): ?>
								  <div>

                    <a href="<?php echo wp_nonce_url( $url , 'emr_prepare_remove' ); ?>">
											<?php _e('New! Click here to remove the background of this image!', 'enable-media-replace'); ?></a>
                    <br>
                    <br>
                    <input type="checkbox" id="remove_after_progress" name="remove_after_progress" value="<?php echo $attachment_id;?>">
                    <label for="remove_after_progress"><?php _e('Remove the background after replacing this image!' ,'enable-media-replace'); ?> </label>
                  </div>
			 <?php endif; ?>
</section>

<div class='option-flex-wrapper'>
  <section class='replace_type wrapper'>
    <div class='section-header'> <?php _e('Replacement Options', 'enable-media-replace'); ?></div>

          <?php
      // these are also used in externals, for checks.
      do_action( 'emr_before_replace_type_options' ); ?>


     <?php $enabled_search = apply_filters( 'emr_display_replace_type_options', true );
       $search_disabled = (! $enabled_search) ? 'disabled' : '';
    ?>
      <div class='option replace <?php echo $search_disabled ?>'>
          <label for="replace_type_1"  ><input <?php checked('replace', $settings['replace_type']) ?> id="replace_type_1" type="radio" name="replace_type" value="replace" <?php echo $search_disabled ?> > <?php echo esc_html__("Just replace the file", "enable-media-replace"); ?>
        </label>

          <p class="howto">
            <?php printf( esc_html__("Note: This option requires you to upload a file of the same type (%s) as the file you want to replace. The attachment name will remain the same (%s) regardless of what the file you upload is called. If a CDN is used, remember to clear the cache for this image!", "enable-media-replace"), $sourceFile->getExtension(), $sourceFile->getFileName() ); ?>
        </p>

				<p class='form-warning filetype'><?php _e('If you replace the file with a different filetype, this file might become unreadable and / or cause unexpected issues', 'enable-media-replace'); ?>
				</p>

        <?php do_action('emr_after_search_type_options'); ?>
      </div>

          <?php $enabled_replacesearch = apply_filters( 'emr_enable_replace_and_search', true );
        $searchreplace_disabled = (! $enabled_replacesearch) ? 'disabled' : '';
      ?>

      <div class="option searchreplace <?php echo $searchreplace_disabled ?>">
          <label for="replace_type_2"><input id="replace_type_2" <?php checked('replace_and_search', $settings['replace_type']) ?> type="radio" name="replace_type" value="replace_and_search" <?php echo $searchreplace_disabled ?> > <?php echo __("Replace the file, use the new file name, and update all links", "enable-media-replace"); ?>
      </label>

          <p class="howto"><?php printf( esc_html__("Note: If you enable this option, the name and type of the file you are uploading will replace the old file. All links pointing to the current file (%s) will be updated to point to the new file name. (If other websites link directly to the file, those links will no longer work. Be careful!)", "enable-media-replace"), $sourceFile->getFileName() ); ?></p>

     <!-- <p class="howto"><?php echo esc_html__("Please note that if you upload a new image, only the embeds/links of the original size image will be replaced in your posts.", "enable-media-replace"); ?></p> -->

      <?php do_action('emr_after_replace_type_options'); ?>
      </div>

    </section>
    <section class='options wrapper'>
      <div class='section-header'> <?php _e('Options', 'enable-media-replace'); ?></div>
      <div class='option timestamp'>
        <?php
          $attachment_current_date = date_i18n('d/M/Y H:i', strtotime($view->attachment->post_date) );
					$attachment_now_date = date_i18n('d/M/Y H:i' );

          $time = current_time('mysql');
          $date = $nowDate = new \dateTime($time); // default to now.
					$attachmentDate = new \dateTime($view->attachment->post_date);


          if ($settings['timestamp_replace'] == ReplaceController::TIME_CUSTOM)
          {
             $date = new \dateTime($settings['custom_date']);
          }
        ?>
          <p><?php _e('When replacing the media, do you want to:', 'enable-media-replace'); ?></p>
          <ul>
            <li><label><input type='radio' <?php checked('1', $settings['timestamp_replace']) ?> name='timestamp_replace' value='1' /><?php printf(__('Replace the date with the current date %s(%s)%s', 'enable-media-replace'), "<span class='small'>", $attachment_now_date, "</span>") ; ?></label></li>
            <li><label><input type='radio' <?php checked('2', $settings['timestamp_replace']) ?> name='timestamp_replace' value='2'  /><?php printf(__('Keep the date %s(%s)%s', 'enable-media-replace'), "<span class='small'>", $attachment_current_date, "</span>"); ?></label></li>
            <li><label><input type='radio' <?php checked('3', $settings['timestamp_replace']) ?> name='timestamp_replace' value='3' /><?php _e('Set a Custom Date', 'enable-media-replace'); ?></label></li>
          </ul>
          <div class='custom_date'>

            <span class='field-title dashicons dashicons-calendar'><?php _e('Custom Date', 'enable-media-replace'); ?></span>
           <input type='text' name="custom_date" value="<?php echo $date->format(get_option('date_format')); ?>" id='emr_datepicker'
            class='emr_datepicker' />

           @ <input type='text' name="custom_hour" class='emr_hour' id='emr_hour' value="<?php echo $date->format('H') ?>" /> &nbsp;
            <input type="text" name="custom_minute" class='emr_minute' id='emr_minute' value="<?php echo $date->format('i'); ?>" />
            <input type="hidden" name="custom_date_formatted" value="<?php echo $date->format('Y-m-d'); ?>" />

						<span class="replace_custom_date_wrapper">
						<?php
						printf('<a class="replace_custom_date" data-date="%s" data-hour="%s" data-min="%s" data-format="%s">%s</a>', $nowDate->format(get_option('date_format')), $nowDate->format('H'), $nowDate->format('i'), $nowDate->format('Y-m-d'), __('Now', 'enable-media-replace'));
						echo " ";
						printf('<a class="replace_custom_date" data-date="%s" data-hour="%s" data-min="%s" data-format="%s">%s</a>', $attachmentDate->format(get_option('date_format')), $attachmentDate->format('H'), $attachmentDate->format('i'), $attachmentDate->format('Y-m-d'), __('Original', 'enable-media-replace'));
						?>
					</span>
         </div>
         <?php if ($subdir = $uiHelper->getRelPathNow()):

            if ($settings['new_location'] !== false)
               $subdir = $settings['new_location_dir'];
          ?>

<!--
				<div class='title_option'>
						<input type="text" name="new_title" value="">
				</div>
-->
         <div class='location_option'>
					 <?php
					 if (true === $view->is_movable): ?>
           <label><input type="checkbox" name="new_location" value="1" <?php checked($settings['new_location'], 1); ?>  /> <?php _e('Place the newly uploaded file in this folder: ', 'enable-media-replace'); ?></label>
					 <br>
            <?php echo $view->custom_basedir ?> <input type="text" name="location_dir" value="<?php echo $subdir ?>" />
						<?php
						else:
								echo __('File is offloaded and can\'t be moved to other directory', 'enable-media-replace');
						endif;
					 ?>
          </div>
        <?php endif; ?>

      </div>

    </section>
  </div>
  <section class='form_controls wrapper'>
		<input id="submit" type="submit" class="button button-primary" disabled="disabled" value="<?php echo esc_attr__("Upload", "enable-media-replace"); ?>" />
        <a href="#" class="button" onclick="history.back();"><?php echo esc_html__("Cancel", "enable-media-replace"); ?></a>
  </section>
</div>

	<?php include_once('upsell.php'); ?>



	</form>
</div>
