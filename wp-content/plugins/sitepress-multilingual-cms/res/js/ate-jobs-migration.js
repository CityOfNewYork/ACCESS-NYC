jQuery(function () {
    var buttonId = '#wpml_tm_ate_source_id_migration_btn';

    jQuery(buttonId).click(function () {
        jQuery(this).prop('disabled', true);
        jQuery(this).after('<span class="wpml-fix-tp-id-spinner">' + icl_ajxloaderimg + '</span>');

        jQuery.ajax({
                        url : ajaxurl,
                        type: 'POST',
                        data: {
				action: jQuery(this).data('action'),
				nonce: ate_jobs_migration_data.nonce,
			},
			success: function () {
				jQuery(buttonId).prop('disabled', false);
				jQuery('.wpml-fix-tp-id-spinner').remove();
			}
		});
	});
});
