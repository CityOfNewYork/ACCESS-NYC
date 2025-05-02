module.exports = function (app) {
	return app.collections.base.extend({
		model: app.models.tabRow,

		initialize: function (models, options) {
			this.tab = options.tab;
		}
	});
};
