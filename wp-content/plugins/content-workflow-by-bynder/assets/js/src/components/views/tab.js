module.exports = function (app) {
	return app.views.statusSelect2.extend({
		template: wp.template('gc-tab-wrapper'),

		tagName: 'fieldset',

		id: function () {
			return this.model.get('id');
		},

		className: function () {
			return 'gc-template-tab ' + (this.model.get('hidden') ? 'hidden' : '');
		},

		render: function () {
			this.$el.html(this.template(this.model.toJSON()));

			var rendered = this.getRenderedModels(app.views.tabRow, this.model.rows);

			this.$el.find('tbody').html(rendered);

			return this;
		}
	});
};
