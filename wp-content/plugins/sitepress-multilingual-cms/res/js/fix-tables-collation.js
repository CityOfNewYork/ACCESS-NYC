jQuery(function () {
    jQuery('#wpml_fix_tables_collation').click(function () {
        jQuery(this).prop('disabled', true);
        jQuery(this).after('<span class="wpml-fix-tables-collation-spinner">' + icl_ajxloaderimg + '</span>');
        jQuery.ajax({
                        url : ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'fix_tables_collation',
                            nonce : WPML_core.sanitize( jQuery('#wpml-fix-tables-collation-nonce').val() ),
                        },
			success: function () {
				jQuery('#wpml_fix_tables_collation').prop('disabled',false);
				jQuery('.wpml-fix-tables-collation-spinner').remove();
			}
		});
	});
});
