window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.single = gc.single || {};
	var app = gc.single;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	/*
	 * Posts
	 */

	app.models.post = require('./models/post.js')(gc);
	app.views.statusSelect2 = require('./views/status-select2.js')(app);

	app.init = function () {
		if (gc._post.mapping) {
			app.views.metabox = require('./views/metabox.js')(app, $, gc);
			app.metaboxView = new app.views.metabox({
				model: new app.models.post(gc._post)
			});
		} else {
			app.views.metabox = require('./views/mapping-metabox.js')(app, $, gc);
			app.metaboxView = new app.views.metabox({
				model: new app.models.post(gc._post)
			});
			app.metaboxView.on('complete', app.reinit);
		}
	};

	app.reinit = function (model) {
		app.metaboxView.unbind();
		if (app.metaboxView.onClose) {
			app.metaboxView.onClose();
		}

		app.views.metabox = require('./views/metabox.js')(app, $, gc);
		app.metaboxView = new app.views.metabox({
			model: model
		});
	};

	// Kick it off.
	$(app.init);

})(window, document, jQuery, window.GatherContent);
