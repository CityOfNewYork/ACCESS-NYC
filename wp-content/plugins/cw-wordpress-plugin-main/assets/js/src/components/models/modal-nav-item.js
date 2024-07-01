module.exports = function (app) {
	return app.models.base.extend({
		defaults: {
			label: '',
			id: '',
			hidden: true,
			rendered: false
		}
	});
};
