/*globals jQuery, wpmlData */

/**
 * @typedef {Object} Localization
 * @property {string} subtitle
 * @property {string} suid_label
 * @property {string} title
 * @property {string} enabled_service
 * @property {string} refresh_page
 * @property {string} server_error
 * @property {string} something_went_wrong
 */

/**
 * @typedef {Object} WPMLData
 * @property {Localization} localization
 * @property {string} ajaxUrl
 * @property {string} nonce
 */

/** @type {WPMLData} */
var wpmlData;

/**
 * Handles the logic for:
 * - Displaying/rendering the dialog form for adding an unlisted translation service.
 * - Sending AJAX requests to enable the service using the SUID.
 * - Showing server responses.
 *
 * This feature allows users to add an unlisted translation service via the UI.
 * Users can click the "Activate a translation service that's not listed here" button in WPML->Translation Management.
 * A dialog form will appear, allowing them to enable an unlisted translation service using its SUID.
 */

 var WPMLUnlistedTranslationService = function () {
    var self = this;
    self.buttonShowForm = jQuery('#add-unlisted-translation-service');
    self.ajaxSpinner = jQuery('<span class="spinner is-active"></span>');

    self.form = jQuery(
        '<div class="wpml-min-width-480px">' +
            '<p class="wpml-text-base wpml-mb-20px wpml-mt-0px">' + wpmlData.localization.subtitle + '</p>' +
            '<div id="response" style="display:none;" class="wpml-response wpml-mb-20px">' +
                '<span class="icon otgs-ico-info-o"></span> ' +
                '<div class="wpml-response-content">' +
                    '<p id="title"></p>' +
                    '<p id="description"></p>' +
                '</div>' +
            '</div>' +
            '<div class="wpml-form-row wpml-mb-20px">' +
                '<label for="suid" class="wpml-no-margin">' + wpmlData.localization.suid_label + '</label>' +
                '<input type="text" id="suid" name="suid" size="42"/>' +
            '</div>' +
      '<div class="wpml-buttons">' +
        '<button type="button" class="wpml-button-secondary">Cancel</button>' +
        '<div class="btn-submit-with-spinner">' +
          '<button type="button" class="wpml-button-primary wpml-flex">' +
              '<span>Submit</span>' +
              '<span class="wpml-spinner wpml-hidden"></span>' +
           '</button>' +
        '</div>' +
      '</div>' +
        '</div>' +
      '');

    self.form.response = self.form.find('#response');
    self.form.response.title = self.form.response.find('#title');
    self.form.response.description = self.form.response.find('#description');
    self.form.buttons = self.form.find('.wpml-buttons');
    self.form.buttons.submit = self.form.find('.wpml-button-primary');
    self.form.buttons.cancel = self.form.find('.wpml-button-secondary');
    self.form.buttons.spinner = self.form.find('.wpml-spinner');
    self.form.fields = {};
    self.form.fields.suid = self.form.find('#suid');

    self.form.buttons.cancel.on('click', function (event) {
      jQuery(self.form).dialog("close");
    });


  self.form.buttons.submit.on('click', function (event) {
    self.form.response.css('display', 'none');
    disableformButtons();
    jQuery.ajax({
      type: "POST",
      url: wpmlData.ajaxUrl,
      data: {
        'action': 'translation_service_enable_unlisted_service',
        'nonce': wpmlData.nonce,
        'suid': self.form.fields.suid.val()
      },
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          showSuccessResponse(wpmlData.localization.enabled_service, wpmlData.localization.refresh_page);
          hideFormButtons();
          window.location.reload();
        } else {
          showErrorResponse(wpmlData.localization.server_error, wpmlData.localization.something_went_wrong);
        }
      },
      error: function (jqXHR, status, error) {
        try {
          showErrorResponse(jqXHR.responseJSON.data.title, jqXHR.responseJSON.data.description);
        } catch (e) {
          let generic_error = jqXHR.statusText || status || error;
          showErrorResponse(generic_error, generic_error);
        }
      },
      complete: function () {
        enableFormButtons();
      }
    });
  });

    self.buttonShowForm.on('click', function (event) {
        reset();
        self.form.dialog({
            dialogClass: 'wpml-dialog otgs-ui-dialog wpml-dialog-v2 wpml-priority',
            width: 'auto',
            title: wpmlData.localization.title,
            modal: true,
            buttons: []
        });
    })


    function showErrorResponse(title, description) {
        self.form.response.addClass('wpml-response-error');
        self.form.response.removeClass('wpml-response-success');
        showResponse(title, description);
    }

    function showSuccessResponse(title, description) {
        self.form.response.removeClass('wpml-response-error');
        self.form.response.addClass('wpml-response-success');
        showResponse(title, description);
    }

    function showResponse(title, description) {
        self.form.response.title.html(title);
        self.form.response.description.html(description);
        self.form.response.css('display', 'flex');
    }

    function disableformButtons() {
        self.form.buttons.cancel.attr('disabled', true);
        self.form.buttons.submit.attr('disabled', true);
        showSpinner();
    }

    function enableFormButtons() {
        self.form.buttons.cancel.attr('disabled', false);
        self.form.buttons.submit.attr('disabled', false);
        hideSpinner();
    }
    function hideFormButtons() {
      self.form.buttons.addClass('wpml-hidden');
    }

    function showSpinner() {
        self.form.buttons.spinner.removeClass('wpml-hidden');
        self.form.buttons.spinner.addClass('wpml-show');
    }

    function hideSpinner() {
      self.form.buttons.spinner.addClass('wpml-hidden');
      self.form.buttons.spinner.removeClass('wpml-show');
    }

    function reset(){
        self.form.fields.suid.val('');
        self.form.response.css('display', 'none');
    }

}


jQuery(function () {
    "use strict";
    var service = new WPMLUnlistedTranslationService();
});
