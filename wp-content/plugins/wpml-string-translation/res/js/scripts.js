jQuery(function () {
    var filterSearch = jQuery("#icl_st_filter_search");

    jQuery('.wpml-colorpicker').wpColorPicker();

    jQuery('#icl_st_filter_search_sb').click(icl_st_filter_search);
	function onDropDownChange() {
		if (jQuery('[name="icl_st_filter_status"]').val() === ''
			&& jQuery('[name="icl_st_filter_context"]').val() === ''
			&& jQuery('[name="icl-st-filter-translation-priority"]').val() === '') {
			jQuery('#icl_st_filter_search_remove').hide();
		} else {
			jQuery('#icl_st_filter_search_remove').show();
		}
	}
	jQuery('select[name="icl_st_filter_status"]').change(onDropDownChange);
	jQuery('select[name="icl_st_filter_context"]').change(onDropDownChange);
	jQuery('select[name="icl-st-filter-translation-priority"]').change(onDropDownChange);

    if (filterSearch.length) {
		updateControls();

		filterSearch.keyup(function (event) {
			if (event.keyCode === 13) {
				icl_st_filter_search();
			}
			updateControls();
		});
	}

    jQuery('#icl_st_filter_search_remove').click(icl_st_filter_search_remove);
    jQuery('#icl-st-delete-selected').click(icl_st_delete_selected);

    var poTranslations = jQuery('#icl_st_po_translations');
    var peTranslations = jQuery('#icl_st_pe_translations');
    var poSelect = jQuery('#icl_st_po_language');
    var peSelect = jQuery('#icl_st_e_language');
    var updateImportExportPoCheckbox = function(checkbox, select) {
        var isChecked = checkbox.is(":checked");
        select.prop('disabled', !isChecked).css('display', isChecked ? 'block' : 'none');
        checkbox.closest('.checkbox-and-select-checkbox').toggleClass('checkbox-and-select-checkbox-checked', isChecked);
    };
    updateImportExportPoCheckbox(poTranslations, poSelect);
    updateImportExportPoCheckbox(peTranslations, peSelect);

    poTranslations.click(function(){
        updateImportExportPoCheckbox(jQuery(this), poSelect);
    });
    peTranslations.click(function(){
        updateImportExportPoCheckbox(jQuery(this), peSelect);
    });
    var iclTMLanguages = jQuery('#icl_tm_languages');
    iclTMLanguages.find(':checkbox').click(icl_st_update_languages);
    jQuery('.icl_st_row_cb, .check-column :checkbox').click(icl_st_update_checked_elements);
    iclTMLanguages.find('select').change(icl_st_change_service);
    jQuery('#icl_st_po_form').submit(icl_validate_po_upload);
    jQuery('#icl_st_send_strings').submit(icl_st_send_strings);
    jQuery('#icl_st_translate_to_all').click(icl_st_select_all);

    jQuery('.hndle-wrap').click(function () {
        jQuery(this).closest('.postbox').toggleClass('closed').toggleClass('opened');
    });

	jQuery('#wpml-user-properties').click(function () {
		var userProperties = jQuery('#dashboard_wpml_user_properties');
		var button = jQuery('#wpml-user-properties');

		userProperties.find('.checkboxes-list').slideToggle();
		const editUserRoleText = button.attr('data-editUserRoleText');
		const applyText = button.attr('data-applyText');
		if (button.val() === editUserRoleText) {
			button.val(applyText);
		} else {
			button.val(editUserRoleText);
		}
	});

    jQuery('#icl_st_track_strings').submit(iclSaveForm);

    var iclSTOptionWriteForm = jQuery('#icl_st_option_write_form');
    iclSTOptionWriteForm.submit(icl_st_admin_options_form_submit);
    iclSTOptionWriteForm.submit(iclSaveForm);

    // Make PO forms equal height
    jQuery('.wpml-string-widgets .postbox').on('click', function() {
        var item = jQuery(this).closest('.postbox');
        if(item.attr('id') === 'dashboard_wpml_st_poie') {
            if(item.hasClass('opened')) {
                var forms = jQuery('#dashboard_wpml_st_poie').find('.form-in-column');
                var height = 0;
                forms.each(function() {
                    var itemHeight = jQuery(this).outerHeight();
                    if(itemHeight > height) {
                        height = itemHeight;
                    }
                });
                forms.each(function() {
                    jQuery(this).css('min-height', height + 'px');
                });
            }
        }
    });

	// Handle click event of AutoRegisterStringsNotice notice.
	jQuery('#wpml_open_autoregistration_setting').on('click', function(e) {
		var item = jQuery('#dashboard_wpml_st_autoregister')
		if(item.hasClass('closed')) {
			item.toggleClass('closed opened');
		}
	});

	// Expand the accordion widget if ID exist in URL hash.
	if (window.location.hash && jQuery(window.location.hash + '.postbox.closed').size() > 0) {
		jQuery(window.location.hash).toggleClass('closed opened');
	}

    // Track strings picker
    var pickerWrap = jQuery('#icl_st_track_strings').find('.wpml-picker-container');
    pickerWrap.find('br').remove();
    pickerWrap.find('label').wrap('<div class="wp-picker-label"></div>');
    pickerWrap.find('.wp-picker-container').wrap('<div class="wp-picker-container-wrap"></div>');

    // Picker align
    jQuery(".pick-show").click(function () {
        var set = jQuery(this).offset();
           jQuery("#colorPickerDiv").css({"top":set.top-180,"left":set.left, "z-index":99});
    });

	jQuery('input[name="wpml_st_theme_localization_type_wpml_td"]').on('click', function () {
		var checked = jQuery(this).prop('checked');
		jQuery('input[name="wpml_st_theme_localization_type_wpml_td"]').prop('checked', checked);
	});

    jQuery(document).on('click', '.wpml_st_pop_download', icl_st_pop_download);

    var ICLSTMoreOptions = jQuery('#icl_st_more_options');
    ICLSTMoreOptions.submit(iclSaveForm);
	var ICLSTMoreOptionsUtilities = jQuery('#icl_st_more_options_utilities');
	ICLSTMoreOptionsUtilities.submit(iclSaveForm);
    ICLSTMoreOptions.submit(
        function () {
            var iclSTTUser = jQuery('#icl_st_more_options .checkboxes-list');

            if (!iclSTTUser.find('.checkbox-wrap input:checked').length) {
                jQuery('#icl_st_tusers_list').html('-');
            } else {
                jQuery('#icl_st_tusers_list').html(iclSTTUser.find('.checkbox-wrap input:checked').next().map(
                    function () {
                        return jQuery(this).html();
                    }).get().join(', '))
            }
        }
    );
	ICLSTMoreOptionsUtilities.submit(
		function () {
			var iclSTTUser = jQuery('#roles_list');
			if (!iclSTTUser.find('.checkbox-wrap input:checked').length) {
				jQuery('#icl_st_tusers_list').html('-');
			} else {
				jQuery('#icl_st_tusers_list').html(iclSTTUser.find('.checkbox-wrap input:checked').next().map(
					function () {
						return jQuery(this).html();
					}).get().join(', '))
			}
		}
	);
	function updateControls() {
		if (filterSearch[0].value !== '') {
			jQuery('.wpml-string-translation-filter__checkboxes').show();
			jQuery('#icl_st_filter_search_remove').show();
		} else {
			jQuery('.wpml-string-translation-filter__checkboxes').hide();
			jQuery('#icl_st_filter_search_remove').hide();
		}
		jQuery('#icl_st_filter_search_em').prop('disabled', filterSearch[0].value === '');
		jQuery('#search_translation').prop('disabled', filterSearch[0].value === '');
	}

    var bulkSelectMsgs = jQuery('.js-wpml-st-table').find('.js-wpml-st-icl-string-translations-bulk-select-msg');
    bulkSelectMsgs.on('click', 'a', function(e) {
        e.preventDefault();
        var link = jQuery(this);
		var isSelected = bulkSelectMsgs.get(0).hasAttribute('data-is-apply-bulk-action-selected');

		bulkSelectMsgs.get(0).toggleAttribute('data-is-apply-bulk-action-selected');
        update_bulk_action_selector_panel_inside_table(true);
    });

	// PO Import/Export
    var importPoNewButton = jQuery('#icl_st_importpo_newbutton');
    var importPoExistingButton = jQuery('#icl_st_importpo_existingbutton');
    var importPoContextNew = jQuery('#icl_st_i_context_new');
    var importPoContextExisting = jQuery('#icl_st_i_context');
	var importSourceLangSelect = jQuery('#st-i-source-lang');

    importPoNewButton.on('click', function(event) {
        event.preventDefault();
        importPoContextNew.val('');
        importPoNewButton.fadeOut('fast',function() {
            importPoExistingButton.fadeIn('fast');
            importPoContextExisting.css('display', 'none');
            importPoContextNew.css('display', 'block');
            importPoExistingButton.closest('.select-and-button-button').addClass('select-and-button-button-big');
            importPoExistingButton.closest('.select-and-button-button').prev('.select-and-button-select').addClass('select-and-button-select-select-small');
        });
    });
    importPoExistingButton.on('click', function() {
        event.preventDefault();
        importPoContextExisting.val('');
        importPoExistingButton.fadeOut('fast', function() {
            importPoNewButton.fadeIn('fast');
            importPoContextExisting.css('display', 'block');
            importPoContextNew.css('display', 'none');
            importPoNewButton.closest('.select-and-button-button').removeClass('select-and-button-button-big');
            importPoNewButton.closest('.select-and-button-button').prev('.select-and-button-select').removeClass('select-and-button-select-select-small');
        });
    });

	// PO Import/Export - Handle source language change.
	updateImportPoTranslationLanguage(importSourceLangSelect, poSelect);
	importSourceLangSelect.on('change', function (){
		updateImportPoTranslationLanguage(importSourceLangSelect, poSelect);
	});

	// If there is any import error expand import/export and scroll to box.
	maybeScrollToStImportErrorNotice();

	createTooltip(jQuery('.js-wpml-translate-admin-texts'));
	createTooltip(jQuery('.js-wpml-translate-user-fields'));
});

function createTooltip(triggerEl) {
	var isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0;

	if (isTouchDevice) {
		return;
	}

	var closeTimeout;
	triggerEl.on('mouseenter', function() {
		if (typeof closeTimeout === "number") {
			clearTimeout(closeTimeout);
		}
		openTooltip(jQuery(this));
	});

	triggerEl.on('mouseleave', function() {
		clearTimeout(closeTimeout);
		closeTimeout = setTimeout(function(event) {
			triggerEl.pointer('close');
		}, 500);
	});

	function openTooltip(triggerNode) {
		var content = triggerNode.data('content');
		var link_text = triggerNode.data('link-text');
		var link_url = triggerNode.data('link-url');
		var link_target = triggerNode.data('link-target');

		if (link_text.length > 0) {
			var content_link_target = 'target="' + link_target + '"';
			content += '<br><a href="' + link_url + '" ' + content_link_target + '>';
			content += link_text;
			content += '</a>';
		}

		clearTimeout(closeTimeout);
		jQuery('.js-wpml-st-active-tooltip').pointer('close');

		if(triggerNode.length && content) {
			triggerNode.addClass('js-wpml-st-active-tooltip');
			triggerNode.pointer({
				pointerClass : 'js-wpml-st-tooltip wpml-st-tooltip',
				content:       content,
				position: {
					edge:  'left',
					align: 'right',
				},
				show: function(event, t){
					jQuery(t.pointer).on('mouseenter', function() {
						clearTimeout(closeTimeout);
					});
					jQuery(t.pointer).on('mouseleave', function() {
						clearTimeout(closeTimeout);
						triggerEl.pointer('close');
					});
				},
				close: function(event, t){
					jQuery(t.pointer).off('mouseenter');
					jQuery(t.pointer).off('mouseleave');
				},
				buttons: function() {},
			}).pointer('open');
		}
	}

	// Opening 'Translate User Meta' popup if required - it can be opened from TM dashboard strings box.
	jQuery(document).ready(function() {
		if (localStorage.getItem("showTranslateUserMetaPopup") !== null) {
			jQuery(".js-wpml-translate-user-fields").trigger("click");
			localStorage.removeItem("showTranslateUserMetaPopup");
		}
	});
}

function icl_st_filter_search(){
	var context            = jQuery('select[name="icl_st_filter_context"]').val(),
		status = jQuery('select[name="icl_st_filter_status"]').val(),
		translation_priority = jQuery('select[name="icl-st-filter-translation-priority"]').val(),
		search_text        = jQuery('#icl_st_filter_search').val(),
		exact_match        = jQuery('#icl_st_filter_search_em').prop('checked'),
		search_translation = jQuery('#search_translation').prop('checked'),
		query_string       = '',
		url                = WPML_core.sanitize(location.href);

	if (icl_st_getUrlParameter('show_results') === 'all') {
		search_translation = false;
	}

	if (typeof search_text === 'string' && search_text.trim() !== '') {
		query_string += '&search=' + encodeURIComponent(search_text);
	}

	if (exact_match){
		query_string += '&em=1';
	}
	if (search_translation) {
		query_string += '&search_translation=1';
	}

	if (typeof status === 'string' && status.trim() !== '') {
		query_string += '&status=' + WPML_core.sanitize(status);
	}

	if (typeof context === 'string' && context.trim() !== '') {
		query_string += '&context=' + encodeURIComponent(context);
	}

	// Add translation priority to query string if selected
	if (typeof translation_priority === 'string' && translation_priority.trim() !== '') {
		query_string += '&translation-priority=' + encodeURIComponent(translation_priority);
	}

	url = url.replace(/#(.*)$/,'')
			.replace(/&paged=([0-9]+)/,'')
			.replace(/&updated=true/,'')
			.replace(/&search=(.*)/g,'')
			.replace(/&status=([0-9a-z-]+)/g, '')
			.replace(/&context=(.*)/g, '')
			.replace(/&translation-priority=(.*)/g, '') + query_string;

	if ('' === context) {
		url = url.replace(/&context=(.*)/g,'');
	}

	location.href = WPML_core.sanitize(url);
}

function icl_st_getUrlParameter(name) {
	name        = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
	var regex   = new RegExp('[\\?&]' + name + '=([^&#]*)');
	var results = regex.exec( WPML_core.sanitize(location.search) );
	return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

function icl_st_filter_search_remove(){
    location.href = WPML_core.sanitize(location.href).replace(/#(.*)$/,'').replace(/&search=(.*)/g,'').replace(/&em=1/g,'');
}

function icl_st_delete_selected() {
    var postVars;
    var delids;
    var proceed;
    var confirmMessage;
    var errorMessage;
    var checkedRows = jQuery('.icl_st_row_cb:checked');
    if (checkedRows.length) {
        confirmMessage = jQuery(this).data('confirm');
        errorMessage = jQuery(this).data('error');
        proceed = confirm(confirmMessage);

        if (proceed) {
            if(WPML_String_Translation.ExecBatchAction.isApplyBulkActionSelected()) {
                WPML_String_Translation.ExecBatchAction.run(
                    wpml_st_exec_batch_action_data.countStringsInDomain,
                    wpml_st_exec_batch_action_data.deleteStringsInDomain,
                    {
                        domain: jQuery('select[name="icl_st_filter_context"] option:selected').val(),
                    },
                    {
                        beforeStart: function() {
                            jQuery('#icl-st-delete-selected').attr('disabled', 'disabled');
                        },
                        onComplete: function(data) {
                            jQuery('#icl-st-delete-selected').removeAttr('disabled');
                            jQuery('select[name="icl_st_filter_context"] option:selected').remove();
                            window.location.href = data.navigateToUrl;
                        },
                    }
                );
                return;
            }

            delids = [];
            checkedRows.each(function () {
                var item = WPML_core.sanitize(jQuery(this).val());
                delids.push(item);
                jQuery(this).trigger('click');
            });
            if (delids) {
                postVars = 'icl_ajx_action=icl_st_delete_strings&value=' + delids.join(',') + '&_icl_nonce=' + jQuery('#_icl_nonce_dstr').val();
                jQuery.post(icl_ajx_url, postVars, function () {
                    var i = 0;
                    for (; i < delids.length; i++) {
                        jQuery('.icl_st_row_cb[value="' + delids[i] + '"]').parent().parent().fadeOut('fast', function () {
                            jQuery(this).remove();
                        });
                    }
                }).success(function (msg) {
                    if ('1' !== msg) {
                        alert(errorMessage);
                    }
                }).error(function () {
                    alert(errorMessage);
                });
            }
        }
    }

    return false;
}

function icl_st_send_strings(){
    var checkedRows = jQuery('.icl_st_row_cb:checked');
    if(!checkedRows.length){
        return false;
    }
    var sendids = [];
    checkedRows.each(function(){
        sendids.push(jQuery(this).val());
    });

    if(!sendids.length){
        return false;
    }
    jQuery('#icl_st_send_strings').find('input[name="strings"]').val(sendids.join(','));

    return true;
}

function icl_st_update_languages() {
    if (!jQuery('#icl_tm_languages').find('input[type=checkbox]:checked:not(:disabled)').length) {
        jQuery('#icl_send_strings').prop('disabled', true);
    } else if (jQuery('.icl_st_row_cb:checked, .check-column input:checked').length && jQuery('.js-lang-not-active:checked').length === 0) {
        jQuery('#icl_send_strings').prop('disabled', false);
    }
    var self = jQuery(this);
    var lang = self.attr('name').replace(/translate_to\[(.+)]/, '$1');
    if (self.prop('checked') && jQuery('#icl_st_service_' + lang).val() === 'icanlocalize') {
        icl_st_show_estimated_cost(lang);
    } else {
        icl_st_hide_estimated_cost(lang);
    }
	icl_st_update_select_all_status();
}

function icl_st_select_all() {
	var self = jQuery(this);
	jQuery('#icl_tm_languages').find('input[type=checkbox]').prop('checked', self.prop('checked'));
	icl_st_update_languages();
}

function icl_st_update_select_all_status() {
	const uncheckedCount = jQuery('#icl_tm_languages input[type=checkbox]:not(:checked)').length;
	jQuery('#icl_st_translate_to_all').prop('checked', uncheckedCount === 0);
}

function show_notice(id, hide) {
	var notice = jQuery(id);
	if (hide) {
		notice.hide();
	} else {
		notice.show();
	}
}

function get_checked_cbs() {
    var package_counts = {};
    var context_select_options = jQuery('select[name="icl_st_filter_context"]').find('option') || [];
    jQuery.each(context_select_options, function (i, option) {
        option = jQuery(option);
        package_counts[option.val()] = option.data('unfiltered-count');
    });
    var st_table = jQuery('#icl_string_translations');
    var package_cbs = st_table.find('.icl_st_row_package:checked') || [];
    var affected_package_counts = {};
    jQuery.each(package_cbs, function (i, cb) {
        var domain = jQuery(jQuery(cb).closest('tr')).find('.wpml-st-col-domain').text();
        affected_package_counts[domain] = affected_package_counts.hasOwnProperty(domain) ? affected_package_counts[domain] : package_counts[domain];
        affected_package_counts[domain] = affected_package_counts[domain] - 1;
        jQuery(cb).data('package-domain', domain);
    });
    var checked_cbs = st_table.find('.icl_st_row_cb:checked');
    checked_cbs = checked_cbs.length > 0 ? checked_cbs : [];
    var incomplete_packages = false;
    var domain;
    for(domain in affected_package_counts){
        if(affected_package_counts.hasOwnProperty(domain) && affected_package_counts[domain] > 0){
            incomplete_packages = true;
        }
    }

	var non_default_lang_string_selected = false;

	for (const checkbox of checked_cbs) {
		if(jQuery(checkbox).data('language') !== wpml_st_main_ui.defaultLang) {
			non_default_lang_string_selected = true;
			break;
		}
	}

	show_notice('#wpml-st-package-incomplete', !incomplete_packages || non_default_lang_string_selected || checked_cbs.length === 0);

    return incomplete_packages ? [] : checked_cbs;
}

function update_bulk_action_selector_panel_inside_table(renderBulkActionSelectorPanel) {
	// Exit if not the ST main page i.e. import page.
	if ( ! jQuery('#icl_string_translations').length ) {
		return;
	}

    // setTimeout is required to get correct checked checkboxes count after update.
    setTimeout(function() {
        var pagesCount = jQuery('.js-icl-st-tablenav').find('.page-numbers').length;
        if(pagesCount < 2) {
            renderBulkActionSelectorPanel = false;
        }

        var msg = jQuery('.js-wpml-st-table').find('.js-wpml-st-icl-string-translations-bulk-select-msg');

		var isUseBulkSelected = msg.get(0).hasAttribute('data-is-apply-bulk-action-selected');
        var displaySelectedAllOnPage = 'inline-block';
        var displaySelectedAll = 'none';
        var pClass = '.js-selected-all-on-page-msg';
        if(isUseBulkSelected) {
            pClass = '.js-selected-all-msg';
            displaySelectedAllOnPage = 'none';
            displaySelectedAll = 'inline-block';
        }

        msg.find('.js-selected-all-on-page-msg').css('display', displaySelectedAllOnPage);
        msg.find('.js-select-all-link').css('display', displaySelectedAllOnPage);
        msg.find('.js-selected-all-msg').css('display', displaySelectedAll);
        msg.find('.js-unselect-all-link').css('display', displaySelectedAll);

        var count = get_checked_cbs().length;
        var allItemsCount = 0;
        var selectedDomainText = jQuery('select[name="icl_st_filter_context"] option:selected').text();
        var res = /\(([^)]+)\)/.exec(selectedDomainText);
        if(!res || res.length < 2) {
            renderBulkActionSelectorPanel = false;
            count = 0;
        }
        else {
            allItemsCount = res[1];
        }
        if(isUseBulkSelected) {
            count = allItemsCount;
        }

        msg.css('display', (renderBulkActionSelectorPanel) ? 'table-cell' : 'none');

        msg.each(function() {
            var msg = jQuery(this);
            var html = msg.find(pClass).html();
            if(html.search('%d') !== -1) {
                var parts = html.split(' ');

                var nextParts = [];
                for(var i = 0; i < parts.length; i++) {
                    nextParts.push((parts[i] !== '%d') ? parts[i] : '<span class="batch-items-count">' + count + '</span>');
                }

                msg.find(pClass).html(jQuery(jQuery.parseHTML(nextParts.join(' '))));
            } else {
                msg.find('.batch-items-count').text(count);
            }
        });
    }, 0);
}

function icl_st_update_checked_elements() {
	const is_st_main_page = jQuery('#icl_string_translations').length === 1

    if (jQuery(this).closest('th, td').hasClass('manage-column')) {
        var isChecked = jQuery(this).prop('checked');
        jQuery('.js-icl-st-row-cb').prop('checked', isChecked);
        update_bulk_action_selector_panel_inside_table(isChecked);
    }

    var selectedStringsCount = get_checked_cbs().length;

	if ( is_st_main_page )  {
		jQuery('#icl-st-change-lang-selected').wpml_select2().prop('disabled', selectedStringsCount === 0);
		jQuery('#icl-st-change-translation-priority-selected').wpml_select2().prop('disabled', selectedStringsCount === 0);
		jQuery('.js-change-translation-priority .wpml_select2-choice, .js-simple-lang-selector-flags .wpml_select2-choice').attr('disabled', selectedStringsCount === 0)
			.addClass('button button-secondary');
	}


    if (!jQuery('.icl_st_row_cb:checked').length) {
        jQuery('#icl-st-delete-selected, #icl_send_strings').prop('disabled', true);
        WPML_String_Translation.translation_basket.clear_message();
    } else {
        jQuery('#icl-st-delete-selected').prop('disabled', false);
        var iclTROpt = jQuery('#icl-tr-opt');
        if (!iclTROpt.length || iclTROpt.find('input:checked').length) {
            if (WPML_String_Translation.translation_basket.maybe_enable_button()) {
                WPML_String_Translation.translation_basket.show_target_languages();
            }
        }
    }
    jQuery('.icl_st_estimate_wrap:visible').each(function () {
        var lang = jQuery(this).attr('id').replace(/icl_st_estimate_(.+)_wrap/, '$1');
        icl_st_show_estimated_cost(lang);
    });

    if (jQuery(this).hasClass('icl_st_row_cb')) {
        set_bulk_selects(jQuery('.icl_st_row_cb:not(:checked)').length === 0);
    }
}

function set_bulk_selects(bulk_cb_checked) {
    jQuery('.check-column input').prop('checked', bulk_cb_checked);
    update_bulk_action_selector_panel_inside_table(bulk_cb_checked);
}

function icl_validate_po_upload(){
    var cont = jQuery(this).contents();
    cont.find('.icl_error_text').hide();
    if(!jQuery('#icl_po_file').val()){
        cont.find('#icl_st_err_po').fadeIn();
        return false;
    }
    if(!cont.find('select[name="icl_st_i_context"]').val() && !cont.find('input[name="icl_st_i_context_new"]').val()){
        cont.find('#icl_st_err_domain').fadeIn();
        return false;
    }
}

var icl_show_in_source_scroll_once = false;
jQuery(document).delegate('#icl_show_source_wrap', 'mouseover', function(){
    if(icl_show_in_source_scroll_once){
        icl_show_in_source(0, icl_show_in_source_scroll_once);
        icl_show_in_source_scroll_once = false;
    }
});

function icl_show_in_source(tabfile, line){

    if(icl_show_in_source_scroll_once){
        if(line > 40){
            line = line - 10;
            location.href= WPML_core.sanitize(location.protocol+'//'+location.host+location.pathname+location.search+'#icl_source_line_'+tabfile+'_'+line);
        }
    }else{
        jQuery('.icl_string_track_source').fadeOut(
            function(){
                jQuery('#icl_string_track_source_'+tabfile).fadeIn(
                    function(){
                        if(line > 40){
                            line = line - 10;
                            location.href= WPML_core.sanitize(location.protocol+'//'+location.host+location.pathname+location.search+'#icl_source_line_'+tabfile+'_'+line);
                        }
                    }
                );
            }
        );
    }
    return false;
}

function iclResizeIframe() {
    var frame = jQuery('#icl_string_track_frame_wrap').find('iframe');
    var tbAjaxContent = jQuery('#TB_ajaxContent');
    frame.attr('height', tbAjaxContent.height() - 20);
    frame.attr('width', tbAjaxContent.width());
}

function icl_st_admin_options_form_submit(){
    if(jQuery('input:checkbox.icl_st_has_translations:not(:checked)').length){
        iclHaltSave = !confirm(jQuery('#icl_st_options_write_confirm').html());
    }
    iclSaveForm_success_cb.push(function(){
        jQuery('#icl_st_options_write_success').fadeIn();
    });
}


function icl_st_pop_download(){
    var file = jQuery(this).data('file');
    var domain = jQuery(this).data('domain');

    location.href = WPML_core.sanitize( ajaxurl + "?action=icl_st_pop_download&wpnonce=" + wpml_scripts_data.nonce_icl_st_pop_download_nonce + "&file=" + file + "&domain=" + domain );

    return false;
}

function icl_st_selected_word_count() {
    var word_count = 0;
    jQuery('.icl_st_row_cb:checked').each(function () {
        var string_id = WPML_core.sanitize(jQuery(this).val());
        word_count += parseInt(jQuery('#icl_st_wc_' + string_id).val())
    });
    return word_count;
}

function icl_st_show_estimated_cost(lang){
    var estimate = icl_st_selected_word_count() * jQuery('#icl_st_max_rate_'+lang).html();
    jQuery('#icl_st_estimate_'+lang).html(Math.round(estimate*100)/100);
    jQuery('#icl_st_estimate_'+lang+'_wrap').show();
}

function icl_st_hide_estimated_cost(lang){
    jQuery('#icl_st_estimate_'+lang+'_wrap').hide();
}

function icl_st_change_service(){

    var lang = jQuery(this).attr('name').replace(/service\[(.+)]/ , '$1');
    if(jQuery(this).val()=='icanlocalize'){
        if(jQuery('#icl_st_translate_to_'+lang).prop('checked')){
            icl_st_show_estimated_cost(lang);
        }
    }else{
        icl_st_hide_estimated_cost(lang);
    }

}

/**
 * Update PO import language select on load and source language change.
 *
 * @param importSourceLangSelect PO import source language select.
 * @param poSelect PO import translation language select.
 */
function updateImportPoTranslationLanguage(importSourceLangSelect, poSelect) {
	const currentlySelectedSourceLang = {
		'value': importSourceLangSelect.val(),
		'label': importSourceLangSelect.children('option:selected').text()
	}

	if (currentlySelectedSourceLang.value && currentlySelectedSourceLang.label) {
		const lastRemovedOption = poSelect.data('lastRemovedOption');
		if (typeof lastRemovedOption === 'object') {
			poSelect.append(jQuery('<option>', {value: lastRemovedOption.value, text: lastRemovedOption.label}))
			poSelect.data('lastRemovedOption', false);
		}

		const optionToRemove = poSelect.children('option[value="'+ currentlySelectedSourceLang.value +'"]');
		// Only store the language when it exists on import translation language select.
		if (optionToRemove.length > 0) {
			poSelect.data('lastRemovedOption', currentlySelectedSourceLang);
			optionToRemove.remove();
		}
	}
}

/**
 * If there is any import error expand import/export and scroll to box.
 */
function maybeScrollToStImportErrorNotice() {
	const noticeContainer = jQuery('#wpml-st-import-error-notice-js').parents('.postbox.closed');

	if ( noticeContainer.length ) {
		noticeContainer.removeClass('closed').addClass('opened');
		jQuery('html, body').animate({
			scrollTop: noticeContainer.offset().top
		}, 750);
	}
}

jQuery(document).ready(function($) {
	var viewScannedStringInterval = null;
	jQuery('.thickbox').click(function() {
		clearInterval(viewScannedStringInterval);
		const newPopupTitle = $(this).data('popup-title');
		const popupDescription = $(this).data('popup-description');
		const domainTitle = $(this).data('domain-title');
		const stringTitle = $(this).data('string-title');
		const domain = $(this).data('domain');
		const string = $(this).data('string');
		const iFrameTitle = $(this).data('iframe-title');
		const imageURL = $(this).data('image-url');
		viewScannedStringInterval = setInterval(function() {
			$('#TB_overlay').addClass('string-preview-overlay');
			if($('#TB_window').is(':visible')) {
				clearInterval(viewScannedStringInterval);
				$('#TB_window').addClass('string-preview');
				$('#TB_window').attr('role', 'dialog');
				$('#TB_window').attr('aria-modal', 'true');
				$('#TB_window').attr('aria-labelledby', 'TB_ajaxWindowTitleText');
				$('#TB_window').attr('aria-describedby', 'TBTB_ajaxWindowTitleDescription');
				$('#TB_ajaxWindowTitle').html(`
					<h3 id="TB_ajaxWindowTitleText">${newPopupTitle}</h3>
					<p id="TBTB_ajaxWindowTitleDescription">${popupDescription}</p>
					<div class="string-info-box">
						<div class="domain">
							<h4 class="string-info-title">${domainTitle}</h4>
							<span>${domain}</span>
						</div>
						<div class="string">
							<h4 class="string-info-title">${stringTitle}</h4>
							<span>
								<img width="18" height="12" src="${imageURL}" role="presentation" />
								${string}
							</span>
						</div>
					</div>
				`);
				$('#TB_title').after(`<h4>${iFrameTitle}</h4>`);
			}
		}, 50);
	});
});
