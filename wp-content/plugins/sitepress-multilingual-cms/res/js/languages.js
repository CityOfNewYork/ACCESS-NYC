/* jslint browser: true, nomen: true, laxbreak: true */
/* global WPML_core, ajaxurl, iclSaveForm, iclSaveForm_success_cb, jQuery, alert, confirm, icl_ajx_url, icl_ajx_saved, icl_ajxloaderimg, icl_default_mark, icl_ajx_error, fadeInAjxResp */

(function () {
  jQuery(function () {
    let icl_hide_languages

    jQuery('.toggle:checkbox').click(iclHandleToggle)
    jQuery('#icl_change_default_button').click(editingDefaultLanguage)
    jQuery('#icl_save_default_button').click(saveDefaultLanguage)
    jQuery('#icl_cancel_default_button').click(doneEditingDefaultLanguage)
    jQuery('#icl_add_remove_button').click(showLanguagePicker)
    jQuery('#icl_cancel_language_selection').click(hideLanguagePicker)
    jQuery('#icl_save_language_selection').click(saveLanguageSelection)
    jQuery('#icl_enabled_languages').find('input').prop('disabled', true)
    jQuery('#icl_save_language_negotiation_type').submit(iclSaveLanguageNegotiationType)
    jQuery('#icl_admin_language_options').submit(iclSaveForm)
    jQuery('#icl_lang_more_options').submit(iclSaveForm)
    jQuery('#icl_blog_posts').submit(iclSaveForm)
    icl_hide_languages = jQuery('#icl_hide_languages')
    icl_hide_languages.submit(iclHideLanguagesCallback)
    icl_hide_languages.submit(iclSaveForm)
    jQuery('#icl_adjust_ids').submit(iclSaveForm)
    jQuery('#icl_automatic_redirect').submit(iclSaveForm)
    jQuery('#icl_automatic_redirect input[name="icl_automatic_redirect"]').on('click', function () {
      const $redirect_warn = jQuery(this).parents('#icl_automatic_redirect').find('.js-redirect-warning')
      if (jQuery(this).val() != 0) {
        $redirect_warn.fadeIn()
      } else {
        $redirect_warn.fadeOut()
      }
    })
    jQuery('input[name="icl_language_negotiation_type"]').change(iclLntDomains)
    jQuery('#icl_use_directory').change(iclUseDirectoryToggle)

    jQuery('input[name="show_on_root"]').change(iclToggleShowOnRoot)
    jQuery('#wpml_show_page_on_root_details').find('a').click(function () {
      if (!jQuery('#wpml_show_on_root_page').is(':checked')) {
        alert(jQuery('#wpml_show_page_on_root_x').html())
        return false
      }
    })

    jQuery('#icl_seo_options').submit(iclSaveForm)
    jQuery('#icl_seo_head_langs').on('click', update_seo_head_langs_priority)

    jQuery('#icl_avail_languages_picker').find('li input:checkbox').click(function () {
      const checkedBoxes = jQuery('#icl_avail_languages_picker').find('li input:checkbox:checked').length
      jQuery('#icl_setup_next_1').prop('disabled', checkedBoxes <= 1)
    })

    jQuery('#icl_promote_form').submit(iclSaveForm)

    jQuery(':radio[name=icl_translation_option]').change(function () {
      jQuery('#icl_enable_content_translation').prop('disabled', false)
    })
    jQuery('#icl_enable_content_translation, .icl_noenable_content_translation').click(iclEnableContentTranslation)

    jQuery(document).on('click', '#installer_registration_form :submit', function () {
      jQuery('#installer_registration_form').find('input[name=button_action]').val(jQuery(this).attr('name'))
    })

    jQuery(document).on('click', '#installer_recommendations_form :submit', function () {
      jQuery('#installer_recommendations_form').find('input[name=button_action]').val(jQuery(this).attr('name'))
    })

    jQuery(document).on('click', '#sso_information', function (e) {
      e.preventDefault()
      jQuery('#language_per_domain_sso_description').dialog({
        modal: true,
        width: 'auto',
        height: 'auto'
      })
    })
  })

  function iclHandleToggle() {
    /* jshint validthis: true */
    const self = this
    const toggleElement = jQuery(self)
    const toggle_value_name = toggleElement.data('toggle_value_name')
    const toggle_value_checked = toggleElement.data('toggle_checked_value')
    const toggle_value_unchecked = toggleElement.data('toggle_unchecked_value')
    let toggle_value = jQuery('[name="' + toggle_value_name + '"]')
    if (toggle_value.length === 0) {
      toggle_value = jQuery('<input type="hidden" name="' + toggle_value_name + '">')
      toggle_value.insertAfter(self)
    }
    if (toggleElement.is(':checked')) {
      toggle_value.val(toggle_value_checked)
    } else {
      toggle_value.val(toggle_value_unchecked)
    }
  }

  function editingDefaultLanguage() {
    jQuery('#icl_change_default_button').hide()
    jQuery('#icl_save_default_button').show()
    jQuery('#icl_cancel_default_button').show()
    const enabled_languages = jQuery('#icl_enabled_languages').find('input')
    enabled_languages.show()
    enabled_languages.prop('disabled', false)
    jQuery('#icl_add_remove_button').hide()
  }

  function doneEditingDefaultLanguage() {
    jQuery('#icl_change_default_button').show()
    jQuery('#icl_save_default_button').hide()
    jQuery('#icl_cancel_default_button').hide()
    const enabled_languages = jQuery('#icl_enabled_languages').find('input')
    enabled_languages.hide()
    enabled_languages.prop('disabled', true)
    jQuery('#icl_add_remove_button').show()
  }

  function saveDefaultLanguage() {
    let enabled_languages, arr, def_lang
    enabled_languages = jQuery('#icl_enabled_languages')
    arr = enabled_languages.find('input[type="radio"]')
    def_lang = ''
    jQuery.each(arr, function () {
      if (this.checked) {
        def_lang = this.value
      }
    })
    jQuery.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        action: 'wpml_set_default_language',
        nonce: jQuery('#set_default_language_nonce').val(),
        language: def_lang
      },
      success: function (response) {
        if (response.success) {
          let enabled_languages_items, spl, selected_language, avail_languages_picker, selected_language_item
          selected_language = enabled_languages.find('li input[value="' + def_lang + '"]')

          fadeInAjxResp(icl_ajx_saved)
          avail_languages_picker = jQuery('#icl_avail_languages_picker')
          avail_languages_picker.find('input[value="' + response.data.previousLanguage + '"]').prop('disabled', false)
          avail_languages_picker.find('input[value="' + def_lang + '"]').prop('disabled', true)
          enabled_languages_items = jQuery('#icl_enabled_languages').find('li')
          enabled_languages_items.removeClass('selected')
          selected_language_item = selected_language.closest('li')
          selected_language_item.addClass('selected')
          selected_language_item.find('label').append(' (' + icl_default_mark + ')')
          enabled_languages_items.find('input').prop('checked', false)
          selected_language.prop('checked', true)
          enabled_languages.find('input[value="' + response.data.previousLanguage + '"]').parent().html(enabled_languages.find('input[value="' + response.data.previousLanguage + '"]').parent().html().replace('(' + icl_default_mark + ')', ''))
          doneEditingDefaultLanguage()
          fadeInAjxResp('#icl_ajx_response', icl_ajx_saved)
          location.href = WPML_core.sanitize(location.href).replace(/#[\w\W]*/, '') + '&setup=2'
        } else {
          fadeInAjxResp('#icl_ajx_response', icl_ajx_error)
        }
      }
    })
  }

  function showLanguagePicker() {
    jQuery('#icl_avail_languages_picker').slideDown()
    jQuery('#icl_add_remove_button').hide()
    jQuery('#icl_change_default_button').hide()
  }

  function hideLanguagePicker() {
    const availableLanguagesPicker = jQuery('#icl_avail_languages_picker')
    availableLanguagesPicker.slideUp()
    // Revert all check/uncheck languages on clicking Cancel button.
    const arr = availableLanguagesPicker.find('ul input[type="checkbox"]')
    jQuery.each(arr, function () {
      const element = jQuery(this)
      const wasActiveBeforeCancel = element.closest('li').hasClass('wpml-selected')
      element.prop('checked', wasActiveBeforeCancel)
    })
    jQuery('#icl_add_remove_button').fadeIn()
    jQuery('#icl_change_default_button').fadeIn()
  }

  function saveLanguageSelection() {
    wpml_fadeIn('#icl_ajx_response', icl_ajxloaderimg)
    const arr = jQuery('#icl_avail_languages_picker').find('ul input[type="checkbox"]');
    const sel_lang = []
    jQuery.each(arr, function () {
      if (this.checked) {
        sel_lang.push(this.value)
      }
    })
    jQuery.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        action: 'wpml_set_active_languages',
        nonce: jQuery('#set_active_languages_nonce').val(),
        languages: sel_lang
      },
      success: function (response) {
        if (response.success) {
          if (!response.data.noLanguages) {
            wpml_fadeIn('#icl_ajx_response', icl_ajx_saved)
            jQuery('#icl_enabled_languages').html(response.data.enabledLanguages)
            location.href = WPML_core.sanitize(location.href).replace(/#[\w\W]*/, '')
          } else {
            wpml_fadeOut('#icl_ajx_response');
            location.href = WPML_core.sanitize(location.href).replace(/(#|&)[\w\W]*/, '')
          }
        } else {
          wpml_fadeIn('#icl_ajx_response', icl_ajx_error)
          console.error(response);
          location.href = WPML_core.sanitize(location.href).replace(/(#|&)[\w\W]*/, '')
        }
      },
      error: function (response){
        wpml_fadeIn('#icl_ajx_response', icl_ajx_error)
        console.error(response);
        setTimeout(()=>{
          location.href = WPML_core.sanitize(location.href).replace(/(#|&)[\w\W]*/, '')
        },3000)

      }

    })
    hideLanguagePicker()
  }

  function iclLntDomains() {
    let language_negotiation_type, icl_lnt_domains_box, icl_lnt_domains_options, icl_lnt_xdomain_options
    icl_lnt_domains_box = jQuery('#icl_lnt_domains_box')
    icl_lnt_domains_options = jQuery('#icl_lnt_domains')
    icl_lnt_xdomain_options = jQuery('#language_domain_xdomain_options')

    if (icl_lnt_domains_options.prop('checked')) {
      icl_lnt_domains_box.html(icl_ajxloaderimg)
      icl_lnt_domains_box.show()
      language_negotiation_type = jQuery('#icl_save_language_negotiation_type').find('input[type="submit"]')
      language_negotiation_type.prop('disabled', true)
      jQuery.ajax({
        type: 'POST',
        url: icl_ajx_url,
        data: 'icl_ajx_action=language_domains' + '&_icl_nonce=' + jQuery('#_icl_nonce_ldom').val(),
        success: function (resp) {
          icl_lnt_domains_box.html(resp)
          language_negotiation_type.prop('disabled', false)
          icl_lnt_xdomain_options.show()
        }
      })
    } else if (icl_lnt_domains_box.length) {
      icl_lnt_domains_box.fadeOut('fast')
      icl_lnt_xdomain_options.fadeOut('fast')
    }
    /* jshint validthis: true */
    if (jQuery(this).val() !== '1') {
      jQuery('#icl_use_directory_wrap').hide()
    } else {
      jQuery('#icl_use_directory_wrap').fadeIn()
    }
  }

  function iclToggleShowOnRoot() {
    /* jshint validthis: true */
    if (jQuery(this).val() === 'page') {
      jQuery('#wpml_show_page_on_root_details').fadeIn()
      jQuery('#icl_hide_language_switchers').fadeIn()
    } else {
      jQuery('#wpml_show_page_on_root_details').fadeOut()
      jQuery('#icl_hide_language_switchers').fadeOut()
    }
  }

  function iclUseDirectoryToggle() {
    if (jQuery(this).prop('checked')) {
      jQuery('#icl_use_directory_details').fadeIn()
    } else {
      jQuery('#icl_use_directory_details').fadeOut()
    }
  }

  function iclSaveLanguageNegotiationType() {
    let validSettings = true
    let ajaxResponse
    let usedUrls
    let formErrors
    let formName

    let languageNegotiationType
    let rootHtmlFile
    let showOnRoot
    let useDirectories
    let validatedDomains
    let domainsToValidateCount
    let domainsToValidate
    let validDomains

    const form = jQuery('#icl_save_language_negotiation_type')

    const useDirectoryWrapper = jQuery('#icl_use_directory_wrap')
    languageNegotiationType = parseInt(form.find('input[name=icl_language_negotiation_type]:checked').val())
    useDirectoryWrapper.find('.icl_error_text').hide()

    formName = form.attr('name')
    formErrors = false
    usedUrls = [jQuery('#icl_ln_home').html()]
    jQuery('form[name="' + formName + '"] .icl_form_errors').html('').hide()
    ajaxResponse = jQuery('form[name="' + formName + '"] .icl_ajx_response').attr('id')
    fadeInAjxResp('#' + ajaxResponse, icl_ajxloaderimg)

    if (languageNegotiationType === 1) {
      useDirectories = form.find('[name=use_directory]').is(':checked')
      showOnRoot = form.find('[name=show_on_root]:checked').val()
      rootHtmlFile = form.find('[name=root_html_file_path]').val()

      if (useDirectories) {
        if (showOnRoot === 'html_file' && !rootHtmlFile) {
          validSettings = false
          useDirectoryWrapper.find('.icl_error_text.icl_error_1').fadeIn()
        }
      }

      if (validSettings === true) {
        saveLanguageForm()
      }
    }

    if (languageNegotiationType === 3) {
      saveLanguageForm()
    }

    if (languageNegotiationType === 2) {
      domainsToValidate = jQuery('.validate_language_domain')
      domainsToValidateCount = domainsToValidate.length
      validatedDomains = 0
      validDomains = 0

      if (domainsToValidateCount > 0) {
        domainsToValidate.filter(':visible').each(function (index, element) {
          let languageDomainURL
          const domainValidationCheckbox = jQuery(element)
          let langDomainInput, lang, languageDomain
          lang = domainValidationCheckbox.attr('value')
          languageDomain = jQuery('.spinner.spinner-' + lang)
          langDomainInput = jQuery('#language_domain_' + lang)
          const validation = new WpmlDomainValidation(langDomainInput, domainValidationCheckbox)
          validation.run()
          const subdirMatches = langDomainInput.parent().html().match(/<code>\/(.+)<\/code>/)
          languageDomainURL = langDomainInput.parent().html().match(/<code>(.+)<\/code>/)[1] + langDomainInput.val() + '/' + (subdirMatches !== null ? subdirMatches[1] : '')
          if (domainValidationCheckbox.prop('checked')) {
            languageDomain.addClass('is-active')
            if (usedUrls.indexOf(languageDomainURL) !== -1) {
              languageDomain.empty()
              formErrors = true
            } else {
              usedUrls.push(languageDomainURL)
              langDomainInput.css('color', '#000')
              jQuery.ajax({
                method: 'POST',
                url: ajaxurl,
                data: {
                  url: languageDomainURL,
                  action: 'validate_language_domain',
                  nonce: jQuery('#validate_language_domain_nonce').val()
                },
                success: function (resp) {
                  const ajaxLanguagePlaceholder = jQuery('#ajx_ld_' + lang)
                  ajaxLanguagePlaceholder.html(resp.data)
                  ajaxLanguagePlaceholder.removeClass('icl_error_text')
                  ajaxLanguagePlaceholder.removeClass('icl_valid_text')
                  if (resp.success) {
                    ajaxLanguagePlaceholder.addClass('icl_valid_text')
                    validDomains++
                  } else {
                    ajaxLanguagePlaceholder.addClass('icl_error_text')
                  }
                  validatedDomains++
                },
                error: function (jqXHR, textStatus) {
                  jQuery('#ajx_ld_' + lang).html('')
                  if (jqXHR === '0') {
                    fadeInAjxResp('#' + textStatus, icl_ajx_error, true)
                  }
                },
                complete: function () {
                  languageDomain.removeClass('is-active')
                  if (domainsToValidateCount === validDomains) {
                    saveLanguageForm()
                  }
                }
              })
            }
          } else {
            saveLanguageForm()
          }
        })
      }
    }

    return false
  }

  function saveLanguageForm() {
    let domains
    let xdomain = 0
    let useDirectory = false
    let hideSwitcher = false
    let data
    const form = jQuery('#icl_save_language_negotiation_type')
    const formName = jQuery(form).attr('name')
    const ajxResponse = jQuery(form).find('.icl_ajx_response').attr('id')
    const sso_enabled = jQuery('#sso_enabled').is(':checked')
    const sso_notice = jQuery('#sso_enabled_notice')

    if (form.find('input[name=use_directory]').is(':checked')) {
      useDirectory = 1
    }
    if (form.find('input[name=hide_language_switchers]').is(':checked')) {
      hideSwitcher = 1
    }
    if (form.find('input[name=icl_xdomain_data]:checked').val()) {
      xdomain = parseInt(form.find('input[name=icl_xdomain_data]:checked').val())
    }
    domains = {}
    form.find('input[name^=language_domains]').each(function () {
      const item = jQuery(this)
      domains[item.data('language')] = item.val()
    })

    data = {
      action: 'save_language_negotiation_type',
      nonce: jQuery('#save_language_negotiation_type_nonce').val(),
      icl_language_negotiation_type: form.find('input[name=icl_language_negotiation_type]:checked').val(),
      language_domains: domains,
      use_directory: useDirectory,
      show_on_root: form.find('input[name=show_on_root]:checked').val(),
      root_html_file_path: form.find('input[name=root_html_file_path]').val(),
      hide_language_switchers: hideSwitcher,
      xdomain: xdomain,
      sso_enabled: sso_enabled
    }

    jQuery.ajax({

      method: 'POST',
      url: ajaxurl,
      data: data,
      success: function (response) {
        let formErrors, rootHtmlFile, rootPage, spl
        if (response.success) {
          fadeInAjxResp('#' + ajxResponse, icl_ajx_saved)
          if (sso_enabled) {
            sso_notice.addClass('updated').fadeIn()
          } else {
            sso_notice.removeClass('updated').fadeOut()
          }

          if (response.data) {
            const formMessage = jQuery('form[name="' + formName + '"]').find('.wpml-form-message')
            formMessage.addClass('updated')
            formMessage.html(response.data)
            formMessage.fadeIn()
          }

          if (jQuery('input[name=use_directory]').is(':checked') && jQuery('input[name=show_on_root]').length) {
            rootHtmlFile = jQuery('#wpml_show_on_root_html_file')
            rootPage = jQuery('#wpml_show_on_root_page')
            if (rootHtmlFile.prop('checked')) {
              rootHtmlFile.addClass('active')
              rootPage.removeClass('active')
            }

            if (rootPage.prop('checked')) {
              rootPage.addClass('active')
              rootHtmlFile.removeClass('active')
            }

          }
        } else {
          formErrors = jQuery('form[name="' + formName + '"] .icl_form_errors')
          if (formErrors.length === 0) {
            formErrors = jQuery('form[name="' + formName + '"] .wpml-form-errors')
          }
          const errors = response.data.join('<br>')
          formErrors.html(errors)
          formErrors.fadeIn()
          fadeInAjxResp('#' + ajxResponse, icl_ajx_error, true)
        }
      }
    })
  }

  function iclHideLanguagesCallback() {
    iclSaveForm_success_cb.push(function (frm, res) {
      jQuery('#icl_hidden_languages_status').html(res[1])

      // Delay 2 seconds before reloading the page to allow users to read the message after hiding the language.
      setTimeout(function () {
        window.location.reload()
      }, 2000)
    })
  }

  function iclEnableContentTranslation() {
    const val = jQuery(':radio[name=icl_translation_option]:checked').val()
    /* jshint validthis:true */
    jQuery(this).prop('disabled', true)
    jQuery.ajax({
      type: 'POST',
      url: icl_ajx_url,
      data: 'icl_ajx_action=toggle_content_translation&wizard=1&new_val=' + val,
      success: function (msg) {
        const spl = msg.split('|')
        if (spl[1]) {
          location.href = WPML_core.sanitize(spl[1])
        } else {
          location.href = WPML_core.sanitize(location.href).replace(/#[\w\W]*/, '')
        }
      }
    })
    return false
  }

  function update_seo_head_langs_priority(event) {
    const element = jQuery(this)
    jQuery('#wpml-seo-head-langs-priority').prop('disabled', !element.prop('checked'))
  }
}())
