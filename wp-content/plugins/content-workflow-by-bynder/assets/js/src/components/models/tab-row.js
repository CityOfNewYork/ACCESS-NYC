module.exports = function (app, gc) {
	return require('./../models/modify-json.js')(app.models.base.extend({
		defaults: {
			id: '',
			label: '',
			name: '',
			field_type: '',
			type: '',
			typeName: '',
			post_type: 'post',
			field_value: false,
			field_field: false,
			field_subfields: false,
			expanded: false,
			required: false,
			value: '',
			microcopy: '',
			limit_type: '',
			limit: 0,
			plain_text: false,
		},

		_get_post_type: function (value) {
			return app.mappingView ? app.mappingView.defaultTab.get('post_type') : value;
		},

		_get_type: function (value) {
			if ('text' === value) {
				value = this.get('plain_text') ? 'text_plain' : 'text_rich';
			}
			return value;
		},

		_get_typeName: function (value) {
			value = this.get('type');
			return gc._type_names[value] ? gc._type_names[value] : value;
		}
	}));
};
