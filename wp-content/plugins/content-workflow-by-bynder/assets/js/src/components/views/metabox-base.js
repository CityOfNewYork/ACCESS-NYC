module.exports = function (app, $, gc) {
	return app.views.base.extend({
		el: '#gc-related-data',

		ajax: function (args, successcb, failcb) {
			var view = this;
			var success = function (response) {
				if (response.success) {
					successcb.call(view, response.data);
				} else if (failcb) {
					failcb.call(view, response.data);
				}
			};

			var promise = $.post(window.ajaxurl, $.extend({
				action: '',
				post: this.model.toJSON(),
				nonce: gc.$id('gc-edit-nonce').val(),
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
			}, args), success);

			if (failcb) {
				promise.fail(function () {
					failcb.call(view);
				});
			}

			return promise;
		}
	});
};
