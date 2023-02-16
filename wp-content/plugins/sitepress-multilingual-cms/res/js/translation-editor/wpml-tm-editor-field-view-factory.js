var WPML_TM = WPML_TM || {};

WPML_TM.FIELD_STYLE_SINGLE_LINE = '0';
WPML_TM.FIELD_STYLE_TEXT_AREA = '1';
WPML_TM.FIELD_STYLE_WYSIWYG = '2';

WPML_TM.fieldViewFactory = {
	create: function (field, args) {
		var view = null;
		if (field.field_style === WPML_TM.FIELD_STYLE_SINGLE_LINE) {
			view = new WPML_TM.editorSingleLineFieldView(args);
		} else if (field.field_style === WPML_TM.FIELD_STYLE_TEXT_AREA) {
			view = new WPML_TM.editorTextareaFieldView(args);
		} else if (field.field_style === WPML_TM.FIELD_STYLE_WYSIWYG) {
			view = new WPML_TM.editorWysiwygFieldView(args);
		}
		return view;
	}

};

