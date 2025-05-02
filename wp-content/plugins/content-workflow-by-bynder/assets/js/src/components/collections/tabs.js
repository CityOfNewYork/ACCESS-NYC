module.exports = function (app) {
	return app.collections.base.extend({
		model: app.models.tab,
	});
};
