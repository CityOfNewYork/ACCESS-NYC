/*jshint devel:true */
/*global jQuery, ajaxurl, get_checked_cbs */
var WPML_String_Translation = WPML_String_Translation || {};

WPML_String_Translation.ChangeLanguage = function () {
	"use strict";
	var privateData = {};

    var init = function () {
        jQuery(function () {

            privateData.language_select = jQuery('#icl-st-change-lang-selected');
            privateData.language_select.on('change', applyChanges);

            privateData.spinner = jQuery('.icl-st-change-spinner');
            privateData.spinner.detach().insertAfter(privateData.language_select);
        });
    };

	var applyChanges = function () {
        if(WPML_String_Translation.ExecBatchAction.isApplyBulkActionSelected()) {
            WPML_String_Translation.ExecBatchAction.run(
                wpml_st_exec_batch_action_data.initChangeStringLangOfDomain,
                wpml_st_exec_batch_action_data.changeLanguageOfStringsInDomain,
                {
                    domain: jQuery('select[name="icl_st_filter_context"] option:selected').val(),
                    targetLanguage: privateData.language_select.val(),
                },
                {
                    beforeStart: function() {
                        jQuery('#icl-st-change-lang-selected').attr('disabled', 'disabled');
                    },
                    onComplete: function(data) {
                        jQuery('#icl-st-change-lang-selected').removeAttr('disabled');
                        window.location.reload();
                    },
                }
            );
            return;
        }

		var checkBoxValue;
		var data;
		var i;
		var checkboxes;
		var strings;

        privateData.spinner.addClass('is-active');

		strings = [];
		checkboxes = get_checked_cbs();
		for (i = 0; i < checkboxes.length; i++) {
			checkBoxValue = jQuery(checkboxes[i]).val();
			strings.push(checkBoxValue);
		}

		data = {
			action:   'wpml_change_string_lang',
			wpnonce:  wpml_st_change_lang_data.nonce,
			strings:  strings,
			language: privateData.language_select.val()
		};

		jQuery.ajax({
			url:      ajaxurl,
			type:     'post',
			data:     data,
			dataType: 'json',
			success:  function (response) {
				if (response.success) {
					window.location.reload(true);
				}
				if (response.error) {
					privateData.spinner.removeClass( 'is-active' );
					alert(response.error);
					privateData.apply_button.prop('disabled', false);
				}
			}
		});
	};

	init();
};

WPML_String_Translation.change_language = new WPML_String_Translation.ChangeLanguage();
