module.exports = Backbone.Collection.extend({
	getById: function (id) {
		return this.find(function (model) {
			var modelId = model.get('id');
			return modelId === id || modelId && id && modelId == id;
		});
	},
});
