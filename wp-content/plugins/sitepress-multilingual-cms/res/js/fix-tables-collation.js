jQuery(document).ready(function () {
	jQuery('#wpml_fix_tables_collation').click(function () {
		jQuery(this).attr('disabled', 'disabled');
		jQuery(this).after('<span class="wpml-fix-tables-collation-spinner">' + icl_ajxloaderimg + '</span>');
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'fix_tables_collation',
				nonce: jQuery('#wpml-fix-tables-collation-nonce').val(),
			},
			success: function () {
				jQuery('#wpml_fix_tables_collation').removeAttr('disabled');
				jQuery('.wpml-fix-tables-collation-spinner').remove();
			}
		});
	});
});