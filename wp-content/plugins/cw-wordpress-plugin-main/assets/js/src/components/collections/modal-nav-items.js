module.exports = function (app) {
	return app.collections.base.extend({
		model: app.models.navItem,

		initialize: function () {
			this.listenTo(this, 'activate', this.activate);
		},

		getActive: function () {
			return this.find(function (model) {
				return !model.get('hidden');
			});
		},

		activate: function (id) {
			this.each(function (model) {
				model.set('hidden', true);
			});
			this.getById(id).set('hidden', false);
			this.trigger('render');
		}
	});
};
