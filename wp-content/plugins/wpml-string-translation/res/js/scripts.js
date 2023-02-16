jQuery(function () {
    var filterSearch = jQuery("#icl_st_filter_search");

    jQuery('.wpml-colorpicker').wpColorPicker();

    jQuery('select[name="icl_st_filter_status"]').change(icl_st_filter_status);
    jQuery('select[name="icl_st_filter_context"]').change(icl_st_filter_context);
    jQuery('select[name="icl-st-filter-translation-priority"]').change(icl_st_filter_translation_priority);
    jQuery('#icl_st_filter_search_sb').click(icl_st_filter_search);

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
    jQuery('#icl_st_delete_selected').click(icl_st_delete_selected);

    jQuery('#icl_st_po_translations').click(function(){
        var po_language = jQuery('#icl_st_po_language');
        po_language.prop('disabled', !jQuery(this).prop('checked'));
        if(jQuery(this).prop('checked')){
            po_language.fadeIn();
        }else{
            po_language.fadeOut();
        }
    });
    var iclTMLanguages = jQuery('#icl_tm_languages');
    iclTMLanguages.find(':checkbox').click(icl_st_update_languages);
    jQuery('.icl_st_row_cb, .check-column :checkbox').click(icl_st_update_checked_elements);
    iclTMLanguages.find('select').change(icl_st_change_service);
    jQuery('#icl_st_po_form').submit(icl_validate_po_upload);
    jQuery('#icl_st_send_strings').submit(icl_st_send_strings);
    jQuery('#icl_st_translate_to_all').click(icl_st_select_all);

    jQuery('.handlediv').click(function () {
        jQuery(this).parent().toggleClass('closed');
    });

    jQuery('#icl_st_track_strings').submit(iclSaveForm);

    var iclSTOptionWriteForm = jQuery('#icl_st_option_write_form');
    iclSTOptionWriteForm.submit(icl_st_admin_options_form_submit);
    iclSTOptionWriteForm.submit(iclSaveForm);

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
    ICLSTMoreOptions.submit(
        function () {
            var iclSTTUser = jQuery('#icl_st_tusers');
            if (!iclSTTUser.find('label input:checked').length) {
                jQuery('#icl_st_tusers_list').html('-');
            } else {
                jQuery('#icl_st_tusers_list').html(iclSTTUser.find('label input:checked').next().map(
                    function () {
                        return jQuery(this).html();
                    }).get().join(', '))
            }
        }
    );

	function updateControls() {
		jQuery('#icl_st_filter_search_em').prop('disabled', filterSearch[0].value === '');
		jQuery('#search_translation').prop('disabled', filterSearch[0].value === '');
	}
});


function icl_st_filter_status(){
    var qs = jQuery(this).val() != '' ? '&status=' + WPML_core.sanitize(jQuery(this).val()) : '';
    location.href = WPML_core.sanitize(location.href).replace(/#(.*)$/,'').replace(/&paged=([0-9]+)/,'').replace(/&updated=true/,'').replace(/&status=([0-9a-z-]+)/g,'') + qs;
}

function icl_st_filter_context(){
    var qs = jQuery(this).val() != '' ? '&context=' + encodeURIComponent(jQuery(this).val()) : '';
    location.href= WPML_core.sanitize(location.href).replace(/#(.*)$/,'').replace(/&paged=([0-9]+)/,'').replace(/&updated=true/,'').replace(/&context=(.*)/g,'') + qs;
}

function icl_st_filter_translation_priority(){
    var qs = jQuery(this).val() != '' ? '&translation-priority=' + encodeURIComponent(jQuery(this).val()) : '';
    location.href= WPML_core.sanitize(location.href).replace(/#(.*)$/,'').replace(/&paged=([0-9]+)/,'').replace(/&updated=true/,'').replace(/&translation-priority=(.*)/g,'') + qs;
}

function icl_st_filter_search(){
	var context            = jQuery('select[name="icl_st_filter_context"]').val(),
		search_text        = jQuery('#icl_st_filter_search').val(),
		exact_match        = jQuery('#icl_st_filter_search_em').prop('checked'),
		search_translation = jQuery('#search_translation').prop('checked'),
		query_string       = search_text !== '' ? '&search=' + encodeURIComponent(search_text) : '',
		url                = WPML_core.sanitize(location.href);

	query_string = query_string.replace(/&em=1/g,'');

	if (icl_st_getUrlParameter('show_results') === 'all') {
		search_translation = false;
	}

	if(exact_match){
		query_string += '&em=1';
	}
	if (search_translation) {
		query_string += '&search_translation=1';
	}

	url = url.replace(/#(.*)$/,'')
			.replace(/&paged=([0-9]+)/,'')
			.replace(/&updated=true/,'')
			.replace(/&search=(.*)/g,'') + query_string;

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

function show_package_incomplete_notice(hide) {
    var notice = jQuery('#wpml-st-package-incomplete');
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
    show_package_incomplete_notice( !incomplete_packages || checked_cbs.length === 0);

    return incomplete_packages ? [] : checked_cbs;
}

function icl_st_update_checked_elements() {
    if (jQuery(this).closest('th, td').hasClass('manage-column')) {
        jQuery('.icl_st_row_cb').prop('checked', jQuery(this).prop('checked'));
    }

    var selectedStringsCount = get_checked_cbs().length;
    jQuery('#icl_st_change_lang_selected').wpml_select2().prop('disabled', selectedStringsCount === 0);
    jQuery('#icl-st-change-translation-priority-selected').wpml_select2().prop('disabled', selectedStringsCount === 0);
    jQuery('.js-change-translation-priority .wpml_select2-choice, .js-simple-lang-selector-flags .wpml_select2-choice').attr('disabled', selectedStringsCount === 0)
                                                                                                              .addClass('button button-secondary');

    if (!jQuery('.icl_st_row_cb:checked').length) {
        jQuery('#icl_st_delete_selected, #icl_send_strings').prop('disabled', true);
        WPML_String_Translation.translation_basket.clear_message();
    } else {
        jQuery('#icl_st_delete_selected').prop('disabled', false);
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

    location.href = WPML_core.sanitize( ajaxurl + "?action=icl_st_pop_download&file=" + file + "&domain=" + domain );

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
