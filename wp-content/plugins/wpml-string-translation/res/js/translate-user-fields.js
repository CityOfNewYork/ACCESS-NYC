jQuery(function () {
    WPML_String_Translation.TranslateUserFields.init(jQuery('.wpml-st-translate-user-fields'));
});

var WPML_String_Translation = WPML_String_Translation || {};

WPML_String_Translation.TranslateUserFields = {
    init: function(box) {
        var self = this;
		self.isInitialised = false;

        this.form = box.find('form');
        this.box = box;
        this.dialog = this.form.parent();
        this.modalForm = new WPML_String_Translation.ModalForm(this.box.find('.js-wpml-translate-user-fields'), this.dialog, {
            onSave: function() {
                self.save.apply(self);
            },
        });
    },

    save: function() {
        var self = this;
        this.modalForm.enableLoading();

        if(!this.isInitialised) {
            this.isInitialised = true;
            this.form.submit(iclSaveForm);
            this.form.submit(function () {
                self.modalForm.disableLoading();
                self.modalForm.showSaveConfirmMsg();
            });
        }
        this.form.submit();
    },
}