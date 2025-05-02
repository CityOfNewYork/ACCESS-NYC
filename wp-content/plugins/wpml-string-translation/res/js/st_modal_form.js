var WPML_String_Translation = WPML_String_Translation || {};

WPML_String_Translation.ModalForm = function($trigger, dialog, settings) {
    this.$trigger = $trigger;
    this.dialog = dialog;
    this.settings = settings || {};
    this.form = dialog.find('form');
    this.rmConfirmMsgTimeout = null;

    if(typeof this.settings.onSave === 'undefined') {
        this.settings.onSave = function() {};
    }

    this.create_dialog();
};

WPML_String_Translation.ModalForm.prototype = {
    create_dialog: function () {
        var that  = this;

		var dialogSettings = {
			autoOpen: false,
			width: 'auto',
			modal: true,
			buttons: [
				{
					class: 'wpml-st-cancel-button',
					text: that.dialog.attr('data-cancelButtonTitle'),
					click: function() {
						that.rmConfirmMsg.call(that);
						jQuery( this ).dialog( 'close' );
					}
				},
				{
					text: that.dialog.attr('data-saveButtonTitle'),
					class: 'button-primary js-wpml-st-apply-button',
					click: function() {
						that.rmConfirmMsg.call(that);
						that.settings.onSave();
					},
				}
			],
			create: function() {
				jQuery(this).css('maxWidth', '650px');
			},
		};
		if(this.settings.width) {
			dialogSettings.width = this.settings.width;
		}

        this.dialog.dialog(dialogSettings);

        this.dialog.on('click', '.checkbox-label', function(event) {
            jQuery(this).parent().find('input[type="checkbox"]').trigger('click');
        });

        this.$trigger.on('click', {'dialog' : this.dialog}, function(event) {
            event.preventDefault();

            event.data.dialog.dialog('open');
            event.data.dialog.closest('.ui-dialog').addClass('wpml-st-modal-form');

            // We need to set title as block element to put inside block element with icon on new line, so should replace span with div.
            var titleEl = event.data.dialog.closest('.ui-dialog').find('.ui-dialog-title')[0];
            titleEl.outerHTML = titleEl.outerHTML.replace(/<span/g, '<div').replace(/<\/span/g, '</div');

            that.init_select_all();
        });

        this.form.find('input[name = "select_all"]').on('click', {'form': this.form}, this.select_all);
        this.form.find('.checkboxes-list input:checkbox').on('change', {'form': this.form}, this.toggle_checkbox);
    },

    init_select_all: function() {
        var areAllChecked = true;
        this.form.find('.checkboxes-list input').each(function() {
            var checkbox = jQuery(this);
            if(!checkbox.prop('checked')) {
                areAllChecked = false;
            }
        });

        this.form.find('input[name = "select_all"]').prop( 'checked', areAllChecked )
    },

    toggle_checkbox: function(event) {
        if (jQuery(this).is(':checked')) {
            if (event.data.form.find('.checkboxes-list input:checkbox:not(:checked)').length == 0) {
                event.data.form.find('input[name = "select_all"]').prop( 'checked', true );
            }
        } else {
            event.data.form.find('input[name = "select_all"]').prop( 'checked', false );
        }
    },

    select_all: function(event) {
        if (jQuery(this).is(':checked')) {
            event.data.form.find('.checkboxes-list input').prop( 'checked', true );
        } else {
            event.data.form.find('.checkboxes-list input').prop( 'checked', false );
        }
    },

    enableLoading: function() {
        var apply_button = this.dialog.parent().find('.js-wpml-st-apply-button');
        apply_button.prop('disabled', true);
    },

    disableLoading: function() {
        var apply_button = this.dialog.parent().find('.js-wpml-st-apply-button');
        apply_button.prop('disabled', false);
    },

    showSaveConfirmMsg: function() {
        var self = this;
        var html = '<span class="icl_ajx_response" style="position: absolute; right: 100px; bottom: 22px; display: block">' + this.dialog.attr('data-saveConfirmMsg') + '</span>';
        this.dialog.closest('.wpml-st-modal-form').find('.ui-dialog-buttonset').append(jQuery(html));

        this.rmConfirmMsgTimeout = setTimeout(function() {
            self.rmConfirmMsg.call(self);
        }, 3000);
    },

    rmConfirmMsg: function() {
        clearTimeout(this.rmConfirmMsgTimeout);
        this.dialog.closest('.wpml-st-modal-form').find('.icl_ajx_response').remove();
    },
};