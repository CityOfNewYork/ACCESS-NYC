module.exports = function (app) {
	return app.views.base.extend({
		tagName: 'a',

		id: function () {
			return 'tabtrigger-' + this.model.get('id');
		},

		className: function () {
			return 'nav-tab ' + (this.model.get('hidden') ? '' : 'nav-tab-active') + ' ' + this.model.get('navClasses');
		},

		render: function () {
			this.$el.text(this.model.get('label')).attr('href', '#' + this.model.get('id'));

			return this;
		}

	});
};
