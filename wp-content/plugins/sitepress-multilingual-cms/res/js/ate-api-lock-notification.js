jQuery('#wpml_save_cloned_sites_report_type').click(function () {
    var reportType = jQuery('input[name*="ate_locked_option"]:checked').val();

    if ( reportType !== undefined ) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpml_save_cloned_sites_report_type',
                nonce: jQuery('#icl_doc_translation_method_cloned_nonce').val(),
                reportType: reportType
            },
            success: function () {
                location.reload();
            }
        });
    }
});