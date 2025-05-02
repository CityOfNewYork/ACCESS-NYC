window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.sync = gc.sync || {};
	var app = gc.sync;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	app.views.tableBase = require('./views/table-base.js')(app, $, gc);

	/*
	 * Item setup
	 */

	app.models.item = require('./models/item.js')(app, gc);
	app.collections.items = require('./collections/items.js')(app);
	app.views.item = require('./views/item.js')(app);
	app.views.items = require('./views/items.js')(app, $, gc);

	app.init = function () {
		// Kick it off.
		app.syncView = new app.views.items({
			el: $('.gc-admin-wrap'),
			collection: new app.collections.items(gc._items)
		});

		// Handle error notice dismissals.
		$(document.body)
			.on('click', '#setting-error-gc-import-last-error .notice-dismiss, #setting-error-gc-import-errors .notice-dismiss', function () {
				var lastError = $(this).parents('#setting-error-gc-import-last-error').length > 0;
				$.post(window.ajaxurl, {
					action    : 'cwby_dismiss_notice',
					lastError: lastError ? 1 : 0,
					mapping: gc.queryargs.mapping,
				}, function (response) {
					gc.log('response', response);
				});
			})
			.on('click', '.gc-notice-dismiss', function () {
				$(this).parents('.notice.is-dismissible').find('.notice-dismiss').trigger('click');
			});
	};

	$(app.init);

})(window, document, jQuery, window.GatherContent);
