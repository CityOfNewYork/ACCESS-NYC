jQuery(document).ready(function () {
	jQuery('#wpml_fix_tp_id_btn').click(function () {
		jQuery(this).attr('disabled', 'disabled');
		jQuery(this).after('<span class="wpml-fix-tp-id-spinner">' + icl_ajxloaderimg + '</span>');
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				job_ids: jQuery( '#wpml_fix_tp_id_text' ).val(),
				action: 'wpml-fix-translation-jobs-tp-id',
				nonce: jQuery('#wpml-fix-tp-id-nonce').val(),
			},
			success: function () {
				jQuery('#wpml_fix_tp_id_btn').removeAttr('disabled');
				jQuery('.wpml-fix-tp-id-spinner').remove();
			}
		});
	});
});
