module.exports = function (app, gc) {
	return require('./../models/modify-json.js')(app.models.base.extend({
		defaults: {
			id: 0,
			item: 0,
			itemName: 0,
			project_id: 0,
			parent_id: 0,
			template_id: 0,
			custom_state_id: 0,
			position: 0,
			name: '',
			config: '',
			notes: '',
			type: '',
			typeName: '',
			overdue: false,
			archived_by: '',
			archived_at: '',
			created_at: null,
			updated_at: null,
			status: null,
			due_dates: null,
			expanded: false,
			checked: false,
			post_title: false,
			ptLabel: false,
		},

		searchAttributes: [
			'itemName',
			'post_title',
		],

		_get_item: function (value) {
			return this.get('id');
		},

		_get_typeName: function (value) {
			if (!value) {
				value = Backbone.Model.prototype.get.call(this, 'type');
			}
			return gc._type_names[value] ? gc._type_names[value] : value;
		}
	}));
};
